<?php
define('AR_ALUMNI_SETUP_RUNNER', true);

$configFile = __DIR__ . '/application/config/setup_database.php';

if (!file_exists($configFile)) {
    die('Config file not found: application/config/setup_database.php');
}

require $configFile;

if (!isset($setup_database)) {
    die('Setup configuration was not loaded correctly.');
}

date_default_timezone_set($setup_database['timezone']);

$dbFile = $setup_database['db_file'];
$dataDir = dirname($dbFile);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

try {
    $conn = new PDO('sqlite:' . $dbFile);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec('PRAGMA foreign_keys = ON;');

    foreach ($setup_database['tables'] as $tableName => $sql) {
        $conn->exec($sql);
    }

    foreach ($setup_database['indexes'] as $sql) {
        $conn->exec($sql);
    }

    $now = date('Y-m-d H:i:s');
    $demoPassword = $setup_database['demo_password'];
    $passwordHash = password_hash($demoPassword, PASSWORD_BCRYPT);

    $insertUserSql = "
        INSERT OR IGNORE INTO users
        (
            full_name,
            email,
            password_hash,
            role,
            email_verified,
            is_active,
            created_at,
            updated_at
        )
        VALUES
        (
            :full_name,
            :email,
            :password_hash,
            :role,
            :email_verified,
            1,
            :created_at,
            :updated_at
        )
    ";

    $insertUser = $conn->prepare($insertUserSql);

    foreach ($setup_database['demo_users'] as $user) {
        $insertUser->execute([
            ':full_name' => $user['full_name'],
            ':email' => strtolower($user['email']),
            ':password_hash' => $passwordHash,
            ':role' => $user['role'],
            ':email_verified' => $user['email_verified'],
            ':created_at' => $now,
            ':updated_at' => $now
        ]);
    }

    $users = $conn->query("
        SELECT id, full_name, email, role, email_verified, created_at
        FROM users
        ORDER BY id ASC
    ")->fetchAll();

    echo "<h1>SQLite Database Setup Complete</h1>";
    echo "<p><strong>Database file:</strong> " . htmlspecialchars($dbFile) . "</p>";

    echo "<h2>Created Tables</h2>";
    echo "<ul>";
    foreach (array_keys($setup_database['tables']) as $tableName) {
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";

    echo "<h2>Demo Accounts</h2>";
    echo "<p>Password for all demo accounts: <strong>" . htmlspecialchars($demoPassword) . "</strong></p>";

    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th></tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . ((int)$user['email_verified'] === 1 ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<hr>";
    echo "<p><strong>Important:</strong> After this works, rename setup_database.php to setup_database_done.php.</p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}