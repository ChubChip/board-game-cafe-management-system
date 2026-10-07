<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$boardgame_id = $_POST['boardgame_id'] ?? '';

if (empty($boardgame_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Boardgame ID']);
    exit();
}

// เช็คว่าเคยกดไว้หรือยัง
$check_sql = "SELECT fav_id FROM favorites WHERE customer_id = ? AND boardgame_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("is", $user_id, $boardgame_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // ถ้ามีอยู่แล้ว -> แปลว่าต้องการ "เอาออก" (Unlike)
    $del_sql = "DELETE FROM favorites WHERE customer_id = ? AND boardgame_id = ?";
    $del_stmt = $conn->prepare($del_sql);
    $del_stmt->bind_param("is", $user_id, $boardgame_id);
    if($del_stmt->execute()) {
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove from favorites']);
    }
} else {
    // ถ้ายังไม่มี -> แปลว่าต้องการ "เพิ่ม" (Like)
    $insert_sql = "INSERT INTO favorites (customer_id, boardgame_id) VALUES (?, ?)";
    $in_stmt = $conn->prepare($insert_sql);
    $in_stmt->bind_param("is", $user_id, $boardgame_id);
    if($in_stmt->execute()) {
        echo json_encode(['status' => 'success', 'action' => 'added']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add to favorites']);
    }
}
?>