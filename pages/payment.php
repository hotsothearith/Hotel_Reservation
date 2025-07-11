<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php"); // Adjust path if needed
    exit();
}

// Retrieve cart items from session (set in user_add_to_cart.php)
if (!isset($_SESSION['checkout_cart_items']) || empty($_SESSION['checkout_cart_items'])) {
    $_SESSION['message'] = "No items in checkout queue. Please add items to cart and proceed to checkout.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user_add_to_cart.php"); // Redirect to cart page
    exit();
}

$cart_items = $_SESSION['checkout_cart_items'];

// Get overall total cost from POST (sent from booking_info.php)
$overallTotal = filter_input(INPUT_POST, 'overall_total_cost', FILTER_VALIDATE_FLOAT);

if ($overallTotal === false || $overallTotal <= 0) {
    $_SESSION['message'] = "Invalid total amount for payment.";
    $_SESSION['message_type'] = "error";
    header("Location: booking_info.php"); // Go back to review if total is bad
    exit();
}

$initialPayment = $overallTotal / 2; // Assuming 50% initial payment

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="header-logo">
        <img src="../image/NokorRealm.png" alt="NokorRealm Logo">
    </header>

    <div class="payment-step">
        <img src="images/stepper2.png" alt="Payment Step 2">
    </div>

    <div class="main-content-wrapper">
        <div class="page-title">
            <h1>Payment Details</h1>
            <p>Please enter your payment information below.</p>
            <?php
            if (isset($_SESSION['message'])) {
                $message_type = htmlspecialchars($_SESSION['message_type'] ?? 'info');
                $message = htmlspecialchars($_SESSION['message']);
                echo "<div class='alert alert-$message_type'>$message</div>";
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>
        </div>

        <div class="payment-overview-section">
            <h3>Your Booking Summary</h3>
            <div class="summary-details">
                <?php foreach ($cart_items as $index => $item):
                    $nights = max(1, (new DateTime($item['check_in_date']))->diff(new DateTime($item['check_out_date']))->days);
                    $item_subtotal = $item['price_at_addition'] * $nights;
                ?>
                    <h4>Item #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($item['hotel_name']); ?> - <?php echo htmlspecialchars($item['room_type']); ?></h4>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <span><?= htmlspecialchars($item['hotel_location']) ?></span></p>
                    <p><i class="far fa-calendar-alt"></i> <strong>Check-in:</strong> <span><?= htmlspecialchars($item['check_in_date']) ?></span></p>
                    <p><i class="far fa-calendar-alt"></i> <strong>Check-out:</strong> <span><?= htmlspecialchars($item['check_out_date']) ?></span></p>
                    <p><i class="fas fa-moon"></i> <strong>Nights:</strong> <span><?= $nights ?></span></p>
                    <p><strong>Subtotal:</strong> <span>$<?= number_format($item_subtotal, 2) ?> USD</span></p>
                    <?php if ($index < count($cart_items) - 1): ?><hr><?php endif; ?>
                <?php endforeach; ?>
                <hr>
                <p class="total-amount">Overall Total Cost: <span>$<?= number_format($overallTotal, 2) ?> USD</span></p>
                <!-- <p class="initial-payment">Initial Payment Due: <span>$<?= number_format($initialPayment, 2) ?> USD</span></p> -->
            </div>
        </div>

        <div class="payment-form-section">
            <h3>Payment Information</h3>
            <form action="payment_verify.php" method="POST" class="payment-form">
                <input type="hidden" name="overall_total_cost" value="<?= $overallTotal ?>">
                <input type="hidden" name="initial_payment" value="<?= $initialPayment ?>">
                <div class="form-group">
                    <label for="card-number"><i class="far fa-credit-card"></i> Card Number <span style="color:red">*</span></label>
                    <input type="text" id="card-number" name="card_number" placeholder="XXXX XXXX XXXX XXXX"
                        pattern="\d{15,20}" maxlength="20" minlength="15" required inputmode="numeric" title="Please enter 15-20 digits">
                </div>

                <div class="form-group">
                    <label for="bank-type"><i class="fas fa-university"></i> Bank Type <span style="color:red">*</span></label>
                    <select name="bank_type" id="bank-type" required>
                        <option value="">Select Bank</option>
                        <option value="MasterCard">MasterCard</option>
                        <option value="Visa">Visa</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry-date"><i class="far fa-calendar-alt"></i> Expiry Date <span style="color:red">*</span></label>
                        <input type="month" min="<?= date('Y-m') ?>" id="expiry-date" name="expiry_date" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv"><i class="fas fa-lock"></i> CVV <span style="color:red">*</span></label>
                        <input type="text" id="cvv" name="cvv" placeholder="3 or 4 digits" pattern="\d{3,4}" maxlength="4" minlength="3" required inputmode="numeric" title="Please enter 3 or 4 digits">
                    </div>
                </div>

                <p class="security-info"><i class="fas fa-lock"></i> Your payment is secure and encrypted.</p>

                <div class="btn-group">
                    <button type="submit" class="primary-btn-lg">Pay Now</button>
                    <a href="booking_info.php" class="secondary-btn">Back to Booking Info</a> </div>
            </form>
        </div>
    </div>
</body>
</html>