<?php
// public/profile.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

class UserProfile {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getUserInfo($user_id) {
        $conn = $this->db->getConnection();

        try {
            $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
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

    public function updateEmail($user_id, $email) {
        $conn = $this->db->getConnection();

        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["success" => false, "message" => "Invalid email format"];
            }

            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);

            if ($stmt->execute()) {
                return ["success" => true, "message" => "Email updated successfully"];
            } else {
                return ["success" => false, "message" => "Error updating email"];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ["success" => false, "message" => "Database error"];
        }
    }

    public function updatePassword($user_id, $current_password, $new_password) {
        $conn = $this->db->getConnection();

        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (!password_verify($current_password, $user['password'])) {
                    return ["success" => false, "message" => "Current password is incorrect"];
                }

                // Password complexity check
                if (strlen($new_password) < 8) {
                    return ["success" => false, "message" => "Password must be at least 8 characters"];
                }

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);

                if ($update_stmt->execute()) {
                    return ["success" => true, "message" => "Password updated successfully"];
                } else {
                    return ["success" => false, "message" => "Error updating password"];
                }
            } else {
                return ["success" => false, "message" => "User not found"];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ["success" => false, "message" => "Database error"];
        }
    }
}

$profile = new UserProfile();
$user_info = $profile->getUserInfo($_SESSION['user_id']);

$email_result = ["success" => false, "message" => ""];
$password_result = ["success" => false, "message" => ""];

// Handle email update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_email'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
        $email_result = ["success" => false, "message" => "Invalid request"];
    } else {
        $email = $_POST['email'] ?? '';
        $email_result = $profile->updateEmail($_SESSION['user_id'], $email);

        // Refresh user info if update was successful
        if ($email_result["success"]) {
            $user_info = $profile->getUserInfo($_SESSION['user_id']);
        }
    }
}

// Handle password update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
        $password_result = ["success" => false, "message" => "Invalid request"];
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $password_result = ["success" => false, "message" => "New passwords do not match"];
        } else {
            $password_result = $profile->updatePassword($_SESSION['user_id'], $current_password, $new_password);
        }
    }
}

// Handle any errors
if (!$user_info) {
    $_SESSION['error'] = "Error retrieving user information";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile - ShieldHub</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Profile Management</h1>

<div class="dashboard-section">
    <h2>Account Information</h2>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_info['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
    <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user_info['created_at']); ?></p>
</div>

<div class="dashboard-section">
    <h2>Update Email</h2>

    <?php if ($email_result["message"]): ?>
        <div class="<?php echo $email_result["success"] ? "success" : "error"; ?>">
            <?php echo htmlspecialchars($email_result["message"]); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">New Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
        </div>

        <input type="hidden" name="update_email" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">
        <button type="submit">Update Email</button>
    </form>
</div>

<div class="dashboard-section">
    <h2>Change Password</h2>

    <?php if ($password_result["message"]): ?>
        <div class="<?php echo $password_result["success"] ? "success" : "error"; ?>">
            <?php echo htmlspecialchars($password_result["message"]); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <small>Password must be at least 8 characters</small>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <input type="hidden" name="update_password" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">
        <button type="submit">Change Password</button>
    </form>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>