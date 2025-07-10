<?php
session_start();

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_signin.php");
    exit();
}

include 'db_conn.php';

$owner_id = $_SESSION['owner_id'];

$owner_query = "SELECT owner_full_name, owner_image FROM owners WHERE owner_id = ?";
$owner_stmt = $conn->prepare($owner_query);
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();
$owner_stmt->close();

if (!isset($_GET['hotel_id'])) {
    die("<p>Error: No hotel selected.</p>");
}

$hotel_id = $_GET['hotel_id'];

$room_query = "SELECT room_id, room_type, price_per_night, guest_capacity, room_status, room_image 
                FROM rooms WHERE hotel_id = ?";
$room_stmt = $conn->prepare($room_query);
$room_stmt->bind_param("i", $hotel_id);
$room_stmt->execute();
$room_result = $room_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Rooms</title>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="hotel_room.css?v=1">
</head>

<body>
    <div class="page-container">
        <div class="mobile-overlay" id="mobile-overlay"></div>
        <div class="side-bar" id="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="Logo">
            </div>
            <ul class="menu">
                <li><a href="owner_hotel.php"><i class="material-icons">arrow_back</i><span>Back</span></a></li>
                <li><a href="owner_das.php"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
                <li><a href="owner_bookings.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
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
                    <p>Welcome back</p>
                </div>
                <div class="header-actions">
                    <div class="ring-icon">
                        <span class="material-symbols-outlined">notifications</span>
                    </div>
                    <div class="profile">
                        <img src="<?php echo htmlspecialchars($owner['owner_image'] ?? 'image/default_profile.png'); ?>" alt="Profile Picture">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($owner['owner_full_name']); ?></h3>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <div class="search-bar-inner">
                    <h2>Rooms</h2>
                    <div class="search-input-wrapper">
                        <input type="text" placeholder="Search rooms">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>
            </div>

            <div class="rooms-content-area">
                <?php
                if ($room_result->num_rows > 0) {
                    while ($room = $room_result->fetch_assoc()) {
                        $amenities_query = "SELECT amenity_name FROM room_amenities WHERE room_id = ?";
                        $amenities_stmt = $conn->prepare($amenities_query);
                        $amenities_stmt->bind_param("i", $room['room_id']);
                        $amenities_stmt->execute();
                        $amenities_result = $amenities_stmt->get_result();
                        $amenities = [];

                        while ($amenity = $amenities_result->fetch_assoc()) {
                            $amenities[] = htmlspecialchars($amenity['amenity_name']);
                        }
                        $amenities_stmt->close();
                ?>
                        <div class="room-card">
                            <img src="image/<?php echo htmlspecialchars($room['room_image']); ?>" alt="Room Image" class="room-image">
                            <div class="room-details">
                                <h2>Type: <?php echo htmlspecialchars($room['room_type']); ?></h2>
                                <p class="availability <?php echo ($room['room_status'] == 'available') ? 'available' : 'unavailable'; ?>">
                                    <?php echo htmlspecialchars($room['room_status']); ?>
                                </p>
                                <p class="price">Price: <strong>$<?php echo htmlspecialchars($room['price_per_night']); ?></strong> per night</p>
                                <p class="guests">Guest: <strong><?php echo htmlspecialchars($room['guest_capacity']); ?></strong></p>
                                <p class="amenities">Amenities: <strong><?php echo empty($amenities) ? "None" : implode(", ", $amenities); ?></strong></p>

                                <div class="actions">
                                    <form action="edit_room.php" method="get">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                        <button type="submit" class="edit-btn">Edit</button>
                                    </form>

                                    <form action="delete_room.php" method="post">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this room?');">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo "<p class='no-rooms-message'>No rooms found for this hotel.</p>";
                }
                ?>
                <div class="add-room-btn-container">
                    <a href="add_room.php?hotel_id=<?php echo htmlspecialchars($hotel_id); ?>" class="add-btn">Add Room</a>
                </div>
            </div>
        </div>
    </div>

    <script src="sidebar.js">
    </script>
</body>

</html>

<?php
$room_stmt->close();
$conn->close();
?>