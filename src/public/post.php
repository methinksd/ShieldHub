<?php
// public/post.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// VULNERABILITY: Insecure Direct Object Reference (IDOR)
// No check if the post belongs to the current user or is public
$post_id = isset($_GET['id']) ? $_GET['id'] : 0;
if (!$post_id) {
    header("Location: dashboard.php");
    exit();
}

class PostView {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // VULNERABILITY: IDOR - No authorization check
    public function getPost($post_id) {
        $conn = $this->db->getConnection();

        // VULNERABILITY: No prepared statement, potential SQL injection
        $query = "SELECT posts.*, users.username FROM posts 
                 JOIN users ON posts.user_id = users.id 
                 WHERE posts.id = $post_id";

        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }

    // Secure method (for comparison)
    public function secureGetPost($post_id, $current_user_id) {
        try {
            $conn = $this->db->getSecureConnection();

            // Only return posts that belong to the current user (or could add a "public" flag check)
            $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                                   JOIN users ON posts.user_id = users.id 
                                   WHERE posts.id = :post_id AND posts.user_id = :user_id");

            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $current_user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                return null;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}

$view = new PostView();
$post = $view->getPost($post_id);

if (!$post) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $post['title']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1><?php echo $post['title']; ?></h1>

<div class="post-meta">
    Posted by <?php echo $post['username']; ?> on <?php echo $post['created_at']; ?>
</div>

<div class="post-content">
    <!-- VULNERABILITY: XSS vulnerability - content not escaped -->
    <?php echo $post['content']; ?>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>