<?php
session_start();
require_once '../config/connect.php';

if (!isset($_GET['id'])) {
    header("Location: boardgame.php");
    exit();
}

$id = $_GET['id'];
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// select boardgame with category name using LEFT JOIN
$sql = "SELECT b.*, c.category_name 
        FROM boardgame b 
        LEFT JOIN type_boardgame tb ON b.boardgame_id = tb.boardgame_id
        LEFT JOIN category c ON tb.category_id = c.category_id
        WHERE b.boardgame_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id); 
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

if(!$game) {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
    echo "<h2>❌ Board game not found</h2>";
    echo "<button onclick='history.back()'>Back to previous page</button>";
    echo "</div>";
    exit();
}

$imageDetailPath = "../img/default_bg.jpg";
if (!empty($game['upload'])) {
    $checkPath = "../img/boardgame/" . $game['upload'];
    if (file_exists($checkPath)) {
        $imageDetailPath = $checkPath;
    } else {
        $imageDetailPath = "../img/boardgame/" . htmlspecialchars($game['upload']);
    }
}

// --- CHECK IF FAVORITED ---
$isFavorited = false;
if ($isLoggedIn) {
    // สมมติว่าตารางเก็บรายการโปรดชื่อ favorites (ถ้าชื่ออื่นให้แก้ด้วยนะครับ)
    $check_fav = "SELECT 1 FROM favorites WHERE customer_id = ? AND boardgame_id = ?";
    $fav_stmt = $conn->prepare($check_fav);
    if ($fav_stmt) {
        $fav_stmt->bind_param("is", $user_id, $id);
        $fav_stmt->execute();
        $fav_stmt->store_result();
        if ($fav_stmt->num_rows > 0) {
            $isFavorited = true;
        }
        $fav_stmt->close();
    }
}

// --- FETCH REVIEWS ---
$review_sql = "SELECT r.*, c.username 
               FROM reviews r 
               LEFT JOIN customer c ON r.customer_id = c.customer_id 
               WHERE r.boardgame_id = ? 
               ORDER BY r.review_date DESC";
$rev_stmt = $conn->prepare($review_sql);
$rev_stmt->bind_param("s", $id);
$rev_stmt->execute();
$reviews_result = $rev_stmt->get_result();

$all_reviews = [];
while ($row = $reviews_result->fetch_assoc()) {
    $all_reviews[] = $row;
}
$total_reviews = count($all_reviews);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Boardgame detail</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    
    <link rel="stylesheet" href="../style/boardgame.css">
    <link rel="stylesheet" href="../style/back.css">
    <link rel="stylesheet" href="../style/review.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* ปุ่มหัวใจ SVG */
        .fav-btn {
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s, color 0.3s;
            outline: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }
        .fav-btn:hover {
            transform: scale(1.1);
        }
        /* กำหนดสีตอนกดแล้ว (สีแดง/ชมพู) */
        .fav-active {
            color: #E5989B; /* เปลี่ยนเป็นรหัสสีแดง/ชมพูที่คุณต้องการได้เลย */
        }
        /* กำหนดสีตอนยังไม่กด (สีเทาตาม SVG ของคุณ) */
        .fav-inactive {
            color: #D9D9D9; 
        }
    </style>
