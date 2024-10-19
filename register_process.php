<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "blog_system";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data and sanitize it
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = trim($_POST['password']);
    $role = trim(mysqli_real_escape_string($conn, $_POST['role']));

    // Validate the inputs
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit;
    }

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Specify the allowed file extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');

        // Validate file extension
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Create a unique file name to avoid conflicts
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './uploads/profile_pictures/';
            $dest_path = $uploadFileDir . $newFileName;

            // Move the file to the upload directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
            } else {
                echo "<script>alert('There was an error uploading the file.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Upload failed. Allowed file types: " . implode(', ', $allowedfileExtensions) . ".'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('No file uploaded or there was an upload error.'); window.history.back();</script>";
        exit;
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare an SQL statement to insert the user data along with the profile picture path
    $sql = "INSERT INTO users (username, email, password, role, profile_picture) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $dest_path);

    // Execute the statement
    if ($stmt->execute()) {
        // Show an alert and redirect to the login page
        echo "<script>
                alert('Registration successful! Please log in.');
                window.location.href = 'login.php';
              </script>";
    } else {
        echo "<script>
                alert('Error: " . $stmt->error . "');
                window.history.back();
              </script>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
