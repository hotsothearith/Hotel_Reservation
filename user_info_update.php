<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: user_signin.php");
    exit();
}

// Include the database connection file
include 'db_conn.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $username = $_POST['username'];

    // Validate the inputs
    if (empty($name) || empty($email) || empty($username)) {
        echo "All fields are required.";
        exit();
    }

    // Update the user's details in the database
    $update_query = "UPDATE users 
                     SET user_full_name = ?, user_email = ? 
                     WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $name, $email, $user_id);

    // Update the associated account's username
    $update_username_query = "UPDATE accounts 
                              SET username = ? 
                              WHERE account_id = (SELECT account_id FROM users WHERE user_id = ?)";
    $update_username_stmt = $conn->prepare($update_username_query);
    $update_username_stmt->bind_param("si", $username, $user_id);

    // Execute the queries
    if ($update_stmt->execute() && $update_username_stmt->execute()) {
        echo "User details updated successfully.";
    } else {
        echo "Error updating details.";
    }

    // Close the statements and connection
    $update_stmt->close();
    $update_username_stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
