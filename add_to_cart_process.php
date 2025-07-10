<?php
// Start the session
session_start();

// Set the Content-Type header to application/json so the browser knows to expect JSON
// This MUST be sent before any other output (including whitespace or PHP warnings)
header('Content-Type: application/json');

// Initialize a response array
$response = ['success' => false, 'message' => '', 'cart_count' => 0];

include 'db_conn.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please sign in to add items to cart.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Sanitize and validate input from GET request
// FIXED: Changed FILTER_SANITIZE_STRING to FILTER_UNSAFE_RAW for deprecated warning
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
$check_in_date = filter_input(INPUT_GET, 'checkIn', FILTER_UNSAFE_RAW); // Line 26
$check_out_date = filter_input(INPUT_GET, 'checkOut', FILTER_UNSAFE_RAW); // Line 27
// Assuming 'guests' might be passed, defaulting to 1 if not.
// You might need an input field for guests on user_hotel_detail.php
$number_of_guests = filter_input(INPUT_GET, 'guests', FILTER_VALIDATE_INT) ?: 1; 

// Basic input validation
if (!$room_id || !$check_in_date || !$check_out_date) {
    $response['message'] = 'Missing room ID or check-in/check-out dates.';
    echo json_encode($response);
    exit();
}

// Validate dates
try {
    $start_date_obj = new DateTime($check_in_date);
    $end_date_obj = new DateTime($check_out_date);

    if ($end_date_obj <= $start_date_obj) {
        $response['message'] = 'Check-out date must be after check-in date.';
        echo json_encode($response);
        exit();
    }
    $num_nights = $start_date_obj->diff($end_date_obj)->days;
    if ($num_nights < 1) { // Ensure at least one night booking
        $response['message'] = 'Booking must be for at least one night.';
        echo json_encode($response);
        exit();
    }
} catch (Exception $e) {
    $response['message'] = 'Invalid date format: ' . $e->getMessage();
    echo json_encode($response);
    exit();
}

// Fetch room details (price, type, hotel name/location) to store in cart
$stmt_room = $conn->prepare("
    SELECT 
        r.price_per_night, 
        r.room_type, 
        h.hotel_name, 
        h.hotel_location,
        r.room_image
    FROM rooms r 
    JOIN hotels h ON r.hotel_id = h.hotel_id 
    WHERE r.room_id = ?
");

if (!$stmt_room) {
    $response['message'] = 'Database error (room fetch prepare): ' . $conn->error;
    echo json_encode($response);
    exit();
}

$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$room_details = $stmt_room->get_result()->fetch_assoc();
$stmt_room->close();

if (!$room_details) {
    $response['message'] = 'Room details not found.';
    echo json_encode($response);
    exit();
}

$price_at_addition = $room_details['price_per_night']; // Price at the time of adding to cart

// Check for existing identical item in cart to prevent duplicates for same dates
$stmt_check_cart = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ? AND room_id = ? AND check_in_date = ? AND check_out_date = ?");
if (!$stmt_check_cart) {
    $response['message'] = 'Database error (cart check prepare): ' . $conn->error;
    echo json_encode($response);
    exit();
}
$stmt_check_cart->bind_param("iiss", $user_id, $room_id, $check_in_date, $check_out_date);
$stmt_check_cart->execute();
$result_check_cart = $stmt_check_cart->get_result();

if ($result_check_cart->num_rows > 0) {
    $response['message'] = 'This room for these dates is already in your cart.';
    echo json_encode($response);
    exit();
}
$stmt_check_cart->close();


// Insert the room into the cart
// FIXED: Ensured 'added_at' is used to match your carts table schema
$stmt_insert_cart = $conn->prepare("INSERT INTO carts (user_id, room_id, check_in_date, check_out_date, number_of_guests, price_at_addition, added_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"); // Line 113 (adjusted line number due to comments/changes)

if (!$stmt_insert_cart) {
    $response['message'] = 'Database error (cart insert prepare): ' . $conn->error;
    echo json_encode($response);
    exit();
}

$stmt_insert_cart->bind_param("iissid", $user_id, $room_id, $check_in_date, $check_out_date, $number_of_guests, $price_at_addition);

if ($stmt_insert_cart->execute()) {
    $response['success'] = true;
    $response['message'] = 'Room added to cart successfully!';

    // Get updated cart count
    $stmt_cart_count = $conn->prepare("SELECT COUNT(*) AS total_items FROM carts WHERE user_id = ?");
    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id);
        $stmt_cart_count->execute();
        $cart_count_result = $stmt_cart_count->get_result();
        $response['cart_count'] = $cart_count_result->fetch_assoc()['total_items'];
        $stmt_cart_count->close();
    } else {
        // Log error if cart count fails, but don't stop execution
        error_log("Failed to get updated cart count: " . $conn->error);
    }

} else {
    $response['message'] = 'Error adding room to cart: ' . $stmt_insert_cart->error;
}

$stmt_insert_cart->close();
$conn->close();

echo json_encode($response);
?>