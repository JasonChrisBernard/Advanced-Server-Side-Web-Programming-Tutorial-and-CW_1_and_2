// Import express-session so the app can persist login state between requests.
const session = require("express-session");

// Decide whether cookies should use the Secure flag based on the deployed app URL or explicit env override.
function shouldUseSecureCookies() {
  return (
    process.env.COOKIE_SECURE === "true" ||
    String(process.env.APP_BASE_URL || "").startsWith("https://")
  );
}

// Build the shared session options so the server can reuse one consistent session policy.
function buildSessionOptions() {
  return {
    // Use a named cookie instead of the default connect.sid value.
    name: "alumni.sid",
    // Use the configured secret, with a development fallback for local setup.
    secret: process.env.SESSION_SECRET || "dev-secret-change-me",
    // Do not rewrite the session store when nothing changed.
    resave: false,
    // Avoid creating empty guest sessions because CSRF uses a separate cookie.
    saveUninitialized: false,
    // Refresh the inactivity timeout whenever the user is still active.
    rolling: true,
    // Configure the browser cookie used to identify the session.
    cookie: {
      // Prevent client-side JavaScript from reading the session cookie.
      httpOnly: true,
      // Protect the cookie from cross-site form submissions while preserving normal navigation.
      sameSite: "lax",
      // Use secure cookies automatically in HTTPS deployments.
      secure: shouldUseSecureCookies(),
      // Expire the cookie after the configured inactivity window.
      maxAge: 1000 * 60 * Number(process.env.SESSION_MAX_AGE_MINUTES || 60),
    },
  };
}

// Create the actual Express session middleware from the shared options.
function createSessionMiddleware() {
  return session(buildSessionOptions());
}

module.exports = {
  buildSessionOptions,
  createSessionMiddleware,
  shouldUseSecureCookies,
};
