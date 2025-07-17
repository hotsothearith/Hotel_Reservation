<?php
// Start the session
session_start();

// Include the database connection file once at the beginning
include 'db_conn.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: user_signin.php"); // Adjust path if needed
    exit();
}

// Get the user ID from the session (only once)
$user_id = $_SESSION['user_id'];

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
    return $icons[$amenity_name] ?? 'fa-question-circle'; // Default icon
}

// Function to check if the user is logged in (already done by session check at top)
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
    if (!$stmt) {
        error_log("Error preparing isUserLiked query: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ii", $hotel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count > 0;
}

// Function to get total likes for the hotel
function getTotalLikes($hotel_id, $conn) {
    $sql = "SELECT COUNT(*) FROM hotel_likes WHERE hotel_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing getTotalLikes query: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count;
}

// Get hotel_id from URL parameter
if (!isset($_GET['hotel_id'])) {
    die("Hotel ID not provided.");
}
$hotel_id = (int) $_GET['hotel_id']; // Ensure it's an integer

// Handle like/unlike actions (This block should be *before* any HTML output)
if (isset($_POST['hotel_id']) && isset($_POST['action'])) {
    // Ensure the connection is still open for this AJAX request
    if (!$conn) {
        echo json_encode(['error' => 'Database connection not available for AJAX.']);
        exit;
    }

    $ajax_hotel_id = (int) $_POST['hotel_id'];
    $action = $_POST['action'];

    if (!isLoggedIn()) {
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }

    $ajax_user_id = getCurrentUserId();

    if ($action === 'like') {
        // Use INSERT IGNORE to prevent duplicate likes if the user clicks multiple times
        $sql = "INSERT IGNORE INTO hotel_likes (hotel_id, user_id, like_at) VALUES (?, ?, NOW())";
    } elseif ($action === 'unlike') {
        $sql = "DELETE FROM hotel_likes WHERE hotel_id = ? AND user_id = ?";
    } else {
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $ajax_hotel_id, $ajax_user_id);
        if ($stmt->execute()) {
            $total_likes_updated = getTotalLikes($ajax_hotel_id, $conn); // Recalculate likes
            echo json_encode(['total_likes' => $total_likes_updated]);
        } else {
            error_log("Database error in like/unlike: " . $stmt->error);
            echo json_encode(['error' => 'Database error on action.']);
        }
        $stmt->close();
    } else {
        error_log("Error preparing like/unlike query: " . $conn->error);
        echo json_encode(['error' => 'Error preparing statement for action.']);
    }
    exit; // Important: Exit after JSON response for AJAX requests
}

// Fetch hotel details (moved below the AJAX handler)
$hotel_sql = "SELECT * FROM hotels WHERE hotel_id = ?";
$stmt = $conn->prepare($hotel_sql);
if (!$stmt) {
    die("Error preparing hotel query: " . $conn->error);
}
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel_result = $stmt->get_result();

if ($hotel_result->num_rows === 0) {
    die("Hotel not found.");
}
$hotel = $hotel_result->fetch_assoc();
$stmt->close();

// Check if the user has liked the hotel and get total likes for display
$user_liked = isUserLiked($hotel_id, $user_id, $conn);
$total_likes = getTotalLikes($hotel_id, $conn);

// Initialize rooms result to an empty set by default
$rooms_result = false; // Will hold mysqli_result object if dates are set

// Handle search for available rooms if dates are provided
$check_in = $_GET['trip-start'] ?? null;
$check_out = $_GET['trip-end'] ?? null;

