// delete_post.php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'blog_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if (isset($_GET['post_id'])) {
    $post_id = $conn->real_escape_string($_GET['post_id']);
    $conn->query("DELETE FROM posts WHERE id = '$post_id'");
}

header('Location: admin.php');
exit();
