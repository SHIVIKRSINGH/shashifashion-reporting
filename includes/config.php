<?php
// Start session
if (session_status() === PHP_SESSION_NONE) session_start();

// DB config
$host = 'localhost';
$user = 'shivendra';
$password = '24199319@Shiv';
$central_db = 'softgen_db_central';

// Determine selected DB
$db = isset($_SESSION['db_selected']) ? $_SESSION['db_selected'] : $central_db;

// Connect
$con = new mysqli($host, $user, $password, $db);
if ($con->connect_error) die("❌ DB Connection failed: " . $con->connect_error);
$con->query("SET time_zone = '+05:30'");
$con->set_charset("utf8mb4");
