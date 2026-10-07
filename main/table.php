<?php
session_start();
require_once '../config/connect.php';

// Get today's date to check current bookings
$today = date('Y-m-d');

// Fetch all tables and check if they have any 'Unpaid' bookings for today
$sql = "SELECT t.table_id, t.table_name, 
               (SELECT COUNT(*) 
                FROM rental r 
                JOIN rental_detail rd ON r.rent_id = rd.rent_id 
                WHERE rd.table_id = t.table_id 
                AND r.rent_date = '$today' 
                AND r.status = 'Unpaid') as booked_count
        FROM table_info t ORDER BY t.table_id ASC";
        
$result = $conn->query($sql);

$tables = [];
$table_status = []; // Array to store true (Booked) or false (Available)

while($row = $result->fetch_assoc()) {
    $tables[$row['table_id']] = $row['table_name'];
    // If booked_count > 0, the table is currently booked today
    $table_status[$row['table_id']] = ($row['booked_count'] > 0);
}

// Check if user is logged in to determine the back URL
$back_url = isset($_SESSION['username']) ? 'home.php' : '../index.php'; 

// Helper function to render table buttons cleanly
function getTableHTML($id, $class_name, $tables, $table_status) {
    $name = isset($tables[$id]) ? $tables[$id] : "Table $id";
    $is_booked = isset($table_status[$id]) ? $table_status[$id] : false;
    
    // Assign classes and text based on status
    $status_class = $is_booked ? 'booked' : 'available';
    $status_text = $is_booked ? 'Booked' : 'Available';
    
    return "<a class=\"table {$class_name} {$status_class}\" href=\"booking.php?table_id={$id}\">
                <span class=\"t-name\">{$name}</span>
                <span class=\"t-status\">{$status_text}</span>
            </a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Table booking</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/table.css">
    <link rel="stylesheet" href="../style/back.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="container">
    <div class="header">
        <a href="<?php echo $back_url; ?>" class="back-btn">
            ← Back
        </a>
        <h1>Table Selection</h1>
    </div>
    
    <div class="table-container">
        <?php echo getTableHTML(8, 'table1', $tables, $table_status); ?>
        <div class="space space1">2</div>
        
        <?php echo getTableHTML(9, 'table2', $tables, $table_status); ?>
        <div class="space space2">4</div>
        
        <?php echo getTableHTML(1, 'table3', $tables, $table_status); ?>
        <div class="space space3">6</div>
        
        <?php echo getTableHTML(2, 'table4', $tables, $table_status); ?>
        <div class="space space4">8</div>
        <div class="space space5">9</div>
        
        <?php echo getTableHTML(3, 'table5', $tables, $table_status); ?>
        <?php echo getTableHTML(4, 'table6', $tables, $table_status); ?>
        
        <div class="space space6">12</div>
        <div class="space space7">13</div>
        
        <?php echo getTableHTML(5, 'table7', $tables, $table_status); ?>
        <?php echo getTableHTML(6, 'table8', $tables, $table_status); ?>
        
        <div class="space space8">16</div>
        
        <?php echo getTableHTML(10, 'table9', $tables, $table_status); ?>
        
        <div class="space space9">18</div>
        <div class="space space10">19</div>
        <div class="space space11">24</div>

        <div class="counter">Counter</div>
        <a class="boardgame" href="boardgame.php">Board Game Area</a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                title: 'Booking Success!',
                text: '<?php echo $_SESSION['success']; ?>',
                timer: 3000,
                showConfirmButton: false,
                iconColor: '#576d5c',
                customClass: {
                    popup: 'custom-swal-popup'
                }
            });
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

</body>
</html>