<?php
// =============================================
//  includes/db.php  —  Database Connection
//  Uses MySQLi (MySQL driver, not PDO)
//  ⚠️  Change credentials below to yours
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password
define('DB_NAME', 'skillforge_db');

// Create connection using MySQLi driver
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // In production you'd log this — here we show a friendly message
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set charset to utf8
$conn->set_charset('utf8mb4');
?>
