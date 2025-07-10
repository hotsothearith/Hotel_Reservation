<?php 
session_start(); 
include "db_conn.php"; // Ensure the database connection works correctly

// Check if the username and password are set
if (isset($_POST['username']) && isset($_POST['password'])) {

    // Function to validate user input
    function validate($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // Get and validate input
    $username = validate($_POST['username']);
    $password = validate($_POST['password']);

    // Check if username is empty
    if (empty($username)) {
        $_SESSION['error'] = "Username is required";
        header("Location: user_signin.html");
        exit();
    } 
    // Check if password is empty
    else if (empty($password)) {
        $_SESSION['error'] = "Password is required";
        header("Location: user_signin.html");
        exit();
    } 
    else {
        // Check database connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Prepare SQL query to fetch user by username
        $sql = "SELECT a.username, a.account_password, u.user_full_name, u.user_id 
                FROM accounts a
                INNER JOIN users u ON u.account_id = a.account_id
                WHERE a.username = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            // Bind the username parameter to the query
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check if the user exists
            if (mysqli_num_rows($result) === 1) {
                $row = mysqli_fetch_assoc($result);

                // Verify the password using password_verify
                if (password_verify($password, $row['account_password'])) {
                    // Store user session data
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['user_full_name'] = $row['user_full_name'];
                    $_SESSION['user_id'] = $row['user_id'];

                    // Redirect to home page
                    header("Location: user_home.php");
                    exit();
                } else {
                    // Incorrect password
                    $_SESSION['error'] = "Incorrect username or password";
                    header("Location: user_signin.html");
                    exit();
                }
            } else {
                // User not found
                $_SESSION['error'] = "Incorrect username or password";
                header("Location: user_signin.html");
                exit();
            }
        } else {
            // Query preparation failed
            $_SESSION['error'] = "Database query failed";
            header("Location: user_signin.html");
            exit();
        }
    }
} else {
    // Redirect to sign-in page if the request is invalid
    header("Location: user_signin.html");
    exit();
}
?>
