<?php
// Start session to access current session variables
session_start(); 

// Unset all session variables to clear them from memory
session_unset();
$_SESSION = array();

// Destroy the session cookie in the browser
if (session_id() != "" || isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 2592000, '/');
}

// Destroy the session completely on the server
session_destroy();

// Prevent browser caching to avoid showing previous user data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// Redirect to the main index page
header("Location: ../main/index.php"); 
exit();
?>