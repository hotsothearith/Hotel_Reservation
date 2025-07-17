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
                $hotel_id = mysqli_insert_id($conn); // Get the ID of the newly inserted hotel

                // ✅ Handle optional discount information
                // Check if discount fields are provided and not empty
                if (!empty($_POST['discount_percentage']) && !empty($_POST['valid_from']) && !empty($_POST['valid_to'])) {
                    $discount_percentage = intval(validate($_POST['discount_percentage'])); // Cast to int
                    $valid_from = validate($_POST['valid_from']);
                    $valid_to = validate($_POST['valid_to']);

                    // Basic validation for discount values
                    if ($discount_percentage < 1 || $discount_percentage > 100) {
                        $_SESSION['warning'] = "Hotel registered, but discount percentage must be between 1 and 100. Discount not applied.";
                    } elseif ($valid_from > $valid_to) {
                        $_SESSION['warning'] = "Hotel registered, but 'Valid From' date cannot be after 'Valid To' date. Discount not applied.";
                    } else {
                        // Insert discount into the 'discount' table
                        $sql_discount = "INSERT INTO discounts (discount_percentage, valid_from, valid_to, hotel_id)
                                         VALUES (?, ?, ?, ?)";
                        $stmt_discount = mysqli_prepare($conn, $sql_discount);

                        if ($stmt_discount) {
                            mysqli_stmt_bind_param($stmt_discount, "isss", $discount_percentage, $valid_from, $valid_to, $hotel_id);
                            if (mysqli_stmt_execute($stmt_discount)) {
                                $_SESSION['success'] = "Hotel and discount registered successfully!";
                            } else {
                                $_SESSION['warning'] = "Hotel registered, but discount registration failed: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmt_discount);
                        } else {
                            $_SESSION['warning'] = "Hotel registered, but database error preparing discount statement: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $_SESSION['success'] = "Hotel registered successfully!"; // No discount info provided
                }

                header("Location: owner_hotel.php");
                exit();

            } else {
                $_SESSION['error'] = "Hotel registration failed: " . mysqli_error($conn);
                header("Location: add_hotel.html");
                exit();
            }
            mysqli_stmt_close($stmt_hotels);
        } else {
            $_SESSION['error'] = "Database error preparing hotel statement: " . mysqli_error($conn);
            header("Location: add_hotel.html");
            exit();
        }

    } else {
        $_SESSION['error'] = "Please complete all required hotel fields.";
        header("Location: add_hotel.html");
        exit();
    }
} else {
    header("Location: add_hotel.html");
    exit();
}
?>
