<?php
session_start();

error_reporting(E_ALL); // For development: display all errors
ini_set('display_errors', 1); // For development: display errors in browser

// Include the database connection file
include 'db_conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php"); // Assuming user_signin.php is correct
    exit();
}

$user_id = $_SESSION['user_id'];
$total_cart_price = 0; // Initialize total price

// Handle item removal from cart
if (isset($_POST['remove_cart_item'])) {
    $cart_id_to_remove = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

    if ($cart_id_to_remove) {
        $stmt = $conn->prepare("DELETE FROM carts WHERE cart_id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $cart_id_to_remove, $user_id);

            if ($stmt->execute()) {
                // $_SESSION['message'] = "Item removed from cart successfully.";
                $_SESSION['message_type'] = "success";
                header("Location: user_add_to_cart.php");
                exit();
            } else {
                $_SESSION['message'] = "Error removing item from cart: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error: Could not prepare statement for removal.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid cart item ID provided.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: user_add_to_cart.php");
    exit();
}

// Handle "Proceed to Checkout" for all items
if (isset($_POST['proceed_to_checkout_all'])) {
    $cart_items_for_checkout = [];
    $sql_fetch_all_cart_items = "SELECT
                                    c.cart_id,
                                    c.room_id,
                                    c.check_in_date,
                                    c.check_out_date,
                                    c.number_of_guests,
                                    c.price_at_addition,
                                    r.room_type,
                                    r.room_image,
                                    h.hotel_name,
                                    h.hotel_location,
                                    h.hotel_image_url,
                                    DATEDIFF(c.check_out_date, c.check_in_date) AS duration_days
                                FROM
                                    carts c
                                JOIN
                                    rooms r ON c.room_id = r.room_id
                                JOIN
                                    hotels h ON r.hotel_id = h.hotel_id
                                WHERE
                                    c.user_id = ?
                                ORDER BY
                                    c.added_at ASC"; // Order by added_at to maintain some consistency

    $stmt_fetch_all = $conn->prepare($sql_fetch_all_cart_items);
    if ($stmt_fetch_all) {
        $stmt_fetch_all->bind_param("i", $user_id);
        $stmt_fetch_all->execute();
        $result_all = $stmt_fetch_all->get_result();

        if ($result_all->num_rows > 0) {
            while ($row_all = $result_all->fetch_assoc()) {
                // Calculate item total price here to pass it to the next page
                $duration_days_all = max(1, (int)$row_all['duration_days']);
                $row_all['calculated_item_total'] = $row_all['price_at_addition'] * $duration_days_all;
                $cart_items_for_checkout[] = $row_all;
            }
        }
        $stmt_fetch_all->close();
    }

    if (!empty($cart_items_for_checkout)) {
        $_SESSION['checkout_cart_items'] = $cart_items_for_checkout;
        header("Location: pages/booking_info.php"); // Redirect to booking_info.php with data in session
        exit();
    } else {
        $_SESSION['message'] = "Your cart is empty. Nothing to checkout.";
        $_SESSION['message_type'] = "info";
        header("Location: user_add_to_cart.php");
        exit();
    }
}


// Query to fetch cart items for the logged-in user for display
$cart_items_display = []; // This array is for displaying on the current page
$sql_display = "SELECT
                    c.cart_id,
                    c.room_id,
                    c.check_in_date,
                    c.check_out_date,
                    c.number_of_guests,
                    c.price_at_addition,
                    r.room_type,
                    r.room_image,
                    h.hotel_name,
                    DATEDIFF(c.check_out_date, c.check_in_date) AS duration_days
                FROM
                    carts c
                JOIN
                    rooms r ON c.room_id = r.room_id
                JOIN
                    hotels h ON r.hotel_id = h.hotel_id
                WHERE
                    c.user_id = ?
                ORDER BY
                    c.added_at DESC";

$stmt_display = $conn->prepare($sql_display);
if ($stmt_display) {
    $stmt_display->bind_param("i", $user_id);
    $stmt_display->execute();
    $result_display = $stmt_display->get_result();

    if ($result_display->num_rows > 0) {
        while ($row_display = $result_display->fetch_assoc()) {
            $cart_items_display[] = $row_display;
        }
    }
    $stmt_display->close();
} else {
    $_SESSION['message'] = "Error fetching cart items for display: " . $conn->error;
    $_SESSION['message_type'] = "error";
}

// Calculate total cart price for display
foreach ($cart_items_display as $item) {
    $duration_days = max(1, (int)$item['duration_days']);
    $item_total_price = $item['price_at_addition'] * $duration_days;
    $total_cart_price += $item_total_price;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Booking Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="add_to_cart.css">
</head>
<body>
     <header class="navbar">
        <div class="header-content">
            
            <div class="logo">
                <img src="image/NokorRealm.png" alt="">
            </div>
            <ul class="menu-content">
                <li><a href="user_home.php">Home</a></li>
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
        <div class="phone-menu">
            <button><span class="material-symbols-outlined">dehaze</span></button>
            <div class="drop-menu">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="user_hotel_all.php">Hotels</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="user_booking.php">Profile</a></li>
                <li><a href="home.php">SignOut</a></li>
                <li><a href="#">HelpCenter</a></li>
            </div>
        </div>
        
    </header>
    <h1>Your Booking Carts</h1>
    <div class="container">
        <?php
        // Display session messages (from add_to_cart_process.php or remove action)
        if (isset($_SESSION['message'])) {
            $message_type = htmlspecialchars($_SESSION['message_type'] ?? 'info');
            $message = htmlspecialchars($_SESSION['message']);
            echo "<div class='alert alert-$message_type'>$message</div>";
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="card-container">
            <?php
            if (!empty($cart_items_display)) {
                foreach ($cart_items_display as $row) {
                    $duration_days = max(1, (int)$row['duration_days']);
                    $item_total_price = $row['price_at_addition'] * $duration_days;
            ?>
              <!-- <?php
            // Debugging line - ADD THIS
            echo "<p>DEBUG PATH 1: image/" . htmlspecialchars($row['room_image']) . "</p>";
            ?> -->
                        <div class="booking-card">
                            <img src="<?php echo htmlspecialchars($row['room_image']); ?>" alt="Hotel Image" class="room-image">
                            <div class="room-details">
                                <h2><?php echo htmlspecialchars($row['hotel_name']); ?> - <?php echo htmlspecialchars($row['room_type']); ?></h2>
                                <p>
                                    <strong>Check-in:</strong> <?php echo htmlspecialchars($row['check_in_date']); ?>
                                    <strong>Check-out:</strong> <?php echo htmlspecialchars($row['check_out_date']); ?>
                                    <strong>Duration:</strong> <?php echo htmlspecialchars($duration_days); ?> Night(s)
                                    <strong>Guests:</strong> <?php echo htmlspecialchars($row['number_of_guests']); ?>
                                </p>
                                <p id="price">$<?php echo number_format($item_total_price, 2); ?> USD</p>
                                <small>($<?php echo number_format($row['price_at_addition'], 2); ?> per night)</small>
                            </div>
                            <div class="actions">
                                <form method="POST" action="user_add_to_cart.php">
                                    <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($row['cart_id']); ?>">
                                    <button type="submit" name="remove_cart_item" class="remove-btn">Remove</button>
                                </form>
                                </div>
                        </div>
            <?php
                }
            } else {
                echo "<p>Your cart is empty.</p>";
            }
            ?>
        </div>
    </div>
    <?php if ($total_cart_price > 0): ?>
        <div class="total-price-section">
            Total: <span>$<?php echo number_format($total_cart_price, 2); ?> USD</span>
        </div>
    <?php endif; ?>

    <div class="booking-btn">
        <?php if (!empty($cart_items_display)): // Only show booking button if cart is not empty ?>
            <form method="POST" action="user_add_to_cart.php">
                <button type="submit" name="proceed_to_checkout_all" class="primary-btn-lg">Proceed to Checkout All Items</button>
            </form>
        <?php endif; ?>
    </div>

    <?php $conn->close(); ?>
</body>
</html>