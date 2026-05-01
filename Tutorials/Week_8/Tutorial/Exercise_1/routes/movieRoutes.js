const express = require("express");
const MovieController = require("../controllers/movieController");

const router = express.Router();

router.get("/", MovieController.showHomePage);

router.get("/api/movies", MovieController.searchMovies);

module.exports = router;