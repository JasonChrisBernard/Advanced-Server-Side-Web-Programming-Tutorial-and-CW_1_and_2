// Import Nodemailer so the app can send emails through SMTP.
const nodemailer = require("nodemailer");

// Keep one transporter in memory so the app does not rebuild it every time.
let transporter;

// Check whether the required mail environment variables exist.
function isMailerConfigured() {
  return Boolean(
    process.env.MAIL_HOST &&
      process.env.MAIL_PORT &&
      process.env.MAIL_USER &&
      process.env.MAIL_PASS
  );
}

// Build and return the Nodemailer transporter used to send emails.
function getTransporter() {
  // If config is missing, return null so callers know mail is unavailable.
  if (!isMailerConfigured()) {
    return null;
  }

  // Only create the transporter once and reuse it afterward.
  if (!transporter) {
    // Start with the shared auth settings.
    const transportConfig = {
      auth: {
        user: process.env.MAIL_USER,
        pass: process.env.MAIL_PASS,
      },
    };

    // If a named service like "gmail" is provided, use that shortcut.
    if (process.env.MAIL_SERVICE) {
      transportConfig.service = process.env.MAIL_SERVICE;
    } else {
      // Otherwise, use the lower-level host/port/secure SMTP settings.
      transportConfig.host = process.env.MAIL_HOST;
      transportConfig.port = Number(process.env.MAIL_PORT);
      transportConfig.secure = process.env.MAIL_SECURE === "true";
    }

    // Create the actual Nodemailer transport object.
    transporter = nodemailer.createTransport(transportConfig);
  }

  // Return the cached transporter.
  return transporter;
}

// Decide which email address should appear as the sender.
function getMailFrom() {
  // Use MAIL_FROM if provided, otherwise fall back to MAIL_USER.
  return process.env.MAIL_FROM || process.env.MAIL_USER;
}

// Export helpers so other files can read mail settings and send mail.
module.exports = {
  getMailFrom,
  getTransporter,
  isMailerConfigured,
  // Verify the mail connection at startup to catch bad credentials early.
  async verifyMailer() {
    // If settings are missing, report that immediately.
    if (!isMailerConfigured()) {
      return {
        ok: false,
        reason: "Mailer is not configured.",
      };
    }

    try {
      // Ask Nodemailer to test the SMTP login and connection.
      await getTransporter().verify();

      // Report success if the SMTP server accepted the login.
      return {
        ok: true,
      };
    } catch (error) {
      // Report the exact failure reason for easier debugging.
      return {
        ok: false,
        reason: error.message,
      };
    }
  },
};
