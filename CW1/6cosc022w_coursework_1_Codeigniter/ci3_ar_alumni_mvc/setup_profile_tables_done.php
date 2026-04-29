<?php
define('AR_ALUMNI_PROFILE_SETUP_RUNNER', true);

$configFile = __DIR__ . '/application/config/setup_profile_tables.php';

if (!file_exists($configFile)) {
    die('Config file not found: application/config/setup_profile_tables.php');
}

require $configFile;

if (!isset($profile_setup)) {
    die('Profile setup configuration was not loaded correctly.');
}

date_default_timezone_set($profile_setup['timezone']);

$dbFile = $profile_setup['db_file'];
$uploadDir = __DIR__ . '/uploads/profiles';

if (!file_exists($dbFile)) {
    die('Main database not found. Please complete Part 1 database setup first.');
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

try {
    $conn = new PDO('sqlite:' . $dbFile);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec('PRAGMA foreign_keys = ON;');

    foreach ($profile_setup['tables'] as $tableName => $sql) {
        $conn->exec($sql);
    }

    foreach ($profile_setup['indexes'] as $sql) {
        $conn->exec($sql);
    }

    foreach ($profile_setup['alters'] as $sql) {
        try {
            $conn->exec($sql);
        } catch (PDOException $e) {
            // Ignore duplicate column errors
        }
    }

    echo "<h1>Profile Tables Setup Complete</h1>";

    echo "<p><strong>Database:</strong> " . htmlspecialchars($dbFile) . "</p>";
    echo "<p><strong>Upload folder:</strong> " . htmlspecialchars($uploadDir) . "</p>";

    echo "<h2>Created Tables</h2>";
    echo "<ul>";
    foreach (array_keys($profile_setup['tables']) as $tableName) {
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";

    echo "<hr>";
    echo "<p><strong>Important:</strong> Rename <code>setup_profile_tables.php</code> to <code>setup_profile_tables_done.php</code> after this works.</p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}