// Import the random-token helper used to create CSRF secrets.
const { createRandomToken } = require("../utils/validators");

// Keep in-memory request buckets for lightweight rate limiting in development.
const rateLimitStore = new Map();
// Use secure cookies only when the app is explicitly configured to run over HTTPS.
const shouldUseSecureCookies =
  process.env.COOKIE_SECURE === "true" ||
  String(process.env.APP_BASE_URL || "").startsWith("https://");

// Add security headers similar to what Helmet would usually provide.
function applySecurityHeaders(req, res, next) {
  res.setHeader("X-Content-Type-Options", "nosniff");
  res.setHeader("X-Frame-Options", "DENY");
  res.setHeader("Referrer-Policy", "strict-origin-when-cross-origin");
  res.setHeader("Permissions-Policy", "camera=(), microphone=(), geolocation=()");
  res.setHeader("Cross-Origin-Opener-Policy", "same-origin");
  res.setHeader("Cross-Origin-Resource-Policy", "same-origin");
  res.setHeader(
    "Content-Security-Policy",
    [
      "default-src 'self'",
      "img-src 'self' data:",
      "style-src 'self' 'unsafe-inline' https://unpkg.com",
      "script-src 'self' 'unsafe-inline' https://unpkg.com",
      "font-src 'self' data:",
      "connect-src 'self'",
      "form-action 'self'",
      "base-uri 'self'",
      "frame-ancestors 'none'",
    ].join("; ")
  );

  next();
}

// Apply a clear CORS policy only to API routes that might be called by external clients.
function applyApiCors(req, res, next) {
  if (!req.path.startsWith("/api/")) {
    return next();
  }

  const allowedOrigins = String(process.env.CORS_ALLOWED_ORIGINS || "")
    .split(",")
    .map((origin) => origin.trim())
    .filter(Boolean);
  const requestOrigin = req.get("origin");

  // Allow same-origin tools like curl or Postman that do not send an Origin header.
  if (!requestOrigin) {
    return next();
  }

  if (!allowedOrigins.includes(requestOrigin)) {
    return res.status(403).json({
      success: false,
      error: "Origin is not allowed for this API.",
    });
  }

  res.setHeader("Access-Control-Allow-Origin", requestOrigin);
  res.setHeader("Vary", "Origin");
  res.setHeader("Access-Control-Allow-Headers", "Authorization, Content-Type");
  res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");

  if (req.method === "OPTIONS") {
    return res.sendStatus(204);
  }

  return next();
}

// Parse the raw Cookie header into a small key/value object.
function parseCookies(req) {
  return String(req.headers.cookie || "")
    .split(";")
    .map((cookiePart) => cookiePart.trim())
    .filter(Boolean)
    .reduce((cookies, cookiePart) => {
      const [key, ...valueParts] = cookiePart.split("=");
      cookies[key] = decodeURIComponent(valueParts.join("="));
      return cookies;
    }, {});
}

// Attach one CSRF token per browser cookie and expose it to every EJS template.
function attachCsrfToken(req, res, next) {
  if (req.path.startsWith("/api/")) {
    res.locals.csrfToken = "";
    return next();
  }

  const cookies = parseCookies(req);
  const csrfToken = cookies.csrfToken || createRandomToken(24);

  // Refresh the CSRF cookie when it does not exist yet.
  if (!cookies.csrfToken) {
    res.cookie("csrfToken", csrfToken, {
      httpOnly: true,
      sameSite: "lax",
      secure: shouldUseSecureCookies,
      path: "/",
    });
  }

  res.locals.csrfToken = csrfToken;
  return next();
}

// Validate CSRF tokens on state-changing browser requests while leaving bearer-token APIs untouched.
function verifyCsrfToken(req, res, next) {
  const protectedMethods = new Set(["POST", "PUT", "PATCH", "DELETE"]);

  if (!protectedMethods.has(req.method) || req.path.startsWith("/api/")) {
    return next();
  }

  // Multipart bodies are parsed later by multer, so route-level middleware must validate them afterward.
  if (req.is("multipart/form-data")) {
    return next();
  }

  return validateCsrfToken(req, res, next);
}

// Reusable CSRF validator used globally and by multipart routes after multer has parsed the body.
function validateCsrfToken(req, res, next) {
  const submittedToken =
    req.body?.csrfToken ||
    req.get("x-csrf-token") ||
    req.get("x-xsrf-token");
  const cookieToken = parseCookies(req).csrfToken;

  if (!submittedToken || !cookieToken || submittedToken !== cookieToken) {
    if (req.accepts("html")) {
      return res.status(403).render("auth/message", {
        title: "Security check failed",
        message: "The form expired or the security token was invalid. Refresh the page and try again.",
      });
    }

    return res.status(403).json({
      success: false,
      error: "Invalid CSRF token.",
    });
  }

  return next();
}

// Create a reusable in-memory rate limiter for sensitive endpoints.
function createRateLimiter({
  windowMs,
  maxRequests,
  keyGenerator = (req) => req.ip,
  message,
}) {
  return (req, res, next) => {
    const now = Date.now();
    const key = keyGenerator(req);
    const currentBucket = rateLimitStore.get(key) || [];
    const recentRequests = currentBucket.filter((timestamp) => now - timestamp < windowMs);

    recentRequests.push(now);
    rateLimitStore.set(key, recentRequests);

    if (recentRequests.length > maxRequests) {
      if (req.accepts("html")) {
        return res.status(429).render("auth/message", {
          title: "Too many requests",
          message,
        });
      }

      return res.status(429).json({
        success: false,
        error: message,
      });
    }

    return next();
  };
}

module.exports = {
  applyApiCors,
  applySecurityHeaders,
  attachCsrfToken,
  createRateLimiter,
  validateCsrfToken,
  verifyCsrfToken,
};
