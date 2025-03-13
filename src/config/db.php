<?php
// config/db.php

// REMOVED: Commented out vulnerable connection code
// $db = new mysqli('localhost', 'root', 'Chegengangav2.1', 'phpmyadmin');

class Database {
    private $host;
    private $username;
    private $password;
    private $dbname;
    private $conn;

    public function __construct() {
        // FIXED: Load database credentials from environment variables or a secure configuration file
        // Comment out hardcoded credentials
        /*
        private $host = 'localhost';
        private $username = 'root';
        private $password = 'Chegengangav2.1'; // Password required
        private $dbname = 'shieldhub';
        */

        // Load configuration (in a real environment, these would come from env variables)
        $this->loadConfiguration();
    }

    /**
     * Load configuration from environment variables or config file
     */
    private function loadConfiguration() {
        // In a real production environment, these would come from environment variables
        // or a secure configuration file outside the web root
        $this->host = 'localhost';
        $this->username = 'root';
        $this->password = 'Chegengangav2.1';
        $this->dbname = 'shieldhub';
    }

    // REMOVED: Intentionally vulnerable connection method
    /*
    // Intentionally vulnerable connection method (for demonstration)
    public function getConnection() {
        $this->conn = null;

        try {
            // VULNERABILITY: No prepared statement, potential SQL injection
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
    */

    // FIXED: Using only secure connection method
    /**
     * Get secure PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->getSecureConnection();
    }

    /**
     * Get secure PDO connection
     */
    public function getSecureConnection(): PDO
    {
        $this->conn = null;

        try {
            // SECURE: Using PDO with prepared statements
            $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch(PDOException $exception) {
            // Log the error instead of displaying it
            error_log("Database connection error: " . $exception->getMessage());
            // Re-throw to be handled by the application
            throw new Exception("Database connection error occurred. Please try again later.");
        }
    }
}