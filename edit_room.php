<?php
session_start();
include 'db_conn.php';

// Check if the owner is logged in
if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_signin.php");
    exit();
}

// Check if room_id is provided
if (!isset($_GET['room_id'])) {
    die("<p>Error: No room selected.</p>");
}

$room_id = $_GET['room_id'];

// Fetch existing room details
$query = "SELECT room_type, price_per_night, guest_capacity, room_status, room_image 
          FROM rooms WHERE room_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

// Fetch existing amenities
$amenities_query = "SELECT amenity_name FROM room_amenities WHERE room_id = ?";
$amenities_stmt = $conn->prepare($amenities_query);
$amenities_stmt->bind_param("i", $room_id);
$amenities_stmt->execute();
$amenities_result = $amenities_stmt->get_result();
$amenities = [];
while ($amenity = $amenities_result->fetch_assoc()) {
    $amenities[] = $amenity['amenity_name'];
}
$amenities_stmt->close();


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_type = $_POST['room_type'];
    $price = $_POST['price'];
    $guest_capacity = $_POST['guest_capacity'];
    $room_status = $_POST['room_status'];
    $new_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

    // Update room details
    $update_query = "UPDATE rooms SET room_type = ?, price_per_night = ?, guest_capacity = ?, room_status = ? WHERE room_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sdisi", $room_type, $price, $guest_capacity, $room_status, $room_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Update amenities: delete old ones and insert new ones
    $delete_amenities_query = "DELETE FROM room_amenities WHERE room_id = ?";
    $delete_stmt = $conn->prepare($delete_amenities_query);
    $delete_stmt->bind_param("i", $room_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    $insert_amenity_query = "INSERT INTO room_amenities (room_id, amenity_name) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_amenity_query);
    foreach ($new_amenities as $amenity) {
        $insert_stmt->bind_param("is", $room_id, $amenity);
        $insert_stmt->execute();
    }
    $insert_stmt->close();

    header("Location: owner_hotel.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room Details</title>
    <link rel="stylesheet" href="owner_signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Protest+Riot&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="right-section">
            <h2>Edit Room</h2>
            <form method="post">
                <div class="input-box">
                    <label class="details">Room Type:</label>
                    <input type="text" name="room_type" value="<?php echo htmlspecialchars($room['room_type']); ?>" required>
                </div>

                <div class="input-box">
                    <label class="details">Price per Night:</label>
                    <input type="number" name="price" value="<?php echo htmlspecialchars($room['price_per_night']); ?>" required>
                </div>

                <div class="input-box">
                    <label class="details">Guest Capacity:</label>
                    <input type="number" name="guest_capacity" value="<?php echo htmlspecialchars($room['guest_capacity']); ?>" required>
                </div>

                <div class="input-box">
                    <label class="details">Status:</label>
                    <select name="room_status">
                        <option value="available" <?php if ($room['room_status'] == 'available') echo "selected"; ?>>Available</option>
                        <option value="unavailable" <?php if ($room['room_status'] == 'unavailable') echo "selected"; ?>>Unavailable</option>
                    </select>
                </div>

                <div class="input-box">
                    <label class="details">Amenities:</label>
                    <input type="text" id="amenity-input" placeholder="Enter amenity and press Enter">

                    <!-- Display current amenities -->
                    <div class="amenities-list">
                        <?php foreach ($amenities as $amenity) : ?>
                            <div class="amenity">
                                <?php echo htmlspecialchars($amenity); ?>
                                <span class="remove-amenity" onclick="removeAmenity(this)">✖</span>
                                <input type="hidden" name="amenities[]" value="<?php echo htmlspecialchars($amenity); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- JavaScript for adding/removing amenities -->
                <script>
                    document.getElementById('amenity-input').addEventListener('keypress', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            let input = this;
                            let value = input.value.trim();
                            if (value) {
                                let list = document.querySelector('.amenities-list');
                                let div = document.createElement('div');
                                div.classList.add('amenity');
                                div.innerHTML = `${value} <span class="remove-amenity" onclick="removeAmenity(this)">✖</span>
                            <input type="hidden" name="amenities[]" value="${value}">`;
                                list.appendChild(div);
                                input.value = ''; // Clear input after adding
                            }
                        }
                    });

                    function removeAmenity(element) {
                        element.parentElement.remove();
                    }
                </script>

                <!-- Submit button -->
                <div class="input-box button">
                    <input type="submit" value="Update Room">
                </div>

                <!-- Back to home -->
                <div class="wrapper-text">
                    <div class="text">
                        <button type="button" onclick="window.location.href='hotel_room.php'">Cancel</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</body>

</html>