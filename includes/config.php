<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'localhost';
$user = 'shivendra';
$password = '24199319@Shiv';
$default_db = 'softgen_db';

// Use selected DB from session if available
$dbname = isset($_SESSION['db_selected']) ? $_SESSION['db_selected'] : $default_db;

// Create mysqli connection
$con = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($con->connect_error) {
    die("❌ DB Connection failed: " . $con->connect_error);
}

// Set MySQL timezone to IST (UTC+5:30)
$con->query("SET time_zone = '+05:30'");

// Set character set to handle Unicode
$con->set_charset("utf8mb4");
