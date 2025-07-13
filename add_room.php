<?php 
// Include database connection
include('db_conn.php');

// Start session
session_start();

// Get hotel_id from URL if provided
$hotel_id = isset($_GET['hotel_id']) ? $_GET['hotel_id'] : null;

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect form data
    $room_name = $_POST['room_name'];
    $room_type = $_POST['room_type'];
    $price_per_night = $_POST['price_per_night'];
    $guest_capacity = $_POST['guest_capacity'];
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

    // Default room_status
    $room_status = isset($_POST['room_status']) ? $_POST['room_status'] : 'available';

    // Get hotel_id from the form submission
    if (isset($_POST['hotel_id']) && !empty($_POST['hotel_id'])) {
        $hotel_id = $_POST['hotel_id'];
    } else {
        die("Error: Hotel ID is missing. Please select a valid hotel.");
    }

    // Handle image upload
    $room_image = 'image/default_room.jpg'; // Default image if none is uploaded
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] == 0) {
        $image_name = $_FILES['room_image']['name'];
        $image_tmp_name = $_FILES['room_image']['tmp_name'];
        $upload_dir = "image/";

        // Ensure the uploads directory exists and is writable
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $room_image_url = $upload_dir . basename($image_name);

        if (!move_uploaded_file($image_tmp_name, $room_image_url)) {
            $_SESSION['error'] = "Image upload failed.";
            header("Location: add_room.php");
            exit();
        } else {
            $room_image = $room_image_url; // Update image path
        }
    }

    // Insert room data into the database
    $sql = "INSERT INTO rooms (room_name, room_type, price_per_night, guest_capacity, room_status, room_image, hotel_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisss", $room_name, $room_type, $price_per_night, $guest_capacity, $room_status, $room_image, $hotel_id);

    if ($stmt->execute()) {
        $room_id = $stmt->insert_id;

        // Insert amenities into the database
        if (!empty($amenities)) {
            $amenity_sql = "INSERT INTO room_amenities (room_id, amenity_name) VALUES (?, ?)";
            $amenity_stmt = $conn->prepare($amenity_sql);
            foreach ($amenities as $amenity) {
                $amenity_stmt->bind_param("is", $room_id, $amenity);
                $amenity_stmt->execute();
            }
            $amenity_stmt->close();
        }

        // Redirect to the room list page for this hotel
        header("Location: hotel_room.php?hotel_id=" . $hotel_id);
        exit;
    } else {
        die("Database Error: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room</title>
    <link rel="stylesheet" href="owner_signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Protest+Riot&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="left-section">
            <h2>Add a Room</h2>
            <form action="add_room.php" method="post" enctype="multipart/form-data">
                <!-- Hidden field for hotel_id -->
                <input type="hidden" name="hotel_id" id="hotel_id" value="<?php echo htmlspecialchars($hotel_id); ?>">

                <!-- Room Name -->
                <div class="input-box">
                    <span class="details">Room Name</span>
                    <input type="text" name="room_name" placeholder="Name of the room" required>
                </div>

                <!-- Room Type -->
                <div class="input-box">
                    <span class="details">Room Type</span>
                    <input type="text" name="room_type" placeholder="Type of the room (e.g., Single, Double)" required>
                </div>

                <!-- Price Per Night -->
                <div class="input-box">
                    <span class="details">Price Per Night</span>
                    <input type="number" name="price_per_night" placeholder="Enter price per night (e.g., 100)" required>
                </div>

                <!-- Guest Capacity -->
                <div class="input-box">
                    <span class="details">Guest Capacity</span>
                    <input type="number" name="guest_capacity" placeholder="Enter guest capacity (e.g., 2)" required>
                </div>

                <!-- Upload Room Image -->
                <div class="input-box">
                    <label for="room_image" class="details">Upload Room Image</label>
                    <input type="file" name="room_image" accept="image/*" required>
                </div>

                <!-- Amenities -->
                <div class="input-box">
                    <span class="details">Amenities</span>
                    <div id="amenities-container">
                        <input type="text" name="amenities[]" placeholder="Enter an amenity" required>
                    </div>
                    <button type="button" class="add-button" onclick="addAmenity()">Add More</button>
                </div>
                <script>
                    function addAmenity() {
                        const container = document.getElementById('amenities-container');
                        const newInput = document.createElement('input');
                        newInput.type = 'text';
                        newInput.name = 'amenities[]';
                        newInput.placeholder = 'Enter an amenity';
                        newInput.required = true;
                        newInput.style.marginTop = '10px';
                        container.appendChild(newInput);
                    }
                </script>

                <!-- Submit button -->
                <div class="input-box button">
                    <input type="submit" value="Add Room">
                </div>

                <!-- Back to home -->
                <div class="wrapper-text">
                    <div class="text">
                        <a href="owner_hotel.php">Cancel</a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</body>
</html>
