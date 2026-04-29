// Import Express so this feature can live in its own router file.
const express = require("express");
// Import the profile controller that renders and saves the alumni profile pages.
const profileController = require("../controllers/profileController");
// Import the middleware that protects profile pages for logged-in users only.
const { requireRole } = require("../middleware/authMiddleware");
// Import the reusable CSRF validator for multipart form submissions.
const { validateCsrfToken } = require("../middleware/securityMiddleware");
// Import the multer upload config used for the profile image field.
const upload = require("../config/upload");

// Create a router dedicated to alumni profile pages.
const router = express.Router();

// Wrap multer so upload errors become normal page errors instead of crashing the request.
function handleProfileImageUpload(req, res, next) {
  upload.single("profileImage")(req, res, (error) => {
    if (error) {
      req.uploadError = error.message;
    }

    next();
  });
}

// Show the saved alumni profile page.
router.get("/alumni-profile", requireRole("alumnus"), profileController.showProfile);
// Provide an explicit create URL that uses the same form as edit.
router.get("/alumni-profile/create", requireRole("alumnus"), profileController.showProfileForm);
// Show the create/edit alumni profile form.
router.get("/alumni-profile/edit", requireRole("alumnus"), profileController.showProfileForm);
// Support form submission from the explicit create URL as well.
router.post(
  "/alumni-profile/create",
  requireRole("alumnus"),
  handleProfileImageUpload,
  validateCsrfToken,
  profileController.saveProfile
);
// Save the submitted alumni profile form, including an optional image upload.
router.post(
  "/alumni-profile/edit",
  requireRole("alumnus"),
  handleProfileImageUpload,
  validateCsrfToken,
  profileController.saveProfile
);
// Delete the full alumni profile and all repeatable section rows.
router.post("/alumni-profile/delete", requireRole("alumnus"), profileController.deleteProfile);

// Export the router so server.js can mount it.
module.exports = router;
