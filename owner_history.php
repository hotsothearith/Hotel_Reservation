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

// Fetch owner details (name and profile image)
$owner_query = "SELECT owner_full_name, owner_image FROM owners WHERE owner_id = ?";
$owner_stmt = $conn->prepare($owner_query);
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();

$history_query = "
    SELECT
    b.booking_id,
    h.hotel_name,
    r.room_type,
    r.room_image,
    b.check_in_date,
    b.check_out_date,
    b.number_of_guests,
    b.total_cost,
    u.user_full_name AS guest_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN hotels h ON r.hotel_id = h.hotel_id
    join users u ON b.user_id = u.user_id
    WHERE h.owner_id =?
    ORDER BY b.booked_at DESC;
";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $owner_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

function calculateDuration($checkIn, $checkOut) {
    $in = new DateTime($checkIn);
    $out = new DateTime($checkOut);
    $duration = $in->diff($out)->format('%a days');
    return $duration;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner-history</title>
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="owner_history.css">
</head>

<body>
    <div class="page-container">
    <div class="side-bar">
        <div class="logo">
            <img src="image/NokorRealm.png" alt="Logo">
        </div>
        <ul class="menu">
            <!-- <li><a href="owner_home.php"><i class="material-icons">arrow_back</i><span>Back</span></a></li> -->
            <li><a href="owner_das.php"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
            <li><a href="owner_booking.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
            <li><a href="owner_hotel.php"><span class="material-symbols-outlined">apartment</span><span>Hotels</span></a></li>
            <li><a href="owner_history.php" id="setting-active"><i class="material-icons">history</i><span>History</span></a></li>
            <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
            <li><a href="owner_setting.php"><i class="material-icons">settings</i><span>Setting</span></a></li>
        </ul>
    </div>

    <div class="main-content">
    <div class="header-content">
        <div class="hamburger-menu">
            <span class="material-symbols-outlined">menu</span>
        </div>
        <div class="nav-bar">
            <h2>Hello, <?php echo htmlspecialchars($owner['owner_full_name']); ?></h2>
            <p>Welcome back!</p>
        </div>
        <div class="profile">
            <img src="<?php echo htmlspecialchars($owner['owner_image'] ?? 'image/default_profile.png'); ?>" alt="Profile">
            <div class="name-user">
                <h3><?php echo htmlspecialchars($owner['owner_full_name']); ?></h3>
                <p>Owner</p>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
        <div class="search-bar-inner">
            <h2>History</h2>
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search history">
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="history-content-area">
        <div class="card-container">
            <?php 
            if ($history_result->num_rows > 0) : 
               while ($booking = $history_result->fetch_assoc()):
                    $duration = calculateDuration($booking['check_in_date'], $booking['check_out_date']);
                    $checkOutDateFormatted = (new DateTime($booking['check_out_date']))->format('d M Y');
       
            ?>
            <div class="booking-card">
                <img src="<?php echo htmlspecialchars($booking['room_image'])?>" alt="Room Image" class="room-image">
                <div class="room-details">
                    <h2><?php echo htmlspecialchars($booking['room_type'] . "at" . $booking['hotel_name']); ?></h2>
                    <p><strong>Checked In:</strong> <?php echo $booking['check_in_date']; ?> &nbsp;
                        <strong>Checked Out:</strong> <?php echo $checkOutDateFormatted; ?> &nbsp; 
                        <strong>Duration:</strong> <?php echo $duration; ?> &nbsp;
                        <strong>Guests:</strong><?php echo htmlspecialchars($booking['number_of_guests']); ?>
                    </p>
                    <p><strong>Booked by: </strong><?php echo htmlspecialchars($booking['guest_name']); ?> &nbsp;</p>
                    <p class="price">$<?php echo number_format($booking['total_cost'], 2);?> USD</p>
                </div>
                <div class="actions">
                    <button class="view-btn">View</button>
                    <button class="cancel-btn">Delete</button>
                </div>
            </div>
            <?php endwhile;?>
        <?php else :?>
            <p class="no-bookings-message">No booking history found.</p>
        <?php endif;?>
        </div>
    </div>
    </div>
    </div>
</body>
<script src="sidebar.js"></script>
</html>
<?php
// Close the statement and connection
$owner_stmt->close();
$conn->close();
?>