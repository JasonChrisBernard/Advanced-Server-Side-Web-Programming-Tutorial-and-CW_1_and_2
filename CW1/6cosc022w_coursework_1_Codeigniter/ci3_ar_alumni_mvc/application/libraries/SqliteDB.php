<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SqliteDB
{
    private $conn;

    public function __construct()
    {
        $dbPath = APPPATH . 'data/ar_alumni.sqlite';

        if (!file_exists($dbPath)) {
            show_error('SQLite database file not found. Please run setup_database.php first.');
        }

        try {
            $this->conn = new PDO('sqlite:' . $dbPath);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $this->conn->exec('PRAGMA foreign_keys = ON;');

        } catch (PDOException $e) {
            show_error('SQLite connection failed: ' . $e->getMessage());
        }
    }

    public function conn()
    {
        return $this->conn;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}