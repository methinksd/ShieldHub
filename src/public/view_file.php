<?php
// public/view_file.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate file ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid file ID";
    header("Location: dashboard.php");
    exit();
}

$file_id = (int)$_GET['id'];

class FileViewer {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getFile($file_id, $user_id) {
        $conn = $this->db->getConnection();

        try {
            // Secure: Only allow users to view their own files
            $stmt = $conn->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $file_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            } else {
                return null;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function deleteFile($file_id, $user_id) {
        $conn = $this->db->getConnection();

        try {
            // First get the file info to delete the physical file
            $stmt = $conn->prepare("SELECT filepath FROM files WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $file_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $file = $result->fetch_assoc();

                // Delete from database first
                $delete_stmt = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $file_id, $user_id);

                if ($delete_stmt->execute()) {
                    // Then delete the physical file
                    if (file_exists($file['filepath'])) {
                        unlink($file['filepath']);
                    }
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}

$viewer = new FileViewer();
$file = $viewer->getFile($file_id, $_SESSION['user_id']);

// Handle file deletion
if (isset($_POST['delete']) && $_POST['delete'] == 'yes') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request";
        header("Location: dashboard.php");
        exit();
    }

    if ($viewer->deleteFile($file_id, $_SESSION['user_id'])) {
        $_SESSION['success'] = "File deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting file";
    }
    header("Location: dashboard.php");
    exit();
}

// If file doesn't exist or doesn't belong to the user
if (!$file) {
    $_SESSION['error'] = "File not found";
    header("Location: dashboard.php");
    exit();
}

// Get file extension for display
$file_extension = pathinfo($file['filepath'], PATHINFO_EXTENSION);
$is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View File - ShieldHub</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>View File</h1>

<div class="file-details">
    <h2><?php echo htmlspecialchars($file['filename']); ?></h2>
    <p>Uploaded on: <?php echo htmlspecialchars($file['uploaded_at']); ?></p>

    <?php if ($is_image): ?>
        <div class="file-preview">
            <img src="<?php echo htmlspecialchars($file['filepath']); ?>" alt="<?php echo htmlspecialchars($file['filename']); ?>" style="max-width: 500px;">
        </div>
    <?php else: ?>
        <p>This file is not an image and cannot be previewed.</p>
    <?php endif; ?>

    <div class="file-actions">
        <a href="<?php echo htmlspecialchars($file['filepath']); ?>" download class="button">Download File</a>

        <form method="POST" action="" style="display: inline-block; margin-left: 10px;">
            <input type="hidden" name="delete" value="yes">
            <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">
            <button type="submit" class="button" onclick="return confirm('Are you sure you want to delete this file?');">Delete File</button>
        </form>
    </div>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>