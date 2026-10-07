<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../main/index.php");
    exit();
}

$id = $_SESSION['user_id'];
$type_code = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'C01'; 

// Dynamic table selection based on user type (Admin vs Customer)
if (strpos($type_code, 'A') === 0) {
    $table = "admins";
    $id_col = "admin_id";
    $target_dir = "../img/admin_img/";
} else {
    $table = "customer";
    $id_col = "customer_id";
    $target_dir = "../img/customer_img/";
}

// Sanitize inputs
$username = mysqli_real_escape_string($conn, $_POST['username']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$gender = mysqli_real_escape_string($conn, $_POST['gender']);
$birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$new_password = $_POST['new_password'];

// Start building the update query
$sql_update = "UPDATE $table SET 
               username='$username', 
               email='$email', 
               gender='$gender', 
               birthday='$birthday', 
               phone='$phone'";

// Update password only if a new one is provided
// NOTE: Ideally, use password_hash($new_password, PASSWORD_DEFAULT)
if (!empty($new_password)) {
    $sql_update .= ", password='$new_password'";
}

// Handle Profile Image Upload
if (isset($_FILES['profile_img']['name']) && $_FILES['profile_img']['name'] != "") {
    
    // Create User Folder based on email prefix
    $email_parts = explode("@", $email);
    $user_folder = $email_parts[0]; 
    $user_folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $user_folder); 
    $full_folder_path = $target_dir . $user_folder . "/"; 

    if (!file_exists($full_folder_path)) {
        mkdir($full_folder_path, 0777, true);
    }

    // Delete old profile image if it exists
    $sql_check_old = "SELECT upload FROM $table WHERE $id_col = '$id'";
    $result_old = mysqli_query($conn, $sql_check_old);
    $row_old = mysqli_fetch_assoc($result_old);
    
    if (!empty($row_old['upload'])) {
        $file_to_delete = $target_dir . $row_old['upload'];
        if (file_exists($file_to_delete)) {
            @unlink($file_to_delete); 
        }
    }

    // Upload new profile image
    $temp = explode(".", $_FILES["profile_img"]["name"]);
    $newfilename = round(microtime(true)) . '.' . end($temp); 
    $target_file = $full_folder_path . $newfilename;
    
    if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file)) {
        $db_save_value = $user_folder . "/" . $newfilename;
        $sql_update .= ", upload='$db_save_value'";
    }
}

$sql_update .= " WHERE $id_col = '$id'";

if (mysqli_query($conn, $sql_update)) {
    $_SESSION['username'] = $username;

    // Set redirect URL based on user role
    if (strpos($type_code, 'A') === 0) {
        $redirect_url = "../admin/index.php"; // Admins go to Dashboard
    } else {
        $redirect_url = "../main/home.php"; // Customers go to Home
    }

    // Success notification via SweetAlert2
    echo "<!DOCTYPE html>";
    echo "<html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head>";
    echo "<body style='background-color: #F5EBE1;'>"; 
    echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Update Successful!',
                text: 'Your profile has been updated.',
                showConfirmButton: false,
                timer: 1500,
                background: '#F5EBE0',
                color: '#5D4038'
            }).then(() => {
                window.location.href = '$redirect_url';
            });
          </script>";
    echo "</body></html>";

} else {
    echo "Database Error: " . mysqli_error($conn);
}
?>