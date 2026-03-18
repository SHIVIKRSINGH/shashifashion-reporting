<?php

/**
 * ajax/get_item_barcode.php
 *
 * Step 1 : Find the item_id from the scanned barcode in m_item_det
 * Step 2 : Fetch ALL PLU rows for that item_id from m_item_det
 *          (so the frontend can show a popup when multiple PLUs exist)
 *
 * Returns JSON:
 * {
 *   "item_id"   : "100211",
 *   "item_name" : "SOME ITEM",
 *   "gst"       : 5,
 *   "plus"      : [
 *      { "plu":"100211-001", "bar_code":"8908000451308",
 *        "cp":27.12, "mrp":80.00, "sp":80.00 },
 *      ...
 *   ]
 * }
 */

require_once "../../includes/config.php";

$branch_id = $_GET['branch_id'] ?? '';
$barcode   = $_GET['barcode']   ?? '';

if (!$branch_id || !$barcode) {
    echo json_encode([]);
    exit;
}

/* ── Connect branch DB ─────────────────────────────────── */
$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id=?");
$stmt->bind_param("s", $branch_id);
$stmt->execute();
$cfg = $stmt->get_result()->fetch_assoc();

if (!$cfg) {
    echo json_encode([]);
    exit;
}

$branch_db = new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_password'], $cfg['db_name']);
if ($branch_db->connect_error) {
    echo json_encode([]);
    exit;
}

/* ── Step 1: find item_id from the scanned barcode ─────── */
$stmt = $branch_db->prepare(
    "SELECT item_id FROM m_item_det WHERE bar_code=? AND branch_id=? LIMIT 1"
);
$stmt->bind_param("ss", $barcode, $branch_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode([]);   // barcode not found
    exit;
}

$item_id = $row['item_id'];

/* ── Step 2: fetch item name + GST from hdr ────────────── */
$stmt = $branch_db->prepare(
    "SELECT item_desc, sale_tax_paid FROM m_item_hdr WHERE item_id=? AND branch_id=?"
);
$stmt->bind_param("ss", $item_id, $branch_id);
$stmt->execute();
$hdr = $stmt->get_result()->fetch_assoc();

$item_name = $hdr['item_desc']    ?? 'Unknown';
$gst       = (int) preg_replace('/[^0-9]/', '', $hdr['sale_tax_paid'] ?? '0');

/* ── Step 3: fetch ALL PLU rows for this item_id ───────── */
$stmt = $branch_db->prepare(
    "SELECT plu, bar_code, cost_price, mrp, sale_price
     FROM m_item_det
     WHERE item_id=? AND branch_id=?
     ORDER BY sl_no"
);
$stmt->bind_param("ss", $item_id, $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$plus = [];
while ($r = $result->fetch_assoc()) {
    $plus[] = [
        "plu"      => $r['plu'],
        "bar_code" => $r['bar_code'],
        "cp"       => (float) $r['cost_price'],
        "mrp"      => (float) $r['mrp'],
        "sp"       => (float) $r['sale_price']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    "item_id"   => $item_id,
    "item_name" => $item_name,
    "gst"       => $gst,
    "plus"      => $plus
]);
