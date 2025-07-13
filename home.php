<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
    <nav class="container-xl mt-4">
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

        <div class="discount-content">
            <h2>Discounts</h2>
            <div id="underline-discount"></div>
        </div>

        <div class="card-container">
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

            // Query to fetch hotels
            $sql = "SELECT hotel_id, hotel_name, hotel_location, hotel_image_url FROM hotels";
            $result = $conn->query($sql);

            // Fetch hotels with discounts
            $query = " 
            SELECT 
            h.hotel_id, 
            h.hotel_name, 
            h.hotel_location, 
            h.hotel_image_url, 
            d.discount_percentage, 
            MIN(r.price_per_night) AS price_per_night  -- Ensures the lowest room price is selected
            FROM hotels h
            INNER JOIN discounts d ON h.hotel_id = d.hotel_id  -- Ensures hotels have discounts
            INNER JOIN rooms r ON h.hotel_id = r.hotel_id      -- Ensures hotels have at least one room
            GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, d.discount_percentage
            ORDER BY d.discount_percentage DESC
            LIMIT 6;
            ";
            // Fetch only the first 6 hotels with discounts

            $result = $conn->query($query);

            // Display the first 6 hotels
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-20">'; // 3 cards per row on medium screens and above
                    echo '<div class="card">';

                    $originalPrice = $row['price_per_night'];
                    $discountPercentage = $row['discount_percentage'];
                    $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));

                    echo '<div class="discount-badge">';
                    if (!empty($discountPercentage)) {
                        echo '<div> <span class="discount-percent">' . htmlspecialchars($discountPercentage) . '% OFF</span></div>';
                        echo '<div class="current-price">$' . number_format($discountedPrice, 2) . '</div>';
                        echo '<div class="original-price"><s>$' . number_format($originalPrice, 2) . '</s></div>';
                    } else {
                        echo '<div class="current-price">$' . number_format($originalPrice, 2) . '</div>';
                    }
                    echo '</div>';

                    echo '<a href="hotel_detail.php?hotel_id=' . htmlspecialchars($row['hotel_id']) . '">'; // Link to hotel detail page
                    echo '<img src="' . htmlspecialchars($row['hotel_image_url']) . '" alt="Hotel Image">';
                     echo '</a>';
                   

                    // Favorite button
                    echo '<button class="favorite-btn" id="favorite-btn-' . htmlspecialchars($row['hotel_id']) . '" onclick="toggleFavorite(' . htmlspecialchars($row['hotel_id']) . ')">★</button>';

                    
                    echo '<div class="card-content">';
                    echo '<h3>' . htmlspecialchars($row['hotel_name']) . '</h3>';
                    echo '<p>  <span class="material-symbols-outlined">
                            location_on
                            </span>' . htmlspecialchars($row['hotel_location']) . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p>No hotels found.</p>";
            }

            // Reset result pointer for the next section
            $result->data_seek(6);
            ?>
        </div>

        <div class="view-all">
            <button class="btn-view-all" onclick="location.href='view_dis.php'">View more</button>
        </div>

        <div class="popular-places">
            <h2>Popular Places</h2>
            <div id="underline-popular"></div>
        </div>

        <div class="card2-container">
            <?php
            // Fetch hotels ordered by likes and get the minimum room price
            $query = "
            SELECT 
            h.hotel_id, 
            h.hotel_name, 
            h.hotel_location, 
            h.hotel_image_url, 
            MIN(r.price_per_night) AS min_price, 
            h.total_likes 
            FROM hotels h
            INNER JOIN rooms r ON h.hotel_id = r.hotel_id  -- Ensures hotel has at least one room
            GROUP BY h.hotel_id, h.hotel_name, h.hotel_location, h.hotel_image_url, h.total_likes
            ORDER BY h.total_likes DESC
            LIMIT 8;
            "; // Get the top 8 most liked hotels

            $result = $conn->query($query);

            // Display the next 8 hotels for "Popular Places"
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-15">'; // 4 cards per row on medium screens and above                  
                    echo '<div class="card2">';
                    echo '<div class="price-badge">$' . htmlspecialchars($row['min_price']) . ' per night</div>'; // Display the minimum room price
                    echo '<a href="hotel_detail.php?hotel_id=' . htmlspecialchars($row['hotel_id']) . '">'; // Link to hotel detail page
                    echo '<img src="' . htmlspecialchars($row['hotel_image_url']) . '" alt="Hotel Image">';
                    echo '</a>';
                    echo '<div class="card2-content">';
                    echo '<h3>' . htmlspecialchars($row['hotel_name']) . '</h3>';
                    echo '<p>  <span class="material-symbols-outlined">
                            location_on
                            </span>' . htmlspecialchars($row['hotel_location']) . '</p>';
                    echo '</div>';
                    echo '<button class="favorite-btn" id="favorite-btn-' . htmlspecialchars($row['hotel_id']) . '" onclick="toggleFavorite(' . htmlspecialchars($row['hotel_id']) . ')">★</button>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p>No more hotels available.</p>";
            }

            $conn->close();
            ?>
        </div>

        <div class="view-all">
            <button class="btn-view-all" onclick="location.href='view_pop.php'">View more</button>
        </div>

    </nav>

    <hr class="divider">
    <div class="container-lg">
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
    <script src="AddToFavorite-Funciton.js">
       
    </script>
   <script src="menu-bar.js"></script>
</body>

</html>