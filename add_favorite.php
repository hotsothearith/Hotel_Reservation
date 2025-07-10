<?php
session_start(); // Always start session at the very beginning
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

    if ($hotel_id === false) { // Check for invalid integer
        echo "invalid_hotel_id";
        exit();
    }

    // Check if already favorited
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND hotel_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $hotel_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Already favorited, so unfavorite (delete)
            $delete_stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND hotel_id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("ii", $user_id, $hotel_id);
                if ($delete_stmt->execute()) {
                    if ($delete_stmt->affected_rows > 0) {
                        echo "removed";
                        // Optional: Decrement total_likes in hotels table
                        $update_likes_stmt = $conn->prepare("UPDATE hotels SET total_likes = total_likes - 1 WHERE hotel_id = ?");
                        if ($update_likes_stmt) {
                            $update_likes_stmt->bind_param("i", $hotel_id);
                            $update_likes_stmt->execute();
                            $update_likes_stmt->close();
                        }
                    } else {
                        echo "not_found"; // Favorite not found, possibly already removed
                    }
                } else {
                    echo "error_removing: " . $delete_stmt->error;
                }
                $delete_stmt->close();
            } else {
                echo "db_prepare_error_delete";
            }
        } else {
            // Not favorited, so favorite (insert)
            $insert_stmt = $conn->prepare("INSERT INTO favorites (user_id, hotel_id) VALUES (?, ?)");
            if ($insert_stmt) {
                $insert_stmt->bind_param("ii", $user_id, $hotel_id);
                if ($insert_stmt->execute()) {
                    echo "added";
                    // Optional: Increment total_likes in hotels table
                    $update_likes_stmt = $conn->prepare("UPDATE hotels SET total_likes = total_likes + 1 WHERE hotel_id = ?");
                    if ($update_likes_stmt) {
                        $update_likes_stmt->bind_param("i", $hotel_id);
                        $update_likes_stmt->execute();
                        $update_likes_stmt->close();
                    }
                } else {
                    echo "error_adding: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            } else {
                echo "db_prepare_error_insert";
            }
        }
        $stmt->close();
    } else {
        echo "db_prepare_error_select";
    }
} else {
    echo "no_hotel_id_provided";
}

$conn->close();
?>