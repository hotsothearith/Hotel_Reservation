<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../db_conn.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.php"); // Adjust path if needed
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if cart data exists in session for checkout
if (!isset($_SESSION['checkout_cart_items']) || empty($_SESSION['checkout_cart_items'])) {
    $_SESSION['message'] = "No items found for payment processing. Please go back to cart.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user_add_to_cart.php"); // Redirect to cart page
    exit();
}
$cart_items_to_process = $_SESSION['checkout_cart_items'];

// Check required POST data for payment
$required = ['overall_total_cost', 'initial_payment', 'bank_type', 'card_number', 'expiry_date', 'cvv']; // Ensure all are present for robust check
foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') { // Use isset and check for empty string
        $_SESSION['message'] = "Missing payment information: " . htmlspecialchars($field);
        $_SESSION['message_type'] = "error";
        header("Location: payment.php"); // Redirect back to payment page
        exit();
    }
}

// Sanitize and Validate Input
$overallTotalCost = filter_input(INPUT_POST, 'overall_total_cost', FILTER_VALIDATE_FLOAT);
$initialPayment = filter_input(INPUT_POST, 'initial_payment', FILTER_VALIDATE_FLOAT);

$cardNumber = preg_replace('/\D/', '', $_POST['card_number']); // Keep digits only for card number
$bankType = trim(strip_tags($_POST['bank_type'])); // Use strip_tags and trim
$expiryDate = trim(strip_tags($_POST['expiry_date'])); // Use strip_tags and trim for MM-YY format
$cvv = preg_replace('/\D/', '', $_POST['cvv']); // Keep digits only for CVV

// Validate numeric inputs more strictly
if ($overallTotalCost === false || $initialPayment === false || $overallTotalCost <= 0) {
    $_SESSION['message'] = "Invalid total amount for payment processing.";
    $_SESSION['message_type'] = "error";
    header("Location: payment.php");
    exit();
}

// Basic validation for card number, expiry date, CVV - add more robust validation in a real system
if (empty($cardNumber) || empty($expiryDate) || empty($cvv)) {
    $_SESSION['message'] = "Credit card details are incomplete.";
    $_SESSION['message_type'] = "error";
    header("Location: payment.php");
    exit();
}

// --- Simulate Payment Processing ---
// In a real application, this is where you'd call a payment gateway API.
// For now, we'll assume success.
$payment_successful = true; // Placeholder for actual payment gateway response

