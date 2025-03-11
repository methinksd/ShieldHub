<?php
// src/config/connection.php

/**
 * Database Connection Handler
 *
 * This file provides a consistent way to connect to the database throughout the application.
 * It offers both a standard connection method and a secure connection method.
 */

// Report all errors except E_NOTICE
error_reporting(E_ALL & ~E_NOTICE);

class DatabaseConnection {
    // Database configuration
    private $host = 'localhost';
    private $username = 'root';
    private $password = 'Chegengangav2.1'; // Use your phpMyAdmin password here
    private $dbname = 'shieldhub';

    // Connection variables
    private static $instance = null;
    private $conn;
    private $securePdo;

    /**
     * Constructor - establishes database connections
     * Private to prevent direct instantiation
     */
    private function __construct() {
        // Create mysqli connection (less secure, for demonstration)
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

            if ($this->conn->connect_error) {
                throw new Exception("MySQL Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            // Log error but don't display to user
            error_log("Connection error: " . $e->getMessage());
        }

        // Create PDO connection (more secure)
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->securePdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log error but don't display to user
            error_log("PDO Connection error: " . $e->getMessage());
        }
    }

    /**
     * Singleton pattern implementation
     * Gets the single instance of the database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get mysqli connection (less secure, for demonstration)
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Get PDO connection (more secure, recommended for production)
     */
    public function getPDO() {
        return $this->securePdo;
    }

    /**
     * Close database connections
     */
    public function closeConnections() {
        if ($this->conn) {
            $this->conn->close();
        }

        $this->securePdo = null;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Database connection helper functions for quick access

/**
 * Get mysqli connection (less secure, for demonstration)
 */
function getDbConnection() {
    return DatabaseConnection::getInstance()->getConnection();
}

/**
 * Get PDO connection (more secure, recommended for production)
 */
function getSecureDbConnection() {
    return DatabaseConnection::getInstance()->getPDO();
}

/**
 * Close database connections
 */
function closeDbConnections() {
    DatabaseConnection::getInstance()->closeConnections();
}
