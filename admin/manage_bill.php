<?php
session_start();
require_once '../config/connect.php';

// ==========================================
// 1. Security System: Admin Access Only
// ==========================================
// If not logged in or the role is not 'admin', redirect to the main page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Permission Denied: You must be an admin to access this page.";
    header("Location: ../main/index.php");
    exit();
}

// ==========================================
// 2. Data Filtering System
// ==========================================
// Get values from the search query (if any)
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$search_text = isset($_GET['search_text']) ? $_GET['search_text'] : '';

// Create WHERE conditions for the SQL query
$conditions = [];

if ($filter_date != '') {
    // Assuming the rental table has a column named rent_date
    $conditions[] = "DATE(r.rent_date) = '" . mysqli_real_escape_string($conn, $filter_date) . "'";
}
if ($filter_status != '') {
    // Column status values: 'pending', 'paid', 'returned'
    $conditions[] = "r.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}
if ($search_text != '') {
    // Search by Bill ID or Customer Username
    $search_clean = mysqli_real_escape_string($conn, $search_text);
    $conditions[] = "(r.rent_id LIKE '%$search_clean%' OR c.username LIKE '%$search_clean%')";
}

// Combine conditions
$where_sql = "";
if (count($conditions) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $conditions);
}

// ==========================================
// 3. Database Query
// ==========================================
// Fetch rental data joined with customer names
$sql = "SELECT r.*, c.username AS customer_name 
        FROM rental r 
        LEFT JOIN customer c ON r.customer_id = c.customer_id 
        $where_sql 
        ORDER BY r.rent_id DESC"; // Latest bills first

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Manage bill</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/bill.css">
</head>
<body>
<?php include_once('../include/topbar.php'); ?>
<div class="container">
    
    <h2>📋 Manage All Bills (Rental Records)</h2>

    <form action="" method="GET" class="filter-form">
        <div class="input-group">
            <label>Search Bill ID / Customer Name</label>
            <input type="text" name="search_text" placeholder="Enter search term..." value="<?php echo htmlspecialchars($search_text); ?>">
        </div>
        <div class="input-group">
            <label>Booking Date</label>
            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="input-group">
            <label>Status</label>
            <select name="filter_status">
                <option value="">-- All --</option>
                <option value="unpaid" <?php if($filter_status == 'unpaid') echo 'selected'; ?>>Unpaid</option>
                <option value="paid" <?php if($filter_status == 'paid') echo 'selected'; ?>>Paid</option>
            </select>
        </div>
        <button type="submit" class="btn">🔍 Filter Data</button>
        <a href="manage_bill.php" class="btn-clear">Clear Filters</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Bill ID</th>
                <th>Booking Date</th>
                <th>Customer Name</th>
                <th>Total Amount (THB)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row['rent_id']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['rent_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <?php 
                                // Check status and display color/text (adjusted to English)
                                if (isset($row['status'])) {
                                    if ($row['status'] == 'pending') echo "<span style='color:orange;'>Pending Payment</span>";
                                    elseif ($row['status'] == 'paid') echo "<span style='color:green;'>Paid</span>";
                                    else echo htmlspecialchars(ucfirst($row['status']));
                                } else {
                                    echo "No Data";
                                }
                            ?>
                        </td>
                        <td>
                            <a href="view_bill_detail.php?id=<?php echo $row['rent_id']; ?>" class="btn-view">👁️ View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No results found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>