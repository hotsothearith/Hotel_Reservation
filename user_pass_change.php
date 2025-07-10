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
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo "All fields are required.";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "New password and confirm password do not match.";
        exit();
    }

    // Fetch the current password from the database
    $query = "SELECT account_password FROM accounts WHERE account_id = (SELECT account_id FROM users WHERE user_id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo "User not found.";
        exit();
    }

    // Verify the old password
    if (!password_verify($old_password, $user['account_password'])) {
        echo "The old password is incorrect.";
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_query = "UPDATE accounts SET account_password = ? WHERE account_id = (SELECT account_id FROM users WHERE user_id = ?)";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $hashed_password, $user_id);

    if ($update_stmt->execute()) {
        echo "Password changed successfully.";
    } else {
        echo "Error updating password.";
    }

    // Close the statements and connection
    $stmt->close();
    $update_stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
