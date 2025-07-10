<?php
session_start();
include "db_conn.php"; // Make sure this connects correctly

// Validate user input
function validate($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// ✅ Ensure the user has registered as an owner
if (!isset($_SESSION['account_id'])) {
    $_SESSION['error'] = "You must first register as an owner.";
    header("Location: owner_signup.html");
    exit();
}

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['hotel_name'], $_POST['location'], $_POST['description'], $_FILES['hotel_image'])) {

        $hotel_name = validate($_POST['hotel_name']);
        $location = validate($_POST['location']);
        $description = validate($_POST['description']);

        // ✅ Handle image upload
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $image_name = basename($_FILES['hotel_image']['name']);
        $image_tmp_name = $_FILES['hotel_image']['tmp_name'];
        $hotel_image_url = $upload_dir . $image_name;

        if (!move_uploaded_file($image_tmp_name, $hotel_image_url)) {
            $_SESSION['error'] = "Image upload failed.";
            header("Location: add_hotel.html");
            exit();
        }

        // ✅ Get owner_id from account_id
        $account_id = $_SESSION['account_id'];
        $query = "SELECT owner_id FROM owners WHERE account_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $owner_id = $row['owner_id'];
        } else {
            $_SESSION['error'] = "Owner not found. Make sure you're registered.";
            header("Location: add_hotel.html");
            exit();
        }

        // ✅ Insert hotel into database
        $sql_hotels = "INSERT INTO hotels (hotel_name, hotel_description, hotel_location, hotel_image_url, owner_id) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_hotels = mysqli_prepare($conn, $sql_hotels);

        if ($stmt_hotels) {
            mysqli_stmt_bind_param($stmt_hotels, "ssssi", $hotel_name, $description, $location, $hotel_image_url, $owner_id);
            if (mysqli_stmt_execute($stmt_hotels)) {
                $_SESSION['success'] = "Hotel registration successful!";
                header("Location: owner_hotel.php");
                exit();
            } else {
                $_SESSION['error'] = "Hotel registration failed: " . mysqli_error($conn);
                header("Location: add_hotel.html");
                exit();
            }
        } else {
            $_SESSION['error'] = "Database error: " . mysqli_error($conn);
            header("Location: add_hotel.html");
            exit();
        }

    } else {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: add_hotel.html");
        exit();
    }
} else {
    header("Location: add_hotel.html");
    exit();
}
?>
