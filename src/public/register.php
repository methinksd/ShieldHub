<?php
// public/register.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';
require_once '../config/connection.php';
// VULNERABILITY: Potential XSS in user input handling
class UserRegistration {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function register($username, $email, $password_hash): bool
    {
        $conn = $this->db->getConnection(); // Intentionally using vulnerable connection

        // VULNERABILITY: No proper input validation
        $username = $conn->real_escape_string($username);
        $email = $conn->real_escape_string($email);

        // VULNERABILITY: Weak password hashing
        $password_hash = password_hash($password_hash, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password_hash')";

        if ($conn->query($query) === TRUE) {
            echo "New record created successfully";
            return true;
        } else {
            echo "Error: " . $query . "<br>" . $conn->error;
            return false;
        }
    }

    // Secure registration method (for comparison)
    public function secureRegister($username, $email, $password, $password_hash): bool
    {
        $conn = $this->db->getSecureConnection();

        try {
            // Proper input validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $password_hash = password_hash($password_hash, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $password_hash);

            return $stmt->execute();
        } catch (Exception $e) {
            // Proper error handling
            error_log($e->getMessage());
            return false;
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $registration = new UserRegistration();

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password_hash = $_POST['password'] ?? '';

    if ($registration->register($username, $email, $password_hash)) {
        $_SESSION['message'] = "Registration successful!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Registration page</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<form method="POST" action="register.php">
    <label>
        Username:
        <input type="text" name="username" required>
    </label>
    <label>
        Email:
        <input type="email" name="email" required>
    </label>
    <label>
        Password:
        <input type="password" name="password" required>
    </label>
    <button type="submit">Register</button>
</form>
<div class="button">Already have an account?<a href="login.php">Login</a> </div>
</body>
</html>