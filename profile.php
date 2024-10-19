<?php
// Start the session to access user data
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Retrieve user data from the session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Database connection (replace with your connection details)
$conn = new mysqli('localhost', 'root', '', 'blog_system');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sql = "SELECT profile_picture FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profile_picture = $user['profile_picture'] ?? ''; // Default to an empty string if not set


// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_post'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = $conn->real_escape_string($_POST['category']);
    $status = 'published';

    // Handle image upload
    $image = $_FILES['image'];
    $imagePath = '';

    if ($image['error'] == 0) {
        $targetDirectory = "uploads/"; // Ensure this directory exists and is writable
        $imagePath = $targetDirectory . basename($image['name']);
        move_uploaded_file($image['tmp_name'], $imagePath);
    }

    $sql = "INSERT INTO posts (user_id, title, content, category, image, status) VALUES ('$user_id', '$title', '$content', '$category', '$imagePath', '$status')";
    if ($conn->query($sql)) {
        echo "<script>alert('Post added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding post: " . $conn->error . "');</script>";
    }
}

// Handle post deletion
if (isset($_GET['delete_post'])) {
    $post_id = $conn->real_escape_string($_GET['delete_post']);
    $sql = "DELETE FROM posts WHERE id = '$post_id' AND user_id = '$user_id'";
    
    if ($conn->query($sql)) {
        echo "<script>alert('Post deleted successfully!'); window.location.href = 'profile.php';</script>";
    } else {
        echo "<script>alert('Error deleting post: " . $conn->error . "');</script>";
    }
}



// Handle post editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_post'])) {
    $post_id = $conn->real_escape_string($_POST['post_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = $conn->real_escape_string($_POST['category']);
    $imagePath = '';

    // Handle image upload
    if ($_FILES['image']['error'] == 0) {
        $targetDirectory = "uploads/"; // Ensure this directory exists and is writable
        $imagePath = $targetDirectory . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);

        // Update post with new image
        $sql = "UPDATE posts SET title = '$title', content = '$content', category = '$category', image = '$imagePath' WHERE id = '$post_id' AND user_id = '$user_id'";
    } else {
        // Only update text and category if no new image is uploaded
        $sql = "UPDATE posts SET title = '$title', content = '$content', category = '$category' WHERE id = '$post_id' AND user_id = '$user_id'";
    }

    if ($conn->query($sql)) {
        echo "<script>alert('Post updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating post: " . $conn->error . "');</script>";
    }
}



// Fetch posts by the user along with username
$sql = "
    SELECT posts.*, users.username 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.user_id = '$user_id' 
    ORDER BY posts.created_at DESC
