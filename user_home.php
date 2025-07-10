<?php
session_start();
include 'db_conn.php';
// Check if user is logged in and retrieve user_id from session
$user_id = null; // Initialize to null
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User-homepage</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="home.css">
</head>

<body>
    <header class="navbar">
        <div class="header-content">
            
            <div class="logo">
                <img src="image/NokorRealm.png" alt="">
            </div>
            <ul class="menu-content">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="user_hotel_all.php">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
            <?php
                // Get cart item count directly from the carts table for the current user
                $cart_count = 0;
                $cart_count_sql = "SELECT COUNT(*) AS total_items FROM carts WHERE user_id = ?";
                $stmt_cart_count = $conn->prepare($cart_count_sql);
                if($stmt_cart_count) {
                    $stmt_cart_count->bind_param("i", $user_id);
                    $stmt_cart_count->execute();
                    $cart_count_result = $stmt_cart_count->get_result();
                    $cart_count = $cart_count_result->fetch_assoc()['total_items'];
                    $stmt_cart_count->close();
                }
                ?>
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
            <div class="phone-menu-content" id="phoneMenuToggle"><span class="material-symbols-outlined">dehaze</span></div>
            <ul class="drop-menu" id="dropdownMenu">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="user_hotel_all.php">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="user_booking.php">Profile</a></li>
                <li><a href="home.php">Sign Out</a></li>
                <li><a href="#">Help Center</a></li>
            </>
        </div>
        
    </header>
    <nav class="container mt-4">
        <div class="home-page">
            <div class="main-content">
                <div class="text-content">
                    <h1>Forget Busy Work,<br>
                        Start Next Vacation</h1>
                    <p>We provide what you need to enjoy your <br>
                        holiday with family. Time to make another <br>
                        memorable moments.</p>
                </div>
                <!-- <a href="owner_signup.html" class="btn">Register</a> -->
            </div>
            <div class="home-img">
                <img src="image/banner.png" alt="">
            </div>
        </div>

        <div class="search-container">
            <form action="user_hotel_cat.php" method="GET">
                <div class="select-bar">
                    <div class="search-field">
                        <span class="material-symbols-outlined">location_on</span>
                        <select name="province" id="province" title="Select Province">
                            <option value="">Select Province:</option>
                            <option value="Banteay Meanchey">Banteay Meanchey</option>
                            <option value="Battambang">Battambang</option>
                            <option value="Kampong Cham">Kampong Cham</option>
                            <option value="Kampong Chhnang">Kampong Chhnang</option>
                            <option value="Kampong Speu">Kampong Speu</option>
                            <option value="Kampong Thom">Kampong Thom</option>
                            <option value="Kampot">Kampot</option>
                            <option value="Kandal">Kandal</option>
                            <option value="Kep">Kep</option>
                            <option value="Kratie">Kratie</option>
                            <option value="Koh Kong">Koh Kong</option>
                            <option value="Siem Reap">Siem Reap</option>
                            <option value="Mondulkiri">Mondulkiri</option>
                            <option value="Oddar Meanchey">Oddar Meanchey</option>
                            <option value="Pailin">Pailin</option>
                            <option value="Phnom Penh">Phnom Penh</option>
                            <option value="Preah Vihear">Preah Vihear</option>
                            <option value="Prey Veng">Prey Veng</option>
                            <option value="Pursat">Pursat</option>
                            <option value="Ratanakiri">Ratanakiri</option>
                            <option value="Sihanoukville">Sihanoukville</option>
                            <option value="Stung Treng">Stung Treng</option>
                            <option value="Svay Rieng">Svay Rieng</option>
                            <option value="Takeo">Takeo</option>
                            <option value="Tboung Khmum">Tboung Khmum</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn1">Search</button>
            </form>
        </div>
        <div class="discount-content">
            <h2>Discounts</h2>
            <div id="underline-discount"></div>
        </div>

       
    <div class="card-container">
        <?php
        include 'db_conn.php'; // Include your database connection

        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Fetch hotels with discounts
        $query_discounts = "
            SELECT 
                h.hotel_id, 
                h.hotel_name, 
                h.hotel_location, 
                h.hotel_image_url, 
                d.discount_percentage, 
                MIN(r.price_per_night) AS price_per_night
            FROM hotels h
            INNER JOIN discounts d ON h.hotel_id = d.hotel_id 
            LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
            WHERE d.discount_percentage IS NOT NULL
            AND r.price_per_night IS NOT NULL 
            GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, d.discount_percentage
            ORDER BY d.discount_percentage DESC
            LIMIT 6;
        ";
        $result_discounts = $conn->query($query_discounts);

        if ($result_discounts->num_rows > 0) {
            while ($row = $result_discounts->fetch_assoc()) {
                echo '<div class="col-md-20">';
                echo '<div class="card">';

                $originalPrice = $row['price_per_night'] ?? 0;
                    $discountPercentage = $row['discount_percentage'] ?? 0;

                    if (is_numeric($originalPrice) && is_numeric($discountPercentage)) {
                        $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                    } else {
                        $originalPrice = 0;
                        $discountedPrice = 0;
                    }
                echo '<div class="discount-badge">';
                if (!empty($discountPercentage)) {
                    echo '<div class="discount_percent"> <span>' . htmlspecialchars($discountPercentage) . '% OFF</span></div>';
                    echo '<div class="discounted-price">$' . number_format($discountedPrice, 2) . '</div>';
                    echo '<div class="original-price"><s>$' . number_format($originalPrice, 2) . '</s></div>';
                } else {
                    echo '<div class="normal-price">$' . number_format($originalPrice, 2) . '</div>';
                }
                echo '</div>';

                echo '<a href="user_hotel_detail.php?hotel_id=' . htmlspecialchars($row['hotel_id']) . '">';
                echo '<img src="' . htmlspecialchars($row['hotel_image_url']) . '" alt="Hotel Image">';
                echo '</a>';
                
                // Favorite button - uses 'favorite-btn' class
                echo '<button class="favorite-btn" id="favorite-btn-' . htmlspecialchars($row['hotel_id']) . '" data-hotel-id="' . htmlspecialchars($row['hotel_id']) . '">★</button>';
                
                echo '<div class="card-content">';
                echo '<h3>' . htmlspecialchars($row['hotel_name']) . '</h3>';
                echo '<p> <span class="material-symbols-outlined">location_on</span>' . htmlspecialchars($row['hotel_location']) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>No hotels with discounts found.</p>";
        }
        ?>
    </div>

    <div class="view-all">
        <button class="btn-view-all" onclick="location.href='user_view_dis.php'">View more</button>
    </div>

    <div class="popular-places">
        <h2>Popular Places</h2>
        <div id="underline-popular"></div>
    </div>

    <div class="card2-container">
        <?php
        // Fetch hotels ordered by likes and get the minimum room price
        $query_popular = "
            SELECT 
                h.hotel_id, 
                h.hotel_name, 
                h.hotel_location, 
                h.hotel_image_url, 
                MIN(r.price_per_night) AS min_price, 
                h.total_likes 
            FROM hotels h
            INNER JOIN rooms r ON h.hotel_id = r.hotel_id
            GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, h.total_likes
            ORDER BY h.total_likes DESC
            LIMIT 8;
        ";
        $result_popular = $conn->query($query_popular);

        if ($result_popular->num_rows > 0) {
            while ($row = $result_popular->fetch_assoc()) {
                echo '<div class="col-md-15">';
                echo '<div class="card2">';
                echo '<div class="price-badge">$' . htmlspecialchars($row['min_price']) . ' per night</div>';
                echo '<a href="user_hotel_detail.php?hotel_id=' . htmlspecialchars($row['hotel_id']) . '">';
                echo '<img src="' . htmlspecialchars($row['hotel_image_url']) . '" alt="Hotel Image">';
                echo '</a>';
                echo '<div class="card2-content">';
                echo '<h3>' . htmlspecialchars($row['hotel_name']) . '</h3>';
                echo '<p> <span class="material-symbols-outlined">location_on</span>' . htmlspecialchars($row['hotel_location']) . '</p>';
                echo '</div>';
                // Favorite button - uses 'favorite-btn' class for consistency
                echo '<button class="favorite-btn" id="favorite-btn-' . htmlspecialchars($row['hotel_id']) . '" data-hotel-id="' . htmlspecialchars($row['hotel_id']) . '">★</button>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>No more popular hotels available.</p>";
        }

        $conn->close();
        ?>
    </div>

    <div class="view-all">
        <button class="btn-view-all" onclick="location.href='user_view_pop.php'">View more</button>
    </div>

    <!-- JAVASCRIPT FOR FAVORITE BUTTONS -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Get user ID from PHP session, safely
        const userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;

        // Function to toggle favorite status via AJAX
        function toggleFavorite(hotelId, buttonElement) {
            if (userId === null) {
                alert("You must be logged in to add favorites.");
                window.location.href = "user_signin.php"; // Redirect to login page
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "add_favorite.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = xhr.responseText;
                    console.log("Server response from add_favorite.php:", response); // For debugging

                    if (response === "added") {
                        buttonElement.classList.add("active");
                        alert("Hotel added to favorites!");
                    } else if (response === "removed") {
                        buttonElement.classList.remove("active");
                        alert("Hotel removed from favorites!");
                    } else if (response === "not_logged_in") {
                        alert("You must be logged in to add favorites.");
                        window.location.href = "user_signin.php";
                    } else {
                        console.error("Unexpected response from add_favorite.php:", response);
                        alert("An unexpected error occurred. Please check console.");
                    }
                } else {
                    console.error("HTTP error from add_favorite.php:", xhr.status, xhr.statusText);
                    alert("An error occurred while processing your request (HTTP " + xhr.status + ").");
                }
            };

            xhr.onerror = function() {
                console.error("Network error during AJAX request to add_favorite.php.");
                alert("A network error occurred. Please try again.");
            };

            xhr.send(`hotel_id=${hotelId}`); // Send hotel ID to the server
        }

        // Attach event listeners to all favorite buttons
        const favoriteButtons = document.querySelectorAll(".favorite-btn");
        favoriteButtons.forEach(button => {
            button.addEventListener("click", function() {
                const hotelId = this.getAttribute("data-hotel-id");
                if (hotelId) {
                    toggleFavorite(hotelId, this); // Pass hotel ID and the button element
                } else {
                    console.error("Hotel ID not found on button:", this);
                }
            });
        });

        // Function to initialize favorite button states on page load
        function initializeFavoriteStates() {
            if (userId === null) {
                console.log("User not logged in. Skipping favorite state initialization.");
                return;
            }

            // Fetch current user's favorite hotel IDs from the server
            fetch('get_favorites.php') 
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json(); // Expecting JSON response
                })
                .then(data => {
                    if (data.success && data.favorites) {
                        data.favorites.forEach(hotelId => {
                            const button = document.getElementById('favorite-btn-' + hotelId);
                            if (button) {
                                button.classList.add('active'); // Add the 'active' class for favorited state
                            }
                        });
                    } else {
                        console.warn("Failed to retrieve favorite states:", data.message || "Unknown error");
                    }
                })
                .catch(error => console.error('Error fetching favorite states:', error));
        }

        // Call the initialization function when the DOM is ready
        initializeFavoriteStates();
    });
    </script>

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
                    <li><a href="#myaccount">My acount</a></li>
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
                <a href="#facebook" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                <a href="#telegram" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
                <a href="instragram" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="youtube" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </footer>
    </div>
    <script src="menu-bar.js"></script>
</body>

</html>