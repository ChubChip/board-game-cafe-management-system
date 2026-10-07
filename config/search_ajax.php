<?php
require_once '../config/connect.php';

if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    $searchTerm = "%" . $q . "%";
    
    // Prepare SQL to search for board games by name
    $sql = "SELECT boardgame_id, boardgame_name, upload FROM boardgame WHERE boardgame_name LIKE ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Set image path: use uploaded file or fallback to default
                $img = !empty($row['upload']) ? "../img/boardgame/" . htmlspecialchars($row['upload']) : "../img/default_bg.jpg";
                
                // Output search result items
                echo '<a href="../main/boardgame_detail.php?id=' . $row['boardgame_id'] . '" class="search-item">';
                echo '<img src="' . $img . '" alt="' . htmlspecialchars($row['boardgame_name']) . '" />';
                echo '<strong>' . htmlspecialchars($row['boardgame_name']) . '</strong>';
                echo '</a>';
            }
        } else {
            // Display message if no board games are found
            echo '<p style="text-align:center; color:#999; margin-top:20px;">No board games found matching your search.</p>';
        }
    }
}
?>