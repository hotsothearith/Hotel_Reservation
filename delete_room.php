<?php
session_start();
include 'db_conn.php';

// Check if the owner is logged in
if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_signin.php");
    exit();
}

// Check if room_id is provided
if (!isset($_POST['room_id'])) {
    die("<p>Error: No room selected.</p>");
}

$room_id = $_POST['room_id'];

// Delete room amenities first to avoid foreign key constraint issues
$delete_amenities_query = "DELETE FROM room_amenities WHERE room_id = ?";
$delete_amenities_stmt = $conn->prepare($delete_amenities_query);
$delete_amenities_stmt->bind_param("i", $room_id);
$delete_amenities_stmt->execute();
$delete_amenities_stmt->close();

// Delete the room
$delete_room_query = "DELETE FROM rooms WHERE room_id = ?";
$delete_room_stmt = $conn->prepare($delete_room_query);
$delete_room_stmt->bind_param("i", $room_id);
$delete_room_stmt->execute();
$delete_room_stmt->close();

$conn->close();

header("Location: hotel_room.php");
exit();
?>
