<?php
// Start the session
session_start();

// Check if the owner is logged in
if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_signin.php");
    exit();
}

// Include database connection
include 'db_conn.php';

// Get the owner ID from the session
$owner_id = $_SESSION['owner_id'];

// Fetch owner details
$owner_query = "SELECT owner_full_name, owner_image FROM owners WHERE owner_id = ?";
$owner_stmt = $conn->prepare($owner_query);
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();
$owner_stmt->close();

// Fetch hotels owned by the current owner
$hotel_query = "SELECT hotel_id, hotel_name, hotel_location, hotel_image_url FROM hotels WHERE owner_id = ?";
$hotel_stmt = $conn->prepare($hotel_query);
$hotel_stmt->bind_param("i", $owner_id);
$hotel_stmt->execute();
$hotel_result = $hotel_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Hotels - NokorRealm</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="owner_hotel.css">
</head>

<body>
    <div class="page-container">

        <div class="mobile-overlay" id="mobile-overlay"></div>

        <div class="side-bar" id="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="NokorRealm Logo">
            </div>

            <ul class="menu">
                <li><a href="owner_das.php"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
                <li><a href="owner_booking.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="owner_hotel.php" id="setting-active"><span
                            class="material-symbols-outlined">apartment</span><span>Hotels</span></a></li>
                <li><a href="owner_history.php"><i class="material-icons">history</i><span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
                <li><a href="owner_setting.php"><i class="material-icons">settings</i><span>Setting</span></a></li>
            </ul>
        </div>

        <div class="main-content">

            <div class="header-content">
                <div class="hamburger-menu" id="hamburger-menu">
                    <span class="material-symbols-outlined">menu</span>
                </div>
                <div class="nav-bar">
                    <h2>Hello, <?php echo htmlspecialchars($owner['owner_full_name']); ?></h2>
                    <p>Welcome back!</p>
                </div>
                <div class="header-actions">
                    <div class="profile">
                        <img src="<?php echo htmlspecialchars($owner['owner_image'] ?? 'image/default_profile.png'); ?>" alt="Profile">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($owner['owner_full_name']); ?></h3>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <div class="search-bar-inner">
                    <h2>Your Hotels</h2>
                    <div class="search-input-wrapper">
                        <input type="text" placeholder="Search hotels by name or location">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>
            </div>

            <div class="hotels-content-area">
                <?php if ($hotel_result->num_rows > 0): ?>
                    <?php while ($hotel = $hotel_result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-image-container">
                                <img src="<?php echo htmlspecialchars($hotel['hotel_image_url'] ?? 'image/default_hotel.jpg'); ?>" alt="Hotel Image" class="card-image">
                                <div class="card-text">
                                    <h2 class="card-title"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h2>
                                    <p class="card-address"><?php echo htmlspecialchars($hotel['hotel_location']); ?></p>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="card-buttons">
                                    <a href="add_room.php?hotel_id=<?php echo $hotel['hotel_id']; ?>" class="btn primary">Add Room</a>
                                    <a href="hotel_room.php?hotel_id=<?php echo $hotel['hotel_id']; ?>" class="btn secondary">View Rooms</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-hotels-message">
                        You currently don't have any hotels listed. <br> Click "Add New Hotel" to get started!
                    </p>
                <?php endif; ?>
            </div> <div class="add-hotel-btn-container">
                <a href="add_hotel.php" class="btn primary">Add New Hotel</a>
            </div>

        </div> 
        </div> 
        <script>
             document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburger-menu');
            const sideBar = document.getElementById('side-bar');
            const mobileOverlay = document.getElementById('mobile-overlay');

            if (hamburgerMenu && sideBar && mobileOverlay) {
                hamburgerMenu.addEventListener('click', function() {
                    sideBar.classList.add('open');
                    mobileOverlay.style.display = 'block';
                });

                mobileOverlay.addEventListener('click', function() {
                    sideBar.classList.remove('open');
                    mobileOverlay.style.display = 'none';
                });
            }
        });
        </script>
</body>
</html>

<?php
// Close the statement and connection
if (isset($hotel_stmt)) {
    $hotel_stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
?>