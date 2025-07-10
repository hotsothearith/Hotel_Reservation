<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../db_conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_signin.html");
    exit();
}

// Redirect if payment data is not coming from receipt or previous step
if (!isset($_POST['room_id']) || !isset($_SESSION['booking_receipt_data'])) {
    $_SESSION['message'] = "Invalid access to booking finalization.";
    $_SESSION['message_type'] = "error";
    header("Location: ../user_home.php");
    exit();
}

// Retrieve data from POST (from receipt.php) and SESSION (for comprehensive booking details)
$userId = $_SESSION['user_id'];
$roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
$checkIn = filter_input(INPUT_POST, 'checkIn', FILTER_SANITIZE_STRING);
$checkOut = filter_input(INPUT_POST, 'checkOut', FILTER_SANITIZE_STRING);
$totalCost = filter_input(INPUT_POST, 'total_cost', FILTER_VALIDATE_FLOAT);
$initialPayment = filter_input(INPUT_POST, 'initial_payment', FILTER_VALIDATE_FLOAT);
$bankType = filter_input(INPUT_POST, 'bank_type', FILTER_SANITIZE_STRING);
$refNumber = filter_input(INPUT_POST, 'refNumber', FILTER_SANITIZE_STRING); // The generated ref number
$paymentTime = filter_input(INPUT_POST, 'paymentTime', FILTER_SANITIZE_STRING); // The time payment occurred

// Fetch current user's username for booking entry
$username = $_SESSION['username'] ?? 'N/A'; // Fallback if username not set

// Recalculate nights to ensure accuracy
try {
    $startDate = new DateTime($checkIn);
    $endDate = new DateTime($checkOut);
    $nights = $startDate->diff($endDate)->days;
} catch (Exception $e) {
    $nights = 0; // Default to 0 if dates are invalid
}



$paymentStatus = 'initial_paid'; // Or 'paid' if 100% payment
$finalPaymentDue = $totalCost - $initialPayment; // Calculate remaining balance

$insertBookingSQL = "INSERT INTO bookings (
                        user_id,
                        room_id,
                        check_in_date,
                        check_out_date,
                        num_nights,
                        total_cost,
                        initial_payment_made,
                        final_payment_due,
                        payment_status,
                        payment_method,
                        transaction_ref,
                        booked_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_insert = $conn->prepare($insertBookingSQL);

if ($stmt_insert) {
    $stmt_insert->bind_param("iisiddsdssss",
        $userId,
        $roomId,
        $checkIn,
        $checkOut,
        $nights,
        $totalCost,
        $initialPayment,
        $finalPaymentDue,
        $paymentStatus,
        $bankType, // Payment method type (e.g., 'MasterCard', 'Visa')
        $refNumber, // Your generated reference number
        $paymentTime // The time recorded from payment_verify.php
    );

    if ($stmt_insert->execute()) {

        $deleteCartItemSQL = "DELETE FROM carts WHERE user_id = ? AND room_id = ? AND check_in_date = ? AND check_out_date = ?";
        $stmt_delete_cart = $conn->prepare($deleteCartItemSQL);
        if($stmt_delete_cart) {
             $stmt_delete_cart->bind_param("iiss", $userId, $roomId, $checkIn, $checkOut);
             $stmt_delete_cart->execute();
             $stmt_delete_cart->close();
        } else {
             error_log("Failed to prepare delete cart item statement: " . $conn->error);
        }

        // Clear the booking receipt data from session
        unset($_SESSION['booking_receipt_data']);

        $_SESSION['message'] = "Booking confirmed and saved!";
        $_SESSION['message_type'] = "success";
        header("Location: ../user_home.php"); // Redirect to user dashboard/home
        exit();

    } else {
        // Error saving booking to database
        $_SESSION['message'] = "Failed to finalize booking. Please contact support. Error: " . $stmt_insert->error;
        $_SESSION['message_type'] = "error";
        error_log("DB Error in finalize_payment: " . $stmt_insert->error);
        header("Location: receipt.php"); // Go back to receipt, user needs to contact support
        exit();
    }
    $stmt_insert->close();
} else {
    // Error preparing statement
    $_SESSION['message'] = "System error during booking finalization. Please try again.";
    $_SESSION['message_type'] = "error";
    error_log("Prepare statement error in finalize_payment: " . $conn->error);
    header("Location: receipt.php"); // Go back to receipt
    exit();
}

$conn->close();
?>