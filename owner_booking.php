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

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the owner ID from the session
$owner_id = $_SESSION['owner_id'];

// --- Handle Accept/Decline Actions ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id']) && isset($_POST['action'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $action = $_POST['action']; // 'accept' or 'decline'

    $new_status = '';
    if ($action === 'accept') {
        $new_status = 'confirmed';
    } elseif ($action === 'decline') {
        $new_status = 'declined';
    }

    if ($new_status && $booking_id) {
        // Ensure the owner is authorized to update this booking
        $verify_owner_query = "
            SELECT b.booking_id
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN hotels h ON r.hotel_id = h.hotel_id
            WHERE b.booking_id = ? AND h.owner_id = ?
        ";
        $verify_owner_stmt = $conn->prepare($verify_owner_query);
        if ($verify_owner_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            // Handle error, e.g., redirect or show message
        } else {
            $verify_owner_stmt->bind_param("ii", $booking_id, $owner_id);
            $verify_owner_stmt->execute();
            $verify_owner_stmt->store_result();

            if ($verify_owner_stmt->num_rows > 0) {
                // Owner is authorized, proceed with update
                $update_query = "UPDATE bookings SET status = ? WHERE booking_id = ?";
                $update_stmt = $conn->prepare($update_query);
                if ($update_stmt === false) {
                    error_log("Prepare failed: " . $conn->error);
                    // Handle error
                } else {
                    $update_stmt->bind_param("si", $new_status, $booking_id);
                    if ($update_stmt->execute()) {
                        // Success: Redirect to prevent form resubmission
                        header("Location: owner_booking.php?status=success&action=" . $action);
                        exit();
                    } else {
                        // Error updating
                        error_log("Execute failed: " . $update_stmt->error);
                        header("Location: owner_booking.php?status=error&action=" . $action);
                        exit();
                    }
                    $update_stmt->close();
                }
            } else {
                // Not authorized
                error_log("Unauthorized attempt to update booking ID: " . $booking_id . " by owner ID: " . $owner_id);
                header("Location: owner_booking.php?status=unauthorized");
                exit();
            }
            $verify_owner_stmt->close();
        }
    }
}
// --- End Handle Accept/Decline Actions ---


// Fetch owner details (name and profile image) from the `owners` table
$owner_query = "SELECT owner_full_name, owner_image FROM owners WHERE owner_id = ?";
$owner_stmt = $conn->prepare($owner_query);
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();
$owner_stmt->close(); // Close owner statement


// Fetch current booking lists for the logged-in owner
// We'll fetch bookings with 'pending' status, assuming these are the "Current Booking Lists"
$bookings_query = "
    SELECT
        b.booking_id,
        b.check_in_date,
        b.check_out_date,
        b.number_of_guests,
        r.room_type,
        r.room_image,
        u.user_full_name AS guest_name
    FROM
        bookings b
    JOIN
        rooms r ON b.room_id = r.room_id
    JOIN
        hotels h ON r.hotel_id = h.hotel_id
    JOIN
        users u ON b.user_id = u.user_id
    WHERE
        h.owner_id = ?
    ORDER BY
        b.booked_at DESC;
";

