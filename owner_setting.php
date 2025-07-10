<?php
// Start the session
session_start();

// Check if the owner is logged in
if (!isset($_SESSION['owner_id'])) {
    // Redirect to login page if not logged in
    header("Location: owner_signin.php");
    exit();
}

// Include the database connection file
include 'db_conn.php';

// Get the owner ID from the session
$owner_id = $_SESSION['owner_id'];

// Fetch owner details from the `owners` and `accounts` tables, including the image
$query = "SELECT 
            owners.owner_full_name AS full_name, 
            accounts.owner_email AS email, 
            owners.owner_image AS profile_image, 
            owners.phone AS phone 
          FROM owners 
          INNER JOIN accounts ON owners.account_id = accounts.account_id 
          WHERE owners.owner_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$owner = $result->fetch_assoc();

// Close the statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner-Setting</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="owner_setting.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar" id="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="NokorRealm">
            </div>
            <ul class="menu">
                <li><a href="owner_das.php"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
                <li><a href="owner_bookings.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="owner_hotel.php"><span class="material-symbols-outlined">apartment</span><span>Hotels</span></a></li>
                <li><a href="owner_history.php"><i class="material-icons">history</i><span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
                <li><a href="owner_setting.php" id="setting-active"><i class="material-icons">settings</i><span>Setting</span></a></li>
                <li><a href="owner_logout.php"><i class="material-icons">logout</i><span>Sign Out</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header-content">
                <div class="hamburger-menu" id="hamburger-menu">
                    <i class="material-icons">menu</i>
                </div>
                <div class="nav-bar">
                    <h2>Hello, <?php echo htmlspecialchars($owner['full_name']); ?></h2>
                    <p>Have a nice day</p>
                </div>
                <div class="header-actions">
                    <div class="profile">
                        <img src="<?php echo htmlspecialchars($owner['profile_image'] ?? 'image/default_profile.png'); ?>" alt="Profile Picture">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($owner['full_name']); ?></h3>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-detail">
                <div class="user-information">
                    <img src="<?php echo htmlspecialchars($owner['profile_image'] ?? 'image/default_profile.png'); ?>" alt="Owner Image">
                    <h3>Information</h3>
                    <div class="info-item">
                        <h4>Phone:</h4><span> <?php echo htmlspecialchars($owner['phone']); ?></span>
                    </div>
                    <div class="info-item">
                        <h4>Name:</h4><span> <?php echo htmlspecialchars($owner['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <h4>Email:</h4><span> <?php echo htmlspecialchars($owner['email']); ?></span>
                    </div>
                </div>

                <div class="user-setting">
                    <form action="owner_info_update.php" method="POST" class="settings-form">
                        <h3>Owner Details</h3>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($owner['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($owner['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($owner['phone']); ?>">
                        </div>
                        <button type="submit" class="submit-btn">Edit Details</button>
                    </form>

                    <form action="owner_pass_change.php" method="POST" class="settings-form">
                        <h3>Password</h3>
                        <div class="form-group">
                            <label for="old_password">Enter old password</label>
                            <input type="password" id="old_password" name="old_password" placeholder="**********">
                        </div>
                        <div class="form-group">
                            <label for="new_password">Enter new password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="**********">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm new password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="***********">
                        </div>
                        <button type="submit" class="submit-btn">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburger-menu');
            const sideBar = document.getElementById('side-bar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            hamburgerMenu.addEventListener('click', function() {
                sideBar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open'); // Toggle overlay visibility
            });

            sidebarOverlay.addEventListener('click', function() {
                sideBar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        });
    </script>
</body>

</html>