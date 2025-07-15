<?php
require_once "../includes/config.php";
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