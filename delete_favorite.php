<?php
session_start(); // Start the session
include 'db_conn.php'; // Include your database connection

// Set header for plain text response
header('Content-Type: text/plain');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if hotel_id is provided via POST
if (isset($_POST['hotel_id'])) {
    $hotel_id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);

    if ($hotel_id === false) {
        echo "invalid_hotel_id";
        exit();
    }

    // Prepare and execute the DELETE statement
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND hotel_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $hotel_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "success";

                // Optional: Decrement total_likes in hotels table
                $update_likes_stmt = $conn->prepare("UPDATE hotels SET total_likes = total_likes - 1 WHERE hotel_id = ?");
                if ($update_likes_stmt) {
                    $update_likes_stmt->bind_param("i", $hotel_id);
                    $update_likes_stmt->execute();
                    $update_likes_stmt->close();
                }
            } else {
                echo "not_found"; // Favorite not found for this user/hotel combination (already removed or never existed)
            }
        } else {
            echo "db_error_execute: " . $stmt->error; // Database execution error
        }
        $stmt->close();
    } else {
        echo "db_error_prepare: " . $conn->error; // Database prepare error
    }
} else {
    echo "no_hotel_id"; // No hotel_id was sent in the POST request
}

$conn->close();
?>