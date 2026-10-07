<?php
session_start();
require_once 'connect.php'; // Call database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $promo_id = $_POST['promo_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // If button text or link is not provided, use default values
    $button_text = !empty($_POST['button_text']) ? $_POST['button_text'] : 'View Details';
    $action_link = !empty($_POST['action_link']) ? $_POST['action_link'] : '#';
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    $image_url = $_POST['existing_image']; 

    // Function to call SweetAlert (basic HTML structure for reusable alerts)
    function sweetAlertMsg($title, $text, $icon, $redirectUrl) {
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
            <style>body { font-family: "Kanit", sans-serif; background-color: #F5EBE1; }</style>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: "' . $title . '",
                    text: "' . $text . '",
                    icon: "' . $icon . '",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#576d5c" // Green color matching your theme
                }).then((result) => {
                    window.location.href = "' . $redirectUrl . '";
                });
            </script>
        </body>
        </html>';
        exit();
    }

    if (isset($_FILES['promo_image']) && $_FILES['promo_image']['error'] == 0) {
        $target_dir = "../uploads/promotions/"; 
        $file_extension = pathinfo($_FILES["promo_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "promo_" . time() . "_" . rand(1000,9999) . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["promo_image"]["tmp_name"], $target_file)) {
            $image_url = "../uploads/promotions/" . $new_filename; 
            
            $old_image_path = $_POST['existing_image'];
            if (!empty($old_image_path) && file_exists($old_image_path) && strpos($old_image_path, '../uploads/') !== false) {
                unlink($old_image_path); 
            }
        } else {
            sweetAlertMsg('Error!', 'An error occurred while uploading the new image', 'error', 'javascript:history.back()');
        }
    }

    $sql = "UPDATE promotions SET 
            title = ?, description = ?, image_url = ?, button_text = ?, 
            action_link = ?, start_date = ?, end_date = ?, status = ? 
            WHERE promo_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssssssssi", $title, $description, $image_url, $button_text, $action_link, $start_date, $end_date, $status, $promo_id);
        
        if ($stmt->execute()) {
            // Success: Call SweetAlert and redirect to add_promotion.php
            sweetAlertMsg('Success!', 'Promotion updated successfully!', 'success', '../admin/add_promotion.php');
        } else {
            sweetAlertMsg('Error!', 'An error occurred during update: ' . $stmt->error, 'error', 'javascript:history.back()');
        }
        $stmt->close();
    } else {
        sweetAlertMsg('Error!', 'An error occurred while preparing SQL statement', 'error', 'javascript:history.back()');
    }
} else {
    header("Location: ../admin/add_promotion.php");
    exit();
}
$conn->close();
?>