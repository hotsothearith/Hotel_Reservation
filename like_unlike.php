<?php
include 'db_conn.php'; // Include your database connection

$hotel_id = $_POST['hotel_id'];
$user_id = $_POST['user_id'];
$action = $_POST['action'];

if ($action == 'like') {
    // Insert like if it doesn't already exist
    $stmt = $conn->prepare("INSERT IGNORE INTO hotel_likes (hotel_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $hotel_id, $user_id);
    $stmt->execute();
} elseif ($action == 'unlike') {
    // Delete like
    $stmt = $conn->prepare("DELETE FROM hotel_likes WHERE hotel_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $hotel_id, $user_id);
    $stmt->execute();
}

// Get the updated like count
$stmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM hotel_likes WHERE hotel_id = ?");
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['total_likes' => $result['total_likes']]);
?>
