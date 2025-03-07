<?php
// config/db.php

//$db = new mysqli('localhost', 'root', 'Chegengangav2.1', 'phpmyadmin');

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = 'Chegengangav2.1';//Password required
    private $dbname = 'shieldHub';
    public $conn;

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

    // Secure connection method (to demonstrate fix)
    public function getSecureConnection() {
        $this->conn = null;

        try {
            // SECURE: Using PDO with prepared statements
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
