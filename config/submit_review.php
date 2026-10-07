<?php
// Start the session to check if the user is logged in
session_start();
require_once '../config/connect.php';

// Initialize variables for SweetAlert
$icon = "";
$title = "";
$message = "";
$redirect = "back"; // Default action is to go back

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the user is logged in (Adjust 'username' if your session variable is named differently)
    if (!isset($_SESSION['user_id'])) {
        $icon = "warning";
        $title = "Authentication Required";
        $message = "Please log in before submitting a review.";
    } else {
        // Retrieve data from session and POST request
        $customer_id = $_SESSION['user_id'];
        $boardgame_id = $_POST['boardgame_id'] ?? '';
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = trim($_POST['comment'] ?? '');
        
        // Basic validation
        if (empty($boardgame_id) || $rating < 1 || $rating > 5) {
            $icon = "error";
            $title = "Invalid Input";
            $message = "Please select a valid star rating before submitting.";
        } else {
            // Prepare the SQL statement to insert the review into the database
            // Note: Make sure your table is named 'reviews' as created previously
            $sql = "INSERT INTO reviews (customer_id, boardgame_id, rating, comment) 
                    VALUES (?, ?, ?, ?)";
                    
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind parameters: i = integer, s = string
                $stmt->bind_param("isis", $customer_id, $boardgame_id, $rating, $comment);

                // Execute the query
                if ($stmt->execute()) {
                    $icon = "success";
                    $title = "Thank You!";
                    $message = "Your review has been submitted successfully.";
                    $redirect = "../main/boardgame_detail.php?id=" . urlencode($boardgame_id);
                } else {
                    $icon = "error";
                    $title = "Submission Failed";
                    $message = "Error saving your review. Please try again later.";
                }
                $stmt->close();
            } else {
                $icon = "error";
                $title = "Database Error";
                $message = "An error occurred with the database connection.";
            }
        }
    }
} else {
    // If someone tries to access this file directly without submitting the form
    header("Location: boardgame.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Review...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Optional: Set the background color to match your theme (--cream) */
        body {
            background-color: #F5EBE1; 
            font-family: sans-serif;
        }
    </style>
</head>
<body>

    <script>
        // Trigger SweetAlert with PHP variables
        Swal.fire({
            icon: '<?= $icon ?>',
            title: '<?= $title ?>',
            text: '<?= $message ?>',
            confirmButtonColor: '#E5989B', // Using your theme's --pink color for the button
            confirmButtonText: 'OK'
        }).then((result) => {
            // After the user clicks "OK", redirect them based on the PHP logic
            <?php if ($redirect === 'back'): ?>
                window.history.back();
            <?php else: ?>
                window.location.href = '<?= $redirect ?>';
            <?php endif; ?>
        });
    </script>

</body>
</html>