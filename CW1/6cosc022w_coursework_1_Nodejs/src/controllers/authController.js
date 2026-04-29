// Import bcrypt so passwords can be hashed securely instead of stored as plain text.
const bcrypt = require("bcrypt");
// Import crypto so we can generate random verification and reset tokens.
const crypto = require("crypto");
// Import the model that talks to the users table in SQLite.
const User = require("../models/userModel");
// Import the helper that tells us whether SMTP settings exist.
const { isMailerConfigured } = require("../config/mailer");
// Import the service that sends verification and reset emails.
const EmailService = require("../services/emailService");
// Import reusable validators so auth rules stay consistent across the app.
const {
  getAllowedEmailDomains,
  isAllowedEmailDomain,
  isValidEmail,
  normalizeEmail,
  sanitizeSingleLineText,
  validateStrongPassword,
} = require("../utils/validators");

// Use a stronger bcrypt cost factor by default while keeping it configurable in .env.
const BCRYPT_SALT_ROUNDS = Number(process.env.BCRYPT_SALT_ROUNDS || 12);
// Expire verification links after one day unless .env overrides the policy.
const VERIFICATION_TOKEN_TTL_MINUTES = Number(
  process.env.VERIFICATION_TOKEN_TTL_MINUTES || 60 * 24
);

// Render the registration page with no error message the first time it opens.
exports.showRegister = (req, res) => {
  res.render("auth/register", { error: null });
};

// Handle the submitted registration form.
exports.register = async (req, res) => {
  try {
    // Read the posted form values from req.body.
    const { fullName, email, password, confirmPassword } = req.body;
    // Trim the name so accidental spaces do not get stored in the database.
    const normalizedFullName = sanitizeSingleLineText(fullName, 120);
    // Trim and lowercase the email so matching is consistent.
    const normalizedEmail = normalizeEmail(email);

    // Reject the request if any required field is empty.
    if (!normalizedFullName || !normalizedEmail || !password || !confirmPassword) {
      return res.render("auth/register", {
        error: "All fields are required.",
      });
    }

    // Reject obviously invalid email formats.
    if (!isValidEmail(normalizedEmail)) {
      return res.render("auth/register", {
        error: "Please enter a valid email address.",
      });
    }

    // Enforce the allowed-domain rule from the coursework marking scheme.
    if (!isAllowedEmailDomain(normalizedEmail)) {
      return res.render("auth/register", {
        error: `Registration is limited to these email domains: ${getAllowedEmailDomains().join(", ")}.`,
      });
    }

    // The password must match the confirmation field.
    if (password !== confirmPassword) {
      return res.render("auth/register", {
        error: "Passwords do not match.",
      });
    }

    // Reject weak passwords so password storage meets the stronger security criteria.
    const passwordCheck = validateStrongPassword(password);
    if (!passwordCheck.isValid) {
      return res.render("auth/register", {
        error: `Password must include ${passwordCheck.feedback.join(", ")}.`,
      });
    }

    // Check whether the email is already in use.
    const existingUser = User.findByEmail(normalizedEmail);
    if (existingUser) {
      return res.render("auth/register", {
        error: "Email is already registered.",
      });
    }

    // Hash the plain-text password before storing it in the database.
    const passwordHash = await bcrypt.hash(password, BCRYPT_SALT_ROUNDS);
    // Generate a random token that will be placed in the verification email.
    const verificationToken = crypto.randomBytes(32).toString("hex");
    // Set an expiry time so verification links are not valid forever.
    const verificationTokenExpiresAt = new Date(
      Date.now() + VERIFICATION_TOKEN_TTL_MINUTES * 60 * 1000
    ).toISOString();

    // Insert the new user row into the database.
    User.createUser({
      fullName: normalizedFullName,
      email: normalizedEmail,
      passwordHash,
      verificationToken,
      verificationTokenExpiresAt,
    });

    // Try to send the verification email to the registered email address.
    const emailResult = await EmailService.sendVerificationEmail(
      normalizedEmail,
      verificationToken
    );

    // Show a success message, or a fallback message if email delivery failed.
    return res.render("auth/message", {
      title: "Registration complete",
      message: emailResult.delivered
        ? "Account created. Please check your email to verify it."
        : "Account created, but the verification email could not be sent. Fix the mail settings and use the verification link shown in the server terminal for now.",
    });
  } catch (error) {
    // Print the full error in the terminal for debugging.
    console.error(error);
    // Show a safe user-facing error message in the browser.
    return res.render("auth/register", {
      error: "Something went wrong during registration.",
    });
  }
};

