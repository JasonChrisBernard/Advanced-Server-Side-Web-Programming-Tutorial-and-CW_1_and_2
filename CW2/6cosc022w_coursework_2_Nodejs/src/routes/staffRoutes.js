// Import Express so staff dashboard pages can be mounted in their own router.
const express = require("express");
// Import the CW2 staff dashboard controller.
const staffController = require("../controllers/staffController");
// Import role middleware so only dashboard/developer staff can access analytics pages.
const { requireRole } = require("../middleware/authMiddleware");

// Create a router dedicated to CW2 staff analytics pages.
const router = express.Router();

// Only staff accounts can access the CW2 staff dashboard pages.
const requireStaffDashboardAccess = requireRole("staff");

// Dashboard landing page for localhost:3001.
router.get("/staff/dashboard", requireStaffDashboardAccess, staffController.showDashboard);
// Short alias so /dashboard works naturally on the CW2 port.
router.get("/dashboard", requireStaffDashboardAccess, staffController.showDashboard);
// Dedicated graph page required by the CW2 brief.
router.get("/staff/graphs", requireStaffDashboardAccess, staffController.showGraphs);
// Alumni directory with programme, graduation date/year, and industry-sector filters.
router.get("/staff/alumni", requireStaffDashboardAccess, staffController.showAlumniDirectory);
// Staff-facing alias that avoids exposing implementation naming in navigation.
router.get("/staff/records", requireStaffDashboardAccess, staffController.showAlumniDirectory);

// Export the router so server.js can mount it.
module.exports = router;
