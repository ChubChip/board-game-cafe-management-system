<?php
session_start();
require_once('connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match. Please try again.";
        header("Location: ../include/register.php");
        exit();
    }

    // 2. Check if email already exists
    $check_email = "SELECT email FROM customer WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $check_email);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "This email is already registered. Please use a different one.";
        header("Location: ../include/register.php");
        exit();
    }

    // 3. Security: Hash the password before saving to the database
    // This turns "password123" into an unreadable string for security.
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert data into Customer table
    // Using $hashed_password instead of plain text $password
    $sql = "INSERT INTO customer (customer_id, username, email, password, gender, birthday, type_user) 
            VALUES (NULL, '$username', '$email', '$hashed_password', NULL, '0000-00-00', 'C02')";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Registration successful! Welcome to the family.";
        header("Location: ../main/home.php"); 
        exit();
    } else {
        $_SESSION['error'] = "Error saving data: " . mysqli_error($conn);
        header("Location: ../include/register.php");
        exit();
    }
}