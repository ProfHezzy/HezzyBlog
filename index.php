<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'blog_system');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get the user ID from the session if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle like functionality
if (isset($_GET['like_post']) && $user_id) {
    $post_id = $conn->real_escape_string($_GET['like_post']);

    // Check if user already liked the post
    $checkLike = $conn->query("SELECT * FROM likes WHERE post_id = '$post_id' AND user_id = '$user_id'");
    if ($checkLike->num_rows == 0) {
        $conn->query("INSERT INTO likes (post_id, user_id) VALUES ('$post_id', '$user_id')");
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment']) && $user_id) {
    $post_id = $conn->real_escape_string($_POST['post_id']);
    $comment = $conn->real_escape_string($_POST['comment']);

    // Insert the comment into the database
    $conn->query("INSERT INTO comments (post_id, user_id, comment, created_at) VALUES ('$post_id', '$user_id', '$comment', NOW())");
}

// Fetch all posts made by all moderators
$sql = "SELECT posts.*, users.username, 
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.status = 'published' 
        ORDER BY posts.created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die('Error retrieving posts: ' . $conn->error);
}

$posts = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HezzyBlog</title>
    <link rel="stylesheet" href="style/home.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>HezzyBlog</h1>
            </div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="services.php">Services</a>
            </nav>
            <div class="auth-buttons">
                <?php if ($user_id): ?>
                    <a href="profile.php" class="button">Profile</a>
                    <a href="logout.php" class="button">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="button">Login</a>
                    <a href="register.php" class="button">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="content">
        <h2>All Posts</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <p class="post-meta">
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong> - <?php echo htmlspecialchars($post['created_at']); ?>
                </p>
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <div class="post-actions">
                    <a href="?like_post=<?php echo $post['id']; ?>" class="like-button">❤️ <?php echo $post['like_count']; ?> Likes</a>
                </div>
                
                <!-- Comment Form -->
                <?php if ($user_id): ?>
                    <form method="post" action="">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                        <button type="submit" name="add_comment">Comment</button>
                    </form>
                <?php else: ?>
                    <p><a href="login.php">Log in</a> to comment.</p>
                <?php endif; ?>
                
                <!-- Display Comments -->
                <div class="comments">
                    <h4>Comments</h4>
                    <?php
                    $post_id = $post['id'];
                    $comment_sql = "SELECT comments.*, users.username 
                                    FROM comments 
                                    JOIN users ON comments.user_id = users.id 
                                    WHERE comments.post_id = '$post_id' 
                                    ORDER BY comments.created_at DESC";
                    $comment_result = $conn->query($comment_sql);

                    if ($comment_result && $comment_result->num_rows > 0): 
                        while ($comment = $comment_result->fetch_assoc()): ?>
                            <div class="comment">
                                <p><strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo htmlspecialchars($comment['comment']); ?></p>
                                <p class="comment-meta"><?php echo htmlspecialchars($comment['created_at']); ?></p>
                            </div>
                        <?php endwhile; 
                    else: ?>
                        <p>No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <p>Connect with us on social media:</p>
            <div class="social-links">
                <a href="https://wa.me/your-number" target="_blank">WhatsApp</a>
                <a href="https://facebook.com/your-page" target="_blank">Facebook</a>
                <a href="https://linkedin.com/in/your-profile" target="_blank">LinkedIn</a>
                <a href="https://twitter.com/your-handle" target="_blank">Twitter</a>
                <a href="https://instagram.com/your-profile" target="_blank">Instagram</a>
                <a href="https://github.com/your-username" target="_blank">GitHub</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> HezzyBlog. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
