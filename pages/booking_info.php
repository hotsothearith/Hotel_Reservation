<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_conn.php';
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php"); // Adjust path if needed
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if there's cart data in the session for checkout
if (!isset($_SESSION['checkout_cart_items']) || empty($_SESSION['checkout_cart_items'])) {
    $_SESSION['message'] = "No items found in cart for checkout. Please add items first.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user_add_to_cart.php"); // Redirect back to cart if no items
    exit();
}

$cart_items = $_SESSION['checkout_cart_items'];
$overall_total_cost = 0; // Initialize overall total for all items
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Information</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="header-logo">
        <img src="../image/NokorRealm.png" alt="NokorRealm Logo">
    </header>

    <div class="payment-step">
        <img src="images/stepper1.png" alt="Booking Step 1">
    </div>

    <div class="main-content-wrapper">
        <div class="page-title">
            <h1>Booking Information</h1>
            <p>Please review your booking details below:</p>
            <?php
            if (isset($_SESSION['message'])) {
                $message_type = htmlspecialchars($_SESSION['message_type'] ?? 'info');
                $message = htmlspecialchars($_SESSION['message'] ?? '');
                echo "<div class='alert alert-$message_type'>$message</div>";
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>
        </div>

        <div class="booking-summary-card">
            <?php foreach ($cart_items as $index => $item):
                $nights = max(1, (new DateTime($item['check_in_date']))->diff(new DateTime($item['check_out_date']))->days);
                $item_total = $item['price_at_addition'] * $nights;
                $overall_total_cost += $item_total;
            ?>
            <?php
                // Debugging line - ADD THIS
                // echo "<p>DEBUG PATH 2: ../image/" . htmlspecialchars($item['room_image'] ?? '') . "</p>";
                // ?>
                <div class="booking-item">
                    <div class="hotel-info-section">
                        <img src="../<?= htmlspecialchars($item['room_image'] ?? '') ?>" alt="Room Image" class="hotel-room-image">
                        <div class="hotel-text-info">
                            <h2><?= htmlspecialchars($item['hotel_name'] ?? '') ?> - <?= htmlspecialchars($item['room_type'] ?? '') ?></h2>
                            <p class="hotel-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['hotel_location'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="booking-details-section">
                        <h3>Details for Item #<?= $index + 1 ?></h3>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-bed"></i> Room Type:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['room_type'] ?? '') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="far fa-calendar-alt"></i> Check-in:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['check_in_date'] ?? '') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="far fa-calendar-alt"></i> Check-out:</span>
                            <span class="detail-value"><?= htmlspecialchars($item['check_out_date'] ?? '') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-moon"></i> Nights:</span>
                            <span class="detail-value"><?= $nights ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Price per night:</span>
                            <span class="detail-value">$<?= number_format($item['price_at_addition'] ?? 0, 2) ?> USD</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Item Subtotal:</span>
                            <span class="detail-value">$<?= number_format($item_total, 2) ?> USD</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="price-summary-section">
                <h3>Overall Summary</h3>
                <p class="total-cost">Total for all items: <span>$<?= number_format($overall_total_cost, 2) ?> USD</span></p>
            </div>
        </div>

        <div class="btn-group">
            <form action="payment.php" method="POST">
                <input type="hidden" name="overall_total_cost" value="<?= $overall_total_cost ?>">
                <button type="submit" class="primary-btn-lg">Proceed to Payment</button>
            </form>
            <button type="button" class="secondary-btn" onclick="window.location.href='../user_add_to_cart.php'">Back to Cart</button>
        </div>
    </div>
</body>
</html>
