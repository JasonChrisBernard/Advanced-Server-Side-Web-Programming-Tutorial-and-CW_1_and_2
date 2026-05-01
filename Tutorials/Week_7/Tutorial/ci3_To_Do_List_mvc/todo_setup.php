    <?php
    // todo_setup.php
    // Run this file ONCE to create the database and table.

    // 1. Database connection details for XAMPP
    $host = "localhost";
    $username = "root";
    $password = "";

    // 2. Create connection to MySQL server
    $conn = new mysqli($host, $username, $password);

    // 3. Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo "Connected to MySQL successfully.<br>";

    // 4. Create database
    $sql = "CREATE DATABASE IF NOT EXISTS todo_ci_db
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_general_ci";

    if ($conn->query($sql) === TRUE) {
        echo "Database created or already exists.<br>";
    } else {
        die("Error creating database: " . $conn->error);
    }

    // 5. Select the database
    $conn->select_db("todo_ci_db");

    // 6. Create table
    $sql = "CREATE TABLE IF NOT EXISTS todo_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(100) NOT NULL,
        action_title VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table todo_actions created or already exists.<br>";
    } else {
        die("Error creating table: " . $conn->error);
    }

    // 7. Add sample records for learning/testing
    // These sample records use demo_user_123.
    // Your CodeIgniter app will create its own session user_id later.
    $demoUserId = "demo_user_123";

    $sql = "INSERT INTO todo_actions (user_id, action_title)
            VALUES 
            ('$demoUserId', 'Finish CodeIgniter To-Do tutorial'),
            ('$demoUserId', 'Check session cookie in browser inspector'),
            ('$demoUserId', 'Check logs inside application/logs')";

    if ($conn->query($sql) === TRUE) {
        echo "Sample records inserted.<br>";
    } else {
        echo "Sample records may already exist or insert failed: " . $conn->error . "<br>";
    }

    // 8. Close connection
    $conn->close();

    echo "<br>Setup complete. You can now configure CodeIgniter.";
    ?>