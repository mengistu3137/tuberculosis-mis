<?php
session_start();
// Database connection included but not strictly required for this bypass
require_once('../config/db.php');

if (isset($_POST['login_btn'])) {
    $username = $_POST['username'];

    // TEMPORARY BYPASS: We set session variables manually 
    // so index.php lets us in.
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = "Project Member"; // Temporary name
    $_SESSION['role'] = "Admin";               // Default to Admin to see everything

    // Redirect to Dashboard
    header("Location: ../index.php");
    exit();
}
?>