<?php
$host = 'localhost';
$dbname = 'ShieldHub';
$username = 'root'; // Default user in phpMyAdmin
$password = 'Chegengangav2.1'; // Leave empty for default phpMyAdmin setup

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

