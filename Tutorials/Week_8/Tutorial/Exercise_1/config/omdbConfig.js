require("dotenv").config();

const omdbConfig = {
  baseUrl: "https://www.omdbapi.com/",
  apiKey: process.env.OMDB_API_KEY
};

module.exports = omdbConfig;