<?php

require_once "../../includes/config.php";

$branch_id = $_GET['branch_id'] ?? '';
$barcode = $_GET['barcode'] ?? '';

$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id=?");
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$branch_db = new mysqli(
    $res['db_host'],
    $res['db_user'],
    $res['db_password'],
    $res['db_name']
);

$sql = "

SELECT
H.item_id,
H.item_desc,
H.sale_tax_paid,
D.bar_code,
D.cost_price,
D.mrp,
D.sale_price

FROM m_item_det D
JOIN m_item_hdr H ON H.item_id=D.item_id

WHERE D.bar_code=?
AND D.branch_id=?

";

$stmt = $branch_db->prepare($sql);
$stmt->bind_param("ss", $barcode, $branch_id);
$stmt->execute();

$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode([]);
    exit;
}

$gst = preg_replace('/[^0-9]/', '', $res['sale_tax_paid']);

echo json_encode([

    "item_id" => $res['item_id'],
    "item_name" => $res['item_desc'],
    "barcode" => $res['bar_code'],
    "cp" => $res['cost_price'],
    "mrp" => $res['mrp'],
    "sp" => $res['sale_price'],
    "gst" => $gst

]);
