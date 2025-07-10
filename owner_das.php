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

// Close the statement (connection closed at end of file)
$owner_stmt->close();

// --- You would add PHP logic here to fetch actual booking/earning data ---
// For now, these are static as in your original code
$totalBookings = 0; // Replace with actual data from DB
$bookingsLast7Days = 0; // Replace with actual data from DB
$totalEarning = 0; // Replace with actual data from DB
$earningLast7Days = 0; // Replace with actual data from DB

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
                    <!-- <div class="ring-icon">
                        <span class="material-symbols-outlined">notifications</span>
                    </div> -->
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
                            <p class="sub-title">Total Bookings for last 7 days</p>
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
                            <p class="sub-title">Total Earning for last 7 days</p>
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