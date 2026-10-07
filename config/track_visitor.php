<?php
require_once(dirname(__FILE__) . '/connect.php');

// ตรวจสอบว่ามีส่งค่ามาจาก JavaScript หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_tab_visit'])) {
    
    // Generate a unique token for the visitor
    $visitor_token = bin2hex(random_bytes(8)); 
    $sql = "INSERT INTO visitor_log (visitor_token, visit_date, type_user) 
            VALUES ('$visitor_token', NOW(), 'C01')";
    
    // Execute query
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>