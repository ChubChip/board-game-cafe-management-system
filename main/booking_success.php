<?php
session_start();
// Note: Check the path to connect.php to ensure it points to the correct folder
include_once('../config/connect.php'); 

// Check Login and Parameter ID
if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    header("location: ../main/booking.php");
    exit();
}

$rent_id = mysqli_real_escape_string($conn, $_GET['id']);
$customer_id = $_SESSION['customer_id'];

// Fetch booking data joined with table details
$sql = "SELECT r.*, t.table_name, t.table_name_th 
        FROM rental r
        JOIN rental_detail rd ON r.rent_id = rd.rent_id
        JOIN table_info t ON rd.table_id = t.table_id
        WHERE r.rent_id = '$rent_id' AND r.customer_id = '$customer_id'";

$result = mysqli_query($conn, $sql);
$booking = mysqli_fetch_assoc($result);

// If booking data is not found
if (!$booking) {
    echo "<script>alert('Booking information not found.'); window.location='booking.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Booking success</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/styles.css">
    <link rel="stylesheet" href="../style/booking_success.css">
</head>
<body>

    <?php include('../include/topbar.php'); ?>

    <div class="success-wrapper">
        <div class="success-card">
            <div class="success-icon">
                <img src="../img/system/check-icon.png" alt="Success" width="80">
            </div>
            
            <h1>Booking Table Successfully!</h1>
            <p class="subtitle">Please provide your Booking ID to the staff when you arrive.</p>

            <div class="booking-details">
                <div class="detail-row">
                    <span>Booking ID:</span>
                    <strong>#<?php echo str_pad($booking['rent_id'], 5, '0', STR_PAD_LEFT); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Booking Date:</span>
                    <strong><?php echo date('d/M/Y', strtotime($booking['rent_date'])); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Table:</span>
                    <strong><?php echo htmlspecialchars($booking['table_name']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Players:</span>
                    <strong>
                        <?php 
                            $players = (int)$booking['num_players'];
                            echo $players . ($players > 1 ? " people" : " person"); 
                        ?>
                    </strong>
                </div>
                <div class="detail-row">
                    <span>Payment Status:</span>
                    <strong class="status-unpaid"><?php echo ucfirst(strtolower($booking['status'])); ?></strong>
                </div>
            </div>

            <div class="next-steps">
                <h3>Next Steps:</h3>
                <ol>
                    <li>A deposit is required within 30 minutes to confirm your reservation.</li>
                    <li>Please arrive at least 15 minutes before your scheduled time.</li>
                </ol>
            </div>

            <div class="action-buttons">
                <a href="payment.php?rent_id=<?php echo $rent_id; ?>" class="btn-pay">Proceed to Payment</a>
                <a href="index.php" class="btn-home">Back to Home</a>
            </div>
        </div>
    </div>

</body>
</html>