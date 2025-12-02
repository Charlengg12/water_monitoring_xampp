<?php
// Database Configuration for XAMPP
// File: includes/db.php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';  // Default XAMPP has no password
$db_name = 'water_monitoring';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Optional: Set timezone (adjust to your location)
date_default_timezone_set('Asia/Manila');
?>
