// Import multer so the app can process multipart/form-data file uploads.
const multer = require("multer");
// Import path so we can build a safe upload folder path.
const path = require("path");
// Import fs so we can create the upload folder automatically.
const fs = require("fs");

// Build the folder path where uploaded profile images will be stored.
const uploadFolder = path.join(__dirname, "../../public/uploads/profiles");
// Create the folder if it does not already exist.
if (!fs.existsSync(uploadFolder)) {
  fs.mkdirSync(uploadFolder, { recursive: true });
}

// Configure how uploaded files are stored on disk.
const storage = multer.diskStorage({
  // Save every uploaded profile image into the shared uploads folder.
  destination(req, file, cb) {
    cb(null, uploadFolder);
  },
  // Generate a unique filename so uploads do not overwrite each other.
  filename(req, file, cb) {
    const extension = path.extname(file.originalname || "").toLowerCase();
    const safeExtension = extension || ".png";
    const uniqueName = `profile-${Date.now()}-${Math.round(Math.random() * 1e9)}${safeExtension}`;
    cb(null, uniqueName);
  },
});

// Accept only common image mime types for profile pictures.
function imageFileFilter(req, file, cb) {
  if (file.mimetype && file.mimetype.startsWith("image/")) {
    cb(null, true);
  } else {
    cb(new Error("Only image uploads are allowed."));
  }
}

// Export the configured multer instance for route-level use.
module.exports = multer({
  storage,
  fileFilter: imageFileFilter,
  limits: {
    // Limit uploads to 5 MB so very large files are rejected early.
    fileSize: 5 * 1024 * 1024,
  },
});
