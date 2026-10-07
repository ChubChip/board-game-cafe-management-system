<?php
// Start the session to check if the user is logged in
session_start();
require_once '../config/connect.php'; 

// Check if logged in to determine the back button URL
// Note: If your home.php or index.php are in a different folder (like '../index.php'), please adjust the path here.
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    // Query for searched items (Filters by name or category)
    $sql = "SELECT b.*, c.category_name 
            FROM boardgame b 
            LEFT JOIN type_boardgame tb ON b.boardgame_id = tb.boardgame_id
            LEFT JOIN category c ON tb.category_id = c.category_id
            WHERE b.boardgame_name LIKE ? OR c.category_name LIKE ?
            GROUP BY b.boardgame_id";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%"; 
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
} else {
    // Default query to show all games
    $sql = "SELECT b.*, c.category_name 
            FROM boardgame b 
            LEFT JOIN type_boardgame tb ON b.boardgame_id = tb.boardgame_id
            LEFT JOIN category c ON tb.category_id = c.category_id
            GROUP BY b.boardgame_id";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Boardgame</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/boardgame.css">
    <link rel="stylesheet" href="../style/back.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <button onclick="window.location.href='<?= $backUrl ?>'" class="back-btn">← Back</button>

            <h1>All Board Games</h1>

            <div class="search-wrapper">
                <form action="boardgame.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search for a board game..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        🔍 Search
                    </button>
                </form>
            </div>

            <?php if ($search !== ''): ?>
                <h2 class="search-result-text">
                    Results for: "<?php echo htmlspecialchars($search); ?>"
                </h2>
            <?php endif; ?>

            <div class="bg-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        // Determine status indicator color
                        $statusColor = 'red';
                        if ($row['status'] == 'Available') $statusColor = 'green';
                        elseif ($row['status'] == 'Maintenance') $statusColor = 'orange';

                        // Calculate difficulty bar percentage (1-5 scale)
                        $difficulty = (float)$row['difficulty'];
                        $diffPercent = ($difficulty / 5) * 100;

                        $imagePath = "../img/default_bg.jpg";

                        if (!empty($row['upload'])) {
                            $filename = $row['upload'];
                            $checkPath = "../img/boardgame/" . $filename;
                            
                            if (file_exists($checkPath)) {
                                $imagePath = $checkPath;
                            }
                        }
                    ?>
                        <div class="bg-card">
                            <div class="status-circle" style="background-color: <?= $statusColor ?>;" title="<?= htmlspecialchars($row['status']) ?>"></div>
                            
                            <div class="bg-img-container">
                                <img src="<?= $imagePath ?>" class="bg-img" alt="<?= htmlspecialchars($row['boardgame_name']) ?>">
                            </div>

                            <div class="bg-content">
                                <h3><?= htmlspecialchars($row['boardgame_name']) ?></h3>
                                <p class="category-tag">Category: <?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></p>
                                <p>Players: <?= $row['min_players'] ?> - <?= $row['max_players'] ?></p>
                                
                                <div class="difficulty">
                                    <span>Difficulty: <?= number_format($difficulty, 1) ?>/5</span>
                                    <div class="diff-bar-bg">
                                        <div class="diff-bar-fill" style="width: <?= $diffPercent ?>%;"></div>
                                    </div>
                                </div>
                                
                                <a href="boardgame_detail.php?id=<?= urlencode($row['boardgame_id']) ?>" class="detail-btn">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <h2>❌ No board games found</h2>
                        <p>Please try a different search term.</p>
                        <a href="boardgame.php" class="reset-link">Show all board games</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>