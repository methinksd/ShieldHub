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

        // Using prepared statement to fix SQL injection vulnerability
        $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getUserFiles($user_id)
    {
        $conn = $this->db->getConnection();

        // Using prepared statement
        $stmt = $conn->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY uploaded_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getUserInfo($user_id)
    {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }
}

// Initialize dashboard
$dashboard = new Dashboard();
$user_posts = $dashboard->getUserPosts($_SESSION['user_id']);
$user_files = $dashboard->getUserFiles($_SESSION['user_id']);
$user_info = $dashboard->getUserInfo($_SESSION['user_id']);

// Handle errors
$error_message = "";
if (!$user_info) {
    $error_message = "Error retrieving user information. Please try again.";
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
    <div class="error"><?php echo $error_message; ?></div>
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
        <?php if ($user_posts->num_rows > 0): ?>
            <?php while ($post = $user_posts->fetch_assoc()): ?>
                <div class="post">
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <div class="meta">Created on <?php echo htmlspecialchars($post['created_at']); ?></div>
                    <a href="post.php?id=<?php echo $post['id']; ?>">View Post</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't created any posts yet.</p>
        <?php endif; ?>
    </div>

    <div class="dashboard-section">
        <h2>Your Files</h2>
        <?php if ($user_files->num_rows > 0): ?>
            <?php while ($file = $user_files->fetch_assoc()): ?>
                <div class="file">
                    <p><?php echo htmlspecialchars($file['filename']); ?></p>
                    <div class="meta">Uploaded on <?php echo htmlspecialchars($file['uploaded_at']); ?></div>
                    <a href="view_file.php?id=<?php echo $file['id']; ?>">View File</a>
                </div>
            <?php endwhile; ?>
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