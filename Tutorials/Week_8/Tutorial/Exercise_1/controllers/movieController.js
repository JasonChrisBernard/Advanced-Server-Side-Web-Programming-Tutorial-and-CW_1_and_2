const MovieModel = require("../models/movieModel");

class MovieController {
  static showHomePage(req, res) {
    res.render("index");
  }

  static async searchMovies(req, res) {
    const searchText = req.query.search;

    if (!searchText || searchText.trim() === "") {
      return res.status(400).json({
        success: false,
        message: "Please enter a movie title."
      });
    }

    try {
      const movieData = await MovieModel.searchMovies(searchText);

      console.log("OMDb Response:", movieData);

      if (movieData.Response === "False") {
        return res.json({
          success: false,
          message: movieData.Error || "No movies found."
        });
      }

      return res.json({
        success: true,
        movies: movieData.Search
      });

    } catch (error) {
      console.error("Backend error:", error.message);

      return res.status(500).json({
        success: false,
        message: "Server error while fetching movie data."
      });
    }
  }
}

module.exports = MovieController;