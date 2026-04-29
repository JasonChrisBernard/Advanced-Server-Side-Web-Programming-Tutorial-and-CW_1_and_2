// Import helpers that know how to create and validate the mail transporter.
const {
  getMailFrom,
  getTransporter,
  isMailerConfigured,
} = require("../config/mailer");

// Build the base URL used inside verification and reset links.
function getBaseUrl() {
  // Prefer APP_BASE_URL from .env, or fall back to localhost in development.
  return process.env.APP_BASE_URL || `http://localhost:${process.env.PORT || 3000}`;
}

// Shared helper that sends one email and handles fallback logging.
async function sendMail({ to, subject, text, html, fallbackLabel, fallbackLink }) {
  // If mail settings are missing, do not crash the app; log the link instead.
  if (!isMailerConfigured()) {
    console.warn(
      "Email is not configured yet. Set MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, and MAIL_FROM in .env."
    );
    console.log(`${fallbackLabel} would be sent to:`, to);
    console.log(fallbackLink);

    // Tell the caller that no real email was delivered.
    return {
      delivered: false,
      reason: "Email is not configured yet.",
    };
  }

  try {
    // Get the configured Nodemailer transporter.
    const transporter = getTransporter();

    // Ask Nodemailer to send the email.
    await transporter.sendMail({
      // This is the sender address shown in the received mail.
      from: getMailFrom(),
      // This is the recipient address, usually read from the database.
      to,
      // This becomes the subject line in the inbox.
      subject,
      // Plain-text body for mail clients that do not render HTML.
      text,
      // HTML body for richer email clients.
      html,
    });

    // Report success back to the controller.
    return { delivered: true };
  } catch (error) {
    // Print the delivery failure so it is visible in the server terminal.
    console.error("Email delivery failed:", error.message);
    // Also print the link so development/testing can continue manually.
    console.log(`${fallbackLabel} would be sent to:`, to);
    console.log(fallbackLink);

    // Report the failure to the controller instead of throwing.
    return {
      delivered: false,
      reason: error.message,
    };
  }
}

// Send the email-verification message after successful registration.
exports.sendVerificationEmail = async (email, token) => {
  // Build the full verification URL that the user must click.
  const verificationLink = `${getBaseUrl()}/verify-email/${token}`;

  // Send the message using the shared mail helper.
  return sendMail({
    to: email,
    subject: "Verify your email",
    text: `Welcome. Verify your email by opening this link within 24 hours: ${verificationLink}`,
    html: `<p>Welcome.</p><p>Verify your email within 24 hours by clicking <a href="${verificationLink}">this link</a>.</p>`,
    fallbackLabel: "Verification email",
    fallbackLink: `Verification link: ${verificationLink}`,
  });
};

// Send the password-reset email after the user requests a reset.
exports.sendPasswordResetEmail = async (email, token) => {
  // Build the full password-reset URL containing the unique token.
  const resetLink = `${getBaseUrl()}/reset-password/${token}`;

  // Send the message using the shared mail helper.
  return sendMail({
    to: email,
    subject: "Reset your password",
    text: `Reset your password by opening this link within 30 minutes: ${resetLink}`,
    html: `<p>Reset your password within 30 minutes by clicking <a href="${resetLink}">this link</a>.</p>`,
    fallbackLabel: "Password reset email",
    fallbackLink: `Reset link: ${resetLink}`,
  });
};

// Send a winner/loser notification after one featured day is resolved.
exports.sendBlindBidOutcomeEmail = async ({
  to,
  fullName,
  featureDateLabel,
  status,
  amount,
}) => {
  const blindBiddingLink = `${getBaseUrl()}/blind-bidding`;
  const outcomeLine =
    status === "WON"
      ? `Congratulations ${fullName}, your blind bid of ${amount} won the featured slot for ${featureDateLabel}.`
      : `Hello ${fullName}, your blind bid of ${amount} for ${featureDateLabel} did not win the featured slot this time.`;

  return sendMail({
    to,
    subject: `Blind bidding result for ${featureDateLabel}`,
    text: `${outcomeLine} View your bidding dashboard here: ${blindBiddingLink}`,
    html: `<p>${outcomeLine}</p><p>View your bidding dashboard <a href="${blindBiddingLink}">here</a>.</p>`,
    fallbackLabel: "Blind bidding outcome email",
    fallbackLink: `Blind bidding dashboard: ${blindBiddingLink}`,
  });
};
