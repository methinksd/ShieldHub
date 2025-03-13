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

    public function getUserPosts($user_id): array
    {
        try {
            // FIXED: Using PDO with prepared statements consistently
            $conn = $this->db->getConnection();

            $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user posts: " . $e->getMessage());
            return [];
        }
    }

    public function getUserFiles($user_id): array
    {
        try {
            // FIXED: Using PDO with prepared statements consistently
            $conn = $this->db->getConnection();

            $stmt = $conn->prepare("SELECT * FROM files WHERE user_id = :user_id ORDER BY uploaded_at DESC");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user files: " . $e->getMessage());
            return [];
        }
    }

    public function getUserInfo($user_id)
    {
        try {
            // FIXED: Using PDO with prepared statements consistently
            $conn = $this->db->getConnection();

            $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting user info: " . $e->getMessage());
            return null;
        }
    }
}

// Initialize dashboard
try {
    $dashboard = new Dashboard();
    $user_posts = $dashboard->getUserPosts($_SESSION['user_id']);
    $user_files = $dashboard->getUserFiles($_SESSION['user_id']);
    $user_info = $dashboard->getUserInfo($_SESSION['user_id']);

    // Handle errors
    $error_message = "";
    if (!$user_info) {
        $error_message = "Error retrieving user information. Please try again.";
    }
} catch (Exception $e) {
    $error_message = "An error occurred while loading your dashboard.";
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - ShieldHub</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

<?php if ($error_message): ?>
    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="dashboard-actions">
    <a href="posts.php" class="button">Create New Post</a>
    <a href="upload.php" class="button">Upload File</a>
    <a href="profile.php" class="button">Manage Profile</a>
    <a href="logout.php" class="button">Logout</a>
</div>

<div class="dashboard-content">
    <div class="dashboard-section">
        <h2>Your Posts</h2>
        <?php if (!empty($user_posts)): ?>
            <?php foreach ($user_posts as $post): ?>
                <div class="post">
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <div class="meta">Created on <?php echo htmlspecialchars($post['created_at']); ?></div>
                    <a href="post.php?id=<?php echo (int)$post['id']; ?>">View Post</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't created any posts yet.</p>
        <?php endif; ?>
    </div>

    <div class="dashboard-section">
        <h2>Your Files</h2>
        <?php if (!empty($user_files)): ?>
            <?php foreach ($user_files as $file): ?>
                <div class="file">
                    <p><?php echo htmlspecialchars($file['filename']); ?></p>
                    <div class="meta">Uploaded on <?php echo htmlspecialchars($file['uploaded_at']); ?></div>
                    <a href="view_file.php?id=<?php echo (int)$file['id']; ?>">View File</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't uploaded any files yet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-section">
    <h2>Account Information</h2>
    <?php if ($user_info): ?>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user_info['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
        <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user_info['created_at']); ?></p>
    <?php endif; ?>
</div>
</body>
</html>