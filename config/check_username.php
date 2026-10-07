<?php
// config/check_username.php
require_once 'connect.php';

header('Content-Type: application/json');

if (isset($_POST['username'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Check if username exists in customer table or admins table
    $sql = "SELECT username FROM customer WHERE username = '$username' 
            UNION 
            SELECT username FROM admins WHERE username = '$username'";
            
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        // Username is taken
        echo json_encode(['status' => 'taken']);
    } else {
        // Username is available
        echo json_encode(['status' => 'available']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No username provided']);
}
?>