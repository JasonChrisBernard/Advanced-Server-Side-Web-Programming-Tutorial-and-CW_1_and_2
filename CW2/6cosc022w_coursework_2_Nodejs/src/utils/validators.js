// Import crypto so CSRF and token helpers can use secure randomness when needed.
const crypto = require("crypto");

// Read and normalize the allowed email domains from .env.
function getAllowedEmailDomains() {
  return String(process.env.ALLOWED_EMAIL_DOMAINS || "iit.ac.lk,westminster.ac.uk")
    .split(",")
    .map((domain) => domain.trim().toLowerCase())
    .filter(Boolean);
}

// Keep only safe plain-text characters and collapse repeated whitespace.
function sanitizeSingleLineText(value, maxLength = 255) {
  return String(value || "")
    .replace(/[<>]/g, "")
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, maxLength);
}

// Keep line breaks for larger text areas such as biographies, while still stripping angle brackets.
function sanitizeMultilineText(value, maxLength = 2000) {
  return String(value || "")
    .replace(/[<>]/g, "")
    .replace(/\r/g, "")
    .replace(/[^\S\n]+/g, " ")
    .trim()
    .slice(0, maxLength);
}

// Normalize emails to a canonical lowercase form before lookup and storage.
function normalizeEmail(email) {
  return String(email || "").trim().toLowerCase();
}

// Validate a typical absolute email format.
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || "").trim());
}

// Enforce the configured domain allow-list used by the coursework registration flow.
function isAllowedEmailDomain(email) {
  const normalizedEmail = normalizeEmail(email);
  const allowedDomains = getAllowedEmailDomains();

  return allowedDomains.some((domain) => normalizedEmail.endsWith(`@${domain}`));
}

// Enforce a stronger password policy for higher security marks.
function validateStrongPassword(password) {
  const normalizedPassword = String(password || "");
  const feedback = [];

  if (normalizedPassword.length < 12) {
    feedback.push("at least 12 characters");
  }

  if (!/[A-Z]/.test(normalizedPassword)) {
    feedback.push("one uppercase letter");
  }

  if (!/[a-z]/.test(normalizedPassword)) {
    feedback.push("one lowercase letter");
  }

  if (!/[0-9]/.test(normalizedPassword)) {
    feedback.push("one number");
  }

  if (!/[^A-Za-z0-9]/.test(normalizedPassword)) {
    feedback.push("one special character");
  }

  return {
    isValid: feedback.length === 0,
    feedback,
  };
}

// Validate an optional absolute URL without rejecting an empty field.
function isValidOptionalUrl(value) {
  if (!value) {
    return true;
  }

  try {
    const parsedUrl = new URL(String(value).trim());
    return parsedUrl.protocol === "https:" || parsedUrl.protocol === "http:";
  } catch (error) {
    return false;
  }
}

// Parse a positive money-like number used for bidding amounts.
function parsePositiveAmount(value) {
  const parsedValue = Number(String(value || "").trim());

  if (!Number.isFinite(parsedValue) || parsedValue <= 0) {
    return null;
  }

  return Number(parsedValue.toFixed(2));
}

// Produce a secure random token for CSRF and other application secrets.
function createRandomToken(byteLength = 32) {
  return crypto.randomBytes(byteLength).toString("hex");
}

module.exports = {
  createRandomToken,
  getAllowedEmailDomains,
  isAllowedEmailDomain,
  isValidEmail,
  isValidOptionalUrl,
  normalizeEmail,
  parsePositiveAmount,
  sanitizeMultilineText,
  sanitizeSingleLineText,
  validateStrongPassword,
};
