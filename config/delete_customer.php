<?php
session_start();
include('../config/connect.php'); // Ensure this path is correct based on your folder structure

if (isset($_GET['id'])) {
    $customer_id = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. (Optional) Delete the customer's profile image from the server folder
    $sql_img = "SELECT upload FROM customer WHERE customer_id = '$customer_id'";
    $res_img = mysqli_query($conn, $sql_img);
    
    if ($res_img && mysqli_num_rows($res_img) > 0) {
        $row = mysqli_fetch_assoc($res_img);
        $old_upload = $row['upload'];
        
        if (!empty($old_upload)) {
            $img_path = "../img/customer_img/" . $old_upload;
            if (file_exists($img_path)) {
                unlink($img_path); // Delete the physical file
            }
        }
    }

    // 2. Execute Delete Command
    $delete_sql = "DELETE FROM customer WHERE customer_id = '$customer_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Deletion successful
        $_SESSION['success'] = "Customer has been deleted successfully!";
    } else {
        // Deletion failed - check for Foreign Key Constraint Error (MySQL Error 1451)
        if (mysqli_errno($conn) == 1451) {
            $_SESSION['error'] = "Cannot delete this customer! They have an existing rental history or related data.";
        } else {
            $_SESSION['error'] = "Failed to delete customer: " . mysqli_error($conn);
        }
    }

} else {
    // No ID passed in URL
    $_SESSION['error'] = "Invalid request. No customer ID provided.";
}

// 3. Redirect back to manage page to show SweetAlert
header("Location: ../admin/manage_customer.php"); // Adjust path if needed
exit();
?>