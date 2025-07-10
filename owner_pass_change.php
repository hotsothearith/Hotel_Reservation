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

    // Retrieve form inputs
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: owner_setting.php?error=All fields are required");
        exit();
    }

    if ($new_password !== $confirm_password) {
        header("Location: owner_setting.php?error=Passwords do not match");
        exit();
    }

    // Fetch the hashed password from the database
    $query = "SELECT accounts.password FROM accounts 
              INNER JOIN owners ON accounts.account_id = owners.account_id 
              WHERE owners.owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify the old password
    if (!password_verify($old_password, $hashed_password)) {
        header("Location: owner_setting.php?error=Old password is incorrect");
        exit();
    }

    // Hash the new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_query = "UPDATE accounts 
                     INNER JOIN owners ON accounts.account_id = owners.account_id 
                     SET accounts.account_password = ? 
                     WHERE owners.owner_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_hashed_password, $owner_id);

    if ($update_stmt->execute()) {
        // Redirect with success message
        header("Location: owner_setting.php?success=Password updated successfully");
    } else {
        // Redirect with error message
        header("Location: owner_setting.php?error=Failed to update password");
    }

    // Close the statement and connection
    $update_stmt->close();
    $conn->close();
} else {
    // Redirect if the form wasn't submitted
    header("Location: owner_setting.php");
    exit();
}
?>
