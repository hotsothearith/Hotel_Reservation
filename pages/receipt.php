<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../db_conn.php'; // Ensure this path is correct

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php"); // Adjust path if needed
    exit();
}

// Redirect if booking data for receipt is missing from session
if (!isset($_SESSION['booking_receipt_data']) || empty($_SESSION['booking_receipt_data'])) {
    $_SESSION['message'] = "No receipt data found. Please complete a booking first.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user_home.php"); // Redirect to home or cart
    exit();
}

$booking_receipt_data = $_SESSION['booking_receipt_data'];

// Assign overall variables from session data
$overallTotalCost = $booking_receipt_data['total_cost'] ?? 0;
$initialPayment = $booking_receipt_data['initial_payment'] ?? 0;
$paymentMethod = htmlspecialchars($booking_receipt_data['payment_method_detail'] ?? 'N/A');
$refNumber = htmlspecialchars($booking_receipt_data['ref_number'] ?? 'N/A');
$senderName = htmlspecialchars($_SESSION['username'] ?? 'Guest'); // Assuming username is in session

// Display time for receipt
$displayTime = date('d-m-Y, H:i:s');

// Get individual items from the receipt data
$booked_items = $booking_receipt_data['items'] ?? [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css" />
    <style>
        /* Styles for receipt */
        .receipt-container {
            max-width: 700px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .receipt-header .header-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        .receipt-header h1 {
            color: #28a745; /* Success green */
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .receipt-header p {
            color: #555;
            font-size: 1.1em;
        }
        .receipt-details-section {
            border-top: 1px dashed #ddd;
            padding-top: 20px;
            margin-top: 20px;
        }
        .receipt-details-section h2 {
            font-size: 1.6em;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #eee;
        }
        .receipt-row:last-child {
            border-bottom: none;
        }
        .receipt-row .label {
            font-weight: 500;
            color: #555;
        }
        .receipt-row .value {
            font-weight: bold;
            color: #333;
            text-align: right;
        }
        .receipt-item-details {
            margin-top: 25px;
            border-top: 1px dashed #ddd;
            padding-top: 20px;
        }
        .receipt-item-details h3 {
            font-size: 1.4em;
            margin-bottom: 15px;
            color: #444;
            text-align: center;
        }
        .receipt-item {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .receipt-item:last-child {
            border-bottom: none;
        }
        .receipt-item p {
            margin: 5px 0;
            font-size: 0.95em;
        }
        .receipt-item strong {
            color: #333;
        }
        .receipt-item .item-subtotal {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1em;
            text-align: right;
            margin-top: 10px;
        }
        .receipt-total-section {
            border-top: 2px solid #ccc;
            padding-top: 20px;
            margin-top: 30px;
            text-align: right;
            font-size: 1.5em;
            font-weight: bold;
            color: #007bff;
        }
        .btn-group {
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header class="header-logo">
        <img src="../image/NokorRealm.png" alt="NokorRealm Logo" />
    </header>
         <div class="payment-step">
        <img src="image/stepper3.png" alt="Booking Step 3">
    </div>
    <div class="main-content-wrapper receipt-container">
        <div class="receipt-header">
            <img src="image/Success Icon.png" alt="Success Icon" class="header-icon" />
            <h1>Booking Confirmed!</h1>
            <p>Thank you for your payment.</p>
        </div>

        <div class="receipt-details-section">
            <h2>Payment Details</h2>
            <div class="receipt-row">
                <span class="label">Reference Number:</span>
                <span class="value"><?= $refNumber ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Payment Date & Time:</span>
                <span class="value"><?= $displayTime ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Payment Method:</span>
                <span class="value"><?= $paymentMethod ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Paid By:</span>
                <span class="value"><?= $senderName ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Initial Payment:</span>
                <span class="value">$<?= number_format($initialPayment, 2) ?> USD</span>
            </div>
            <div class="receipt-row">
                <span class="label">Remaining Due:</span>
                <span class="value">$<?= number_format($overallTotalCost - $initialPayment, 2) ?> USD</span>
            </div>
        </div>

        <div class="receipt-item-details">
            <h3>Booked Items</h3>
            <?php if (!empty($booked_items)): ?>
                <?php foreach ($booked_items as $index => $item):
                    $nights = max(1, (new DateTime($item['check_in_date']))->diff(new DateTime($item['check_out_date']))->days);
                    $item_subtotal = $item['price_at_addition'] * $nights;
                ?>
                    <div class="receipt-item">
                        <h4><?= htmlspecialchars($item['hotel_name']); ?> - <?= htmlspecialchars($item['room_type']); ?></h4>
                        <p><strong>Check-in:</strong> <?= htmlspecialchars($item['check_in_date']); ?></p>
                        <p><strong>Check-out:</strong> <?= htmlspecialchars($item['check_out_date']); ?></p>
                        <p><strong>Nights:</strong> <?= $nights; ?></p>
                        <p><strong>Guests:</strong> <?= htmlspecialchars($item['number_of_guests']); ?></p>
                        <p class="item-subtotal">Subtotal: $<?= number_format($item_subtotal, 2); ?> USD</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center;">No items found in this receipt.</p>
            <?php endif; ?>
        </div>

        <div class="receipt-total-section">
            Overall Total: <span>$<?= number_format($overallTotalCost, 2) ?> USD</span>
        </div>

        <div class="btn-group">
            <a href="../user_home.php" class="primary-btn-lg">Back to Home</a>
        </div>
    </div>
</body>
</html>