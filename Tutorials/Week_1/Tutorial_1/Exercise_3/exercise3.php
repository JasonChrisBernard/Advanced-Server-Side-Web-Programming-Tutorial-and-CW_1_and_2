
<!DOCTYPE html>
<html>
<body>
<h1>Book Search</h1>
<?php 

    //Giving the connection to the database
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "tutorial1_db";

    $conn = new mysqli($host, $username, $password);

    if($conn -> connect_error){
        die("Connection failed: " . $conn->connect_error);
    }

    //Creating a new database 
    $sql = "CREATE DATABASE IF NOT EXISTS $database";

    if($conn -> query($sql) === TRUE){}
    else {
        die("Error Creating database: " . $conn->error);
    }

    //Selecting the Database
    $conn -> select_db($database);

    //Creating a book table if it doesnt exists
    $sql = "CREATE TABLE IF NOT EXISTS  books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255) NOT NULL,
                year_of_publication INT NOT NULL,
                price DECIMAL(10,2) NOT NULL
    
    )";

    if($conn -> query($sql) === TRUE){}
    else {
        die("Error creating table: " . $conn-> error);
    }

    //Checking if the table is empty only
    $checkData = "SELECT COUNT(*) AS total FROM books";
    $results = $conn->query($checkData);
    $row = $results-> fetch_assoc();

    //Inserting Data
    if($row['total'] == 0){
        $insertData = "INSERT INTO books (title,author,year_of_publication,price) VALUES
          ('Harry Potter and the Philosopher''s Stone', 'J.K. Rowling', 1997, 12.99),
        ('The Hobbit', 'J.R.R. Tolkien', 1937, 10.50),
        ('The Lord of the Rings', 'J.R.R. Tolkien', 1954, 25.00),
        ('Animal Farm', 'George Orwell', 1945, 8.99),
        ('1984', 'George Orwell', 1949, 9.99),
        ('Pride and Prejudice', 'Jane Austen', 1813, 7.50)";

        if ($conn->query($insertData) !== TRUE) {
        die("Error inserting sample data: " . $conn->error);
    }

    }

?>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
    Title : <input type="text" name="title">
    Author : <input type="text" name="author">
    Year Of Publication : <input type="number" name="year">
    <input type="submit" value="search">
</form>

<?php 
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $title = "";
    $author = "";
    $year = "";
    $searchErr = "";
    $titleErr = "";
    $authorErr = "";
    $yearErr = "";

    if(!empty($_POST['title'])){
        $title = trim($_POST['title']);
    }

    if(!empty($_POST['author'])){
        $author = trim($_POST['author']);
    }

    if(!empty($_POST['year'])){
        $year = trim($_POST['year']); 
    }

    if($title == "" && $author == "" && $year == ""){
        $searchErr = "Please enter at least one search value.";
    } else {
        if($searchErr != ""){
            echo "<p>$searchErr</p>";
        } else {
            $sql = "SELECT title, author, year_of_publication, price FROM books WHERE 1=1";
            $params = [];
            $types = "";

            if($title != ""){
                $sql .= " AND title LIKE ?";
                $params[] = "%" . $title . "%";
                $types .= "s";
            }

            if($author != ""){
                $sql .= " AND author LIKE ?";
                $params[] = "%" . $author . "%";
                $types .= "s";
            }

            if ($year != "") {
                $sql .= " AND year_of_publication = ?";
                $params[] = $year;
                $types .= "i";
            }

            $stmt = $conn->prepare($sql);

            if(!empty($params)) {
                $stmt -> bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt -> get_result();

            echo  "<h2>Matching Books</h2>";

            if($result->num_rows > 0){
                echo "<table border='1' cellpadding='8'>";
                echo "<tr><th>Title</th><th>Author</th><th>Year</th><th>Price</th></tr>";

                while($row = $result->fetch_assoc()){
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['year_of_publication']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>No matching books found.</p>";
            }

            $stmt->close();


        }
    } 
}

$conn->close();

?>





</body>
</html>