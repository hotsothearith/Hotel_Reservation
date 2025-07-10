<?php
// Start the session
session_start();

// Check if the owner is logged in
if (!isset($_SESSION['owner_id'])) {
    // Redirect to login page if not logged in
    header("Location: owner_signin.php");
    exit();
}

// Include the database connection file
include 'db_conn.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the owner ID from the session
    $owner_id = $_SESSION['owner_id'];

    // Get the updated details from the POST request
    $full_name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Validate the inputs (basic validation for this example)
    if (empty($full_name) || empty($email) || empty($phone)) {
        echo "All fields are required.";
        exit();
    }

    // Update the owner's details in the database
    $query = "UPDATE owners 
              INNER JOIN accounts ON owners.account_id = accounts.account_id
              SET owners.owner_full_name = ?, accounts.owner_email = ?, owners.phone = ?
              WHERE owners.owner_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $full_name, $email, $phone, $owner_id);

    if ($stmt->execute()) {
        // Redirect to the owner settings page with a success message
        header("Location: owner_setting.php?success=Details updated successfully");
    } else {
        // Redirect with an error message in case of failure
        header("Location: owner_setting.php?error=Failed to update details");
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Redirect to the owner settings page if the form wasn't submitted
    header("Location: owner_setting.php");
    exit();
}
?>