</head>
<body>
    <div class="container">
        <button onclick="window.location.href='boardgame.php'" class="back-btn">← Back</button>
        
        <div class="detail-card">
            
            <div class="detail-left">
                <img src="<?= $imageDetailPath ?>" alt="<?= htmlspecialchars($game['boardgame_name']) ?>" class="detail-img">
                <button class="review-btn" id="openReviewBtn">Write a Review</button>
            </div>
            
            <div class="detail-right">
                
                <div class="title-container">
                    <h1 class="detail-title" style="margin: 0;"><?= htmlspecialchars($game['boardgame_name']) ?></h1>
                    
                    <button id="favoriteBtn" class="fav-btn <?= $isFavorited ? 'fav-active' : 'fav-inactive' ?>" onclick="toggleFavorite()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="24" viewBox="0 0 26 24" fill="none">
                            <path d="M12.9167 23.7021L11.0438 21.9971C4.39167 15.965 0 11.9737 0 7.10417C0 3.11292 3.12583 0 7.10417 0C9.35167 0 11.5088 1.04625 12.9167 2.68667C14.3246 1.04625 16.4817 0 18.7292 0C22.7075 0 25.8333 3.11292 25.8333 7.10417C25.8333 11.9737 21.4417 15.965 14.7896 21.9971L12.9167 23.7021Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                <hr style="border:0; border-bottom:1px solid var(--cream); margin: 15px 0;">
                
                <div class="detail-diff-container">
                    <span class="diff-label">Difficulty: <?= number_format($game['difficulty'], 1) ?> / 5.0</span>
                    <div class="diff-bar-bg">
                        <div class="diff-bar-fill" style="width: <?= ($game['difficulty'] / 5) * 100 ?>%;"></div>
                    </div>
                </div>

                <div class="info-frame">
                    <p><strong>Category:</strong> <?= htmlspecialchars($game['category_name'] ?? 'Uncategorized') ?></p>
                    <p><strong>Players:</strong> <?= $game['min_players'] ?> - <?= $game['max_players'] ?></p>
                    <p><strong>Status:</strong> <span class="status-badge <?= strtolower(htmlspecialchars($game['status'])) ?>"><?= htmlspecialchars($game['status']) ?></span></p>
                </div>

                <div class="info-frame">
                    <h3>Theme & Story</h3>
                    <p><?= nl2br(htmlspecialchars($game['genre'] ?? '-')) ?></p>
                </div>
                
                <?php if(!empty($game['equipment_list'])): ?>
                <div class="info-frame">
                    <h3>Equipment List</h3>
                    <p><?= nl2br(htmlspecialchars($game['equipment_list'])) ?></p>
                </div>
                <?php endif; ?>

            </div> 
        </div> 

        <div class="reviews-section">
            <h2 style="color: var(--brown-dark); margin-top:0;">Player Reviews (<?= $total_reviews ?>)</h2>
            
            <?php if ($total_reviews > 0): ?>
                <?php 
                $display_count = min(3, $total_reviews);
                for ($i = 0; $i < $display_count; $i++): 
                    $rev = $all_reviews[$i];
                ?>
                    <div class="review-box">
                        <div class="review-header">
                            <div>
                                <span class="reviewer-name"><?= htmlspecialchars($rev['username'] ?? 'Anonymous') ?></span>
                                <span class="review-date"> • <?= date('d M Y', strtotime($rev['review_date'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                            </div>
                        </div>
                        <div class="review-comment">
                            <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                        </div>
                    </div>
                <?php endfor; ?>

                <?php if ($total_reviews > 3): ?>
                    <button id="openAllReviewsBtn" class="view-all-btn">Read All <?= $total_reviews ?> Reviews</button>
                <?php endif; ?>

            <?php else: ?>
                <p style="color: gray; font-style: italic;">No reviews yet. Be the first to review this game!</p>
            <?php endif; ?>
        </div>
    </div> 

    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-review" id="closeWriteModal">&times;</span>
            <h2 style="color: var(--brown-dark);">Review: <?= htmlspecialchars($game['boardgame_name']) ?></h2>
            
            <form action="../config/submit_review.php" method="POST">
                <input type="hidden" name="boardgame_id" value="<?= htmlspecialchars($game['boardgame_id']) ?>">
                
                <div class="rating-container">
                    <p>Rate your experience:</p>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 Stars">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 Stars">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 Stars">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 Stars">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 Star">&#9733;</label>
                    </div>
                </div>

                <textarea name="comment" rows="4" placeholder="Share your thoughts or feedback about this game..."></textarea>
                
                <button type="submit" class="submit-review-btn">Submit Review</button>
            </form>
        </div>
    </div>

    <div id="allReviewsModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-review" id="closeAllModal">&times;</span>
            <h2 style="color: var(--brown-dark); margin-bottom: 20px;">All Reviews</h2>
            
            <?php foreach ($all_reviews as $rev): ?>
                <div class="review-box">
                    <div class="review-header">
                        <div>
                            <span class="reviewer-name"><?= htmlspecialchars($rev['username'] ?? 'Anonymous') ?></span>
                            <span class="review-date"> • <?= date('d M Y', strtotime($rev['review_date'])) ?></span>
                        </div>
                        <div class="review-stars">
                            <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                        </div>
                    </div>
                    <div class="review-comment">
                        <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
        </div>
    </div>

    <script>
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const boardgameId = "<?= htmlspecialchars($id) ?>";

        // ฟังก์ชันเมื่อกดปุ่มหัวใจ
        function toggleFavorite() {
            if (!isLoggedIn) {
                // ถ้ายังไม่ได้ล็อคอิน ให้เด้งไปหน้าล็อคอิน
                Swal.fire({
                    title: 'Please Log In',
                    text: "You must log in to add favorite games.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#E5989B',
                    cancelButtonColor: '#9B6E60',
                    confirmButtonText: 'Login / Sign Up'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../include/login.php';
                    }
                });
                return;
            }

            // ถ้าล็อคอินแล้ว ยิง AJAX ไปเซฟลง Database
            fetch('../config/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'boardgame_id=' + encodeURIComponent(boardgameId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const favBtn = document.getElementById('favoriteBtn');
                    if (data.action === 'added') {
                        // เปลี่ยนเป็นสีแดง
                        favBtn.classList.remove('fav-inactive');
                        favBtn.classList.add('fav-active');
                    } else if (data.action === 'removed') {
                        // กลับเป็นสีเทา
                        favBtn.classList.remove('fav-active');
                        favBtn.classList.add('fav-inactive');
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Server connection problem.', 'error');
            });
        }

        // Modal logic ของเดิม
        const writeModal = document.getElementById("reviewModal");
        const allModal = document.getElementById("allReviewsModal");
        const openWriteBtn = document.getElementById("openReviewBtn");
        const openAllBtn = document.getElementById("openAllReviewsBtn");
        const closeWrite = document.getElementById("closeWriteModal");
        const closeAll = document.getElementById("closeAllModal");

        if (openWriteBtn) openWriteBtn.onclick = function() { writeModal.style.display = "block"; }
        if (openAllBtn) openAllBtn.onclick = function() { allModal.style.display = "block"; }
        if (closeWrite) closeWrite.onclick = function() { writeModal.style.display = "none"; }
        if (closeAll) closeAll.onclick = function() { allModal.style.display = "none"; }

        window.onclick = function(event) {
            if (event.target == writeModal) writeModal.style.display = "none";
            if (event.target == allModal) allModal.style.display = "none";
        }
    </script>
</body>
</html>