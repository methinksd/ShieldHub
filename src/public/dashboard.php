<?php
// public/dashboard.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

class Dashboard
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getUserPosts($user_id)
    {
        $conn = $this->db->getConnection();

        $query = "SELECT * FROM posts WHERE user_id = $user_id ORDER BY created_at DESC";
        return $conn->query($query);
    }
}