// Import Express so we can create the web server and define routes.
const express = require("express");
// Import path so we can build safe file-system paths on any operating system.
const path = require("path");
// Load values from .env into process.env before the app starts.
require("dotenv").config();

// Import the mail verification helper so startup can report mail status.
const { verifyMailer } = require("./src/config/mailer");
// Import the shared session middleware configuration.
const { createSessionMiddleware } = require("./src/config/session");
// Import auth middleware used to protect the home page.
const { requireAuth } = require("./src/middleware/authMiddleware");
// Import shared security middleware for headers, CORS, CSRF, and rate limiting support.
const {
  applyApiCors,
  applySecurityHeaders,
  attachCsrfToken,
  verifyCsrfToken,
} = require("./src/middleware/securityMiddleware");
// Import all auth-related routes from the router file.
const authRoutes = require("./src/routes/authRoutes");
// Import the alumni profile routes.
const profileRoutes = require("./src/routes/profileRoutes");
// Import the blind bidding routes.
const blindBidRoutes = require("./src/routes/blindBidRoutes");
// Import the developer portal and bearer-token API routes.
const developerApiRoutes = require("./src/routes/developerApiRoutes");
// Import the CW2 staff analytics dashboard routes.
const staffRoutes = require("./src/routes/staffRoutes");
// Import the blind bidding resolver helpers used at startup and midnight.
const {
  runBlindBidResolutionJob,
  startBlindBidResolutionScheduler,
} = require("./src/services/blindBidService");
// Import the API client model so expired bearer tokens can be synchronized at startup.
const ApiClient = require("./src/models/apiClientModel");

// Create the main Express application object.
const app = express();

// Detect the separate CW2 staff-dashboard runtime used on localhost:3001.
const isStaffDashboardMode =
  process.env.APP_MODE === "cw2_staff" || Number(process.env.PORT || 3000) === 3001;
const staffDashboardRoles = new Set(["staff"]);

function isStaffDashboardUser(user) {
  return Boolean(user && staffDashboardRoles.has(user.role));
}

function clearKnownSessionCookies(res) {
  res.clearCookie("alumni.sid");
  res.clearCookie("cw2.staff.sid");

  if (process.env.SESSION_NAME) {
    res.clearCookie(process.env.SESSION_NAME);
  }
}

function renderStaffOnlyMessage(res) {
  return res.status(403).render("auth/message", {
    title: "Staff dashboard only",
    message: "This portal is reserved for staff accounts. Please sign in with a staff account or register as staff.",
  });
}

function enforceStaffDashboardBoundary(req, res, next) {
  if (!isStaffDashboardMode || req.path.startsWith("/api/")) {
    return next();
  }

  if (req.path.startsWith("/alumni-profile") || req.path.startsWith("/blind-bidding")) {
    return renderStaffOnlyMessage(res);
  }

  if (!req.session.user || isStaffDashboardUser(req.session.user)) {
    return next();
  }

  return req.session.destroy(() => {
    clearKnownSessionCookies(res);

    if (req.path === "/login") {
      return res.redirect("/login");
    }

    return renderStaffOnlyMessage(res);
  });
}

// Hide the default Express signature header from responses.
app.disable("x-powered-by");
// Trust the first proxy so secure cookies work correctly behind a reverse proxy in production.
app.set("trust proxy", 1);

// Apply core security headers to every response.
app.use(applySecurityHeaders);
// Apply a configured CORS policy to developer API routes.
app.use(applyApiCors);

// Read HTML form data such as login and register forms.
app.use(express.urlencoded({ extended: true }));
// Read JSON bodies in case any route sends JSON in the future.
app.use(express.json());

// Serve files from /public, but do not automatically serve public/index.html for "/".
app.use(
  express.static(path.join(__dirname, "public"), {
    index: false,
  })
);
// Serve the local Chart.js browser bundle used by the CW2 graph dashboard.
app.use(
  "/vendor/chartjs",
  express.static(path.join(__dirname, "node_modules/chart.js/dist"), {
    index: false,
  })
);

// Tell Express to render views using the EJS template engine.
app.set("view engine", "ejs");
// Tell Express where the EJS view files are stored.
app.set("views", path.join(__dirname, "src/views"));

// Enable sessions so the app can remember who is logged in.
app.use(createSessionMiddleware());

// Create or expose the session-bound CSRF token before templates render.
app.use(attachCsrfToken);
// Reject invalid CSRF tokens on state-changing browser requests.
app.use(verifyCsrfToken);

// Expose the logged-in user to every EJS template through res.locals.user.
app.use((req, res, next) => {
  // If the session has a user object, templates can read it as "user".
  res.locals.user = req.session.user || null;
  // Let shared templates know whether this server instance is the CW2 staff dashboard.
  res.locals.isStaffDashboardMode = isStaffDashboardMode;
  // Continue to the next middleware or route.
  next();
});

// Keep localhost:3001 staff-only even if an alumnus has a stale session cookie.
app.use(enforceStaffDashboardBoundary);

// Mount the auth router so all auth pages and actions are available.
app.use("/", authRoutes);

if (!isStaffDashboardMode) {
  // Mount CW1 alumni-only profile and blind bidding pages only on the alumni portal.
  app.use("/", profileRoutes);
  app.use("/", blindBidRoutes);
}

// Mount the developer portal and public developer API routes.
app.use("/", developerApiRoutes);

if (isStaffDashboardMode) {
  // Mount CW2 staff dashboard pages only on the staff-dashboard port.
  app.use("/", staffRoutes);
}

// Protect the home page so only logged-in users can access it.
app.get("/", requireAuth, (req, res) => {
  if (isStaffDashboardMode && isStaffDashboardUser(req.session.user)) {
    return res.redirect("/staff/dashboard");
  }

  // Render the home template and tell it whether a session user exists.
  res.render("auth/home", {
    title: "Home",
    isLoggedIn: Boolean(req.session.user),
  });
});

// Read the port from .env, or fall back to 3000 for local development.
const PORT = process.env.PORT || 3000;
// Start the server and run this callback once the app is listening.
app.listen(PORT, () => {
  // Print the local URL so it is easy to open in the browser.
  console.log(`Server running on http://localhost:${PORT}`);

  // Resolve any overdue daily blind bidding results immediately on startup.
  runBlindBidResolutionJob("server startup").catch((error) => {
    console.error("[BlindBidding] Startup resolver failed.", error);
  });

  // Mark overdue bearer tokens as expired before the portal or API starts serving requests.
  ApiClient.synchronizeTokenExpiryStates();

  // Start the in-process midnight scheduler for daily winner selection.
  startBlindBidResolutionScheduler();

  // Check whether the configured mail account can log in successfully.
  verifyMailer().then((result) => {
    // If mail works, show a success message in the terminal.
    if (result.ok) {
      console.log("Mailer connected successfully.");
    } else {
      // Otherwise, print the reason so mail issues are easier to debug.
      console.warn(`Mailer not ready: ${result.reason}`);
    }
  });
});
