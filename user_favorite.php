<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
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
// IMPORTANT: Ensure 'user_image' exists in your 'users' table
$user_query = "SELECT user_full_name, user_image FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Fetch user's favorite hotels
$fav_query = "
    SELECT
        f.hotel_id,
        h.hotel_name,
        h.hotel_location,
        h.hotel_image_url,
        d.discount_percentage,
        MIN(r.price_per_night) AS price_per_night
    FROM favorites f
    JOIN hotels h ON f.hotel_id = h.hotel_id
    LEFT JOIN discounts d ON h.hotel_id = d.hotel_id
    LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
    WHERE f.user_id = ?
    GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, d.discount_percentage
    ORDER BY h.hotel_name ASC;
";
$fav_stmt = $conn->prepare($fav_query);
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Favorites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="user_favorite.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="Logo">
            </div>
            <ul class="menu">
                <li><a href="user_home.php"><i class="material-icons">arrow_back</i><span>Back</span></a></li>
                <li><a href="user_booking.php"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="user_favorite.php" id="setting-active"><i class="material-icons">favorite</i><span>Favorites</span></a></li>
                <li><a href="user_history.php"><i class="material-icons">history</i><span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
                <li><a href="user_setting.php"><i class="material-icons">settings</i><span>Settings</span></a></li>
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
                    <h2>Favorites</h2>
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search favorites">
                    </div>
                </div>
            </div>

            <div class="favorite-content-area"> 
                <div class="grid-container">
                    <?php if ($fav_result->num_rows > 0) : ?>
                        <?php while ($fav = $fav_result->fetch_assoc()) : ?>
                            <?php
                            $originalPrice = $fav['price_per_night'];
                            // Handle cases where discount_percentage might be NULL for LEFT JOIN
                            $discountPercentage = $fav['discount_percentage'] ?? 0;
                            if (is_numeric($originalPrice) && is_numeric($discountPercentage)) {
                                $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                            } else {
                                $originalPrice = 0; // Default to 0 if price is not numeric
                                $discountedPrice = 0; // Default to 0
                            }
                            ?>
                            <div class="card">
                                <img src="<?php echo htmlspecialchars($fav['hotel_image_url'] ?? 'image/default_hotel.jpg'); ?>" alt="<?php echo htmlspecialchars($fav['hotel_name']); ?>">

                                <div class="price-badge">
                                    <?php if (!empty($discountPercentage) && $discountPercentage > 0): ?>
                                        <span class="discount-percent"><?php echo htmlspecialchars($discountPercentage); ?>% OFF</span>
                                        <div class="current-price">$<?php echo number_format($discountedPrice, 2); ?></div>
                                        <div class="original-price"><s>$<?php echo number_format($originalPrice, 2); ?></s></div>
                                    <?php else: ?>
                                        <div class="current-price">$<?php echo number_format($originalPrice, 2); ?></div>
                                    <?php endif; ?>
                                </div>

                                <a href="user_hotel_detail.php?hotel_id=<?php echo htmlspecialchars($fav['hotel_id']); ?>" class="card-link-overlay"></a>

                                <div class="info">
                                    <h3><?php echo htmlspecialchars($fav['hotel_name']); ?></h3>
                                    <p><span class="material-symbols-outlined">location_on</span> <?php echo htmlspecialchars($fav['hotel_location']); ?></p>
                                </div>
                                <button class="close-btn" onclick="removeFavorite(<?php echo $fav['hotel_id']; ?>)">&times;</button>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="no-favorites-message">No favorite hotels found.</p>
                    <?php endif; ?>
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

        // Function to remove favorite hotel
        function removeFavorite(hotelId) {
            if (confirm("Are you sure you want to remove this hotel from favorites?")) {
                fetch("delete_favorite.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "hotel_id=" + hotelId
                    })
                    .then(response => response.text()) // Expecting plain text response
                    .then(data => {
                        console.log("Delete response:", data); // Log response for debugging
                        if (data.trim() === "success") { // Use trim() to remove potential whitespace
                            alert("Hotel removed from favorites!");
                            location.reload(); // Refresh the page to show updated list
                        } else if (data.trim() === "not_logged_in") {
                            alert("You must be logged in to remove favorites.");
                            window.location.href = "user_signin.php"; // Redirect to login
                        } else if (data.trim() === "invalid_hotel_id" || data.trim() === "no_hotel_id" || data.startsWith("db_error")) {
                            alert("Error removing favorite: " + data + ". Please try again.");
                        } else {
                            alert("Failed to remove favorite. Please try again. Unexpected response: " + data);
                        }
                    })
                    .catch(error => {
                        console.error("Network error removing favorite:", error);
                        alert("An error occurred while removing favorite. Please check your network connection.");
                    });
            }
        }
    </script>
</body>

</html>
<?php
// Close the statement and connection
$fav_stmt->close();
$conn->close();
?>