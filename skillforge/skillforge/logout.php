<?php
// =============================================
//  logout.php  —  Destroy session and redirect
// =============================================
require_once 'includes/auth.php'; // starts session

// Destroy all session data
$_SESSION = [];
session_destroy();

// Redirect to login with a logout message
header('Location: login.php?loggedout=1');
exit;
?>
