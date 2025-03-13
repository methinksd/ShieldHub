<?php
// src/config/connection.php

/**
 * Database Connection Handler
 *
 * This file provides a consistent way to connect to the database throughout the application.
 * It offers a secure connection method using PDO.
 */

// Report all errors except E_NOTICE
error_reporting(E_ALL & ~E_NOTICE);

class DatabaseConnection {
    // Database configuration
    private $host;
    private $username;
    private $password;
    private $dbname;

    // Connection variables
    private static $instance = null;
    private $securePdo;

    /**
     * Constructor - establishes database connections
     * Private to prevent direct instantiation
     * @throws Exception
     */
    private function __construct() {
        // First check if PDO MySQL driver is available
        if (!extension_loaded('pdo_mysql')) {
            error_log("PDO MySQL driver is not installed");
            throw new Exception("Database connection error occurred: could not find driver. Please install PDO MySQL extension.");
        }

        // Load from environment variables or config file
        $this->loadConfiguration();

        // Create PDO connection (secure)
        try {
            $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->securePdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log error with specific message for debugging
            error_log("PDO Connection error details: " . $e->getMessage());
            // Throw exception to be caught by application
            throw new Exception("Database connection error occurred: " . $e->getMessage());
        }
    }

    /**
     * Load configuration from environment variables or config file
     */
    private function loadConfiguration() {
        // In a real production environment, these would come from environment variables
        // or a secure configuration file outside the web root

        // Use a consistent password across the application
        $this->host = 'localhost';
        $this->username = 'root';
        $this->password = 'Justleo12#'; // Standardized on this password
        $this->dbname = 'shieldhub';
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
     * Get PDO connection (secure)
     */
    public function getPDO(): PDO
    {
        return $this->securePdo;
    }

    /**
     * Close database connections
     */
    public function closeConnections() {
        $this->securePdo = null;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     * @throws Exception
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get secure PDO connection
 * @throws Exception
 */
function getSecureDbConnection(): PDO
{
    try {
        return DatabaseConnection::getInstance()->getPDO();
    } catch (Exception $e) {
        // Log the detailed error
        error_log("Failed to get secure database connection: " . $e->getMessage());
        // Re-throw to be handled by the application
        throw $e;
    }
}

/**
 * Close database connections
 */
function closeDbConnections() {
    try {
        DatabaseConnection::getInstance()->closeConnections();
    } catch (Exception $e) {
        error_log("Error closing database connections: " . $e->getMessage());
    }
}