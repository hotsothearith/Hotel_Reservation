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
include 'db_conn.php'; // Ensure this path is correct

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user details (name and profile image)
$user_query = "SELECT user_full_name, user_image FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close(); // Close user statement

// --- Fetch user's booking history ---
$history_query = "
    SELECT
        b.booking_id,
        h.hotel_name,
        r.room_type,
        r.room_image, 
        b.check_in_date,
        b.check_out_date,
        b.number_of_guests,
        b.total_cost
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN hotels h ON r.hotel_id = h.hotel_id
     WHERE b.user_id = ?
    ORDER BY b.check_out_date DESC;
";

$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Function to calculate duration
function calculateDuration($checkIn, $checkOut) {
    $in = new DateTime($checkIn);
    $out = new DateTime($checkOut);
    $interval = $in->diff($out);
    return $interval->days;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User History</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="user_history.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="Logo">
            </div>
            <ul class="menu">
                <li><a href="user_home.php"><i class="material-icons">arrow_back</i> <span>Back</span></a></li>
                <li><a href="user_booking.php"><i class="material-icons">event</i> <span>Bookings</span></a></li>
                <li><a href="user_favorite.php"><i class="material-icons">favorite</i> <span>Favorite</span></a></li>
                <li><a href="user_history.php" id="setting-active"><i class="material-icons">history</i> <span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i> <span>Help</span></a></li>
                <li><a href="user_setting.php"><i class="material-icons">settings</i> <span>Settings</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header-content">
                <div class="hamburger-menu">
                    <span class="material-symbols-outlined">menu</span>
                </div>
                <div class="nav-bar">
                    <h2>Hello, <?php echo htmlspecialchars($user['user_full_name']); ?></h2>
                    <p>Have a nice day!</p>
                </div>
                <div class="header-actions"> <div class="ring-icon">
                        <span class="material-symbols-outlined">notifications</span>
                    </div>
                    <div class="profile">
                        <img src="<?php echo htmlspecialchars($user['user_image'] ?? 'image/default_profile.png'); ?>" alt="Profile">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($user['user_full_name']); ?></h3>
                            <p>User</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <div class="search-bar-inner">
                    <h2>History</h2>
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search history">
                    </div>
                </div>
            </div>

            <div class="history-content-area">
                <div class="card-container">
                    <?php if ($history_result->num_rows > 0) : ?>
                        <?php while ($booking = $history_result->fetch_assoc()) : ?>
                            <?php
                            $duration = calculateDuration($booking['check_in_date'], $booking['check_out_date']);
                            $checkOutDateFormatted = (new DateTime($booking['check_out_date']))->format('d M Y');
                            ?>
                            <div class="booking-card">
                                <img src="<?php echo htmlspecialchars($booking['room_image'] ?? 'image/default_room.jpg'); ?>" alt="Room Image" class="room-image">
                                <div class="room-details">
                                    <h2><?php echo htmlspecialchars($booking['room_type'] . " at " . $booking['hotel_name']); ?></h2>
                                    <p>
                                        <strong>Checked Out:</strong> <?php echo $checkOutDateFormatted; ?> &nbsp;
                                        <strong>Duration:</strong> <?php echo $duration; ?> Days &nbsp;
                                        <strong>Guests:</strong> <?php echo htmlspecialchars($booking['number_of_guests']); ?>
                                    </p>
                                    <p class="price">$<?php echo number_format($booking['total_cost'], 2); ?> USD</p>
                                </div>
                                <div class="actions">
                                    <button class="like-btn">Like</button>
                                    <button class="view-btn">View</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="no-history-message">No booking history found.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div> </div> <script src="sidebar.js">
    </script>
</body>

</html>
<?php
// Close the statement and connection
$history_stmt->close();
$conn->close();
?>