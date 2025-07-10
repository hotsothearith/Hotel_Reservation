<?php 
session_start(); 
include "db_conn.php"; // Ensure the database connection works correctly

// Check if email and password are set
if (isset($_POST['email']) && isset($_POST['password'])) {

    // Function to validate user input
    function validate($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // Get and validate input
    $email = validate($_POST['email']);
    $password = validate($_POST['password']);

    // Check if email is empty
    if (empty($email)) {
        $_SESSION['error'] = "Email is required";
        header("Location: owner_signin.html");
        exit();
    } 
    // Check if password is empty
    else if (empty($password)) {
        $_SESSION['error'] = "Password is required";
        header("Location: owner_signin.html");
        exit();
    } 
    else {
        // Check database connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Prepare SQL query to fetch user by email
        $sql = "SELECT a.account_id, a.owner_email, a.account_password, o.owner_full_name, o.owner_id 
        FROM accounts a
        INNER JOIN owners o ON o.account_id = a.account_id
        WHERE a.owner_email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            // Bind the email parameter to the query
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check if the user exists
            if (mysqli_num_rows($result) === 1) {
                $row = mysqli_fetch_assoc($result);

                // Verify the password using password_verify
                if (password_verify($password, $row['account_password'])) {
                    // Store user session data
                    $_SESSION['account_id'] = $row['account_id'];
                    $_SESSION['owner_email'] = $row['owner_email'];
                    $_SESSION['owner_full_name'] = $row['owner_full_name'];
                    $_SESSION['owner_id'] = $row['owner_id'];

                    // Close statement and connection
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);

                    // Redirect to owner home page
                    header("Location: owner_das.php");
                    exit();
                } else {
                    // Incorrect password
                    $_SESSION['error'] = "Incorrect email or password";
                }
            } else {
                // User not found
                $_SESSION['error'] = "Incorrect email or password";
            }
            mysqli_stmt_close($stmt);
        } else {
            // Query preparation failed
            $_SESSION['error'] = "Database query failed";
        }

        // Close database connection
        mysqli_close($conn);
        header("Location: owner_signin.html");
        exit();
    }
} else {
    // Redirect to sign-in page if the request is invalid
    header("Location: owner_signin.html");
    exit();
}
?>
