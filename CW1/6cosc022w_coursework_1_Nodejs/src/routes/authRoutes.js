// Import Express so we can build a modular router instead of placing every route in server.js.
const express = require("express");
// Import the controller that contains the actual route-handling logic.
const authController = require("../controllers/authController");
// Import middleware that blocks or allows routes based on login state.
const {
  requireAuth,
  requireGuest,
} = require("../middleware/authMiddleware");
// Import the generic rate limiter so sensitive auth routes cannot be hammered endlessly.
const { createRateLimiter } = require("../middleware/securityMiddleware");

// Create a new router instance to hold auth-related routes.
const router = express.Router();

// Rate-limit registration attempts to slow down abuse and duplicate spam.
const registerLimiter = createRateLimiter({
  windowMs: 15 * 60 * 1000,
  maxRequests: 10,
  keyGenerator: (req) => `${req.ip}:register`,
  message: "Too many registration attempts. Please wait a few minutes before trying again.",
});

// Rate-limit login attempts to reduce brute-force guessing.
const loginLimiter = createRateLimiter({
  windowMs: 15 * 60 * 1000,
  maxRequests: 12,
  keyGenerator: (req) => `${req.ip}:login:${String(req.body.email || "").toLowerCase()}`,
  message: "Too many login attempts. Please wait a few minutes before trying again.",
});

// Rate-limit forgot-password requests so reset tokens cannot be spammed.
const passwordResetRequestLimiter = createRateLimiter({
  windowMs: 15 * 60 * 1000,
  maxRequests: 8,
  keyGenerator: (req) => `${req.ip}:forgot:${String(req.body.email || "").toLowerCase()}`,
  message: "Too many password reset requests. Please try again later.",
});

// Rate-limit password-reset submissions to slow brute-force token abuse.
const passwordResetSubmitLimiter = createRateLimiter({
  windowMs: 15 * 60 * 1000,
  maxRequests: 10,
  keyGenerator: (req) => `${req.ip}:reset:${req.params.token}`,
  message: "Too many password reset attempts. Please request a new reset link later.",
});

// Show the registration form only when the user is not logged in.
router.get("/register", requireGuest, authController.showRegister);
// Handle submitted registration form data.
router.post("/register", requireGuest, registerLimiter, authController.register);

// Read the email verification token from the URL and verify the account.
router.get("/verify-email/:token", authController.verifyEmail);

// Show the login form only for guests.
router.get("/login", requireGuest, authController.showLogin);
// Handle the login form submission.
router.post("/login", requireGuest, loginLimiter, authController.login);

// Allow logout only for users who already have an active session.
router.post("/logout", requireAuth, authController.logout);

// Show the forgot-password page only for guests.
router.get(
  "/forgot-password",
  requireGuest,
  authController.showForgotPassword
);
// Handle forgot-password form submission.
router.post(
  "/forgot-password",
  requireGuest,
  passwordResetRequestLimiter,
  authController.forgotPassword
);

// Show the reset-password page when a token is provided in the URL.
router.get(
  "/reset-password/:token",
  requireGuest,
  authController.showResetPassword
);
// Handle the new password form submission for the matching token.
router.post(
  "/reset-password/:token",
  requireGuest,
  passwordResetSubmitLimiter,
  authController.resetPassword
);

// Export the router so server.js can mount it with app.use().
module.exports = router;
