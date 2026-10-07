<?php
session_start();
require_once '../config/connect.php';

// 1. Security Check: Only Owner (A01)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A01') {
    $_SESSION['error'] = "Access Denied!";
    header("Location: ../admin/manage_role.php");
    exit();
}

if (isset($_POST['update_btn'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $source_table = $_POST['source_table']; // 'admins' or 'customers'
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);

    // 2. Determine target table and column names
    if ($source_table === 'admins') {
        $table = "admins";
        $id_col = "admin_id";
    } else {
        $table = "customer";
        $id_col = "customer_id";
    }

    // 3. Update query
    $update_sql = "UPDATE $table SET type_user = '$new_role' WHERE $id_col = '$user_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['success'] = "Role updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update role. Please try again.";
    }

    // 4. Redirect back to the same tab
    header("Location: ../admin/manage_role.php?tab=" . $source_table);
    exit();
} else {
    header("Location: ../admin/manage_role.php");
    exit();
}
?>