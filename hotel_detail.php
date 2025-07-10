<?php
// Database connection
$sname = "localhost";
$uname = "root";
$pass = "";
$db_name = "hotel-reservations";

$conn = mysqli_connect($sname, $uname, $pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to get icon class based on amenity name
function getIconClass($amenity_name)
{
    $icons = [
        'WiFi' => 'fa-wifi',
        'Parking' => 'fa-parking',
        'Pool' => 'fa-swimming-pool',
        'Gym' => 'fa-dumbbell',
        'Spa' => 'fa-spa',
        'TV' => 'fa-tv',
        'Minibar' => 'fa-wine-glass-alt',
        'Balcony' => 'fa-wind', // Or a better balcony icon if available
        'AC' => 'fa-fan',
        'Breakfast' => 'fa-coffee',
        'Bathtub' => 'fa-bath',
        'Seating Area' => 'fa-chair',
        'Desk' => 'fa-desktop',
        'Soundproof' => 'fa-volume-mute',
    ];
    return $icons[$amenity_name] ?? 'fa-question';
}

// Get hotel_id from URL parameter
if (!isset($_GET['hotel_id'])) {
    die("Hotel ID not provided.");
}

$hotel_id = (int) $_GET['hotel_id']; // Ensure it's an integer

// Fetch hotel details
$hotel_sql = "SELECT * FROM hotels WHERE hotel_id = ?";
$stmt = $conn->prepare($hotel_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel_result = $stmt->get_result();

if ($hotel_result->num_rows === 0) {
    die("Hotel not found.");
}
$hotel = $hotel_result->fetch_assoc();
$stmt->close();

// Fetch rooms for the hotel
$rooms_sql = "SELECT * FROM rooms WHERE hotel_id = ?";
$stmt = $conn->prepare($rooms_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms_result = $stmt->get_result();
$stmt->close();

// Fetch 4 random hotels for "Popular Places"
$popular_sql = "SELECT * FROM hotels ORDER BY RAND() LIMIT 4";
$popular_result = $conn->query($popular_sql);

function isLoggedIn() {
    return isset($_SESSION['user_id']); // Assuming you're using sessions
}

// Function to get the current user ID
function getCurrentUserId() {
    if (isLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}

// Function to check if the user has liked the hotel
function isUserLiked($hotel_id, $user_id, $conn) {
    if (!$user_id) { // Check if user is logged in
        return false;
    }
    $sql = "SELECT COUNT(*) FROM hotel_likes WHERE hotel_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hotel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count > 0;
}

// Function to get total likes for the hotel
function getTotalLikes($hotel_id, $conn) {
    $sql = "SELECT COUNT(*) FROM hotel_likes WHERE hotel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    return $count;
}

// Get hotel_id from URL parameter
if (!isset($_GET['hotel_id'])) {
    die("Hotel ID not provided.");
}

$hotel_id = (int) $_GET['hotel_id'];

$user_id = getCurrentUserId();

// Check if the user has liked the hotel
$user_liked = isUserLiked($hotel_id, $user_id, $conn);

// Get total likes for the hotel
$total_likes = getTotalLikes($hotel_id, $conn);

// Handle like/unlike actions
if (isset($_POST['hotel_id']) && isset($_POST['action'])) {
    $hotel_id = (int) $_POST['hotel_id'];
    $action = $_POST['action'];

    if (!isLoggedIn()) {
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }

    $user_id = getCurrentUserId();

    if ($action === 'like') {
        $sql = "INSERT INTO likes (hotel_id, user_id, like_at) VALUES (?, ?, NOW())";
    } elseif ($action === 'unlike') {
        $sql = "DELETE FROM likes WHERE hotel_id = ? AND user_id = ?";
    } else {
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hotel_id, $user_id);

    if ($stmt->execute()) {
        $total_likes = getTotalLikes($hotel_id, $conn);
        echo json_encode(['total_likes' => $total_likes]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// ... (your HTML code) ...
?>

<script>
    function toggleLike(hotelId) {
        if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
            if(confirm("Please sign in or sign up to like this hotel. Click Ok to go to the sign-up page.")) {
                window.location.href = "user_signup.html";
            }
            return;
        }

        const likeButton = document.getElementById('like-button');
        const likeCount = document.getElementById('like-count');
        const isLiked = likeButton.classList.contains('liked');
        const action = isLiked ? 'unlike' : 'like';

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `hotel_id=${hotelId}&action=${action}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                alert(data.error);
            } else {
                likeCount.textContent = data.total_likes + " Likes";
                likeButton.classList.toggle('liked');
                // Add redirection here if you want to go to the home page after liking/unliking
                // window.location.href = "home.php";
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel-details</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="hotel_detail.css">
</head>

<body>
    <header class="navbar">
        <div class="header-content">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="">
            </div>
            <ul class="menu-content">
                <li><a href="home.php">Home</a></li>
                <li><a href="hotel_detail.php" class="active">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
            <div class="icons">
            <div class="dropdown">
                <div data-bs-toggle="dropdown">
                    <span class="material-symbols-outlined" id="menu-icon">menu</span>
                    <span class="material-symbols-outlined" id="acount-icon">account_circle</span>
                </div>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="user_signup.html">Sign Up</a></li>
                    <li><a class="dropdown-item" href="user_signin.html">Sign In</a></li>
                   <li><a class="dropdown-item" href="owner_signup.html" id="owner-btn">As Owner Hotel/House</a></li>
                    <li><a class="dropdown-item" href="#">Help Center</a></li>
                </ul>
            </div>
        </div>
        </div>
        <div class="phone-menu">
            <button><span class="material-symbols-outlined">dehaze</span></button>
            <div class="drop-menu">
                 <li><a href="home.php">Home</a></li>
                <li><a href="hotel_detail.php" class="active">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="user_signup.html">Sign Up</a></li>
                <li><a href="user_signin.html">Sign In</a></li>
                <li><a href="owner_signup.html" id="owner-btn">As Owner Hotel/House</a></li>
                <li><a href="#">Help Center</a></li>
            </div>
        </div>
    </header>

    <nav class="containers">
        <div class="name-hotel-detail">
            <h1><b><?php echo htmlspecialchars($hotel['hotel_name']); ?></b></h1>
            <p><?php echo htmlspecialchars($hotel['hotel_location']); ?></p>
        </div>

        <div class="img_detail">
            <img src="<?php echo htmlspecialchars($hotel['hotel_image_url']); ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>">
        </div>

        <div class="rating-container">
            <span class="like-icon <?php echo $user_liked ? 'liked' : ''; ?>" 
                onclick="toggleLike(<?php echo $hotel_id; ?>, <?php echo $user_id; ?>)" 
                id="like-button">
                <i class="fas fa-thumbs-up"></i>
            </span>
            <span id="like-count"><?php echo $total_likes; ?> Likes</span>
        </div>

        <div class="map">
            <a href="https://www.google.com/maps?q=<?php echo urlencode($hotel['hotel_location']); ?>" target="_blank" rel="noopener">
                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($hotel['hotel_location']); ?></p>
            </a>
        </div>

        <div class="description">
            <h2>Descriptions</h2>
            <p><?php echo htmlspecialchars($hotel['hotel_description']); ?></p>
        </div>

        <div class="search-container">
            <form action="#">
                <div class="select-bar">
                    <div class="search-field">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <input type="date" id="trip-start" name="trip-start" placeholder="Check In" title="Check In">
                    </div>
                </div>
                <div class="select-bar">
                    <div class="search-field">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <input type="date" id="trip-end" name="trip-end" placeholder="Check Out" title="Check Out">
                    </div>
                </div>
                <!-- <button type="submit" class="btn2">Search</button> -->
            </form>
        </div>

        <div class="room-type">
            <h2>Rooms</h2>
            <div id="underline-room"></div>
        </div>
        <?php if ($rooms_result->num_rows > 0): ?>
            <?php while ($room = $rooms_result->fetch_assoc()): ?>
                <div class="room">
                    <div class="room-img">
                        <img src="image/<?php echo htmlspecialchars($room['room_image']); ?>" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                    </div>
                    <div class="room-details">
                        <div class="room-info">
                            <h4><?php echo htmlspecialchars($room['room_type']); ?></h4>
                            <p>Start Booking</p>
                            <p><span id="price">$<?php echo htmlspecialchars($room['price_per_night']); ?></span> <span id="days">per Night</span></p>
                        </div>
                        <div class="icon">
                            <?php
                            $amenities_sql = "SELECT amenity_name 
                                                  FROM room_amenities 
                                                  WHERE room_id = ?";
                            $stmt = $conn->prepare($amenities_sql);
                            $stmt->bind_param("i", $room['room_id']);
                            $stmt->execute();
                            $amenities_result = $stmt->get_result();

                            if ($amenities_result->num_rows > 0) {
                                while ($amenity = $amenities_result->fetch_assoc()) {
                                    $icon_class = getIconClass($amenity['amenity_name']);
                                    echo '<div>';
                                    echo '<i class="fa-solid ' . $icon_class . '"></i>';
                                    echo '<p> <span>' . htmlspecialchars($amenity['amenity_name']) . '</span></p>';
                                    echo '</div>';
                                }
                            } else {
                                echo "<p>No amenities found for this room.</p>";
                            }
                            $stmt->close();
                            ?>
                        </div>
                        <!-- <a href="booking/booking_info.html"><button class="btn1">Book Now!</button></a> -->
                        <?php if (isset($_SESSION['username'])): ?>
                            <a href="booking/booking_info.php"><!-- From Uiverse.io by andrew-demchenk0 --> 
                            <button type="button" class="button">
                            <span class="button__text">Add Cart</span>
                            <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24" fill="none" class="svg"><line y2="19" y1="5" x2="12" x1="12"></line><line y2="12" y1="12" x2="19" x1="5"></line></svg></span>
                            </button></a>
                        <?php else: ?>
                            <!-- <button class="btn1" onclick="window.location.href='user_signin.html'">Add to Cart</button> -->
                            <!-- From Uiverse.io by andrew-demchenk0 --> 
                            <button type="button" class="button" onclick="window.location.href='user_signin.html'">
                            <span class="button__text">Add Cart</span>
                            <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24" fill="none" class="svg"><line y2="19" y1="5" x2="12" x1="12"></line><line y2="12" y1="12" x2="19" x1="5"></line></svg></span>
                            </button>
                        <?php endif; ?>


                    </div>
                    <div class="icon-people">
                        <i class="fa-solid fa-users"></i>
                        <span><?php echo htmlspecialchars($room['guest_capacity']); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No rooms found for this hotel.</p>
        <?php endif; ?>

        <div class="view-all">
            <a href="all_room.php"><button class="btn-view-all">View All Rooms</button></a>
        </div>
        </div>

        <div class="popular-places">
            <h2>Popular Places</h2>
            <div id="underline-popular"></div>
        </div>

        <div class="card2-container">
            <?php
            // Fetch hotels with the minimum room price
            $query = "
                SELECT 
                h.hotel_id, 
                h.hotel_name, 
                h.hotel_location, 
                h.hotel_image_url, 
                MIN(r.price_per_night) AS min_price
                FROM hotels h
                INNER JOIN rooms r ON h.hotel_id = r.hotel_id  -- Ensures hotel has at least one room
                GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url
                ORDER BY min_price ASC
                LIMIT 8;
                "; // Sort by the cheapest price (optional)

            $popular_result = $conn->query($query);

            if ($popular_result->num_rows > 0): ?>
                <?php while ($row = $popular_result->fetch_assoc()): ?>
                    <div class="col-md-15">

                        <div class="card2">
                            <div class="price-badge">$<?php echo htmlspecialchars($row['min_price']); ?> per night</div> <!-- Display minimum price -->
                            <a href="hotel_detail.php?hotel_id=<?php echo htmlspecialchars($row['hotel_id']); ?>" class="card2-link">
                            <img src="<?php echo htmlspecialchars($row['hotel_image_url']); ?>" alt="Hotel Image">
                            </a>
                                <div class="card2-content">
                                    <h3><?php echo htmlspecialchars($row['hotel_name']); ?></h3>
                                    <p><span class="material-symbols-outlined">
                            location_on
                            </span><?php echo htmlspecialchars($row['hotel_location']); ?></p>
                                </div>
                            <button class="favor-btn" id="favorite-btn-<?php echo htmlspecialchars($row['hotel_id']); ?>"
                                data-hotel-id="<?php echo htmlspecialchars($row['hotel_id']); ?>">★</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No more hotels available.</p>
            <?php endif; ?>
            <script src="AddToFavorite-Funciton.js">
            </script>
        </div>

        <div class="view-all">
            <a href="view_pop.php"><button class="btn-view-all">View more</button></a>
        </div>
        </div>
    </nav>

    <hr class="divider">

    <div class="container">
        <footer class="footer-container">
            <div class="footer-content">
                <h3>Support</h3>
                <p>Phnom Penh, Cambodia</p>
                <p>Rith@gmail.com</p>
                <p>+855 884217043</p>
            </div>

            <div class="footer-content">
                <ul>
                    <li>
                        <h3>Account</h3>
                    </li>
                    <li><a href="#myaccount">My account</a></li>
                    <li><a href="#login/logout">Login / Register</a></li>
                    <li><a href="#booking">Booking</a></li>
                </ul>
            </div>

            <div class="footer-content">
                <ul>
                    <li>
                        <h3>Quick Link</h3>
                    </li>
                    <li><a href="#privacypolicy">Privacy Policy</a></li>
                    <li><a href="#termsofuse">Terms of Use</a></li>
                    <li><a href="faq">FAQ</a></li>
                    <li><a href="Contact">Contact</a></li>
                </ul>
            </div>

            <div class="footer-content">
                <h3>Contact</h3>
                <a href="#facebook"><i class="fa-brands fa-facebook"></i></a>
                <a href="#telegram"><i class="fa-brands fa-telegram"></i></a>
                <a href="#instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#youtube"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </footer>
    </div>
</body>

</html>

<?php
$conn->close();
?>