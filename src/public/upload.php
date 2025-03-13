<?php
// public/upload.php
session_start();
require_once '../config/db.php';
require_once '../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

class FileUpload {
    private $db;
    private $upload_dir;
    private $max_file_size;
    private $allowed_types;

    public function __construct() {
        $this->db = new Database();
        $this->upload_dir = 'uploads/';
        $this->max_file_size = 5 * 1024 * 1024; // 5MB
        $this->allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        // Create directory if it doesn't exist
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    // REMOVED: Vulnerable file upload method
    /*
    // VULNERABILITY: Insecure file upload (minimal validation)
    public function uploadFile($file, $user_id): array
    {
        // VULNERABILITY: Minimal file type checking
        $target_file = $this->upload_dir . basename($file["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // VULNERABILITY: Insufficient file type validation
        if ($file_type != "jpg" && $file_type != "png" && $file_type != "jpeg" && $file_type != "gif") {
            return ["success" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed."];
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $conn = $this->db->getConnection();

            // VULNERABILITY: SQL Injection potential
            $filepath = $conn->real_escape_string($target_file);
            $filename = $conn->real_escape_string(basename($file["name"]));

            $query = "INSERT INTO files (user_id, filename, filepath) VALUES ('$user_id', '$filename', '$filepath')";

            if ($conn->query($query) === TRUE) {
                return ["success" => true, "message" => "File uploaded successfully."];
            } else {
                return ["success" => false, "message" => "Error recording file in database."];
            }
        } else {
            return ["success" => false, "message" => "Error uploading file."];
        }
    }
    */

    /**
     * Upload file with comprehensive security checks
     */
    public function uploadFile($file, $user_id): array
    {
        try {
            // 1. Validate file existence
            if (!isset($file) || !is_array($file) || empty($file['tmp_name'])) {
                return ["success" => false, "message" => "No file was uploaded."];
            }

            // 2. Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
                    UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
                    UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
                    UPLOAD_ERR_NO_FILE => "No file was uploaded.",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
                ];
                return ["success" => false, "message" => $errors[$file['error']] ?? "Unknown upload error."];
            }

            // 3. Check file size
            if ($file['size'] > $this->max_file_size) {
                return ["success" => false, "message" => "File is too large. Maximum size is " . ($this->max_file_size / 1024 / 1024) . "MB."];
            }

            // 4. Validate MIME type using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $this->allowed_types)) {
                return ["success" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed."];
            }

            // 5. Validate file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($extension, $allowed_extensions)) {
                return ["success" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed."];
            }

            // 6. Additional security: verify it's actually an image
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return ["success" => false, "message" => "Invalid image file."];
            }

            // 7. Generate secure filename
            $secure_filename = SecurityHelpers::generateSecureFilename($file['name']);
            $target_file = $this->upload_dir . $secure_filename;

            // 8. Move the file
            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                return ["success" => false, "message" => "Error uploading file. Please try again."];
            }

            // 9. Record in database using PDO
            $conn = $this->db->getSecureConnection();

            $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath) VALUES (:user_id, :filename, :filepath)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':filename', $file['name']);
            $stmt->bindParam(':filepath', $target_file);

            if ($stmt->execute()) {
                return ["success" => true, "message" => "File uploaded successfully."];
            } else {
                // If database insertion fails, remove the uploaded file
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
                return ["success" => false, "message" => "Error recording file in database."];
            }
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred during file upload."];
        }
    }
}

// Handle form submission
$result = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !SecurityHelpers::validateCSRFToken($_POST['csrf_token'])) {
            $result = ["success" => false, "message" => "Invalid request"];
        } else if (isset($_FILES["file"])) {
            $uploader = new FileUpload();
            $result = $uploader->uploadFile($_FILES["file"], $_SESSION['user_id']);
        }
    } catch (Exception $e) {
        error_log("Upload page error: " . $e->getMessage());
        $result = ["success" => false, "message" => "An error occurred. Please try again."];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>File Upload</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h1>Upload File</h1>

<?php if ($result["message"]): ?>
    <div class="<?php echo $result["success"] ? "success" : "error"; ?>">
        <?php echo htmlspecialchars($result["message"]); ?>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="form-group">
        <label for="file">Select File:</label>
        <input type="file" id="file" name="file" required>
        <small>Max file size: 5MB. Allowed types: JPG, JPEG, PNG, GIF</small>
    </div>

    <!-- Added CSRF token for protection -->
    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelpers::generateCSRFToken(); ?>">

    <button type="submit">Upload</button>
</form>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>