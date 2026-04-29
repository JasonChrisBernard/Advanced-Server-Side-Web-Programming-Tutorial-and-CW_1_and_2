// Import crypto so bearer tokens can be hashed before lookup.
const crypto = require("crypto");
// Import the API client model that stores tokens and usage logs.
const ApiClient = require("../models/apiClientModel");

// Convert the raw bearer token into the same SHA-256 hash stored in the database.
function hashToken(rawToken) {
  return crypto.createHash("sha256").update(rawToken).digest("hex");
}

// Split the stored comma-separated scope string into a clean array.
function parseScopeList(scopeList) {
  return String(scopeList || "")
    .split(",")
    .map((scope) => scope.trim())
    .filter(Boolean);
}

// Keep old coursework tokens constrained to the same alumnus-of-day permission after scope renaming.
function tokenHasRequiredScope(scopes, requiredScope) {
  const scopeAliases = {
    "read:alumni_of_day": ["featured:read"],
  };

  return (
    scopes.includes(requiredScope) ||
    (scopeAliases[requiredScope] || []).some((aliasScope) => scopes.includes(aliasScope))
  );
}

// Extract the raw bearer token from the Authorization header.
function readBearerToken(req) {
  const authorizationHeader = req.get("authorization") || "";
  const match = authorizationHeader.match(/^Bearer\s+(.+)$/i);

  return match ? match[1].trim() : null;
}

// Build a reusable payload for API usage logging.
function buildUsageLog(req, responseStatus, outcome, extra = {}) {
  return {
    tokenId: extra.tokenId || null,
    clientId: extra.clientId || null,
    requestMethod: req.method,
    endpointPath: req.originalUrl,
    usedAt: new Date().toISOString(),
    outcome,
    httpStatus: responseStatus,
    ipAddress: req.ip,
    userAgent: req.get("user-agent") || "",
    details: extra.details || null,
  };
}

// Protect a JSON API route with bearer-token authentication and scope checks.
exports.requireBearerToken = (requiredScope) => (req, res, next) => {
  const rawToken = readBearerToken(req);

  // Reject requests that do not send a bearer token at all.
  if (!rawToken) {
    res.status(401).json({
      success: false,
      error: "Missing bearer token.",
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 401, "MISSING_TOKEN", {
        details: requiredScope
          ? `Required scope: ${requiredScope}`
          : "Bearer token missing from Authorization header.",
      })
    );
    return;
  }

  ApiClient.synchronizeTokenExpiryStates();
  const tokenRecord = ApiClient.findTokenByHash(hashToken(rawToken));

  // Reject requests that use an unknown token.
  if (!tokenRecord) {
    res.status(401).json({
      success: false,
      error: "Invalid or revoked bearer token.",
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 401, "INVALID_TOKEN", {
        details: "No active API token matched the provided bearer token.",
      })
    );
    return;
  }

  // Reject tokens that were previously revoked in the developer portal.
  if (tokenRecord.status === "REVOKED") {
    res.status(401).json({
      success: false,
      error: "Invalid or revoked bearer token.",
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 401, "REVOKED_TOKEN", {
        tokenId: tokenRecord.token_id,
        clientId: tokenRecord.client_id,
        details: "A revoked API token was presented to the developer API.",
      })
    );
    return;
  }

  // Reject tokens whose expiry time has passed.
  if (tokenRecord.status === "EXPIRED") {
    res.status(401).json({
      success: false,
      error: "Expired bearer token.",
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 401, "EXPIRED_TOKEN", {
        tokenId: tokenRecord.token_id,
        clientId: tokenRecord.client_id,
        details: `Token expired at ${tokenRecord.expires_at}.`,
      })
    );
    return;
  }

  // Reject any token that is not currently active even if it is known.
  if (tokenRecord.status !== "ACTIVE") {
    res.status(401).json({
      success: false,
      error: "Invalid bearer token.",
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 401, "INACTIVE_TOKEN", {
        tokenId: tokenRecord.token_id,
        clientId: tokenRecord.client_id,
        details: `Token status was ${tokenRecord.status}.`,
      })
    );
    return;
  }

  const scopes = parseScopeList(tokenRecord.scope_list);

  // Reject tokens that do not include the required API permission.
  if (requiredScope && !tokenHasRequiredScope(scopes, requiredScope)) {
    res.status(403).json({
      success: false,
      error: `This token does not include the required scope: ${requiredScope}.`,
    });

    ApiClient.recordUsageLog(
      buildUsageLog(req, 403, "INSUFFICIENT_SCOPE", {
        tokenId: tokenRecord.token_id,
        clientId: tokenRecord.client_id,
        details: `Required scope: ${requiredScope}. Token scopes: ${tokenRecord.scope_list}`,
      })
    );
    return;
  }

  // Expose the authenticated client and token to the route handler.
  req.apiToken = tokenRecord;
  req.apiClient = {
    id: tokenRecord.client_id,
    clientName: tokenRecord.client_name,
    clientType: tokenRecord.client_type,
    accessStrategy: tokenRecord.access_strategy,
  };

  // Log the request when the response finishes so the final status code is captured.
  res.on("finish", () => {
    ApiClient.touchTokenLastUsed(tokenRecord.token_id);
    ApiClient.recordUsageLog(
      buildUsageLog(
        req,
        res.statusCode,
        res.statusCode < 400 ? "SUCCESS" : "FAILED",
        {
          tokenId: tokenRecord.token_id,
          clientId: tokenRecord.client_id,
          details: `Authenticated client: ${tokenRecord.client_name}`,
        }
      )
    );
  });

  next();
};
