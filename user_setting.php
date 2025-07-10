<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: user_signin.php");
    exit();
}

// Include the database connection file
include 'db_conn.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user details from the `users` and `accounts` tables, including the image
$query = "SELECT
            users.user_full_name AS full_name,
            users.user_email AS email,
            users.user_image AS profile_image,
            accounts.username
          FROM users
          INNER JOIN accounts ON users.account_id = accounts.account_id
          WHERE users.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Close the statement and connection (keep it open for potential future ops, or close if done)
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="user_setting.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="NokorRealm">
            </div>
            <ul class="menu">
                <li><a href="user_home.php"><i class="material-icons">arrow_back</i><span>Back</span></a></li>
                <li><a href="user_booking.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="user_favorite.php"><i class="material-icons">favorite</i><span>Favorite</span></a></li>
                <li><a href="user_history.php"><i class="material-icons">history</i><span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
                <li><a href="user_setting.php" id="setting-active"><i class="material-icons">settings</i><span>Setting</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header-content">
                <div class="hamburger-menu">
                    <span class="material-symbols-outlined">menu</span>
                </div>
                <div class="nav-bar">
                    <h2>Hello, <?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p>Have a nice day</p>
                </div>
                <div class="header-actions">
                    <div class="ring-icon">
                        <span class="material-symbols-outlined">notifications</span>
                    </div>
                    <div class="profile">
                        <img
                            src="<?php echo htmlspecialchars($user['profile_image'] ?? 'image/default_profile.png'); ?>"
                            alt="Profile Picture"
                        >
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p>User</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-detail">
                <div class="user-information">
                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'image/default_profile.png'); ?>"
                        alt="User Image">
                    <h3>Information</h3>
                    <div class="info-item">
                        <h4>Username:</h4>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <h4>Name:</h4>
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <h4>Email:</h4>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                </div>

                <div class="user-setting">
                    <form action="user_info_update.php" method="POST" class="settings-form">
                        <div class="form-section">
                            <h3>User Details</h3>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                            <button type="submit" class="submit-btn">Edit Details</button>
                        </div>
                    </form>

                    <form action="user_pass_change.php" method="POST" class="settings-form password-form">
                        <div class="form-section">
                            <h3>Password</h3>
                            <div class="form-group">
                                <label for="old_password">Enter old password</label>
                                <input type="password" id="old_password" name="old_password" placeholder="*********">
                            </div>
                            <div class="form-group">
                                <label for="new_password">Enter new password</label>
                                <input type="password" id="new_password" name="new_password" placeholder="*********">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm new password</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="*********">
                            </div>
                            <button type="submit" class="submit-btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>

        </div> </div> <script>
        // JavaScript for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.side-bar');
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            const pageContainer = document.querySelector('.page-container');

            if (hamburgerMenu) {
                hamburgerMenu.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    if (sidebar.classList.contains('open')) {
                        const overlay = document.createElement('div');
                        overlay.classList.add('sidebar-overlay');
                        pageContainer.appendChild(overlay);
                        overlay.addEventListener('click', function() {
                            sidebar.classList.remove('open');
                            overlay.remove();
                        });
                    } else {
                        document.querySelector('.sidebar-overlay')?.remove();
                    }
                });
            }

            // Close sidebar if screen resizes to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) { // Adjust breakpoint to match your CSS media query
                    sidebar.classList.remove('open');
                    document.querySelector('.sidebar-overlay')?.remove();
                }
            });
        });
    </script>
</body>

</html>