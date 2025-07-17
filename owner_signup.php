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
            mysqli_stmt_close($stmt_check_email);
            header("Location: owner_signup.html");
            exit();
        }
        mysqli_stmt_close($stmt_check_email);

        $sql_check_phone = "SELECT * FROM owners WHERE phone = ?";
        $stmt_check_phone = mysqli_prepare($conn, $sql_check_phone);
        mysqli_stmt_bind_param($stmt_check_phone, "s", $phone);
        mysqli_stmt_execute($stmt_check_phone);
        $result_phone = mysqli_stmt_get_result($stmt_check_phone);

        if (mysqli_num_rows($result_phone) > 0) {
            $_SESSION['error'] = "Phone number is already registered.";
            mysqli_stmt_close($stmt_check_phone);
            header("Location: owner_signup.html");
            exit();
        }
        mysqli_stmt_close($stmt_check_phone);

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $account_role = 'owner';

        // Start transaction for atomicity
        mysqli_begin_transaction($conn);

        try {
            // Insert into accounts table
            $sql_accounts = "INSERT INTO accounts (account_role, owner_email, account_password) VALUES (?, ?, ?)";
            $stmt_accounts = mysqli_prepare($conn, $sql_accounts);

            if (!$stmt_accounts) {
                throw new Exception("Accounts table prepare failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_accounts, "sss", $account_role, $email, $hashed_password);
            if (!mysqli_stmt_execute($stmt_accounts)) {
                throw new Exception("Accounts table insert failed: " . mysqli_error($conn));
            }

            // Get the generated account_id
            $account_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_accounts);

            // Insert into owners table
            $sql_owners = "INSERT INTO owners (account_id, owner_full_name, phone) VALUES (?, ?, ?)";
            $stmt_owners = mysqli_prepare($conn, $sql_owners);

            if (!$stmt_owners) {
                throw new Exception("Owners table prepare failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_owners, "iss", $account_id, $full_name, $phone);
            if (!mysqli_stmt_execute($stmt_owners)) {
                throw new Exception("Owners table insert failed: " . mysqli_error($conn));
            }

            // --- IMPORTANT CHANGE HERE: Get owner_id and set it in session ---
            $owner_id = mysqli_insert_id($conn); // Get the newly generated owner_id
            mysqli_stmt_close($stmt_owners);

            // Commit transaction
            mysqli_commit($conn);

            $_SESSION['owner_id'] = $owner_id; // THIS IS THE FIX
            $_SESSION['account_id'] = $account_id; // Keep account_id if other parts of system use it
            $_SESSION['owner_full_name'] = $full_name;
            $_SESSION['account_role'] = $account_role; // 'owner'
            $_SESSION['loggedin'] = true; // Still a good idea for general login state

            $_SESSION['success'] = "Owner registration successful! Welcome to your dashboard.";
            header("Location: owner_das.php"); // Redirect to owner dashboard
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['error'] = "Registration failed: " . $e->getMessage();
            error_log("Registration Error: " . $e->getMessage()); // Log detailed error
            header("Location: owner_signup.html");
            exit();
        }
    } else {
        // If not all expected POST variables are set
        $_SESSION['error'] = "Incomplete form submission.";
        header("Location: owner_signup.html");
        exit();
    }
} else {
    // If accessed directly without POST request
    header("Location: owner_signup.html");
    exit();
}
?>