<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
/* 

Property @insert_batch()

*/
class Setup extends CI_Controller{
    public function install(){
        // Load CodeIgniter database library
        $this->load->database();

        $database = 'tutorial_movies_db';

        
        // 1. Create database if it does not exist
        $sql = "CREATE DATABASE IF NOT EXISTS `$database`
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_general_ci";

        // 2. Select the database
        $this -> db -> query("USE `$database");


        // 3. Create films table if it does not exist
        $sql = "CREATE TABLE IF NOT EXISTS films (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    director VARCHAR(255) NOT NULL,
                    genre VARCHAR(100) NOT NULL,
                    imdb_rating DECIMAL(3,1) NOT NULL,
                    release_year INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if(!$this -> db -> query($sql)){
            show_error('Error creating films table.');
        }

        // 4. Check if films table is empty
        $query = $this->db->query("SELECT COUNT(*) AS total FROM films");
        $row = $query->row_array();

        // 5. Insert sample films only if table is empty
        if ($row['total'] == 0) {
            $films = array(
                array(
                    'title' => 'The Shawshank Redemption',
                    'director' => 'Frank Darabont',
                    'genre' => 'Drama',
                    'imdb_rating' => 9.3,
                    'release_year' => 1994
                ),
                array(
                    'title' => 'The Godfather',
                    'director' => 'Francis Ford Coppola',
                    'genre' => 'Crime',
                    'imdb_rating' => 9.2,
                    'release_year' => 1972
                ),
                array(
                    'title' => 'The Dark Knight',
                    'director' => 'Christopher Nolan',
                    'genre' => 'Action',
                    'imdb_rating' => 9.0,
                    'release_year' => 2008
                ),
                array(
                    'title' => 'Forrest Gump',
                    'director' => 'Robert Zemeckis',
                    'genre' => 'Drama',
                    'imdb_rating' => 8.8,
                    'release_year' => 1994
                ),
                array(
                    'title' => 'Inception',
                    'director' => 'Christopher Nolan',
                    'genre' => 'Sci-Fi',
                    'imdb_rating' => 8.8,
                    'release_year' => 2010
                ),
                array(
                    'title' => 'Back to the Future',
                    'director' => 'Robert Zemeckis',
                    'genre' => 'Sci-Fi',
                    'imdb_rating' => 8.5,
                    'release_year' => 1985
                ),
                array(
                    'title' => 'Toy Story',
                    'director' => 'John Lasseter',
                    'genre' => 'Animation',
                    'imdb_rating' => 8.3,
                    'release_year' => 1995
                ),
                array(
                    'title' => 'The Terminator',
                    'director' => 'James Cameron',
                    'genre' => 'Sci-Fi',
                    'imdb_rating' => 8.1,
                    'release_year' => 1984
                ),
                array(
                    'title' => 'Terminator 2: Judgment Day',
                    'director' => 'James Cameron',
                    'genre' => 'Action',
                    'imdb_rating' => 8.6,
                    'release_year' => 1991
                ),
                array(
                    'title' => 'The Hangover',
                    'director' => 'Todd Phillips',
                    'genre' => 'Comedy',
                    'imdb_rating' => 7.7,
                    'release_year' => 2009
                ),
                array(
                    'title' => 'Home Alone',
                    'director' => 'Chris Columbus',
                    'genre' => 'Comedy',
                    'imdb_rating' => 7.7,
                    'release_year' => 1990
                ),
                array(
                    'title' => 'The Mask',
                    'director' => 'Chuck Russell',
                    'genre' => 'Comedy',
                    'imdb_rating' => 6.9,
                    'release_year' => 1994
                )
            );

            $this->db->insert_batch('films', $films);
        }

        echo "<h1>Setup complete</h1>";
        echo "<p>Database <strong>$database</strong> created.</p>";
        echo "<p>Table <strong>films</strong> created.</p>";
        echo "<p>Sample film records inserted if the table was empty.</p>";
        echo "<p>Now update database.php and set database to <strong>tutorial_movies_db</strong>.</p>";
        

    }
}




?>