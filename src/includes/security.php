<?php
// includes/security.php
class SecurityHelpers {
    // CSRF Token Generation
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // Add token creation time for expiration check
            $_SESSION['csrf_token_time'] = time();
        } else {
            // Regenerate token if it's older than 1 hour
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['csrf_token_time'] = time();
            }
        }
        return $_SESSION['csrf_token'];
    }

    // FIXED: Improved CSRF Token Validation
    // REMOVED: Easy to bypass CSRF protection
    /*
    public static function validateCSRFToken($token): bool
    {
        // VULNERABILITY: Easy to bypass CSRF protection
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    */

    public static function validateCSRFToken($token): bool
    {
        try {
            // Check if token exists
            if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
                return false;
            }

            // Check if token is too old (1 hour expiration)
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                return false;
            }

            // Use hash_equals for timing attack protection
            return hash_equals($_SESSION['csrf_token'], $token);
        } catch (Exception $e) {
            error_log("CSRF validation error: " . $e->getMessage());
            return false;
        }
    }

    // FIXED: Improved Input Sanitization
    public static function sanitizeInput($input): string
    {
        try {
            if (is_array($input)) {
                $sanitized = [];
                foreach ($input as $key => $value) {
                    $sanitized[self::sanitizeInput($key)] = self::sanitizeInput($value);
                }
                return $sanitized;
            }

            // More comprehensive sanitization
            $input = trim($input);
            $input = stripslashes($input);
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        } catch (Exception $e) {
            error_log("Input sanitization error: " . $e->getMessage());
            return "";
        }
    }

    // FIXED: Improved File Upload Validation
    public static function validateFileUpload($file): bool
    {
        try {
            if (!isset($file) || !is_array($file) || empty($file['tmp_name'])) {
                return false;
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB

            // Check file size
            if ($file['size'] > $maxFileSize) {
                return false;
            }

            // Check MIME type using finfo (more secure than just checking extension)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $allowedTypes)) {
                return false;
            }

            // Validate file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($extension, $allowedExtensions)) {
                return false;
            }

            // Additional security: check that the file is actually an image
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("File validation error: " . $e->getMessage());
            return false;
        }
    }

    // New method: Generate secure filename
    public static function generateSecureFilename($originalName): string
    {
        try {
            $fileInfo = pathinfo($originalName);
            $extension = isset($fileInfo['extension']) ? '.' . strtolower($fileInfo['extension']) : '';
            return bin2hex(random_bytes(16)) . $extension;
        } catch (Exception $e) {
            error_log("Error generating secure filename: " . $e->getMessage());
            // Fallback to timestamp-based name
            return time() . rand(1000, 9999) . '.tmp';
        }
    }
}