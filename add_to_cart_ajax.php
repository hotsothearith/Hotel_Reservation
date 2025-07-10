<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// Set the content type header for JSON response
header('Content-Type: application/json');

// Include your database connection file
include 'db_conn.php';

// Initialize a response array
$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please sign in to add items to your cart.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the request method is POST and necessary parameters are set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'], $_POST['checkIn'], $_POST['checkOut'])) {
    // Sanitize and validate input
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $check_in_date = filter_input(INPUT_POST, 'checkIn', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $check_out_date = filter_input(INPUT_POST, 'checkOut', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$room_id || !$check_in_date || !$check_out_date) {
        $response['message'] = 'Invalid room ID or dates provided.';
        echo json_encode($response);
        exit();
    }

    // Validate dates: Check-out must be after check-in
    try {
        $in_date_obj = new DateTime($check_in_date);
        $out_date_obj = new DateTime($check_out_date);

        if ($out_date_obj <= $in_date_obj) {
            $response['message'] = "Check-out date must be after check-in date.";
            echo json_encode($response);
            exit();
        }
    } catch (Exception $e) {
        $response['message'] = "Invalid date format.";
        echo json_encode($response);
        exit();
    }


    // 1. Fetch room details to get price_per_night and guest_capacity
    $room_details_sql = "SELECT price_per_night, guest_capacity FROM rooms WHERE room_id = ?";
    $stmt_room_details = $conn->prepare($room_details_sql);

    if ($stmt_room_details) {
        $stmt_room_details->bind_param("i", $room_id);
        $stmt_room_details->execute();
        $room_result = $stmt_room_details->get_result();

        if ($room_result->num_rows > 0) {
            $room_data = $room_result->fetch_assoc();
            $price_at_addition = $room_data['price_per_night'];
            $number_of_guests = $room_data['guest_capacity']; // Assuming guest_capacity from room is the default for cart
        } else {
            $response['message'] = "Room not found.";
            echo json_encode($response);
            $stmt_room_details->close();
            exit();
        }
        $stmt_room_details->close();
    } else {
        $response['message'] = "Database error fetching room details: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    // 2. Check room availability for the selected dates
    // This query checks if *any* booking already overlaps with the requested dates for this specific room
    $availability_sql = "
        SELECT COUNT(bd.room_id) AS booked_count
        FROM bookings b
        JOIN booking_details bd ON b.booking_id = bd.booking_id
        WHERE bd.room_id = ?
          AND b.check_in_date < ?
          AND b.check_out_date > ?
    ";
    $stmt_availability = $conn->prepare($availability_sql);

    if ($stmt_availability) {
        $stmt_availability->bind_param("iss", $room_id, $check_out_date, $check_in_date);
        $stmt_availability->execute();
        $availability_result = $stmt_availability->get_result();
        $booked_rooms_count = $availability_result->fetch_assoc()['booked_count'];
        $stmt_availability->close();

        if ($booked_rooms_count > 0) {
            $response['message'] = 'This room is not available for the selected dates.';
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = "Database error checking room availability: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    // 3. Insert into the carts table
    $insert_cart_sql = "INSERT INTO carts (user_id, room_id, check_in_date, check_out_date, number_of_guests, price_at_addition) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert_cart = $conn->prepare($insert_cart_sql);

    if ($stmt_insert_cart) {
        $stmt_insert_cart->bind_param("iissid", $user_id, $room_id, $check_in_date, $check_out_date, $number_of_guests, $price_at_addition);

        if ($stmt_insert_cart->execute()) {
            $response['success'] = true;
            $response['message'] = "Room added to cart successfully!";

            // Get updated cart count for the response
            $cart_count_sql = "SELECT COUNT(*) AS total_items FROM carts WHERE user_id = ?";
            $stmt_cart_count = $conn->prepare($cart_count_sql);
            if($stmt_cart_count) {
                $stmt_cart_count->bind_param("i", $user_id);
                $stmt_cart_count->execute();
                $cart_count_result = $stmt_cart_count->get_result();
                $response['cart_count'] = $cart_count_result->fetch_assoc()['total_items'];
                $stmt_cart_count->close();
            }

        } else {
            $response['message'] = "Error adding room to cart: " . $stmt_insert_cart->error;
        }
        $stmt_insert_cart->close();
    } else {
        $response['message'] = "Database error preparing cart insertion: " . $conn->error;
    }
} else {
    $response['message'] = 'Invalid request. Missing room_id, checkIn, or checkOut parameters, or incorrect method.';
}

$conn->close();
echo json_encode($response);
exit();
?>