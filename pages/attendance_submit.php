<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['emp_id'])) {
    echo "<span class='text-danger'>Session expired, please login again.</span>";
    exit;
}

$emp_id = $_SESSION['emp_id'];
$branch_id = $_SESSION['branch_id'];
$type = $_POST['type'] ?? '';
$lat = $_POST['lat'] ?? 0;
$lon = $_POST['lon'] ?? 0;
$today = date('Y-m-d');

if (!$type) {
    echo "<span class='text-danger'>Invalid request.</span>";
    exit;
}

$stmt = $con->prepare("SELECT * FROM attendance WHERE emp_id=? AND att_date=?");
$stmt->bind_param("is", $emp_id, $today);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();

$now = date('Y-m-d H:i:s');

if (!$rec) {
    // New day
    $stmt = $con->prepare("INSERT INTO attendance (emp_id, branch_id, att_date, $type, latitude, longitude, system_status) VALUES (?, ?, ?, ?, ?, ?, 'Present')");
    $stmt->bind_param("isssdd", $emp_id, $branch_id, $today, $now, $lat, $lon);
    $stmt->execute();
    echo "<span class='text-success'>Marked $type successfully ✅</span>";
} else {
    // Update existing
    if ($rec[$type] != null) {
        echo "<span class='text-warning'>Already marked $type earlier.</span>";
        exit;
    }
    $stmt = $con->prepare("UPDATE attendance SET $type=?, latitude=?, longitude=? WHERE id=?");
    $stmt->bind_param("sddi", $now, $lat, $lon, $rec['id']);
    $stmt->execute();

    // Auto compute total hours if punching out
    if ($type === 'clock_out' && $rec['clock_in']) {
        $inTime = strtotime($rec['clock_in']);
        $outTime = strtotime($now);
        $hours = round(($outTime - $inTime)/3600, 2);
        $stmt = $con->prepare("UPDATE attendance SET total_hours=? WHERE id=?");
        $stmt->bind_param("di", $hours, $rec['id']);
        $stmt->execute();
    }

    echo "<span class='text-success'>Marked $type successfully ✅</span>";
}
?>
