const axios = require("axios");
const omdbConfig = require("../config/omdbConfig");

class MovieModel {
  static async searchMovies(searchText) {
    const response = await axios.get(omdbConfig.baseUrl, {
      params: {
        apikey: omdbConfig.apiKey,
        s: searchText,
        type: "movie"
      }
    });

    return response.data;
  }
}

module.exports = MovieModel;