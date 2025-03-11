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

    // VULNERABILITY: Potential SQL Injection and XSS
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

    // Secure post creation (for comparison)
    public function secureCreatePost($user_id, $title, $content): array
    {
        try {
            $conn = $this->db->getSecureConnection();

            // Proper sanitization
            $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (:user_id, :title, :content)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);

            if ($stmt->execute()) {
                return ["success" => true, "message" => "Post created successfully."];
            } else {
                return ["success" => false, "message" => "Error creating post."];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ["success" => false, "message" => "Database error."];
        }
    }
}

// Handle form submission
$result = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $posts = new Posts();
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $result = $posts->createPost($_SESSION['user_id'], $title, $content);
}

// Get all posts
$posts = new Posts();
$all_posts = $posts->getAllPosts();
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
        <?php echo $result["message"]; ?>
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

    <!-- VULNERABILITY: No CSRF token -->
    <button type="submit">Create Post</button>
</form>

<h2>All Posts</h2>

<?php if (empty($all_posts)): ?>
    <p>No posts yet.</p>
<?php else: ?>
    <div class="posts">
        <?php foreach ($all_posts as $post): ?>
            <div class="post">
                <h3><?php echo $post['title']; ?></h3>
                <!-- VULNERABILITY: XSS possible here -->
                <div class="content"><?php echo $post['content']; ?></div>
                <div class="meta">
                    Posted by <?php echo $post['username']; ?> on <?php echo $post['created_at']; ?>
                </div>
                <!-- VULNERABILITY: IDOR possible here -->
                <a href="public/post.php?id=<?php echo $post['id']; ?>">View Post</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>