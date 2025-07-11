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

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the owner ID from the session
$owner_id = $_SESSION['owner_id'];

// Fetch owner details (name and profile image) from the `owners` table
$owner_query = "SELECT owner_full_name, owner_image FROM owners WHERE owner_id = ?";
$owner_stmt = $conn->prepare($owner_query);
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();
$owner_stmt->close();



// --- Fetch ACTUAL Booking and Earning Data ---

// 1. Get current date and date 7 days ago
$today = date('Y-m-d H:i:s');
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

// SQL Query to get total bookings and total earnings for ALL TIME
// Join bookings -> rooms -> hotels -> owners
$total_data_query = "
    SELECT
        COUNT(b.booking_id) AS total_bookings,
        SUM(b.total_cost) AS total_earning -- Use total_cost from your booking table
    FROM
        bookings b
    JOIN
        rooms r ON b.room_id = r.room_id
    JOIN
        hotels h ON r.hotel_id = h.hotel_id
    WHERE
        h.owner_id = ?
        AND b.payment_status = 'fully_paid'; -- Assuming 'paid' means a completed booking from your table
";

$total_data_stmt = $conn->prepare($total_data_query);
if ($total_data_stmt) {
    $total_data_stmt->bind_param("i", $owner_id);
    $total_data_stmt->execute();
    $total_data_result = $total_data_stmt->get_result();
    $total_data = $total_data_result->fetch_assoc();

    if ($total_data) {
        $totalBookings = $total_data['total_bookings'] ?? 0;
        $totalEarning = $total_data['total_earning'] ?? 0;
    }
    $total_data_stmt->close();
} else {
    error_log("Error preparing total data query: " . $conn->error); // Log error instead of die
}


// SQL Query to get bookings and earnings for the LAST 7 DAYS
// Join bookings -> rooms -> hotels -> owners and filter by booked_at
$last_7_days_query = "
    SELECT
        COUNT(b.booking_id) AS bookings_last_7_days,
        SUM(b.total_cost) AS earning_last_7_days -- Use total_cost from your booking table
    FROM
        bookings b
    JOIN
        rooms r ON b.room_id = r.room_id
    JOIN
        hotels h ON r.hotel_id = h.hotel_id
    WHERE
        h.owner_id = ?
        AND b.payment_status = 'fully_paid' -- Assuming 'paid' means a completed booking from your table
        AND b.booked_at >= ?; -- Use 'booked_at' column for filtering by date
";

$last_7_days_stmt = $conn->prepare($last_7_days_query);
if ($last_7_days_stmt) {
    $last_7_days_stmt->bind_param("is", $owner_id, $seven_days_ago);
    $last_7_days_stmt->execute();
    $last_7_days_result = $last_7_days_stmt->get_result();
    $last_7_days_data = $last_7_days_result->fetch_assoc();

    if ($last_7_days_data) {
        $bookingsLast7Days = $last_7_days_data['bookings_last_7_days'] ?? 0;
        $earningLast7Days = $last_7_days_data['earning_last_7_days'] ?? 0;
    }
    $last_7_days_stmt->close();
} else {
    error_log("Error preparing 7-day data query: " . $conn->error); // Log error instead of die
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="owner_das.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="Logo">
            </div>

            <ul class="menu">
                <li><a href="owner_das.php" id="dashboard-active"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
                <li><a href="owner_booking.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="owner_hotel.php"><span class="material-symbols-outlined">apartment</span><span>Hotels</span></a></li>
                <li><a href="owner_history.php"><i class="material-icons">history</i><span>History</span></a></li>
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
                    <p>Welcome back</p>
                </div>
                <div class="header-actions">
                    <div class="profile">
                        <img
                            src="<?php echo htmlspecialchars($owner['owner_image'] ?? 'image/default_profile.png'); ?>"
                            alt="Profile Picture">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($owner['owner_full_name']); ?></h3>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-title-section">
                <h2>Dashboard</h2>
            </div>

            <div class="das-container">
                <div class="card">
                    <div class="card-content">
                        <div class="info">
                            <p class="title">Total Bookings</p>
                            <h2><?php echo $totalBookings; ?></h2>
                            <p class="sub-title">Bookings Last 7 Days</p>
                            <h2><?php echo $bookingsLast7Days; ?></h2>
                        </div>
                        <div class="icon-box yellow">
                            <span class="material-symbols-outlined">shopping_bag</span> </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <div class="info">
                            <p class="title">Total Earning</p>
                            <h2>$<?php echo number_format($totalEarning, 2); ?></h2>
                            <p class="sub-title">Earning Last 7 Days</p>
                            <h2>$<?php echo number_format($earningLast7Days, 2); ?></h2>
                        </div>
                        <div class="icon-box green">
                            <span class="material-symbols-outlined">payments</span> </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="sidebar.js">
    </script>
</body>

</html>
<?php
// Close the database connection
$conn->close();
?>