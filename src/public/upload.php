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

    public function __construct() {
        $this->db = new Database();
        $this->upload_dir = 'uploads/';

        // Create directory if it doesn't exist
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

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

    // Secure file upload method (for comparison)
    public function secureUploadFile($file, $user_id): array
    {
        // Generate a unique filename
        $filename = basename($file["name"]);
        $fileinfo = pathinfo($filename);
        $new_filename = uniqid() . '_' . $fileinfo['filename'] . '.' . $fileinfo['extension'];
        $target_file = $this->upload_dir . $new_filename;

        // Thorough file validation
        $helper = new SecurityHelpers();
        if (!$helper->validateFileUpload($file)) {
            return ["success" => false, "message" => "Invalid file type or size."];
        }

        // Process the file upload
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            try {
                $conn = $this->db->getSecureConnection();

                $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath) VALUES (:user_id, :filename, :filepath)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':filename', $filename);
                $stmt->bindParam(':filepath', $target_file);

                if ($stmt->execute()) {
                    return ["success" => true, "message" => "File uploaded successfully."];
                } else {
                    return ["success" => false, "message" => "Error recording file in database."];
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                return ["success" => false, "message" => "Database error."];
            }
        } else {
            return ["success" => false, "message" => "Error uploading file."];
        }
    }
}

// Handle form submission
$result = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $uploader = new FileUpload();
    $result = $uploader->uploadFile($_FILES["file"], $_SESSION['user_id']);
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
        <?php echo $result["message"]; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="form-group">
        <label for="file">Select File:</label>
        <input type="file" id="file" name="file" required>
    </div>

    <!-- VULNERABILITY: No CSRF token -->
    <button type="submit">Upload</button>
</form>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>