if ($payment_successful) {
    // Start a transaction for atomicity
    $conn->begin_transaction();
    $booking_successful = true;
    $ref_number = 'NR' . strtoupper(substr(md5(uniqid()), 0, 10)); // Generate one reference for the whole transaction
    $successful_bookings_data = []; // To collect data for the receipt (e.g., from the first item)

    // Common data for all bookings in this transaction
    $payment_status = 'fully_paid'; // Or 'initial_paid' if not full
    $booking_status = 'Confirmed'; // Or 'Pending', 'Cancelled' etc.
    $booked_at = date('Y-m-d H:i:s'); // Current timestamp for booking

    foreach ($cart_items_to_process as $item) {
        $roomId = $item['room_id'] ?? null;
        $checkIn = $item['check_in_date'] ?? null;
        $checkOut = $item['check_out_date'] ?? null;
        $numGuests = $item['number_of_guests'] ?? 1; // Default to 1 if not set in cart data
        $itemTotalCost = $item['calculated_item_total'] ?? 0; // Use 'total_cost' for the column

        // Calculate num_nights
        try {
            $startDate = new DateTime($checkIn);
            $endDate = new DateTime($checkOut);
            $numNights = $startDate->diff($endDate)->days;
        } catch (Exception $e) {
            $numNights = 0; // Default or handle error
            error_log("Date calculation error for item: " . json_encode($item) . " - " . $e->getMessage());
        }

        // Calculate initial_payment_made and final_payment_due for this specific item
        // Assuming initialPayment is a percentage or fixed amount of overall total,
        // you might need a more precise calculation per item if your system supports partial payments per item.
        // For simplicity, let's assume this item is fully paid by initialPayment
        $initialPaymentMadeItem = $itemTotalCost; // Assuming full payment for each item within the overall transaction
        $finalPaymentDueItem = 0.00; // If fully paid

        // Basic validation for item details
        if ($roomId === null || $checkIn === null || $checkOut === null || $itemTotalCost <= 0) {
            $booking_successful = false;
            error_log("Missing or invalid data for cart item: " . json_encode($item));
            break; // Exit loop on first invalid item
        }

        // Insert booking into 'bookings' table
        // IMPORTANT: Ensure 'number_of_guests' column now exists in your 'bookings' table.
        $sql_insert_booking = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, num_nights, total_cost, initial_payment_made, final_payment_due, payment_status, payment_method, transaction_ref, booked_at, number_of_guests)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Total 13 placeholders

        $stmt_insert = $conn->prepare($sql_insert_booking);
        if ($stmt_insert) {
            // Types: i (user_id), i (room_id), s (check_in), s (check_out), i (num_nights),
            // d (total_cost), d (initial_payment_made), d (final_payment_due),
            // s (payment_status), s (payment_method), s (transaction_ref), s (booked_at), i (number_of_guests)
            $stmt_insert->bind_param("iissiddsssssi",
                $user_id,
                $roomId,
                $checkIn,
                $checkOut,
                $numNights,
                $itemTotalCost,
                $initialPaymentMadeItem,
                $finalPaymentDueItem,
                $payment_status,
                $bankType, // Using bankType as payment_method
                $ref_number,
                $booked_at,
                $numGuests
            );

            if (!$stmt_insert->execute()) {
                $booking_successful = false;
                error_log("Error inserting booking for room ID {$roomId}: " . $stmt_insert->error); // Log the actual DB error
                break; // Exit loop on first failure
            }
            $stmt_insert->close();

            // Collect data for consolidated receipt (e.g., from the first item for simplicity)
            // This is for the `receipt.php` to display. Adjust if `receipt.php` needs all items' details.
            if (empty($successful_bookings_data)) {
                $successful_bookings_data = [
                    'hotel_name' => $item['hotel_name'] ?? 'Hotel Name N/A', // Assuming these are available from cart data or fetched
                    'hotel_location' => $item['hotel_location'] ?? 'Location N/A',
                    'room_type' => $item['room_type'] ?? 'Room Type N/A',
                    'checkIn' => $checkIn,
                    'checkOut' => $checkOut,
                    'number_of_guests' => $numGuests,
                    // You might want total_cost for this specific item, not the overall
                    'total_cost_item' => $itemTotalCost,
                    'nights' => $numNights // Added for receipt
                ];
            }

        } else {
            $booking_successful = false;
            error_log("Database error: Could not prepare statement for booking insertion. " . $conn->error); // Log DB preparation error
            break; // Exit loop on first failure
        }
    }

    if ($booking_successful) {
        // If all bookings were inserted successfully, delete items from carts table
        $cart_ids_to_delete = array_column($cart_items_to_process, 'cart_id');
        if (!empty($cart_ids_to_delete)) { // Only delete if there are items to delete
            $placeholders = implode(',', array_fill(0, count($cart_ids_to_delete), '?'));
            $types = str_repeat('i', count($cart_ids_to_delete));

            $sql_delete_carts = "DELETE FROM carts WHERE cart_id IN ($placeholders) AND user_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_carts);
            if ($stmt_delete) {
                // PHP 5.6+ using splat operator (preferred way for dynamic bind_param)
                // $stmt_delete->bind_param($types . 'i', ...$cart_ids_to_delete, $user_id);
                // For older PHP or if keeping refValues helper:
                $params = array_merge([$types . 'i'], $cart_ids_to_delete, [$user_id]);
                call_user_func_array([$stmt_delete, 'bind_param'], refValues($params));

                if (!$stmt_delete->execute()) {
                    $conn->rollback(); // Rollback bookings if cart deletion fails
                    $_SESSION['message'] = "Payment successful but failed to clear cart. Please contact support. Error: " . $stmt_delete->error;
                    $_SESSION['message_type'] = "error";
                    header("Location: ../user_add_to_cart.php");
                    exit();
                }
                $stmt_delete->close();
            } else {
                $conn->rollback();
                $_SESSION['message'] = "Database error: Could not prepare statement for cart deletion.";
                $_SESSION['message_type'] = "error";
                header("Location: ../user_add_to_cart.php");
                exit();
            }
        }

        $conn->commit(); // Commit the transaction

        // Prepare data for the receipt
        // This overall total cost is for the entire payment, not per item.
        $_SESSION['booking_receipt_data'] = [
            'total_cost' => $overallTotalCost, // Overall total paid for all items
            'initial_payment' => $initialPayment,
            'bank_type' => $bankType,
            'payment_method_detail' => $bankType . ' ****' . substr($cardNumber, -4),
            'user_id' => $user_id,
            'ref_number' => $ref_number,
            // These next fields are for the receipt page, often showing details of ONE primary booking or the first one.
            'hotel_name' => $successful_bookings_data['hotel_name'] ?? 'N/A',
            'hotel_location' => $successful_bookings_data['hotel_location'] ?? 'N/A',
            'room_type' => $successful_bookings_data['room_type'] ?? 'N/A',
            'checkIn' => $successful_bookings_data['checkIn'] ?? 'N/A',
            'checkOut' => $successful_bookings_data['checkOut'] ?? 'N/A',
            'number_of_guests' => $successful_bookings_data['number_of_guests'] ?? 'N/A',
            'nights' => $successful_bookings_data['nights'] ?? 'N/A', // Pass calculated nights
            'room_id' => $successful_bookings_data['room_id'] ?? null, // Added for finalize_payment.php to potentially use
            // If you need all items to be displayed on the receipt, receipt.php would loop through `items`
            'items' => $cart_items_to_process // Pass full cart for detailed receipt if needed
        ];

        // Clear the checkout_cart_items from session after successful booking
        unset($_SESSION['checkout_cart_items']);

        header("Location: receipt.php");
        exit();

    } else {
        $conn->rollback(); // Rollback all insertions if any failed
        $_SESSION['message'] = "Payment processed but booking insertion failed. Please contact support.";
        $_SESSION['message_type'] = "error";
        header("Location: payment.php"); // Go back to payment page
        exit();
    }
} else {
    $_SESSION['message'] = "Payment failed. Please try again.";
    $_SESSION['message_type'] = "error";
    header("Location: payment.php");
    exit();
}

$conn->close();

// Helper function for bind_param with dynamic arguments (for PHP < 5.6)
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) // Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Complete!</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <header class="header-logo">
        <img src="../image/NokorRealm.png" alt="NokorRealm Logo" />
    </header>

    <div class="payment-step">
        <img src="../image/stepper3.png" alt="Payment Step 3" />
    </div>

    <div class="main-content-wrapper success-container">
        <div class="success-message">
            <h1>Yay! Payment Complete 🎉</h1>
            <p>Thank you for your booking!</p>
            <p>We've sent a detailed confirmation to your email and phone.<br />Please check them for all the information.</p>
        </div>
        <div class="btn-group">
            <a href="receipt.php" class="primary-btn-lg">View Receipt</a>
            <a href="../user_home.php" class="secondary-btn">Back to Home</a>
        </div>
    </div>
</body>

</html>