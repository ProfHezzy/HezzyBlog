<?php
session_start();
include('db_connection.php');

// Check if the user is logged in and if the role is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch users from the database
$users_query = "SELECT * FROM users"; // Fetch all users
$users_result = $conn->query($users_query);

// Fetch posts from the database, including the username of the poster
$posts_query = "
    SELECT p.id, p.title, p.content, p.image, u.username AS posted_by 
    FROM posts p 
    JOIN users u ON p.user_id = u.id"; // Assuming user_id is the foreign key in posts table
$posts_result = $conn->query($posts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/admin.css">
    <title>Admin Dashboard</title>
</head>
<body>

    <div class="header">
        <div class="container">
            <div class="logo">
                <h2>Admin Dashboard</h2>
            </div>
            <div class="header-right">
                <div class="header-buttons">
                    <a href="index.php" class="home-button">Home</a> <!-- Home Button -->
                    <a href="profile.php" class="profile-button">Profile</a> <!-- Profile Button -->
                    <div class="profile-dropdown">
                        <span class="username"><?php echo $_SESSION['username']; ?></span>
                        <div class="dropdown-content">
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <div class="user-management">
            <h1>User Management</h1>
            <table>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php while ($user = $users_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="post-management">
            <h2>Published Posts</h2>
            <table>
                <tr>
                    <th>Post ID</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Image</th> <!-- New Image Column -->
                    <th>Posted By</th> <!-- New Posted By Column -->
                    <th>Action</th>
                </tr>
                <?php while ($post = $posts_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td><?php echo $post['title']; ?></td>
                        <td><?php echo substr($post['content'], 0, 50) . '...'; ?></td>
                        <td>
                            <?php if ($post['image']): ?>
                                <img src="<?php echo $post['image']; ?>" alt="Post Image" style="width: 100px; height: auto;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo $post['posted_by']; ?></td> <!-- Display Posted By -->
                        <td>
                            <a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
                            <a href="delete_post.php?id=<?php echo $post['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
