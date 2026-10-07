<?php 
session_start();
require_once 'connect.php';

if (isset($_POST['login_btn'])) {
    $identity = mysqli_real_escape_string($conn, $_POST['identity']);
    // Do not escape the password before verifying it, as it can alter the string
    $password = $_POST['password'];

    // Define admin roles array for routing
    $admin_roles = ['A01', 'A02', 'A03', 'A04', 'A05'];

    // ------------------------------------
    // 1. Check in Admin table
    // ------------------------------------
    $check_admin = "SELECT * FROM admins WHERE username = '$identity' OR email = '$identity'";
    $res_admin = mysqli_query($conn, $check_admin);

    if (mysqli_num_rows($res_admin) == 1) {
        $row = mysqli_fetch_array($res_admin);
        
        // 1.1 Check hashed password (for updated accounts)
        if (password_verify($password, $row['password'])) {
            // --- Admin Login Success ---
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['type_user']; 
            $_SESSION['role'] = 'admin'; 
            $_SESSION['login_success'] = true;

            // Route based on role using in_array
            if (in_array($row['type_user'], $admin_roles)) {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../main/index.php");
            }
            exit();
        } 
        // 1.2 Seamless Migration: Check plain-text password for old accounts
        elseif ($password === $row['password']) {
            // Hash the old password
            $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $admin_id = $row['admin_id'];
            
            // Update the database with the new hashed password
            $update_query = "UPDATE admins SET password = '$new_hashed_password' WHERE admin_id = '$admin_id'";
            mysqli_query($conn, $update_query);

            // --- Admin Login Success ---
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['type_user']; 
            $_SESSION['role'] = 'admin'; 
            $_SESSION['login_success'] = true;

            // Route based on role using in_array
            if (in_array($row['type_user'], $admin_roles)) {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../main/index.php");
            }
            exit();
        } else {
            // Wrong password
            $_SESSION['error'] = "Incorrect password. Please try again.";
            header("Location: ../include/login.php");
            exit();
        }
    }

    // ------------------------------------
    // 2. Check in Customer table
    // ------------------------------------
    $check_user = "SELECT * FROM customer WHERE username = '$identity' OR email = '$identity'";
    $res_user = mysqli_query($conn, $check_user);

    if (mysqli_num_rows($res_user) == 1) {
        $row = mysqli_fetch_array($res_user);
        
        // 2.1 Check hashed password (for updated accounts)
        if (password_verify($password, $row['password'])) {
            // --- Customer Login Success ---
            $_SESSION['user_id'] = $row['customer_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['type_user']; 
            $_SESSION['role'] = 'customer';
            $_SESSION['login_success'] = true;

            header("Location: ../main/home.php");
            exit();
        }
        // 2.2 Seamless Migration: Check plain-text password for old accounts
        elseif ($password === $row['password']) {
            // Hash the old password
            $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $customer_id = $row['customer_id'];
            
            // Update the database with the new hashed password
            $update_query = "UPDATE customer SET password = '$new_hashed_password' WHERE customer_id = '$customer_id'";
            mysqli_query($conn, $update_query);

            // --- Customer Login Success ---
            $_SESSION['user_id'] = $row['customer_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['type_user']; 
            $_SESSION['role'] = 'customer';
            $_SESSION['login_success'] = true;

            header("Location: ../main/home.php");
            exit();
        } else {
            // Wrong password
            $_SESSION['error'] = "Incorrect password. Please try again.";
            header("Location: ../include/login.php");
            exit();
        }
    } else {
        // --- User Not Found ---
        $_SESSION['error'] = "Account or email not found in the system.";
        header("Location: ../include/login.php");
        exit();
    }
} else {
    header("Location: ../include/login.php");
    exit();
}
?>