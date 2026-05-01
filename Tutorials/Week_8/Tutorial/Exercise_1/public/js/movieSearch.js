const movieSearchForm = document.getElementById("movieSearchForm");
const movieInput = document.getElementById("movieInput");
const message = document.getElementById("message");
const movieResults = document.getElementById("movieResults");

movieSearchForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  const searchText = movieInput.value.trim();

  if (searchText === "") {
    message.textContent = "Please enter a movie title.";
    movieResults.innerHTML = "";
    return;
  }

  message.textContent = "Loading movies...";
  movieResults.innerHTML = "";

  try {
    const response = await fetch(`/api/movies?search=${encodeURIComponent(searchText)}`);
    const data = await response.json();

    console.log(data);

    if (!data.success) {
      message.textContent = data.message;
      return;
    }

    message.textContent = "";

    data.movies.forEach(function (movie) {
      const movieCard = document.createElement("div");
      movieCard.className = "movie-card";

      const posterUrl =
        movie.Poster && movie.Poster !== "N/A"
          ? movie.Poster
          : "https://via.placeholder.com/200x300?text=No+Poster";

      movieCard.innerHTML = `
        <img src="${posterUrl}" alt="${movie.Title} poster">
        <h3>${movie.Title}</h3>
        <p>${movie.Year}</p>
      `;

      movieResults.appendChild(movieCard);
    });

  } catch (error) {
    console.error(error);
    message.textContent = "Something went wrong. Please try again.";
  }
});