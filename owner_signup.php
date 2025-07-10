<?php
session_start();
include "db_conn.php"; // Ensure the database connection works correctly

// Function to validate user input
function validate($data) {
    return htmlspecialchars(trim($data));
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['owner_full_name'], $_POST['owner_email'], $_POST['owner_phone'], $_POST['owner_password'], $_POST['con_owner_password'])) {
        // Get and validate input
        $full_name = validate($_POST['owner_full_name']);
        $email = filter_var($_POST['owner_email'], FILTER_SANITIZE_EMAIL);
        $phone = preg_replace('/[^0-9+]/', '', $_POST['owner_phone']); // Only allow numbers and '+'
        $password = validate($_POST['owner_password']);
        $confirm_password = validate($_POST['con_owner_password']);

        // Check for empty fields
        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: owner_signup.html");
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: owner_signup.html");
            exit();
        }

        // Validate phone format
        if (!preg_match("/^\+?\d{10,15}$/", $phone)) {
            $_SESSION['error'] = "Invalid phone number format.";
            header("Location: owner_signup.html");
            exit();
        }

        // Check if passwords match
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: owner_signup.html");
            exit();
        }

        // Check password strength
        if (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $_SESSION['error'] = "Password must be at least 8 characters long and include both letters and numbers.";
            header("Location: owner_signup.html");
            exit();
        }

        // Check for existing email and phone
        $sql_check_email = "SELECT * FROM accounts WHERE owner_email = ?";
        $stmt_check_email = mysqli_prepare($conn, $sql_check_email);
        mysqli_stmt_bind_param($stmt_check_email, "s", $email);
        mysqli_stmt_execute($stmt_check_email);
        $result_email = mysqli_stmt_get_result($stmt_check_email);

        if (mysqli_num_rows($result_email) > 0) {
            $_SESSION['error'] = "Email is already registered.";
            header("Location: owner_signup.html");
            exit();
        }

        $sql_check_phone = "SELECT * FROM owners WHERE phone = ?";
        $stmt_check_phone = mysqli_prepare($conn, $sql_check_phone);
        mysqli_stmt_bind_param($stmt_check_phone, "s", $phone);
        mysqli_stmt_execute($stmt_check_phone);
        $result_phone = mysqli_stmt_get_result($stmt_check_phone);

        if (mysqli_num_rows($result_phone) > 0) {
            $_SESSION['error'] = "Phone number is already registered.";
            header("Location: owner_signup.html");
            exit();
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $account_role = 'owner';

        // Insert into accounts table
        $sql_accounts = "INSERT INTO accounts (account_role, owner_email, account_password) VALUES (?, ?, ?)";
        $stmt_accounts = mysqli_prepare($conn, $sql_accounts);

        if ($stmt_accounts) {
            mysqli_stmt_bind_param($stmt_accounts, "sss", $account_role, $email, $hashed_password);
            if (mysqli_stmt_execute($stmt_accounts)) {
                // Get the generated account_id
                $account_id = mysqli_insert_id($conn);

                // Insert into owners table
                $sql_owners = "INSERT INTO owners (account_id, owner_full_name, phone) VALUES (?, ?, ?)";
                $stmt_owners = mysqli_prepare($conn, $sql_owners);

                if ($stmt_owners) {
                    mysqli_stmt_bind_param($stmt_owners, "iss", $account_id, $full_name, $phone);
                    if (mysqli_stmt_execute($stmt_owners)) {
                        // Registration successful, store account ID in session
                        $_SESSION['account_id'] = $account_id;
                        $_SESSION['owner_full_name'] = $full_name;
                        $_SESSION['success'] = "Owner registration successful! Please go to owner home page.";
                        header("Location: owner_das.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Owner registration failed. Please try again.";
                        error_log("MySQL Error (owners): " . mysqli_error($conn));
                        header("Location: owner_signup.html");
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "Owner registration failed. Please try again.";
                    error_log("MySQL Error (owners): " . mysqli_error($conn));
                    header("Location: owner_signup.html");
                    exit();
                }
                if ($stmt_owners) {
                    mysqli_stmt_close($stmt_owners);
                }
            }
            mysqli_stmt_close($stmt_accounts);
        }
    }
} else {
    header("Location: owner_signup.html");
    exit();
}
?>
