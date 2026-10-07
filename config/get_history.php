<?php
session_start();
include_once('connect.php'); // Database connection file

// Set response header to JSON
header('Content-Type: application/json');

// Login verification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch table reservation data (rental and rental_detail)
$sql = "SELECT r.rent_id, r.rent_date, r.rent_time, rd.start_time, rd.end_time, rd.duration, t.table_name 
        FROM rental r
        LEFT JOIN rental_detail rd ON r.rent_id = rd.rent_id
        LEFT JOIN table_info t ON rd.table_id = t.table_id
        WHERE r.customer_id = ?
        ORDER BY r.rent_date DESC, r.rent_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history_data = [];

while ($row = $result->fetch_assoc()) {
    $rent_id = $row['rent_id'];
    
    // 2. Fetch board games rented in that specific session
    $games_sql = "SELECT b.boardgame_name 
                  FROM rental_boardgame rb
                  JOIN boardgame b ON rb.boardgame_id = b.boardgame_id
                  WHERE rb.rent_id = ?";
    $games_stmt = $conn->prepare($games_sql);
    $games_stmt->bind_param("i", $rent_id);
    $games_stmt->execute();
    $games_result = $games_stmt->get_result();
    
    $game_names = [];
    while ($game_row = $games_result->fetch_assoc()) {
        $game_names[] = $game_row['boardgame_name'];
    }
    
    // Concatenate game names, or display 'No games rented' if empty
    $games_str = empty($game_names) ? 'No board games rented' : implode(', ', $game_names);
    
    // 3. Format Date and Time
    $rent_date = date('d/m/Y', strtotime($row['rent_date']));
    $actual_date = null;
    $start_time = null;
    $end_time = null;
    $duration = null;
    
    if (!empty($row['start_time'])) {
        $actual_date = date('d/m/Y', strtotime($row['start_time']));
        $start_time = date('H:i', strtotime($row['start_time']));
    }
    
    if (!empty($row['end_time'])) {
        $end_time = date('H:i', strtotime($row['end_time']));
    }
    
    // Format duration string to English (e.g., 2 hrs 30 mins)
    if (!empty($row['duration'])) {
        $duration_parts = explode(':', $row['duration']);
        if (count($duration_parts) >= 2) {
            $hours = (int)$duration_parts[0];
            $mins = (int)$duration_parts[1];
            if ($hours > 0 && $mins > 0) {
                $duration = "{$hours} hr(s) {$mins} min(s)";
            } elseif ($hours > 0) {
                $duration = "{$hours} hr(s)";
            } else {
                $duration = "{$mins} min(s)";
            }
        }
    }
    
    $history_data[] = [
        'rent_date' => $rent_date,
        'actual_date' => $actual_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'duration' => $duration,
        'table_name' => $row['table_name'] ?? 'N/A',
        'games' => $games_str
    ];
}

// Send data back to the JavaScript caller (e.g., topbar.php)
echo json_encode(['status' => 'success', 'data' => $history_data]);
?>