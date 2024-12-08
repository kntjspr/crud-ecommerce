<?php
// Start the session to get session variables
session_start();
// Destroy the session to clear all session variables
session_destroy();
// Unset all session variables
session_unset();
// Redirect to login page
header("Location: login.php");
exit();
?> 