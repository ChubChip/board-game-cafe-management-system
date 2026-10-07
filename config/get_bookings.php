<?php
/* Include database connection */
require_once('../connect.php');

/* Fetch booking data */
$sql = "SELECT b.booking_id, b.date_booking, t.table_name 
        FROM booking_boardgame b
        JOIN table_info t ON b.table_id = t.table_id";
$result = $conn->query($sql);

$events = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = array(
            'id'    => $row['booking_id'],
            'title' => "Reserved: " . $row['table_name'],
            'start' => $row['date_booking'],
            'color' => '#E5989B' // Using --pink color for booked dates
        );
    }
}

/* Output as JSON */
header('Content-Type: application/json');
echo json_encode($events);
?>