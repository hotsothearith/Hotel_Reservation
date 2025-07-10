<?php
session_start();
require 'db_conn.php';

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$hotel_id = $data['hotel_id'] ?? null;
$liked = $data['liked'] ?? false;

if (!$user_id) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

if (!$hotel_id) {
    echo json_encode(["error" => "Hotel ID missing"]);
    exit;
}

// Toggle like in the database
if ($liked) {
    $stmt = $conn->prepare("INSERT IGNORE INTO likes (user_id, hotel_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $hotel_id]);
} else {
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND hotel_id = ?");
    $stmt->execute([$user_id, $hotel_id]);
}

// Update total likes in the hotel table
$updateTotal = $conn->prepare("UPDATE hotels SET total_likes = (SELECT COUNT(*) FROM likes WHERE hotel_id = ?) WHERE id = ?");
$updateTotal->execute([$hotel_id, $hotel_id]);

// Get updated like count
$total_likes_query = $conn->prepare("SELECT total_likes FROM hotels WHERE id = ?");
$total_likes_query->execute([$hotel_id]);
$total_likes = $total_likes_query->fetchColumn();

echo json_encode(["total_likes" => $total_likes]);
?>
