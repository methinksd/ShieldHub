<?php
// public/login.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

class UserLogin {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Fixed the SQL Injection vulnerability
    public function login($username, $password): bool
    {
        $conn = $this->db->getConnection();

        try {
            // Fixed: Using prepared statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                return false;
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize variables for error messages
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid request";
    } else {
        $login = new UserLogin();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($username) || empty($password)) {
            $error_message = "Username and password are required";
        } else {
            if ($login->login($username, $password)) {
                // Redirect to dashboard on successful login
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - ShieldHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/validation.js"></script>
</head>
<body>
<h1>Login</h1>

<?php if (!empty($error_message)): ?>
    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>

    <!-- Added CSRF token for protection -->
    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">

    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>
<p><a href="index.php">Back to Home</a></p>
</body>
</html>