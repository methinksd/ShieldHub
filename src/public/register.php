<?php
// public/register.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';
require_once '../config/connection.php';

class UserRegistration {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * @throws Exception
     */
    public function register($username, $email, $password): bool
    {
        try {
            $conn = $this->db->getSecureConnection();

            // Proper input validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Validate username length and format
            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
                throw new Exception("Username must be 3-30 characters and contain only letters, numbers, underscores, and hyphens");
            }

            // Prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $password_hash);

            return $stmt->execute();
        } catch (Exception $e) {
            // Proper error handling
            error_log("Registration error: " . $e->getMessage());
            throw $e; // Rethrow to be caught by the handler
        }
    }
}

// Initialize variables for error messages
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $registration = new UserRegistration();

        // Sanitize inputs
        $username = SecurityHelpers::sanitizeInput($_POST['username'] ?? '');
        $email = SecurityHelpers::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $error_message = "All fields are required";
        } elseif ($password !== $confirmPassword) {
            $error_message = "Passwords do not match";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters";
        } else {
            if ($registration->register($username, $email, $password)) {
                $_SESSION['message'] = "Registration successful!";
                header("Location: login.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $error_message = "Database connection error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Register</h1>

<?php if (!empty($error_message)): ?>
    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <small>3-30 characters, letters, numbers, and _-</small>
    </div>

    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <small>At least 8 characters</small>
    </div>

    <div class="form-group">
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login</a></p>
<p><a href="index.php">Back to Home</a></p>
</body>
</html>