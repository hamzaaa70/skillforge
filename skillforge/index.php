<?php
// index.php  —  Entry point, redirect based on login status
require_once 'includes/auth.php';
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
} else {
    header('Location: signup.php');
}
exit;
?>
