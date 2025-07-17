<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php");
    exit();
}

// Include DB connection
include 'db_conn.php'; // Ensure this path is correct

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user info
$user_query = "SELECT user_full_name, user_image FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close(); // Close the user statement

// Fetch user bookings
$booking_query = "
    SELECT b.*, r.room_name, r.room_image, r.price_per_night, r.room_type,
           (r.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date)) AS total_price
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ?
    ORDER BY b.check_in_date DESC; -- Order by check-in date
";

$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="user_booking.css">
</head>
<body>

<div class="page-container">
    <div class="side-bar">
        <div class="logo">
            <img src="image/NokorRealm.png" alt="Logo">
        </div>

        <ul class="menu">
            <li><a href="user_home.php"><i class="material-icons">arrow_back</i><span>Back</span></a></li>
            <li><a href="user_booking.php" id="setting-active"><i class="material-icons">event</i><span>Bookings</span></a></li>
            <li><a href="user_favorite.php"><i class="material-icons">favorite</i><span>Favorite</span></a></li>
            <li><a href="user_history.php"><i class="material-icons">history</i><span>History</span></a></li>
            <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
            <li><a href="user_setting.php"><i class="material-icons">settings</i><span>Setting</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-content">
            <div class="hamburger-menu">
                <span class="material-symbols-outlined">menu</span>
            </div>
            <div class="nav-bar">
                <h2>Hello, <?= htmlspecialchars($user['user_full_name']); ?></h2>
                <p>Have a nice day</p>
            </div>
            <div class="header-actions"> <div class="ring-icon">
                    <span class="material-symbols-outlined">notifications</span>
                </div>
                <div class="profile">
                    <img src="<?= htmlspecialchars($user['user_image'] ?? 'image/default_profile.png'); ?>" alt="Profile Picture">
                    <div class="name-user">
                        <h3><?= htmlspecialchars($user['user_full_name']); ?></h3>
                        <p>User</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="search-section">
            <div class="search-bar-inner"> <h2>Bookings</h2>
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search documents">
                </div>
            </div>
        </div>

        <div class="booking-content-area"> 
            <div class="card-container">
                <?php if ($booking_result->num_rows > 0): ?>
                    <?php while ($booking = $booking_result->fetch_assoc()): ?>
                        <div class="booking-card">
                            <img src="<?= htmlspecialchars($booking['room_image']) ?>" alt="Room Image" class="room-image">
                            <div class="room-details">
                                <h2><?= htmlspecialchars($booking['room_type']) ?></h2>
                                <p>
                                    <strong>Check In:</strong> <?= date('d M Y', strtotime($booking['check_in_date'])) ?> &nbsp;
                                </p>
                                <p>
                                    <strong>Duration:</strong>
                                    <?php
                                        $check_in = new DateTime($booking['check_in_date']);
                                        $check_out = new DateTime($booking['check_out_date']);
                                        $interval = $check_in->diff($check_out)->days;
                                        echo $interval . ' Day' . ($interval > 1 ? 's' : '');
                                    ?>
                                    &nbsp;
                                    <strong>Guests:</strong> <?= htmlspecialchars($booking['number_of_guests']) ?> Adults
                                </p>
                                <p class="price">$<?= number_format($booking['total_price'], 2) ?> USD</p>
                            </div>
                            <div class="actions">
                                <button class="view-btn">View</button>
                                <button class="cancel-btn">Cancel</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-bookings-message">No bookings found.</p>
                <?php endif; ?>
            </div>
        </div>

    </div> </div> <script>
    // JavaScript for sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.side-bar');
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const pageContainer = document.querySelector('.page-container'); // Or document.body for full page overlay

        if (hamburgerMenu) {
            hamburgerMenu.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                if (sidebar.classList.contains('open')) {
                    // Create an overlay to close sidebar when clicking outside
                    const overlay = document.createElement('div');
                    overlay.classList.add('sidebar-overlay');
                    pageContainer.appendChild(overlay); // Append to the page-container
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

<?php
// Close the database connections
$booking_stmt->close();
$conn->close();
?>