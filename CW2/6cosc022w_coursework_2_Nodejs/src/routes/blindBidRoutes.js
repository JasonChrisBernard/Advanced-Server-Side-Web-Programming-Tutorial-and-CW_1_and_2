// Import Express so the blind bidding feature can live in its own router.
const express = require("express");
// Import the controller that renders and saves blind bids.
const blindBidController = require("../controllers/blindBidController");
// Import the auth guard so only logged-in users can access blind bidding.
const { requireRole } = require("../middleware/authMiddleware");
// Import the rate limiter used to protect bid submissions from spam.
const { createRateLimiter } = require("../middleware/securityMiddleware");

// Create a router dedicated to the blind bidding pages.
const router = express.Router();

// Limit how often one browser/IP can submit bid updates in a short window.
const blindBidLimiter = createRateLimiter({
  windowMs: 10 * 60 * 1000,
  maxRequests: 20,
  keyGenerator: (req) => `${req.ip}:blind-bid:${req.session.user?.id || "guest"}`,
  message: "Too many bid submissions. Please wait a few minutes before trying again.",
});

// Show the blind bidding dashboard page.
router.get("/blind-bidding", requireRole("alumnus"), blindBidController.showBlindBiddingPage);
// Accept a posted bid amount for the current month.
router.post("/blind-bidding", requireRole("alumnus"), blindBidLimiter, blindBidController.submitBlindBid);
// Let the user record one alumni-event participation credit for the current bidding month.
router.post(
  "/blind-bidding/event-credit",
  requireRole("alumnus"),
  blindBidLimiter,
  blindBidController.claimAlumniEventCredit
);
// Allow the user to cancel tomorrow's still-pending bid before the winner is resolved.
router.post(
  "/blind-bidding/cancel",
  requireRole("alumnus"),
  blindBidLimiter,
  blindBidController.cancelBlindBid
);

// Export the router so server.js can mount it.
module.exports = router;
