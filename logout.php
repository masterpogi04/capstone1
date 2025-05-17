<?php
session_start();
include '../db.php'; // Ensure database connection is established

// Destroy the session and clear session variables
session_unset();
session_destroy();

// Redirect to the login page
header("Location: ../login.php");
exit();
?>