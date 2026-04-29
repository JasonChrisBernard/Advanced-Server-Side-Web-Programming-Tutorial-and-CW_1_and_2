// Import the SQLite library used by this project.
const Database = require("better-sqlite3");
// Import path so we can safely build the database folder path.
const path = require("path");
// Import fs so we can create the folder if it does not exist yet.
const fs = require("fs");
// Import crypto so sensitive tokens and attendance codes can be hashed during migration and seeding.
const crypto = require("crypto");

// Build the absolute path to the database directory.
const dbFolder = path.join(__dirname, "../../database");
// Create the database directory on first run if it does not already exist.
if (!fs.existsSync(dbFolder)) {
  fs.mkdirSync(dbFolder, { recursive: true });
}

// Build the absolute path to the SQLite file.
const dbPath = path.join(dbFolder, "cw1.sqlite");
// Open a connection to the SQLite database file.
const db = new Database(dbPath);

// Use write-ahead logging to improve SQLite reliability for this app.
db.pragma("journal_mode = WAL");

// Check whether a table already exists in the current SQLite database file.
function tableExists(tableName) {
  return Boolean(
    db
      .prepare(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?"
      )
      .get(tableName)
  );
}

// Read the known column names for one table so migrations can detect older schemas.
function getTableColumns(tableName) {
  if (!tableExists(tableName)) {
    return [];
  }

  return db
    .prepare(`PRAGMA table_info(${tableName})`)
    .all()
    .map((column) => column.name);
}

// Add a missing column to an existing table when an older database file is reused.
function ensureColumnExists(tableName, columnName, columnDefinition) {
  const existingColumns = getTableColumns(tableName);

  if (!existingColumns.includes(columnName)) {
    db.exec(`ALTER TABLE ${tableName} ADD COLUMN ${columnName} ${columnDefinition}`);
  }
}

// Build a non-conflicting backup table name before archiving an outdated schema.
function getBackupTableName(tableName) {
  let suffix = "legacy_monthly";
  let candidate = `${tableName}_${suffix}`;
  let counter = 1;

  while (tableExists(candidate)) {
    candidate = `${tableName}_${suffix}_${counter}`;
    counter += 1;
  }

  return candidate;
}

// Move an outdated table aside so the app can create a fresh replacement without deleting data.
function archiveTable(tableName) {
  const backupTableName = getBackupTableName(tableName);
  db.exec(`ALTER TABLE ${tableName} RENAME TO ${backupTableName}`);
  return backupTableName;
}

// Hash opaque tokens and attendance codes before they are persisted in SQLite.
function hashOpaqueValue(rawValue) {
  return crypto.createHash("sha256").update(String(rawValue || "")).digest("hex");
}

// Backfill older raw verification/reset tokens into hashed columns and then remove the raw values.
function migrateLegacyAuthTokensToHashes() {
  const now = new Date().toISOString();
  const verificationRows = db.prepare(`
    SELECT id, verification_token
    FROM users
    WHERE verification_token IS NOT NULL
      AND verification_token_hash IS NULL
  `).all();
  const resetRows = db.prepare(`
    SELECT id, reset_token
    FROM users
    WHERE reset_token IS NOT NULL
      AND reset_token_hash IS NULL
  `).all();
  const verificationUpdate = db.prepare(`
    UPDATE users
    SET verification_token_hash = ?,
        verification_token = NULL,
        updated_at = ?
    WHERE id = ?
  `);
  const resetUpdate = db.prepare(`
    UPDATE users
    SET reset_token_hash = ?,
        reset_token = NULL,
        updated_at = ?
    WHERE id = ?
  `);
  const clearRawVerificationTokens = db.prepare(`
    UPDATE users
    SET verification_token = NULL,
        updated_at = ?
    WHERE verification_token IS NOT NULL
      AND verification_token_hash IS NOT NULL
  `);
  const clearRawResetTokens = db.prepare(`
    UPDATE users
    SET reset_token = NULL,
        updated_at = ?
    WHERE reset_token IS NOT NULL
      AND reset_token_hash IS NOT NULL
  `);

  verificationRows.forEach((row) => {
    verificationUpdate.run(hashOpaqueValue(row.verification_token), now, row.id);
  });

  resetRows.forEach((row) => {
    resetUpdate.run(hashOpaqueValue(row.reset_token), now, row.id);
  });

  clearRawVerificationTokens.run(now);
  clearRawResetTokens.run(now);
}

function synchronizeDeveloperRoles() {
  const configuredDeveloperEmails = String(process.env.DEVELOPER_EMAILS || "")
    .split(",")
    .map((email) => email.trim().toLowerCase())
    .filter(Boolean);

  if (configuredDeveloperEmails.length === 0) {
    return;
  }

  const now = new Date().toISOString();
  const markDeveloper = db.prepare(`
    UPDATE users
    SET role = 'developer',
        updated_at = ?
    WHERE lower(email) = ?
  `);

  configuredDeveloperEmails.forEach((email) => {
    markDeveloper.run(now, email);
  });
}

