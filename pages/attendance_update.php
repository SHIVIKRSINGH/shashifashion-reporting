<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['admin_status'] ?? '');
    $remarks = trim($_POST['admin_remarks'] ?? '');

    if ($id <= 0 || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $stmt = $con->prepare("UPDATE attendance SET admin_status=?, admin_remarks=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $remarks, $id);
    $ok = $stmt->execute();

    echo json_encode(['success' => $ok]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
