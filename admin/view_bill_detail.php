<?php
session_start();
require_once '../config/connect.php';

// 1. Admin Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access Denied: You do not have permission to view this page.";
    header("Location: ../main/index.php");
    exit();
}

// 2. Check if Bill ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../manage_bill.php");
    exit();
}

$rent_id = mysqli_real_escape_string($conn, $_GET['id']);

// 3. Fetch Bill and Primary Customer Data
$sql_bill = "SELECT r.*, c.username AS customer_name, c.email, c.phone 
             FROM rental r 
             LEFT JOIN customer c ON r.customer_id = c.customer_id 
             WHERE r.rent_id = '$rent_id'";
$res_bill = mysqli_query($conn, $sql_bill);

if (mysqli_num_rows($res_bill) == 0) {
    echo "<h2 style='text-align:center; color:red; margin-top:50px;'>Bill record not found in the system.</h2>";
    echo "<div style='text-align:center;'><a href='../manage_bill.php'>Return to Bill Management</a></div>";
    exit();
}
$bill = mysqli_fetch_assoc($res_bill);

// 4. Fetch Rental Details (Table and Time)
$sql_detail = "SELECT rd.*, t.table_name 
                FROM rental_detail rd 
                LEFT JOIN table_info t ON rd.table_id = t.table_id 
                WHERE rd.rent_id = '$rent_id'";
$res_detail = mysqli_query($conn, $sql_detail);
$detail = mysqli_fetch_assoc($res_detail);

// 5. Fetch Board Games rented in this bill
$sql_games = "SELECT b.boardgame_name 
              FROM rental_boardgame rb 
              JOIN boardgame b ON rb.boardgame_id = b.boardgame_id 
              WHERE rb.rent_id = '$rent_id'";
$res_games = mysqli_query($conn, $sql_games);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Bill Details #<?php echo $rent_id; ?></title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/bill.css">
</head>
<body>
<div class="receipt-card">
    <div class="header">
        <h1>Order Summary</h1>
        <p>Ref. No.: #<?php echo $rent_id; ?></p>
        <p>Transaction Date: <?php echo date('d/m/Y', strtotime($bill['rent_date'])); ?></p>
        
        <p style="margin-top: 10px;">
            <strong>Status: </strong>
            <?php 
                $status = strtolower($bill['status']);
                if ($status == 'unpaid') echo "<span style='color:orange;'>Unpaid</span>";
                elseif ($status == 'paid') echo "<span style='color:green;'>Paid</span>";
                else echo "<span>" . htmlspecialchars($bill['status']) . "</span>";
            ?>
        </p>
    </div>

    <div class="section-title">Customer Info</div>
    <div class="info-row">
        <span class="label">Customer:</span>
        <span><?php echo htmlspecialchars($bill['customer_name'] ?? 'Unknown'); ?></span>
    </div>
    <div class="info-row">
        <span class="label">Tel:</span>
        <span><?php echo htmlspecialchars($bill['phone'] ?? '-'); ?></span>
    </div>
    <div class="info-row">
        <span class="label">E-mail:</span>
        <span><?php echo htmlspecialchars($bill['email'] ?? '-'); ?></span>
    </div>

    <div class="section-title">Table Usage</div>
    <?php if ($detail): ?>
        <div class="info-row">
            <span class="label">Table name:</span>
            <span><?php echo htmlspecialchars($detail['table_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Start time:</span>
            <span><?php echo !empty($detail['start_time']) ? date('H:i', strtotime($detail['start_time'])) : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="label">End time:</span>
            <span><?php echo !empty($detail['end_time']) ? date('H:i', strtotime($detail['end_time'])) : '-'; ?></span>
        </div>
        <div class="info-row">
            <span class="label">Total Playtime:</span>
            <span><?php echo htmlspecialchars($detail['duration'] ?? '-'); ?></span>
        </div>
    <?php else: ?>
        <p style="color:#888; font-size:14px; text-align:center;">- No table session recorded -</p>
    <?php endif; ?>

    <div class="section-title">Game List</div>
    <?php if (mysqli_num_rows($res_games) > 0): ?>
        <ul class="games-list">
            <?php while ($game = mysqli_fetch_assoc($res_games)): ?>
                <li>🎲 <?php echo htmlspecialchars($game['boardgame_name']); ?></li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p style="color:#888; font-size:14px; text-align:center;">- No games selected -</p>
    <?php endif; ?>

    <div class="total-box">
        <h2>Total: <span>฿<?php echo number_format($bill['total_amount'] ?? $bill['total_price'] ?? 0, 2); ?></span></h2>
    </div>

    <div class="btn-group">
        <button class="btn btn-print" onclick="window.print()">🧾 Print Receipt</button>
        <a href="manage_bill.php" class="btn btn-back">← Back</a>
    </div>

</div>

</body>
</html>