<?php
// public/posts.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

class Posts {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // REMOVED: Vulnerable methods with SQL Injection and XSS
    /*
    public function createPost($user_id, $title, $content) {
        $conn = $this->db->getConnection();

        // VULNERABILITY: Minimal sanitization
        $title = $conn->real_escape_string($title);
        // VULNERABILITY: XSS possible in content

        $query = "INSERT INTO posts (user_id, title, content) VALUES ('$user_id', '$title', '$content')";

        if ($conn->query($query) === TRUE) {
            return ["success" => true, "message" => "Post created successfully."];
        } else {
            return ["success" => false, "message" => "Error creating post."];
        }
    }

    // VULNERABILITY: Insecure Direct Object Reference
    public function getPost($post_id) {
        $conn = $this->db->getConnection();

        // VULNERABILITY: No authorization check
        $query = "SELECT * FROM posts WHERE id = $post_id";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }

    public function getAllPosts(): array
    {
        $conn = $this->db->getConnection();

        $query = "SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC";
        $result = $conn->query($query);

        $posts = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
        }

        return $posts;
    }
    */

    /**
     * Create a new post using secure methods
     */
    public function createPost($user_id, $title, $content): array
    {
        try {
            $conn = $this->db->getSecureConnection();

            // Proper sanitization before storing
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (:user_id, :title, :content)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return ["success" => true, "message" => "Post created successfully."];
            } else {
                return ["success" => false, "message" => "Error creating post."];
            }
        } catch (Exception $e) {
            error_log("Error creating post: " . $e->getMessage());
            return ["success" => false, "message" => "Database error occurred."];
        }
    }

    /**
     * Get post with authorization check
     */
    public function getPost($post_id, $current_user_id) {
        try {
            $conn = $this->db->getSecureConnection();

            // Query with authorization check
            $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                               JOIN users ON posts.user_id = users.id 
                               WHERE posts.id = :post_id AND (posts.user_id = :user_id OR posts.is_public = 1)");

            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            return null;
        } catch (Exception $e) {
            error_log("Error fetching post: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all posts using secure connection
     */
    public function getAllPosts(): array
    {
        try {
            $conn = $this->db->getSecureConnection();

            $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts 
                               JOIN users ON posts.user_id = users.id 
                               ORDER BY posts.created_at DESC");
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching all posts: " . $e->getMessage());
            return [];
        }
    }
}

// Handle form submission
$result = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
            $result = ["success" => false, "message" => "Invalid request"];
        } else {
            $posts = new Posts();

            // Sanitize input
            $title = SecurityHelpers::sanitizeInput($_POST['title'] ?? '');
            $content = SecurityHelpers::sanitizeInput($_POST['content'] ?? '');

            // Basic validation
            if (empty($title) || empty($content)) {
                $result = ["success" => false, "message" => "Title and content are required"];
            } else {
                $result = $posts->createPost($_SESSION['user_id'], $title, $content);
            }
        }
    } catch (Exception $e) {
        error_log("Error in post creation: " . $e->getMessage());
        $result = ["success" => false, "message" => "An error occurred. Please try again."];
    }
}

// Get all posts
try {
    $posts = new Posts();
    $all_posts = $posts->getAllPosts();
} catch (Exception $e) {
    error_log("Error fetching posts: " . $e->getMessage());
    $all_posts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Posts</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Create New Post</h1>

<?php if ($result["message"]): ?>
    <div class="<?php echo $result["success"] ? "success" : "error"; ?>">
        <?php echo htmlspecialchars($result["message"]); ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="form-group">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required>
    </div>

    <div class="form-group">
        <label for="content">Content:</label>
        <textarea id="content" name="content" rows="5" required></textarea>
    </div>

    <!-- Added CSRF token for protection -->
    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">

    <button type="submit">Create Post</button>
</form>

<h2>All Posts</h2>

<?php if (empty($all_posts)): ?>
    <p>No posts yet.</p>
<?php else: ?>
    <div class="posts">
        <?php foreach ($all_posts as $post): ?>
            <div class="post">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <!-- Fixed XSS vulnerability with proper escaping -->
                <div class="content"><?php echo htmlspecialchars($post['content']); ?></div>
                <div class="meta">
                    Posted by <?php echo htmlspecialchars($post['username']); ?> on <?php echo htmlspecialchars($post['created_at']); ?>
                </div>
                <!-- Fixed IDOR by using the proper path -->
                <a href="post.php?id=<?php echo (int)$post['id']; ?>">View Post</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>