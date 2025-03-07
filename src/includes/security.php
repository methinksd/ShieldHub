<?php
// includes/security.php
class SecurityHelpers {
    // CSRF Token Generation
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // CSRF Token Validation
    public static function validateCSRFToken($token): bool
    {
        // VULNERABILITY: Easy to bypass CSRF protection
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Input Sanitization
    public static function sanitizeInput($input): string
    {
        // Basic input sanitization
        $input = trim($input);
        $input = stripslashes($input);
        return htmlspecialchars($input);
    }

    // File Upload Validation
    public static function validateFileUpload($file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxFileSize) {
            return false;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        return true;
    }
}
