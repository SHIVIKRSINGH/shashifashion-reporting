<?php
// Enable error reporting (only for development)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Central DB configuration
$host = 'localhost';
$user = 'shivendra';
$password = '24199319@Shiv';
$central_db = 'softgen_db_central';

// Connect to central database
$con = new mysqli($host, $user, $password, $central_db);
if ($con->connect_error) {
    die("❌ Central DB connection failed: " . $con->connect_error);
}

$con->query("SET time_zone = '+05:30'");
$con->set_charset("utf8mb4");
