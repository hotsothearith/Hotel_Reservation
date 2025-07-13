<?php
// Database connection
$sname = "localhost";
$uname = "root";
$pass = "";
$db_name = "hotel-reservations";

$conn = mysqli_connect($sname, $uname, $pass, $db_name);

// Check connection
if (!$conn) {
    echo "Connection failed!";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Category</title>
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
                <li><a href="hotel_display_all.php">Hotels</a></li>
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
            <div class="phone-menu-content" id="phoneMenuToggle">
                <span class="material-symbols-outlined">dehaze</span>
            </div>
            <ul class="drop-menu" id="dropdownMenu"> <!-- Changed div to ul for semantic correctness -->
                <li><a href="#" class="active">Home</a></li>
                <li><a href="hotel_display_all.php">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="user_signup.html">Sign Up</a></li>
                <li><a href="user_signin.html">Sign In</a></li>
                <li><a href="owner_signup.html" id="owner-btn">As Owner Hotel/House</a></li>
                <li><a href="#">Help Center</a></li>
            </ul>
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
            </div>
            <div class="home-img">
                <img src="image/banner.png" alt="">
            </div>
        </div>

        <div class="search-container">
            <form action="hotel_display_cat.php" method="GET">
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


        <div class="popular-places">
            <?php
            // Get the selected province from the URL
            $province = isset($_GET['province']) ? $_GET['province'] : '';

            if ($province) {
                // Ensure database connection is established
                if ($conn) {
                    // Fetch the most liked hotel based on the selected province
                    $query = "SELECT hotel_location FROM hotels WHERE hotel_location = ? ";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('s', $province); // Bind the province parameter (hotel_location)
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo "<h2>" . htmlspecialchars($row['hotel_location']) . "</h2>";
                    } else {
                        echo "<h2>No popular hotels found in the selected location.</h2>"; // Fallback message
                    }
                } else {
                    echo "<h2>Database connection error.</h2>"; // Error message for connection issues
                }
            } else {
                echo "<h2>Please select a province.</h2>"; // Message if no province is selected
            }
            ?>
             <div id="underline-discount"></div>
        </div>

        <div class="card2-container">
            <?php
            // Get selected province
            $province = isset($_GET['province']) ? $_GET['province'] : '';

            // Fetch hotels ordered by likes and get the minimum room price
            $query = "  SELECT h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, h.total_likes, 
                        COALESCE(MIN(r.price_per_night), 0) AS min_price 
                        FROM hotels h
                        LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
                        WHERE h.hotel_location = ?
                        GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, h.total_likes
                        ORDER BY h.total_likes DESC, min_price ASC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $province);
            $stmt->execute();
            $result = $stmt->get_result();

            // Display the next hotels for "Popular Places"
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-15">'; // 4 cards per row on medium screens and above
                    echo '<div class="card2">';
                    echo '<div class="price-badge">$' . htmlspecialchars($row['min_price']) . ' per night</div>'; // Display the minimum room price
                    echo '<a href="hotel_detail.php?hotel_id=' . htmlspecialchars($row['hotel_id']) . '">';
                    echo '<img src="' . htmlspecialchars($row['hotel_image_url']) . '" alt="Hotel Image">';
                    echo '</a>';
                    echo '<div class="card2-content">';
                    echo '<h3>' . htmlspecialchars($row['hotel_name']) . '</h3>';
                    echo '<p> <span class="material-symbols-outlined">location_on</span>' . htmlspecialchars($row['hotel_location']) . '</p>';
                    echo '<button class="favor-btn">★</button>';
                    echo '</div>';
                    
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p>No more hotels available.</p>";
            }

            $conn->close();
            ?>
        </div>

    </nav>
    <script src="menu-bar.js"></script>

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
</body>

</html>