";
$result = $conn->query($sql);
$posts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch comments for each post
$comments = [];
foreach ($posts as $post) {
    $post_id = $post['id'];
    $comment_result = $conn->query("
        SELECT comments.*, users.username 
        FROM comments 
        JOIN users ON comments.user_id = users.id 
        WHERE comments.post_id = '$post_id'
    ");
    $comments[$post_id] = $comment_result ? $comment_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $imagePath = $profile_picture; // Default to current picture if not uploading a new one

    // Handle image upload
    if ($_FILES['profile_picture']['error'] == 0) {
        $targetDirectory = "uploads/"; // Ensure this directory exists and is writable
        $imagePath = $targetDirectory . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $imagePath);
    }

    $sql = "UPDATE users SET username = '$username', profile_picture = '$imagePath' WHERE id = '$user_id'";

    if ($conn->query($sql)) {
        $_SESSION['username'] = $username; // Update the session variable
        echo "<script>alert('Profile updated successfully!'); window.location.href = 'profile.php';</script>";
    } else {
        echo "<script>alert('Error updating profile: " . $conn->error . "');</script>";
    }
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($username); ?>'s Profile</title>
        <link rel="stylesheet" href="style/profile.css">
        <script>
            function showAddPostForm() {
                document.getElementById('addPostForm').style.display = 'block';
            }

            function hideAddPostForm() {
                document.getElementById('addPostForm').style.display = 'none';
            }

            function showEditPostForm(postId) {
                document.getElementById('editPostForm-' + postId).style.display = 'block';
            }

            function hideEditPostForm(postId) {
                document.getElementById('editPostForm-' + postId).style.display = 'none';
            }

            function showEditProfileForm() {
                document.getElementById('editProfileForm').style.display = 'block';
            }

            function hideEditProfileForm() {
                document.getElementById('editProfileForm').style.display = 'none';
            }
        </script>
    </head>
    <body>
        <header class="header">
            <div class="container">
                <div class="logo">
                    <h2>MyBlog</h2>
                </div>
                <div class="header-right">
                    <button class="home-button" onclick="window.location.href='index.php'">Home</button>
                    <button class="add-post-button" onclick="showAddPostForm()">Add Post</button>
                    <div class="profile-dropdown">
                        <span class="username"><?php echo htmlspecialchars($username); ?> &#9662;</span>
                        <div class="dropdown-content">
                            <a href="javascript:void(0);" onclick="showEditProfileForm()">Edit Profile</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div id="addPostForm" class="modal">
            <div class="modal-content">
                <span class="close" onclick="hideAddPostForm()">&times;</span>
                <form method="post" action="" enctype="multipart/form-data">
                    <h2>Add a New Post</h2>
                    <input type="text" name="title" placeholder="Post Title" required>
                    <textarea name="content" rows="5" placeholder="Write your post content here..." required></textarea>
                    <label for="category">Category:</label>
                    <select name="category" required>
                        <option value="Sport">Sport</option>
                        <option value="Education">Education</option>
                        <option value="Science">Science</option>
                        <option value="Technology">Technology</option>
                        <option value="Medicals">Medicals</option>
                    </select>
                    <label for="image">Image:</label>
                    <input type="file" name="image" accept="image/*">
                    <input type="hidden" name="add_post" value="1">
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div id="editProfileForm" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="hideEditProfileForm()">&times;</span>
                <form method="post" action="" enctype="multipart/form-data">
                    <h2>Edit Profile</h2>
                    <label for="username">Username:</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <label for="profile_picture">Profile Picture:</label>
                    <input type="file" name="profile_picture" accept="image/*">
                    <button type="submit" name="update_profile">Update</button>
                </form>
            </div>
        </div>

        <div class="profile-container">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

            <!-- Display the profile picture -->
            <div class="profile-picture">
                <?php if (!empty($profile_picture)): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%;">
                <?php else: ?>
                    <img src="path/to/default/profile_picture.jpg" alt="Default Profile Picture" style="width: 100px; height: 100px; border-radius: 50%;">
                <?php endif; ?>
            </div>

            <div class="posts">
                <h2>Your Posts</h2>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <p class="post-meta">
                            <strong><?php echo htmlspecialchars($post['username']); ?></strong> - <?php echo htmlspecialchars($post['created_at']); ?>
                        </p>
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p>
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" class="post-image" alt="Post Image" style="max-width: 100%; height: auto;">
                        <?php endif; ?>
                        <p class="post-category">Category: <?php echo htmlspecialchars($post['category']); ?></p>
                        <a href="?delete_post=<?php echo $post['id']; ?>" class="delete-button">Delete</a>
                        <button onclick="showEditPostForm(<?php echo $post['id']; ?>)" class="edit-button">Edit</button>

                        <div id="editPostForm-<?php echo $post['id']; ?>" class="modal" style="display: none;">
                            <div class="modal-content">
                                <span class="close" onclick="hideEditPostForm(<?php echo $post['id']; ?>)">&times;</span>
                                <form method="post" action="" enctype="multipart/form-data">
                                    <h2>Edit Post</h2>
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                                    <textarea name="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                    <label for="category">Category:</label>
                                    <select name="category" required>
                                        <option value="Sport" <?php echo ($post['category'] == 'Sport') ? 'selected' : ''; ?>>Sport</option>
                                        <option value="Education" <?php echo ($post['category'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                                        <option value="Science" <?php echo ($post['category'] == 'Science') ? 'selected' : ''; ?>>Science</option>
                                        <option value="Technology" <?php echo ($post['category'] == 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                        <option value="Medicals" <?php echo ($post['category'] == 'Medicals') ? 'selected' : ''; ?>>Medicals</option>
                                    </select>
                                    <label for="image">Image:</label>
                                    <input type="file" name="image" accept="image/*">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="edit_post" value="1">
                                    <button type="submit">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer Section -->
        <footer class="footer">
            <div class="container">
                <p>Connect with us on social media:</p>
                <div class="social-links">
                    <a href="https://wa.me/+2348140272765" target="_blank">WhatsApp</a>
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
