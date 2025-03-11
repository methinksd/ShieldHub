<?php
// public/index.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';
require_once '../config/connection.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Web Security Demo Project</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Web Security Demonstration Project</h1>

<div class="intro">
    <p>This website demonstrates common web security vulnerabilities and mitigation techniques.</p>
    <p>Features available:</p>
    <ul>
        <li>User registration and authentication</li>
        <li>File uploads</li>
        <li>Dynamic content creation</li>
        <li>User profiles</li>
    </ul>
    <p><strong>Note:</strong> This site intentionally contains security vulnerabilities for educational purposes.</p>
</div>

<div class="actions">
    <a href="login.php" class="button">Login</a>
    <a href="register.php" class="button">Register</a>
</div>
</body>
</html>