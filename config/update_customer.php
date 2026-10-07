<?php
session_start();
include('../config/connect.php');

// Check if POST data was actually sent
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customer_id'])) {
    
    // 1. Sanitize input data (Prevent SQL Injection)
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    // 2. Image Handling
    $update_img_sql = ""; // SQL partial string for image update
    
    if (isset($_FILES['upload']['name']) && $_FILES['upload']['name'] != "") {
        
        // --- Optional: Delete the old image file to save server space ---
        $sql_old_img = "SELECT upload FROM customer WHERE customer_id = '$customer_id'";
        $res_old = mysqli_query($conn, $sql_old_img);
        $row_old = mysqli_fetch_assoc($res_old);
        $old_filename = $row_old['upload'];
        
        if (!empty($old_filename)) {
            $old_path = "../img/customer_img/" . $old_filename;
            if (file_exists($old_path)) {
                unlink($old_path); // Remove old file from folder
            }
        }
        // -------------------------------------------------------------

        // Upload New Image
        $ext = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
        $new_name = "cust_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        $target_path = "../img/customer_img/" . $new_name;

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target_path)) {
            $update_img_sql = ", upload = '$new_name'";
        }
    }

    // 3. Update SQL Command
    $sql = "UPDATE customer SET 
                username = '$username', 
                email = '$email', 
                phone = '$phone', 
                gender = '$gender' 
                $update_img_sql 
            WHERE customer_id = '$customer_id'";

    // 4. Execute and Redirect (MODIFIED FOR SWEETALERT)
    if (mysqli_query($conn, $sql)) {
        // Set success session and redirect
        $_SESSION['success'] = "Customer information updated successfully!";
        header("Location: ../admin/manage_customer.php");
        exit();
    } else {
        // Set error session and redirect
        $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
        header("Location: ../admin/manage_customer.php");
        exit();
    }

} else {
    // Redirect if the script is accessed without submitting the form
    header("Location: ../admin/manage_customer.php");
    exit();
}

mysqli_close($conn);
?>