<?php
session_start();
require_once '../config/connect.php'; // ปรับ path ให้ตรงกับโฟลเดอร์ config ของคุณ

// --- Handle Delete Request ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $del_sql = "DELETE FROM reviews WHERE review_id = ?";
    $stmt = $conn->prepare($del_sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            // Success alert
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'The review has been deleted successfully.',
                        confirmButtonColor: '#E5989B'
                    }).then(() => {
                        window.location.href = 'manage_review.php';
                    });
                });
            </script>";
        } else {
            // Error alert
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to delete the review. Please try again.',
                        confirmButtonColor: '#E5989B'
                    }).then(() => {
                        window.location.href = 'manage_review.php';
                    });
                });
            </script>";
        }
        $stmt->close();
    }
}

// --- Fetch Reviews Data ---
// Join reviews with customer (to get reviewer name) and boardgame (to get game name)
$sql = "SELECT r.review_id, r.rating, r.comment, r.review_date, 
               c.username AS reviewer_name, 
               b.boardgame_name 
        FROM reviews r
        LEFT JOIN customer c ON r.customer_id = c.customer_id
        LEFT JOIN boardgame b ON r.boardgame_id = b.boardgame_id
        ORDER BY r.review_date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Manage review</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="../style/manage_review.css">
</head>
<body>

    <div class="container">
        <div class="header-top">
            <h1>⭐ Manage Board Game Reviews</h1>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Board Game</th>
                    <th>Reviewer</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['review_id']) ?></td>
                            <td><strong><?= htmlspecialchars($row['boardgame_name'] ?? 'Unknown Game') ?></strong></td>
                            <td><?= htmlspecialchars($row['reviewer_name'] ?? 'Anonymous') ?></td>
                            <td>
                                <span class="star-rating">
                                    <?= str_repeat('★', $row['rating']) ?><?= str_repeat('☆', 5 - $row['rating']) ?>
                                </span>
                            </td>
                            <td class="comment-text"><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($row['review_date'])) ?></td>
                            <td>
                                <button class="delete-btn" onclick="confirmDelete(<?= $row['review_id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-data">No reviews found in the database.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmDelete(reviewId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this review? This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E5989B',   // var(--pink)
                cancelButtonColor: '#9B6E60',    // var(--brown-light)
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to the same page with delete_id parameter
                    window.location.href = `manage_review.php?delete_id=${reviewId}`;
                }
            });
        }
    </script>

</body>
</html>