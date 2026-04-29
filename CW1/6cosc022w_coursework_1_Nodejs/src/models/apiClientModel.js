// Import the shared SQLite database connection used by the rest of the app.
const db = require("../config/db");

// Read the configured developer-token lifetime once and fall back to 30 days in local development.
function getDefaultTokenTtlDays() {
  return Number(process.env.API_TOKEN_TTL_DAYS || 30);
}

// Add a whole-number day offset to an ISO timestamp and return another ISO timestamp.
function addDaysToIsoTimestamp(isoTimestamp, daysToAdd) {
  const baseDate = new Date(isoTimestamp);
  const safeBaseDate = Number.isNaN(baseDate.getTime()) ? new Date() : baseDate;

  safeBaseDate.setDate(safeBaseDate.getDate() + daysToAdd);
  return safeBaseDate.toISOString();
}

// Backfill missing expiry dates and mark overdue tokens as expired before they are used again.
exports.synchronizeTokenExpiryStates = db.transaction(() => {
  const now = new Date().toISOString();
  const tokenTtlDays = getDefaultTokenTtlDays();
  const activeTokensMissingExpiry = db.prepare(`
    SELECT id, created_at
    FROM api_tokens
    WHERE status = 'ACTIVE' AND expires_at IS NULL
  `).all();

  const backfillExpiryStatement = db.prepare(`
    UPDATE api_tokens
    SET expires_at = ?,
        updated_at = ?
    WHERE id = ?
  `);

  activeTokensMissingExpiry.forEach((token) => {
    backfillExpiryStatement.run(
      addDaysToIsoTimestamp(token.created_at, tokenTtlDays),
      now,
      token.id
    );
  });

  const expireResult = db.prepare(`
    UPDATE api_tokens
    SET status = 'EXPIRED',
        updated_at = ?
    WHERE status = 'ACTIVE'
      AND expires_at IS NOT NULL
      AND expires_at <= ?
  `).run(now, now);

  return {
    backfilledCount: activeTokensMissingExpiry.length,
    expiredCount: expireResult.changes,
  };
});

// Create one external client record and its first bearer token inside one transaction.
exports.createClientWithToken = db.transaction((clientData) => {
  const now = new Date().toISOString();

  // Save the external client that will access the developer API.
  const clientInfo = db.prepare(`
    INSERT INTO api_clients (
      user_id,
      client_name,
      client_type,
      access_strategy,
      description,
      created_at,
      updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `).run(
    clientData.userId,
    clientData.clientName,
    clientData.clientType,
    clientData.accessStrategy,
    clientData.description,
    now,
    now
  );

  const clientId = Number(clientInfo.lastInsertRowid);

  // Store only the token hash and a short prefix so the raw bearer token stays secret.
  const tokenInfo = db.prepare(`
    INSERT INTO api_tokens (
      client_id,
      token_label,
      token_prefix,
      token_hash,
      scope_list,
      status,
      created_at,
      updated_at,
      expires_at
    )
    VALUES (?, ?, ?, ?, ?, 'ACTIVE', ?, ?, ?)
  `).run(
    clientId,
    clientData.tokenLabel,
    clientData.tokenPrefix,
    clientData.tokenHash,
    clientData.scopeList.join(","),
    now,
    now,
    clientData.expiresAt
  );

  return {
    clientId,
    tokenId: Number(tokenInfo.lastInsertRowid),
  };
});

// Read all clients and their tokens created by one logged-in user.
exports.getClientsWithTokensByUserId = (userId) => {
  return db.prepare(`
    SELECT
      c.id AS client_id,
      c.client_name,
      c.client_type,
      c.access_strategy,
      c.description,
      c.created_at AS client_created_at,
      c.updated_at AS client_updated_at,
      t.id AS token_id,
      t.token_label,
      t.token_prefix,
      t.scope_list,
      t.status,
      t.created_at AS token_created_at,
      t.updated_at AS token_updated_at,
      t.expires_at,
      t.last_used_at,
      t.revoked_at,
      COUNT(l.id) AS usage_count,
      SUM(CASE WHEN l.outcome = 'SUCCESS' THEN 1 ELSE 0 END) AS success_count,
      MAX(l.used_at) AS latest_request_at
    FROM api_clients c
    LEFT JOIN api_tokens t ON t.client_id = c.id
    LEFT JOIN api_usage_logs l ON l.token_id = t.id
    WHERE c.user_id = ?
    GROUP BY
      c.id,
      c.client_name,
      c.client_type,
      c.access_strategy,
      c.description,
      c.created_at,
      c.updated_at,
      t.id,
      t.token_label,
      t.token_prefix,
      t.scope_list,
      t.status,
      t.created_at,
      t.updated_at,
      t.expires_at,
      t.last_used_at,
      t.revoked_at
    ORDER BY c.created_at DESC, t.created_at DESC
  `).all(userId);
};

