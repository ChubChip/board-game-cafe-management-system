<?php
session_start();
// Note: Please verify the path to connect.php to ensure it points to the correct folder
include_once('../config/connect.php'); 

// Set the header to return JSON for AJAX requests
header('Content-Type: application/json');

// Check if the user is logged in and if a message was actually sent
if (!isset($_SESSION['user_id']) || !isset($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing data']);
    exit;
}

$customer_id = $_SESSION['user_id'];
// Prevent SQL Injection
$message_text = mysqli_real_escape_string($conn, trim($_POST['message']));
$sender_type = 'customer'; // Define the sender role

if(empty($message_text)){
    echo json_encode(['status' => 'error', 'message' => 'Message is empty']);
    exit;
}

// Save the message to the database
$sql = "INSERT INTO messages (customer_id, sender_type, message_text, created_at) 
        VALUES ('$customer_id', '$sender_type', '$message_text', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
?>