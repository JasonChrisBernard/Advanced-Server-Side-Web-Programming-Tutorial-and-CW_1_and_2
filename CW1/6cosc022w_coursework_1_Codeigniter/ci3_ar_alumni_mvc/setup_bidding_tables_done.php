<?php
define('AR_ALUMNI_BIDDING_SETUP_RUNNER', true);

$configFile = __DIR__ . '/application/config/setup_bidding_tables.php';

if (!file_exists($configFile)) {
    die('Config file not found: application/config/setup_bidding_tables.php');
}

require $configFile;

if (!isset($bidding_setup)) {
    die('Bidding setup configuration was not loaded correctly.');
}

date_default_timezone_set($bidding_setup['timezone']);

$dbFile = $bidding_setup['db_file'];

if (!file_exists($dbFile)) {
    die('Main database not found. Please complete Part 1 database setup first.');
}

try {
    $conn = new PDO('sqlite:' . $dbFile);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec('PRAGMA foreign_keys = ON;');

    foreach ($bidding_setup['tables'] as $tableName => $sql) {
        $conn->exec($sql);
    }

    foreach ($bidding_setup['indexes'] as $sql) {
        $conn->exec($sql);
    }

    echo "<h1>Bidding Tables Setup Complete</h1>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($dbFile) . "</p>";

    echo "<h2>Created Tables</h2>";
    echo "<ul>";
    foreach (array_keys($bidding_setup['tables']) as $tableName) {
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";

    echo "<hr>";
    echo "<p><strong>Important:</strong> Rename <code>setup_bidding_tables.php</code> to <code>setup_bidding_tables_done.php</code> after this works.</p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}