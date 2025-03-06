<?php
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $passwordHash])) {
        header("Location: login.php");
        exit;
    } else {
        echo "Registration failed!";
    }
}
?>