if ($check_in && $check_out) {
    // Validate dates before querying
    try {
        $start_date_obj = new DateTime($check_in);
        $end_date_obj = new DateTime($check_out);

        if ($end_date_obj <= $start_date_obj) {
            $_SESSION['message'] = 'Check-out date must be after check-in date.';
            $_SESSION['message_type'] = 'error';
            // You might want to redirect back or just let the message display on current page
        } else {
            // Fetch available rooms for the hotel based on dates
            $rooms_sql = "
                SELECT r.* FROM rooms r
                WHERE r.hotel_id = ?
                AND r.room_id NOT IN (
                    SELECT b.room_id
                    FROM bookings b
                    WHERE b.check_in_date < ? AND b.check_out_date > ?
                )
            ";
            $stmt = $conn->prepare($rooms_sql);
            if (!$stmt) {
                $_SESSION['message'] = 'Database error preparing room availability query: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt->bind_param("iss", $hotel_id, $check_out, $check_in); // Note the order for BETWEEN logic
                $stmt->execute();
                $rooms_result = $stmt->get_result();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Invalid date format provided for search.';
        $_SESSION['message_type'] = 'error';
    }
} else {
    // If no dates are provided, fetch all rooms for the hotel
    $rooms_sql = "SELECT * FROM rooms WHERE hotel_id = ?";
    $stmt = $conn->prepare($rooms_sql);
    if (!$stmt) {
        die("Error preparing all rooms query: " . $conn->error);
    }
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $rooms_result = $stmt->get_result();
    $stmt->close();
}

// Fetch 4 random hotels for "Popular Places" - adjusted query to include likes and order
$query_popular = "
    SELECT h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url,
    MIN(r.price_per_night) AS min_price,
    (SELECT COUNT(*) FROM hotel_likes WHERE hotel_id = h.hotel_id) AS total_likes_count
    FROM hotels h
    INNER JOIN rooms r ON h.hotel_id = r.hotel_id
    GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url
    ORDER BY total_likes_count DESC, min_price ASC
    LIMIT 8";

$popular_result = $conn->query($query_popular); // Using query() directly as no user input for this query

// Get cart item count directly from the carts table for the current user
$cart_count = 0;
$stmt_cart_count = $conn->prepare("SELECT COUNT(*) AS total_items FROM carts WHERE user_id = ?");
if($stmt_cart_count) {
    $stmt_cart_count->bind_param("i", $user_id);
    $stmt_cart_count->execute();
    $cart_count_result = $stmt_cart_count->get_result();
    $cart_count = $cart_count_result->fetch_assoc()['total_items'];
    $stmt_cart_count->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User - Hotel Details</title>
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
                <img src="image/NokorRealm.png" alt="NokorRealm Logo">
            </div>
            <ul class="menu-content">
                <li><a href="user_home.php">Home</a></li>
                <li><a href="#" class="active">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
            <a href="user_add_to_cart.php" class="cart-icon-link">
            <div class="cart-icon-container">
               <i class="fa-solid fa-hotel"></i>
                <span class="cart-count"><?php echo $cart_count; ?></span>
            </div>
            </a>
             <div class="icons">
            <div class="dropdown">
                <div data-bs-toggle="dropdown">
                    <span class="material-symbols-outlined" id="menu-icon">menu</span>
                    <span class="material-symbols-outlined" id="acount-icon">account_circle</span>
                </div>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="user_booking.php">Profile</a></li>
                    <li><a class="dropdown-item" href="home.php">Sign Out</a></li>
                    <li><a class="dropdown-item" href="#">Help Center</a></li>
                </ul>
            </div>
        </div>
        </div>
        </div>
        <div class="phone-menu">
            <button><span class="material-symbols-outlined">dehaze</span></button>
            <div class="drop-menu">
                <li><a href="user_home.php">Home</a></li>
                <li><a href="#" class="active">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="user_booking.php">Profile</a></li>
                <li><a href="home.php">SignOut</a></li>
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
                onclick="toggleLike(<?php echo $hotel_id; ?>)"
                id="like-button">
                <i class="fas fa-thumbs-up"></i>
            </span>
            <span id="like-count"><?php echo $total_likes; ?> Likes</span>
        </div>

        <div class="map">
            <a href="http://maps.google.com/?q=<?php echo urlencode($hotel['hotel_location']); ?>" target="_blank" rel="noopener">
                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($hotel['hotel_location']); ?></p>
            </a>
        </div>

        <div class="description">
            <h2>Descriptions</h2>
            <p><?php echo htmlspecialchars($hotel['hotel_description']); ?></p>
        </div>

        <div class="search-container">
            <form method="GET">
                <input type="hidden" name="hotel_id" value="<?= htmlspecialchars($hotel_id) ?>">

                <div class="select-bar">
                    <div class="search-field">
                        
                        <span class="material-symbols-outlined">calendar_month</span>
                        <input type="date" id="trip-start" name="trip-start" min="<?= date('Y-m-d') ?>"
                            value="<?php echo isset($_GET['trip-start']) ? htmlspecialchars($_GET['trip-start']) : ''; ?>">
                    </div>
                </div>

                <div class="select-bar">
                    <div class="search-field">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <input type="date" id="trip-end" name="trip-end" min="<?= date('Y-m-d') ?>"
                            value="<?php echo isset($_GET['trip-end']) ? htmlspecialchars($_GET['trip-end']) : ''; ?>">
                    </div>
                </div>

                <!-- <button type="submit" class="btn2">Search</button> -->
            </form>
        </div>

        <div id="add-to-cart-message"></div>

        <div class="room-type">
            <h2>Rooms</h2>

            <?php if ($rooms_result && $rooms_result->num_rows > 0): ?>
                <?php while ($room = $rooms_result->fetch_assoc()):
                    // Fetch amenities for the current room
                    $amenities_sql = "SELECT amenity_name FROM room_amenities WHERE room_id = ?";
                    $stmt_amenities = $conn->prepare($amenities_sql); // $conn is now guaranteed to be open
                    ?>
                    <div class="room">
                        <div class="room-img">
                            <img src="<?php echo htmlspecialchars($room['room_image']); ?>" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                        </div>
                        <div class="room-details">
                            <div class="room-info">
                                <h4><?php echo htmlspecialchars($room['room_type']); ?></h4>
                                <p>Start Booking</p>
                                <p><span id="price">$<?php echo htmlspecialchars($room['price_per_night']); ?></span> <span id="days">per Night</span></p>
                            </div>
                            <div class="icon">
                                <?php
                                if ($stmt_amenities) {
                                    $stmt_amenities->bind_param("i", $room['room_id']);
                                    $stmt_amenities->execute();
                                    $amenities_result_loop = $stmt_amenities->get_result();

                                    if ($amenities_result_loop->num_rows > 0) {
                                        while ($amenity = $amenities_result_loop->fetch_assoc()) {
                                            $icon_class = getIconClass($amenity['amenity_name']);
                                            echo '<div>';
                                            echo '<i class="fa-solid ' . $icon_class . '"></i>';
                                            echo '<p> <span>' . htmlspecialchars($amenity['amenity_name']) . '</span></p>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo "<p>No amenities found for this room.</p>";
                                    }
                                    $stmt_amenities->close();
                                } else {
                                    echo "<p>Error fetching amenities.</p>";
                                }
                                ?>
                            </div>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- <button type="button" class="btn1" onclick="addToCart(<?php echo $room['room_id']; ?>)">Add To Cart</button> -->
                                  <button type="button" class="button" onclick="addToCart(<?php echo $room['room_id']; ?>)">
                                <span class="button__text">Add Cart</span>
                                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24" fill="none" class="svg"><line y2="19" y1="5" x2="12" x1="12"></line><line y2="12" y1="12" x2="19" x1="5"></line></svg></span>
                                </button>
                            <?php else: ?>
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
                <p>No rooms found for this hotel or no rooms available for the selected dates.</p>
            <?php endif; ?>

            <div class="view-all">
                <a href="all_rooms.php?hotel_id=<?php echo $hotel_id; ?>" class="btn-view-all">View All Rooms</a>
            </div>
        </div>

        <div class="popular-places">
            <h2>Popular Places</h2>
            <div id="underline-popular"></div>
        </div>

        <div class="card2-container">
            <?php
            if ($popular_result && $popular_result->num_rows > 0):
                while ($row = $popular_result->fetch_assoc()): ?>
                    <div class="col-md-15">
                        <div class="card2">
                            <div class="price-badge">$<?php echo htmlspecialchars(number_format($row['min_price'], 2)); ?> per night</div>
                            <img src="<?php echo htmlspecialchars($row['hotel_image_url']); ?>" alt="Hotel Image">
                            <a href="user_hotel_detail.php?hotel_id=<?php echo htmlspecialchars($row['hotel_id']); ?>" class="card2-link"></a>
                            <div class="card2-content">
                                <h3><?php echo htmlspecialchars($row['hotel_name']); ?></h3>
                                <p><?php echo htmlspecialchars($row['hotel_location']); ?></p>
                            </div>
                            <button class="favor-btn" id="favorite-btn-<?php echo htmlspecialchars($row['hotel_id']); ?>"
                                data-hotel-id="<?php echo htmlspecialchars($row['hotel_id']); ?>">★</button>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <p>No popular hotels available.</p>
            <?php endif; ?>
        </div>

        <div class="view-all">
            <a href="user_view_pop.php"><button class="btn-view-all">View more</button></a>
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
                    <li><a href="user_booking.php">My account</a></li>
                    <li><a href="user_signin.php">Login / Register</a></li>
                    <li><a href="user_booking.php">Booking</a></li>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // --- Like Button Functionality ---
            const likeButton = document.getElementById('like-button');
            if (likeButton) {
                likeButton.addEventListener('click', function() {
                    const hotelId = <?php echo $hotel_id; ?>; // Hotel ID from PHP
                    toggleLike(hotelId);
                });
            }

            function toggleLike(hotelId) {
                // Check if user is logged in (PHP variable passed to JS)
                if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                    if(confirm("Please sign in or sign up to like this hotel. Click Ok to go to the sign-up page.")) {
                        window.location.href = "user_signup.php"; // Corrected to .php
                    }
                    return;
                }

                const likeButton = document.getElementById('like-button');
                const likeCount = document.getElementById('like-count');
                const isLiked = likeButton.classList.contains('liked');
                const action = isLiked ? 'unlike' : 'like';

                fetch(window.location.href, { // Send AJAX request to the same page
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `hotel_id=${hotelId}&action=${action}`,
                })
                .then(response => {
                    if (!response.ok) {
                        // Log the raw response text for debugging server errors
                        return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}, response: ${text}`); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        alert(data.error); // Display error message from server
                    } else {
                        likeCount.textContent = data.total_likes + " Likes";
                        likeButton.classList.toggle('liked');
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // --- Add To Cart Functionality ---
            // Function to update the cart count display
            function updateCartCount(count) {
                const cartCountElement = document.querySelector('.cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = count;
                }
            }

            // Function to display messages (optional, but good for user feedback)
            function displayMessage(message, type) {
                const messageDiv = document.getElementById('add-to-cart-message');
                if (messageDiv) {
                    messageDiv.textContent = message;
                    messageDiv.className = type; // Add 'success' or 'error' class
                    messageDiv.style.display = 'block'; // Show the div

                    // Hide message after a few seconds
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                        messageDiv.className = ''; // Clear classes after hiding
                    }, 5000);
                }
            }

            // Global addToCart function
            window.addToCart = function(room_id) { // Attach to window to make it globally accessible
                const checkIn = document.getElementById('trip-start').value;
                const checkOut = document.getElementById('trip-end').value;

                if (!checkIn || !checkOut) {
                    displayMessage('Please select both Check In and Check Out dates before adding to cart.', 'error');
                    return;
                }

                if (new Date(checkOut) <= new Date(checkIn)) {
                    displayMessage('Check Out date must be after Check In date.', 'error');
                    return;
                }

                const numberOfGuests = 1; // Defaulting to 1 if no input field

                const url = `add_to_cart_process.php?room_id=${encodeURIComponent(room_id)}&checkIn=${encodeURIComponent(checkIn)}&checkOut=${encodeURIComponent(checkOut)}&guests=${encodeURIComponent(numberOfGuests)}`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            displayMessage(data.message, 'success');
                            updateCartCount(data.cart_count); // Update the cart count on the page
                        } else {
                            displayMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        displayMessage('An error occurred while adding to cart. Please try again. Check console for details.', 'error');
                    });
            }

            // --- Favorite Button Functionality (for popular places) ---
            const favoriteButtons = document.querySelectorAll(".favor-btn");

            favoriteButtons.forEach(button => {
                button.addEventListener("click", function() {
                    this.classList.toggle("active");
                    const hotelId = this.getAttribute("data-hotel-id");

                    if (!hotelId) {
                        console.error("Hotel ID not found!");
                        return;
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "add_favorite.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            const response = xhr.responseText;
                            if (response === "added") {
                                alert("Hotel is moved to favorites!");
                            } else if (response === "removed") {
                                alert("Hotel removed from favorites!");
                            } else if (response === "not_logged_in") {
                                alert("You must be logged in to add favorites.");
                                window.location.href = "user_signin.php";
                            } else {
                                console.error("Unexpected response:", response);
                            }
                        } else {
                            alert("An error occurred while processing your request.");
                        }
                    };
                    xhr.send(`hotel_id=${hotelId}`);
                });
            });
        });
    </script>
</body>

</html>
<?php
// Close connection at the very end of the script after all database operations are done
$conn->close();
?>