// Seed or refresh verified university alumni events from the environment so attendance codes can be checked server-side.
function synchronizeConfiguredAlumniEvents() {
  if (!tableExists("university_alumni_events")) {
    return;
  }

  const rawCatalog = String(process.env.ALUMNI_EVENT_CATALOG || "").trim();

  if (!rawCatalog) {
    return;
  }

  const now = new Date().toISOString();
  const upsertEvent = db.prepare(`
    INSERT INTO university_alumni_events (
      month_key,
      event_name,
      event_date,
      attendance_code_hash,
      is_active,
      created_at,
      updated_at
    )
    VALUES (?, ?, ?, ?, 1, ?, ?)
    ON CONFLICT(month_key, event_name, event_date)
    DO UPDATE SET
      attendance_code_hash = excluded.attendance_code_hash,
      is_active = 1,
      updated_at = excluded.updated_at
  `);

  rawCatalog
    .split(";")
    .map((entry) => entry.trim())
    .filter(Boolean)
    .forEach((entry) => {
      const [eventName, eventDate, attendanceCode] = entry
        .split("|")
        .map((value) => String(value || "").trim());

      if (!eventName || !/^\d{4}-\d{2}-\d{2}$/.test(eventDate) || !attendanceCode) {
        return;
      }

      upsertEvent.run(
        eventDate.slice(0, 7),
        eventName,
        eventDate,
        hashOpaqueValue(attendanceCode),
        now,
        now
      );
    });
}

// Upgrade the original monthly blind-bidding schema to the daily-slot schema required by the brief.
function migrateBlindBiddingTablesIfNeeded() {
  const blindBidColumns = getTableColumns("blind_bids");
  const winnerColumns = getTableColumns("featured_profile_winners");
  const blindBidNeedsMigration =
    blindBidColumns.length > 0 && blindBidColumns.includes("bidding_month");
  const winnerNeedsMigration =
    winnerColumns.length > 0 && winnerColumns.includes("bidding_month");

  if (!blindBidNeedsMigration && !winnerNeedsMigration) {
    return;
  }

  if (winnerNeedsMigration) {
    archiveTable("featured_profile_winners");
  }

  if (blindBidNeedsMigration) {
    archiveTable("blind_bids");
  }
}

// Create the users table if it does not exist yet.
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'alumnus' CHECK(role IN ('developer', 'alumnus')),
    is_verified INTEGER NOT NULL DEFAULT 0,
    verification_token TEXT,
    verification_token_hash TEXT,
    verification_token_expires_at TEXT,
    reset_token TEXT,
    reset_token_hash TEXT,
    reset_token_expires_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  );
`);

// Ensure newer security-related columns exist even when the database file predates the change.
ensureColumnExists("users", "verification_token_expires_at", "TEXT");
ensureColumnExists("users", "verification_token_hash", "TEXT");
ensureColumnExists("users", "reset_token_hash", "TEXT");
ensureColumnExists("users", "role", "TEXT NOT NULL DEFAULT 'alumnus' CHECK(role IN ('developer', 'alumnus'))");
migrateLegacyAuthTokensToHashes();
synchronizeDeveloperRoles();

// Create the main alumni profile table that stores one profile per user.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    contact_number TEXT,
    location TEXT,
    professional_headline TEXT,
    biography TEXT,
    linkedin_url TEXT,
    profile_image_path TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
  );
`);

// Create the degrees table so one profile can store many degree entries.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profile_degrees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    institution_name TEXT,
    official_url TEXT,
    completion_date TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Create the certifications table for professional certifications.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profile_certifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    provider_name TEXT,
    official_url TEXT,
    completion_date TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Create the licenses table for professional licences.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profile_licenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    awarding_body TEXT,
    official_url TEXT,
    completion_date TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Create the short courses table for additional learning records.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profile_courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    provider_name TEXT,
    official_url TEXT,
    completion_date TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Create the employment history table for career timeline entries.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_profile_employment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    employer_name TEXT NOT NULL,
    job_title TEXT,
    start_date TEXT,
    end_date TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Archive the original monthly blind-bidding tables if this database file predates the daily-slot design.
migrateBlindBiddingTablesIfNeeded();

// Create the blind bids table so each user can place one hidden bid for one featured day.
db.exec(`
  CREATE TABLE IF NOT EXISTS blind_bids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL,
    feature_date TEXT NOT NULL,
    amount REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'PENDING',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    resolved_at TEXT,
    outcome_notification_sent_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id),
    UNIQUE (user_id, feature_date)
  );
`);

// Ensure the blind-bid notification tracking column exists for already-created databases.
ensureColumnExists("blind_bids", "outcome_notification_sent_at", "TEXT");

