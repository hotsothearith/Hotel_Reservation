<?php
session_start(); // Always start session at the very beginning
include 'db_conn.php'; // Include your database connection

// Set header for JSON response
header('Content-Type: application/json');

$response = ['success' => false, 'favorites' => [], 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT hotel_id FROM favorites WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorite_hotel_ids = [];
    while ($row = $result->fetch_assoc()) {
        $favorite_hotel_ids[] = (int)$row['hotel_id'];
    }

    $response['success'] = true;
    $response['favorites'] = $favorite_hotel_ids;
    $response['message'] = 'Favorites fetched successfully.';
    $stmt->close();
} else {
    $response['message'] = 'Database error: Could not prepare statement to fetch favorites.';
}

$conn->close();
echo json_encode($response);
?>