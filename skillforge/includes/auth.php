<?php
// =============================================
//  includes/auth.php  —  Session Helpers
// =============================================

session_start();

// Call this at the top of any protected page
// If user is NOT logged in → redirect to login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Call on login/signup pages — redirect logged-in users to home
function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header('Location: home.php');
        exit;
    }
}
?>
