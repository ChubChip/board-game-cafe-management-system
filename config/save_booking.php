<?php
session_start();
include('connect.php');

// Check login and required parameters
if (!isset($_SESSION['username']) || !isset($_POST['table_id'])) {
    header("location: ../main/table.php");
    exit();
}

$username = $_SESSION['username'];

// Verify if the customer exists in the database
$sql_user = "SELECT customer_id FROM customer WHERE username = '$username'";
$res_user = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($res_user);

if (!$user_data) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_msg'] = 'Customer record not found! (Admin accounts cannot book tables).';
    header("location: ../main/booking.php?table_id=" . $_POST['table_id']);
    exit();
}

$customer_id = $user_data['customer_id'];

// Input data from form
$table_id = mysqli_real_escape_string($conn, $_POST['table_id']);
$phone_no = mysqli_real_escape_string($conn, $_POST['phone_no']);
$rent_date = mysqli_real_escape_string($conn, $_POST['rent_date']);
$rent_time = mysqli_real_escape_string($conn, $_POST['rent_time']);
$num_players = (int)$_POST['num_players'];

// Check reservation date (must be within 3 days from today)
$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+3 days'));
if ($rent_date < $today || $rent_date > $max_date) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_msg'] = 'Reservations can only be made up to 3 days in advance.';
    header("location: ../main/booking.php?table_id=" . $table_id);
    exit();
}

$error_msg = "";

// --- RULE 1: Max 3 bookings per day for the entire shop ---
$sql_count_daily = "SELECT COUNT(*) as total_books FROM rental WHERE rent_date = '$rent_date' AND status = 'Unpaid'";
$res_count = mysqli_query($conn, $sql_count_daily);
$row_count = mysqli_fetch_assoc($res_count);

if ($row_count['total_books'] >= 3) {
    $error_msg = 'Sorry, the shop has reached the maximum limit of 3 bookings for this date.';
}

// --- RULE 2: Cannot book the SAME table again until the previous one is paid ---
if (empty($error_msg)) {
    // Join rental and rental_detail to check if this user has unpaid booking for this specific table
    $sql_check_unpaid = "SELECT r.rent_id FROM rental r 
                         JOIN rental_detail rd ON r.rent_id = rd.rent_id 
                         WHERE r.customer_id = '$customer_id' 
                         AND rd.table_id = '$table_id' 
                         AND r.status = 'Unpaid'";
    $res_unpaid = mysqli_query($conn, $sql_check_unpaid);

    if (mysqli_num_rows($res_unpaid) > 0) {
        $error_msg = 'You already have an unpaid booking for this table. Please complete the payment first.';
    }
}

// --- RULE 3: Check if the table is already booked by SOMEONE ELSE on the same date and time ---
if (empty($error_msg)) {
    // Check if any active booking exists for this table at this date and time
    $sql_check_conflict = "SELECT r.rent_id FROM rental r 
                           JOIN rental_detail rd ON r.rent_id = rd.rent_id 
                           WHERE rd.table_id = '$table_id' 
                           AND r.rent_date = '$rent_date' 
                           AND r.rent_time = '$rent_time' 
                           AND r.status = 'Unpaid'";
    $res_conflict = mysqli_query($conn, $sql_check_conflict);

    if (mysqli_num_rows($res_conflict) > 0) {
        $error_msg = 'This table is already booked for the selected date and time.';
    }
}

// --- Return error if any rule is violated ---
if (!empty($error_msg)) {
    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_msg'] = $error_msg;
    header("Location: ../main/booking.php?table_id=" . $table_id);
    exit();
}

// Start Transaction
mysqli_begin_transaction($conn);
try {
    // Update customer phone number
    $res1 = mysqli_query($conn, "UPDATE customer SET phone = '$phone_no' WHERE customer_id = '$customer_id'");
    if (!$res1) throw new Exception("Failed to update phone number: " . mysqli_error($conn));

    // --- Generate Custom Running ID safely (e.g. 20260314001) ---
    
    // 1. Create Prefix from current date
    $today_prefix = date("Ymd");

    // 2. Use MAX() to get the highest existing number today to prevent duplicate key errors
    $sql_check = "SELECT MAX(SUBSTRING(rent_id, 9, 3)) as max_id FROM rental WHERE rent_id LIKE '$today_prefix%'";
    $res_check = mysqli_query($conn, $sql_check);
    $row_check = mysqli_fetch_assoc($res_check);
    
    // 3. Next sequence is MAX + 1
    $next_number = ($row_check['max_id'] ? (int)$row_check['max_id'] : 0) + 1;
    
    // 4. Combine Prefix and 3-digit padded number
    $new_rent_id = $today_prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);

    // -----------------------------------------------------------

    // Save booking data to rental table
    $sql_rent = "INSERT INTO rental (rent_id, customer_id, rent_date, rent_time, num_players, booking_type, total_amount, status) 
                 VALUES ('$new_rent_id', '$customer_id', '$rent_date', '$rent_time', '$num_players', 'Online', 0, 'Unpaid')";
    $res2 = mysqli_query($conn, $sql_rent);
    if (!$res2) throw new Exception("Failed to create billing record: " . mysqli_error($conn));
    
    // Link rent_id to table_info in rental_detail
    $sql_detail = "INSERT INTO rental_detail (rent_id, table_id) VALUES ('$new_rent_id', '$table_id')";
    $res3 = mysqli_query($conn, $sql_detail);
    if (!$res3) throw new Exception("Failed to save table details: " . mysqli_error($conn));

    // If all queries succeed, commit the transaction
    mysqli_commit($conn);
    
    $_SESSION['booking_status'] = 'success';
    $_SESSION['booking_msg'] = 'Table booking completed successfully! ID: ' . $new_rent_id;
    header("location: ../main/home.php");
    exit();

} catch (Exception $e) {
    // If any error occurs, rollback all database changes
    mysqli_rollback($conn);

    $_SESSION['booking_status'] = 'error';
    $_SESSION['booking_msg'] = 'An error occurred: ' . $e->getMessage();
    header("location: ../main/booking.php?table_id=" . $table_id);
    exit();
}
?>