// Read a usage summary that can be rendered as high-level statistics cards on the portal page.
exports.getUsageSummaryByUserId = (userId) => {
  const logSummary = db.prepare(`
    SELECT
      COUNT(l.id) AS total_requests,
      SUM(CASE WHEN l.outcome = 'SUCCESS' THEN 1 ELSE 0 END) AS successful_requests,
      SUM(CASE WHEN l.outcome <> 'SUCCESS' THEN 1 ELSE 0 END) AS failed_requests,
      MAX(l.used_at) AS last_activity_at
    FROM api_clients c
    INNER JOIN api_tokens t ON t.client_id = c.id
    LEFT JOIN api_usage_logs l ON l.token_id = t.id
    WHERE c.user_id = ?
  `).get(userId);

  const tokenSummary = db.prepare(`
    SELECT
      SUM(CASE WHEN t.status = 'ACTIVE' THEN 1 ELSE 0 END) AS active_tokens,
      SUM(CASE WHEN t.status = 'REVOKED' THEN 1 ELSE 0 END) AS revoked_tokens,
      SUM(CASE WHEN t.status = 'EXPIRED' THEN 1 ELSE 0 END) AS expired_tokens
    FROM api_clients c
    INNER JOIN api_tokens t ON t.client_id = c.id
    WHERE c.user_id = ?
  `).get(userId);

  return {
    totalRequests: logSummary.total_requests || 0,
    successfulRequests: logSummary.successful_requests || 0,
    failedRequests: logSummary.failed_requests || 0,
    lastActivityAt: logSummary.last_activity_at || null,
    activeTokens: tokenSummary.active_tokens || 0,
    revokedTokens: tokenSummary.revoked_tokens || 0,
    expiredTokens: tokenSummary.expired_tokens || 0,
  };
};

// Read recent usage events for display in the developer portal.
exports.getRecentUsageLogsByUserId = (userId, limit = 20) => {
  return db.prepare(`
    SELECT
      l.id,
      l.request_method,
      l.endpoint_path,
      l.used_at,
      l.outcome,
      l.http_status,
      l.ip_address,
      l.user_agent,
      l.details,
      c.client_name,
      c.client_type,
      t.token_prefix
    FROM api_usage_logs l
    INNER JOIN api_clients c ON c.id = l.client_id
    LEFT JOIN api_tokens t ON t.id = l.token_id
    WHERE c.user_id = ?
    ORDER BY l.used_at DESC, l.id DESC
    LIMIT ?
  `).all(userId, limit);
};

// Find one owned token before revoking it so the controller can enforce ownership.
exports.findOwnedTokenById = (userId, tokenId) => {
  return db.prepare(`
    SELECT
      t.id,
      t.client_id,
      t.token_label,
      t.token_prefix,
      t.status,
      t.expires_at,
      c.client_name
    FROM api_tokens t
    INNER JOIN api_clients c ON c.id = t.client_id
    WHERE c.user_id = ? AND t.id = ?
  `).get(userId, tokenId);
};

// Revoke an API token so it can no longer authenticate developer API requests.
exports.revokeTokenById = (userId, tokenId) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE api_tokens
    SET status = 'REVOKED',
        revoked_at = ?,
        updated_at = ?
    WHERE id = ?
      AND client_id IN (
        SELECT id
        FROM api_clients
        WHERE user_id = ?
      )
      AND status = 'ACTIVE'
  `).run(now, now, tokenId, userId);
};

// Find one token by its hash so bearer authentication can distinguish active, revoked, and expired states.
exports.findTokenByHash = (tokenHash) => {
  return db.prepare(`
    SELECT
      t.id AS token_id,
      t.client_id,
      t.token_label,
      t.token_prefix,
      t.scope_list,
      t.status,
      t.created_at AS token_created_at,
      t.expires_at,
      t.last_used_at,
      t.revoked_at,
      c.client_name,
      c.client_type,
      c.access_strategy,
      c.user_id
    FROM api_tokens t
    INNER JOIN api_clients c ON c.id = t.client_id
    WHERE t.token_hash = ?
  `).get(tokenHash);
};

// Update the token's last-used timestamp after a successful bearer-authenticated request.
exports.touchTokenLastUsed = (tokenId) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE api_tokens
    SET last_used_at = ?,
        updated_at = ?
    WHERE id = ?
  `).run(now, now, tokenId);
};

// Insert one usage log row so the portal can show request counts, endpoints, and timestamps.
exports.recordUsageLog = (logData) => {
  return db.prepare(`
    INSERT INTO api_usage_logs (
      token_id,
      client_id,
      request_method,
      endpoint_path,
      used_at,
      outcome,
      http_status,
      ip_address,
      user_agent,
      details
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    logData.tokenId || null,
    logData.clientId || null,
    logData.requestMethod,
    logData.endpointPath,
    logData.usedAt,
    logData.outcome,
    logData.httpStatus || null,
    logData.ipAddress || null,
    logData.userAgent || null,
    logData.details || null
  );
};
