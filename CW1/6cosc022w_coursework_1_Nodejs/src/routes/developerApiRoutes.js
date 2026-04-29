// Import Express so the developer portal and API routes can live together.
const express = require("express");
// Import the controller that renders the portal, docs, and public JSON API.
const developerApiController = require("../controllers/developerApiController");
// Import the session auth guard used for the web portal pages.
const { requireRole } = require("../middleware/authMiddleware");
// Import the bearer-token middleware used for the public developer API.
const { requireBearerToken } = require("../middleware/apiAuthMiddleware");
// Import the rate limiter used for developer-client management routes.
const { createRateLimiter } = require("../middleware/securityMiddleware");

// Create a router dedicated to developer portal and API routes.
const router = express.Router();

// Slow down repeated token-creation requests from the same browser session/IP pair.
const developerClientCreationLimiter = createRateLimiter({
  windowMs: 15 * 60 * 1000,
  maxRequests: 12,
  keyGenerator: (req) => `${req.ip}:developer-client-create`,
  message: "Too many developer client requests. Please wait before creating more tokens.",
});

// Show the logged-in developer portal page.
router.get("/developer-portal", requireRole("developer"), developerApiController.showDeveloperPortal);
// Create a new external client and bearer token from the portal form.
router.post(
  "/developer-portal/clients",
  requireRole("developer"),
  developerClientCreationLimiter,
  developerApiController.createDeveloperClient
);
// Revoke one issued bearer token from the portal page.
router.post(
  "/developer-portal/tokens/:tokenId/revoke",
  requireRole("developer"),
  developerApiController.revokeDeveloperToken
);

// Render the human-friendly documentation page.
router.get("/developer-api/docs", developerApiController.showDeveloperDocs);
// Render the interactive Swagger UI requested by the coursework rubric.
router.get("/api-docs", developerApiController.showSwaggerUi);
// Return the raw OpenAPI/Swagger JSON document.
router.get("/developer-api/swagger.json", developerApiController.showSwaggerJson);

// Protect the featured alumnus endpoint with a bearer token that includes the featured:read scope.
router.get(
  "/api/v1/featured-alumnus/today",
  requireBearerToken("featured:read"),
  developerApiController.getTodayFeaturedAlumnusApi
);

// Export the router so server.js can mount it.
module.exports = router;
