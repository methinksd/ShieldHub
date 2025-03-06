<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filePath = "uploads/" . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $stmt = $pdo->prepare("INSERT INTO uploads (user_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $file['name'], $filePath]);
        echo "File uploaded!";
    } else {
        echo "Upload failed!";
    }
}
?>

