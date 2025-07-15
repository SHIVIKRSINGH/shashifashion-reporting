<?php
require_once __DIR__ . '/../includes/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "../includes/header.php";
?>

<div class="container mt-4">
    <h2>Welcome, <?= $_SESSION['username'] ?></h2>
    <p>This is your dashboard.</p>
</div>