// Handle a clicked verification link.
exports.verifyEmail = (req, res) => {
  try {
    // Read the token from the URL segment /verify-email/:token.
    const { token } = req.params;

    // Find the user that owns this verification token.
    const user = User.findByVerificationToken(token);
    // If no user matches, the token is invalid or already used.
    if (!user) {
      return res.render("auth/message", {
        title: "Verification failed",
        message: "Invalid or expired verification link.",
      });
    }

    // Reject tokens that are older than the configured verification expiry window.
    if (
      !user.verification_token_expires_at ||
      new Date(user.verification_token_expires_at) < new Date()
    ) {
      return res.render("auth/message", {
        title: "Verification failed",
        message: "This verification link has expired. Register again or request a fresh verification email.",
      });
    }

    // Mark the account as verified and clear the used token.
    User.markVerified(user.id);

    // Tell the user they can now log in.
    return res.render("auth/message", {
      title: "Email verified",
      message: "Your email has been verified. You can now log in.",
    });
  } catch (error) {
    // Print the detailed error in the server terminal.
    console.error(error);
    // Show a generic browser message instead of exposing internals.
    return res.render("auth/message", {
      title: "Error",
      message: "Something went wrong while verifying your email.",
    });
  }
};

// Render the login page with no initial error.
exports.showLogin = (req, res) => {
  res.render("auth/login", { error: null });
};

// Handle the submitted login form.
exports.login = async (req, res) => {
  try {
    // Read the posted credentials.
    const { email, password } = req.body;
    // Normalize email so the lookup matches how registration stored it.
    const normalizedEmail = normalizeEmail(email);

    // Reject empty login submissions.
    if (!normalizedEmail || !password) {
      return res.render("auth/login", {
        error: "Email and password are required.",
      });
    }

    // Try to find the matching user account.
    const user = User.findByEmail(normalizedEmail);
    // If no account exists, return a generic message for security.
    if (!user) {
      return res.render("auth/login", {
        error: "Invalid email or password.",
      });
    }

    // Compare the submitted password with the stored password hash.
    const passwordMatches = await bcrypt.compare(password, user.password_hash);
    // If the password is wrong, return the same generic login error.
    if (!passwordMatches) {
      return res.render("auth/login", {
        error: "Invalid email or password.",
      });
    }

    // Block login until the email address has been verified.
    if (!user.is_verified) {
      return res.render("auth/login", {
        error: "Please verify your email before logging in.",
      });
    }

    // Regenerate the session id before login succeeds to defend against session fixation.
    return req.session.regenerate((sessionError) => {
      if (sessionError) {
        console.error(sessionError);
        return res.render("auth/login", {
          error: "Something went wrong during login.",
        });
      }

      // Preserve the CSRF token after the new session id is created.
      req.session.csrfToken = req.session.csrfToken || crypto.randomBytes(24).toString("hex");
      // Save only the essential user data in the session cookie store.
      req.session.user = {
        id: user.id,
        fullName: user.full_name,
        email: user.email,
        role: user.role || "alumnus",
      };

      // Send the logged-in user to the protected home page.
      return res.redirect("/");
    });
  } catch (error) {
    // Log the detailed error for debugging.
    console.error(error);
    // Show a generic login error to the user.
    return res.render("auth/login", {
      error: "Something went wrong during login.",
    });
  }
};

// Destroy the session so the user is logged out.
exports.logout = (req, res) => {
  // Remove the session from the session store, then redirect to login.
  req.session.destroy(() => {
    res.clearCookie("alumni.sid");
    res.redirect("/login");
  });
};

// Render the forgot-password page with no initial error or success text.
exports.showForgotPassword = (req, res) => {
  res.render("auth/forgot-password", { error: null, success: null });
};

