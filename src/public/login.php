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

    // VULNERABILITY: Potential SQL Injection
    public function login($username, $password): bool
    {
        $conn = $this->db->getConnection();

        // VULNERABILITY: No prepared statement, SQL injection possible
        $query = "SELECT , username, password FROM users WHERE username = '$username'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        return false;
    }

    // Secure login method (for comparison)
    public function secureLogin($username, $password): bool
    {
        $conn = $this->db->getSecureConnection();

        try {
            // Prepared statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = new UserLogin();

    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';

    if ($login->login($username, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Login</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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

    <!-- VULNERABILITY: No CSRF token -->
    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>