// Create a table of verified university alumni events whose attendance codes can unlock one extra monthly appearance.
db.exec(`
  CREATE TABLE IF NOT EXISTS university_alumni_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    month_key TEXT NOT NULL,
    event_name TEXT NOT NULL,
    event_date TEXT NOT NULL,
    attendance_code_hash TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE (month_key, event_name, event_date)
  );
`);

// Create a table that records one verified alumni-event participation credit per user per month.
db.exec(`
  CREATE TABLE IF NOT EXISTS alumni_event_credits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_id INTEGER,
    month_key TEXT NOT NULL,
    event_name TEXT NOT NULL,
    event_date TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (event_id) REFERENCES university_alumni_events (id),
    UNIQUE (user_id, month_key)
  );
`);
ensureColumnExists("alumni_event_credits", "event_id", "INTEGER");
synchronizeConfiguredAlumniEvents();

// Create a table for the daily winning featured profiles selected from blind bids.
db.exec(`
  CREATE TABLE IF NOT EXISTS featured_profile_winners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feature_date TEXT NOT NULL UNIQUE,
    bid_id INTEGER NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    profile_id INTEGER NOT NULL,
    selected_at TEXT NOT NULL,
    FOREIGN KEY (bid_id) REFERENCES blind_bids (id),
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (profile_id) REFERENCES alumni_profiles (id)
  );
`);

// Create the developer API clients table so each logged-in user can register external clients.
db.exec(`
  CREATE TABLE IF NOT EXISTS api_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    client_name TEXT NOT NULL,
    client_type TEXT NOT NULL,
    access_strategy TEXT NOT NULL,
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
  );
`);

// Create the API tokens table that stores hashed bearer tokens instead of raw secrets.
db.exec(`
  CREATE TABLE IF NOT EXISTS api_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    token_label TEXT NOT NULL,
    token_prefix TEXT NOT NULL UNIQUE,
    token_hash TEXT NOT NULL UNIQUE,
    scope_list TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'ACTIVE',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    expires_at TEXT,
    last_used_at TEXT,
    revoked_at TEXT,
    FOREIGN KEY (client_id) REFERENCES api_clients (id)
  );
`);

// Ensure API tokens created before expiry support was added still gain an expiry field.
ensureColumnExists("api_tokens", "expires_at", "TEXT");

// Create the API usage log table so the portal can show timestamps, endpoints, and token activity.
db.exec(`
  CREATE TABLE IF NOT EXISTS api_usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token_id INTEGER,
    client_id INTEGER,
    request_method TEXT NOT NULL,
    endpoint_path TEXT NOT NULL,
    used_at TEXT NOT NULL,
    outcome TEXT NOT NULL,
    http_status INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    details TEXT,
    FOREIGN KEY (token_id) REFERENCES api_tokens (id),
    FOREIGN KEY (client_id) REFERENCES api_clients (id)
  );
`);

// Add indexes for the most common lookups so auth, bidding, and API usage queries stay efficient.
db.exec(`
  CREATE INDEX IF NOT EXISTS idx_users_verification_token
    ON users (verification_token);
  CREATE INDEX IF NOT EXISTS idx_users_verification_token_hash
    ON users (verification_token_hash);
  CREATE INDEX IF NOT EXISTS idx_users_reset_token
    ON users (reset_token);
  CREATE INDEX IF NOT EXISTS idx_users_reset_token_hash
    ON users (reset_token_hash);
  CREATE INDEX IF NOT EXISTS idx_alumni_profiles_user_id
    ON alumni_profiles (user_id);
  CREATE INDEX IF NOT EXISTS idx_blind_bids_feature_date_status
    ON blind_bids (feature_date, status);
  CREATE INDEX IF NOT EXISTS idx_blind_bids_profile_feature_date
    ON blind_bids (profile_id, feature_date);
  CREATE INDEX IF NOT EXISTS idx_blind_bids_user_feature_date
    ON blind_bids (user_id, feature_date);
  CREATE INDEX IF NOT EXISTS idx_university_alumni_events_month_key
    ON university_alumni_events (month_key, event_date);
  CREATE INDEX IF NOT EXISTS idx_alumni_event_credits_user_month_key
    ON alumni_event_credits (user_id, month_key);
  CREATE INDEX IF NOT EXISTS idx_featured_profile_winners_feature_date
    ON featured_profile_winners (feature_date);
  CREATE INDEX IF NOT EXISTS idx_featured_profile_winners_user_feature_date
    ON featured_profile_winners (user_id, feature_date);
  CREATE INDEX IF NOT EXISTS idx_api_tokens_client_status
    ON api_tokens (client_id, status);
  CREATE INDEX IF NOT EXISTS idx_api_tokens_status_expires_at
    ON api_tokens (status, expires_at);
  CREATE INDEX IF NOT EXISTS idx_api_usage_logs_client_used_at
    ON api_usage_logs (client_id, used_at DESC);
`);

// Export the database connection so model files can run queries.
module.exports = db;
