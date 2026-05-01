const express = require("express");
const path = require("path");
require("dotenv").config();

const movieRoutes = require("./routes/movieRoutes");

const app = express();

const PORT = process.env.PORT || 3000;

// View engine setup
app.set("view engine", "ejs");
app.set("views", path.join(__dirname, "views"));

// Static files
app.use(express.static(path.join(__dirname, "public")));

// Routes
app.use("/", movieRoutes);

// Start server
app.listen(PORT, () => {
  console.log(`Server running at http://localhost:${PORT}`);
});