<?php
session_start();
include "db_conn.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function validate($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    $full_name = validate($_POST['user_full_name']);
    $email = validate($_POST['user_email']);
    $username = validate($_POST['username']);
    $password = validate($_POST['user_password']);
    $con_password = validate($_POST['con_user_password']);

    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($con_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: user_signup.html");
        exit();
    } elseif ($password !== $con_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: user_signup.html");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check username
    $stmt1 = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
    $stmt1->bind_param("s", $username);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    // Check email
    $stmt2 = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result1->num_rows > 0) {
        $_SESSION['error'] = "Username is already taken";
        header("Location: user_signup.html");
        exit();
    } elseif ($result2->num_rows > 0) {
        $_SESSION['error'] = "Email is already taken";
        header("Location: user_signup.html");
        exit();
    }

    // Insert into accounts table
    $account_role = "user";
    $stmt3 = $conn->prepare("INSERT INTO accounts (username, account_password, account_role) VALUES (?, ?, ?)");
    $stmt3->bind_param("sss", $username, $hashed_password, $account_role);

    if ($stmt3->execute()) {
        $account_id = $conn->insert_id;

        // Insert into users table
        $stmt4 = $conn->prepare("INSERT INTO users (user_full_name, user_email, account_id) VALUES (?, ?, ?)");
        $stmt4->bind_param("ssi", $full_name, $email, $account_id);

        if ($stmt4->execute()) {
            $user_id = $conn->insert_id;

            // ✅ Set session variables so user is logged in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['account_id'] = $account_id;
            $_SESSION['username'] = $username;

            $_SESSION['success'] = "Account created and logged in successfully!";
            header("Location: user_home.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to insert user data.";
            header("Location: user_signup.html");
            exit();
        }
    } else {
        $_SESSION['error'] = "Failed to create account.";
        header("Location: user_signup.html");
        exit();
    }
}
?>
