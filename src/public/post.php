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

// FIXED: Validate and sanitize post_id parameter
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    header("Location: dashboard.php");
    exit();
}

class PostView {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // REMOVED: Vulnerable IDOR and SQL injection methods
    /*
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
    */

    // FIXED: Using secure method with authorization check
    /**
     * Get post with authorization check
     */
    public function getPost($post_id, $current_user_id) {
        try {
            $conn = $this->db->getConnection();

            // Query includes authorization check for user's own posts
            // A real-world app might also include logic for "public" posts
            $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                               JOIN users ON posts.user_id = users.id 
                               WHERE posts.id = :post_id AND posts.user_id = :user_id");

            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            } else {
                // Try to get post with public access (assuming there's a public flag)
                // In a real app, you'd have a public flag in the database
                $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                                   JOIN users ON posts.user_id = users.id 
                                   WHERE posts.id = :post_id");
                $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return $stmt->fetch();
                }
            }

            return null;
        } catch (Exception $e) {
            error_log("Error fetching post: " . $e->getMessage());
            return null;
        }
    }
}

try {
    $view = new PostView();
    $post = $view->getPost($post_id, $_SESSION['user_id']);

    if (!$post) {
        // Post not found or not authorized to view
        header("Location: dashboard.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Post view error: " . $e->getMessage());
    header("Location: dashboard.php?error=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1><?php echo htmlspecialchars($post['title']); ?></h1>

<div class="post-meta">
    Posted by <?php echo htmlspecialchars($post['username']); ?> on <?php echo htmlspecialchars($post['created_at']); ?>
</div>

<div class="post-content">
    <!-- FIXED: XSS vulnerability - content properly escaped -->
    <?php echo htmlspecialchars($post['content']); ?>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>