$bookings_stmt = $conn->prepare($bookings_query);
if ($bookings_stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$bookings_stmt->bind_param("i", $owner_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Bookings</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="owner_booking.css">
</head>

<body>
    <div class="page-container">
        <div class="side-bar">
            <div class="logo">
                <img src="image/NokorRealm.png" alt="Logo">
            </div>

            <ul class="menu">
                <li><a href="owner_das.php"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
                <li><a href="owner_booking.php" id="booking-active"><i class="material-icons">event</i><span>Bookings</span></a></li>
                <li><a href="owner_hotel.php"><span class="material-symbols-outlined">apartment</span><span>Hotels</span></a></li>
                <li><a href="owner_history.php"><i class="material-icons">history</i><span>History</span></a></li>
                <li><a href="#"><i class="material-icons">help</i><span>Help</span></a></li>
                <li><a href="owner_setting.php"><i class="material-icons">settings</i><span>Setting</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header-content">
                <div class="hamburger-menu">
                    <span class="material-symbols-outlined">menu</span>
                </div>
                <div class="nav-bar">
                    <h2>Hello, <?php echo htmlspecialchars($owner['owner_full_name'] ?? 'Owner'); ?></h2>
                    <p>Welcome back</p>
                </div>
                <div class="header-actions">
                    <!-- <div class="ring-icon">
                        <span class="material-symbols-outlined">notifications</span>
                    </div> -->
                    <div class="profile">
                        <img
                            src="<?php echo htmlspecialchars($owner['owner_image'] ?? 'image/default_profile.png'); ?>"
                            alt="Profile Picture">
                        <div class="name-user">
                            <h3><?php echo htmlspecialchars($owner['owner_full_name'] ?? 'Owner Name'); ?></h3>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-bar-section">
                <h2>Current Booking Lists</h2>
                <div class="search-input-container">
                    <input type="text" placeholder="Search bookings">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <div class="content-area">
                <div class="card-container">
                    <?php if ($bookings_result->num_rows > 0): ?>
                        <?php while($booking = $bookings_result->fetch_assoc()):
                            // Calculate duration (simple date difference in days)
                            $check_in_ts = strtotime($booking['check_in_date']);
                            $check_out_ts = strtotime($booking['check_out_date']);
                            $duration_days = ($check_out_ts && $check_in_ts) ? round(abs($check_out_ts - $check_in_ts) / (60 * 60 * 24)) : 'N/A';
                            $duration_text = ($duration_days == 1) ? "1 Day" : ($duration_days > 1 ? "{$duration_days} Days" : "Same day");

                            // Use default image if room_image is empty or null
                            // $room_image_path = htmlspecialchars($booking['room_image'] ?? 'image/default_room.png');
                            // if (!file_exists($room_image_path) || empty($booking['room_image'])) {
                            //     $room_image_path = 'image/default_room.png'; // Fallback to a generic image
                            // }
                        ?>
                        <div class="booking-card">
                            <img src="image/<?php echo htmlspecialchars($booking['room_image']); ?>" alt="Hotel Image" class="room-image">
                            <div class="room-details">
                                <h2><?php echo htmlspecialchars($booking['room_type'] ?? 'N/A Room Type'); ?></h2>
                                <p class="booking-info">
                                    <strong>Check In:</strong> <?php echo htmlspecialchars(date('d M Y', strtotime($booking['check_in_date'])) ?? 'N/A'); ?> &nbsp;
                                    <strong>Check Out:</strong> <?php echo htmlspecialchars(date('d M Y', strtotime($booking['check_out_date'])) ?? 'N/A'); ?> &nbsp;
                                    <strong>Duration:</strong> <?php echo $duration_text; ?> &nbsp;
                                    <strong>Guests:</strong> <?php echo htmlspecialchars($booking['number_of_guests'] ?? 'N/A'); ?> Adults
                                </p>
                                <p class="booked-by"><strong>Booked by: </strong><?php echo htmlspecialchars($booking['guest_name'] ?? 'N/A'); ?></p>
                            </div>
                            <!-- <div class="actions">
                                <form method="POST" action="owner_booking.php" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="accept-btn">Accept</button>
                                </form>
                                <form method="POST" action="owner_booking.php" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="decline-btn">Decline</button>
                                </form>
                            </div> -->
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; width: 100%; padding: 20px; color: #777;">No pending bookings found.</p>
                    <?php endif; ?>
                </div>
            </div> </div> </div> <script>
        // JavaScript for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.side-bar');
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            const pageContainer = document.querySelector('.page-container');

            if (hamburgerMenu) {
                hamburgerMenu.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    if (sidebar.classList.contains('open')) {
                        let overlay = document.querySelector('.sidebar-overlay');
                        if (!overlay) { // Create overlay only if it doesn't exist
                            overlay = document.createElement('div');
                            overlay.classList.add('sidebar-overlay');
                            pageContainer.appendChild(overlay);
                        }
                        overlay.addEventListener('click', function() {
                            sidebar.classList.remove('open');
                            overlay.remove();
                        }, { once: true }); // Ensure event listener is removed after one use
                    } else {
                        document.querySelector('.sidebar-overlay')?.remove();
                    }
                });
            }

            // Close sidebar if screen resizes to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) { // Adjust breakpoint to match your CSS media query
                    sidebar.classList.remove('open');
                    document.querySelector('.sidebar-overlay')?.remove();
                }
            });
        });
    </script>
</body>

</html>
<?php
// Close the database connection
$bookings_stmt->close();
$conn->close();
?>