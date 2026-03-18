<?php

/**
 * ajax/search_item.php
 * Returns item list with cp, mrp, sp, gst for autofill
 */
require_once "../../includes/config.php";

$branch_id = $_GET['branch_id'] ?? '';
$term      = $_GET['term'] ?? '';

if (!$branch_id || !$term) {
    echo json_encode([]);
    exit;
}

/* Connect to branch DB */
$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id=?");
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode([]);
    exit;
}

$branch_db = new mysqli(
    $res['db_host'],
    $res['db_user'],
    $res['db_password'],
    $res['db_name']
);

if ($branch_db->connect_error) {
    echo json_encode([]);
    exit;
}

/*
 * Join m_item_hdr + m_item_det so we can return pricing data.
 * We take the FIRST det row per item (MIN) to avoid duplicates.
 * Adjust the JOIN if your schema uses a different key.
 */
$sql = "
SELECT
    H.item_id,
    H.item_desc,
    H.sale_tax_paid,
    D.cost_price,
    D.mrp,
    D.sale_price
FROM m_item_hdr H
LEFT JOIN m_item_det D
    ON D.item_id = H.item_id
    AND D.branch_id = H.branch_id
WHERE H.item_desc LIKE ?
  AND H.branch_id = ?
GROUP BY H.item_id
LIMIT 20
";

$like = "%$term%";
$stmt = $branch_db->prepare($sql);
$stmt->bind_param("ss", $like, $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $gst = (int) preg_replace('/[^0-9]/', '', $row['sale_tax_paid']);
    $data[] = [
        "id"   => $row['item_id'],
        "text" => $row['item_desc'],
        "gst"  => $gst,
        "cp"   => (float) $row['cost_price'],
        "mrp"  => (float) $row['mrp'],
        "sp"   => (float) $row['sale_price']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
