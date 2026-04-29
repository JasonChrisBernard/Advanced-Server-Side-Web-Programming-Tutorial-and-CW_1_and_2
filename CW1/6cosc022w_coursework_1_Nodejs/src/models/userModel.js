// Import the shared SQLite connection created in config/db.js.
const db = require("../config/db");
// Import crypto so verification and reset tokens can be hashed before lookup/storage.
const crypto = require("crypto");

// Hash one opaque token value before it is stored or compared against the database.
function hashOpaqueToken(rawToken) {
  return crypto.createHash("sha256").update(String(rawToken || "")).digest("hex");
}

// Find one user row by email address.
exports.findByEmail = (email) => {
  // prepare() compiles the SQL once, and get() returns a single matching row.
  return db.prepare("SELECT * FROM users WHERE email = ?").get(email);
};

// Find the user that owns a specific email-verification token.
exports.findByVerificationToken = (token) => {
  return db
    .prepare("SELECT * FROM users WHERE verification_token_hash = ?")
    .get(hashOpaqueToken(token));
};

// Find the user that owns a specific password-reset token.
exports.findByResetToken = (token) => {
  return db
    .prepare("SELECT * FROM users WHERE reset_token_hash = ?")
    .get(hashOpaqueToken(token));
};

// Insert a brand-new user row into the database.
exports.createUser = ({
  fullName,
  email,
  passwordHash,
  verificationToken,
  verificationTokenExpiresAt,
  role = "alumnus",
}) => {
  // Save the same timestamp for both created_at and updated_at.
  const now = new Date().toISOString();

  // Prepare the INSERT statement that writes the user into SQLite.
  const stmt = db.prepare(`
    INSERT INTO users (
      full_name,
      email,
      password_hash,
      role,
      is_verified,
      verification_token,
      verification_token_hash,
      verification_token_expires_at,
      created_at,
      updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `);

  // Run the INSERT with values in the same order as the placeholders.
  const info = stmt.run(
    fullName,
    email,
    passwordHash,
    role,
    0,
    null,
    hashOpaqueToken(verificationToken),
    verificationTokenExpiresAt,
    now,
    now
  );

  // Return the new row id so callers can use it if needed.
  return info.lastInsertRowid;
};

// Mark a user as verified after they click the verification link.
exports.markVerified = (userId) => {
  // Record when this update happened.
  const now = new Date().toISOString();

  // Set is_verified to 1 and clear the one-time verification token.
  return db.prepare(`
    UPDATE users
    SET is_verified = 1,
        verification_token = NULL,
        verification_token_hash = NULL,
        verification_token_expires_at = NULL,
        updated_at = ?
    WHERE id = ?
  `).run(now, userId);
};

// Save a password-reset token and its expiry time for a specific user.
exports.saveResetToken = (userId, resetToken, expiresAt) => {
  // Record when the user row was updated.
  const now = new Date().toISOString();

  // Store the token and expiry so the reset link can be validated later.
  return db.prepare(`
    UPDATE users
    SET reset_token = NULL,
        reset_token_hash = ?,
        reset_token_expires_at = ?,
        updated_at = ?
    WHERE id = ?
  `).run(hashOpaqueToken(resetToken), expiresAt, now, userId);
};

// Replace the old password hash with a new one after a password reset.
exports.updatePassword = (userId, passwordHash) => {
  // Record the update time.
  const now = new Date().toISOString();

  // Save the new password and clear the reset token so it cannot be reused.
  return db.prepare(`
    UPDATE users
    SET password_hash = ?,
        reset_token = NULL,
        reset_token_hash = NULL,
        reset_token_expires_at = NULL,
        updated_at = ?
    WHERE id = ?
  `).run(passwordHash, now, userId);
};
