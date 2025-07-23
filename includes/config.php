<?php
// Display errors (for development only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB config
$host = 'localhost';
$user = 'shivendra';
$password = '24199319@Shiv';
$central_db = 'softgen_db_central';

// Determine selected DB (can be switched later via session)
$db = $_SESSION['db_selected'] ?? $central_db;

// Connect to the database
$con = new mysqli($host, $user, $password, $db);
if ($con->connect_error) {
    die("❌ DB Connection failed: " . $con->connect_error);
}

// Set timezone and charset
$con->query("SET time_zone = '+05:30'");
$con->set_charset("utf8mb4");
