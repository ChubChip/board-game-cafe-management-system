<?php
// config/cancel_booking.php
session_start();
include('connect.php');

header('Content-Type: application/json');

// Check if user is logged in and rent_id is provided
if (!isset($_SESSION['user_id']) || !isset($_POST['rent_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing data.']);
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$rent_id = mysqli_real_escape_string($conn, $_POST['rent_id']);

// 1. Verify that this booking belongs to the current user
$sql_check = "SELECT status FROM rental WHERE rent_id = '$rent_id' AND customer_id = '$user_id'";
$res_check = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($res_check) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found or access denied.']);
    exit();
}

$row = mysqli_fetch_assoc($res_check);

// 2. Only allow cancellation if status is 'Unpaid'
if ($row['status'] != 'Unpaid') {
    echo json_encode(['status' => 'error', 'message' => 'Only unpaid bookings can be cancelled.']);
    exit();
}

// 3. Update status to 'Cancelled'
$sql_update = "UPDATE rental SET status = 'Cancelled' WHERE rent_id = '$rent_id'";
if (mysqli_query($conn, $sql_update)) {
    echo json_encode(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>