// Handle the forgot-password form submission.
exports.forgotPassword = async (req, res) => {
  try {
    // Read the submitted email and normalize it.
    const { email } = req.body;
    const normalizedEmail = normalizeEmail(email);
    // This will later hold the result of the email send attempt.
    let emailResult = null;

    // Reject empty submissions.
    if (!normalizedEmail) {
      return res.render("auth/forgot-password", {
        error: "Email is required.",
        success: null,
      });
    }

    if (!isValidEmail(normalizedEmail)) {
      return res.render("auth/forgot-password", {
        error: "Please enter a valid email address.",
        success: null,
      });
    }

    // Look up the matching user in the database.
    const user = User.findByEmail(normalizedEmail);

    // Only create a reset token if the account actually exists.
    if (user) {
      // Build a one-time token that will go into the reset link.
      const resetToken = crypto.randomBytes(32).toString("hex");
      // Make the reset link expire after 30 minutes.
      const expiresAt = new Date(Date.now() + 1000 * 60 * 30).toISOString();

      // Save the token and expiry time to the user row.
      User.saveResetToken(user.id, resetToken, expiresAt);
      // Try to send the reset link to the email stored in the user record.
      emailResult = await EmailService.sendPasswordResetEmail(user.email, resetToken);
    }

    // Show the appropriate success/fallback message without revealing account existence.
    return res.render("auth/forgot-password", {
      error: null,
      success:
        user && emailResult && !emailResult.delivered
          ? "A reset link was created, but the email could not be sent. Fix the mail settings and use the reset link shown in the server terminal for now."
          : isMailerConfigured()
            ? "If that email exists, a password reset link has been sent."
            : "If that email exists, a reset link has been prepared, but email sending is not configured yet. Set MAIL_USER and MAIL_PASS in .env.",
    });
  } catch (error) {
    // Print the detailed error for debugging.
    console.error(error);
    // Show a generic page-level error.
    return res.render("auth/forgot-password", {
      error: "Something went wrong.",
      success: null,
    });
  }
};

// Show the password-reset form when a user opens a reset link.
exports.showResetPassword = (req, res) => {
  // Look up the user who owns the token from the URL.
  const user = User.findByResetToken(req.params.token);

  // Reject the link if no user matches it, if it has no expiry, or if it has expired.
  if (
    !user ||
    !user.reset_token_expires_at ||
    new Date(user.reset_token_expires_at) < new Date()
  ) {
    return res.render("auth/message", {
      title: "Reset failed",
      message: "Invalid or expired reset link.",
    });
  }

  // Render the reset-password form and pass the token back into the template.
  res.render("auth/reset-password", {
    token: req.params.token,
    error: null,
  });
};

// Handle submission of the new password form.
exports.resetPassword = async (req, res) => {
  try {
    // Read the token from the URL and the new passwords from the form.
    const { token } = req.params;
    const { password, confirmPassword } = req.body;

    // Find the user row that owns this reset token.
    const user = User.findByResetToken(token);

    // Reject completely unknown tokens.
    if (!user) {
      return res.render("auth/message", {
        title: "Reset failed",
        message: "Invalid reset token.",
      });
    }

    // Reject expired reset links.
    if (!user.reset_token_expires_at || new Date(user.reset_token_expires_at) < new Date()) {
      return res.render("auth/message", {
        title: "Reset failed",
        message: "This reset link has expired.",
      });
    }

    // Require both password fields before continuing.
    if (!password || !confirmPassword) {
      return res.render("auth/reset-password", {
        token,
        error: "Both password fields are required.",
      });
    }

    // Make sure the new password and confirmation match.
    if (password !== confirmPassword) {
      return res.render("auth/reset-password", {
        token,
        error: "Passwords do not match.",
      });
    }

    // Enforce the same strong-password rule during resets.
    const passwordCheck = validateStrongPassword(password);
    if (!passwordCheck.isValid) {
      return res.render("auth/reset-password", {
        token,
        error: `Password must include ${passwordCheck.feedback.join(", ")}.`,
      });
    }

    // Hash the new password securely before storing it.
    const passwordHash = await bcrypt.hash(password, BCRYPT_SALT_ROUNDS);
    // Save the new hash and clear the reset token fields.
    User.updatePassword(user.id, passwordHash);

    // Tell the user the reset succeeded and they can log in again.
    return res.render("auth/message", {
      title: "Password updated",
      message: "Your password has been reset. You can now log in.",
    });
  } catch (error) {
    // Print the detailed error in the terminal.
    console.error(error);
    // Show a safe generic browser message.
    return res.render("auth/message", {
      title: "Error",
      message: "Something went wrong while resetting the password.",
    });
  }
};
