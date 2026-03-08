<?php

require_once "../../includes/config.php";

$branch_id = $_GET['branch_id'] ?? '';
$term = $_GET['term'] ?? '';

if (!$branch_id || !$term) {
    echo json_encode([]);
    exit;
}

/* CONNECT BRANCH DB */

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

/* SEARCH ITEMS */

$sql = "

SELECT
item_id,
item_desc,
sale_tax_paid
FROM m_item_hdr
WHERE item_desc LIKE ?
AND branch_id=?
LIMIT 20
";

$like = "%$term%";

$stmt = $branch_db->prepare($sql);
$stmt->bind_param("ss", $like, $branch_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {

    $gst = preg_replace('/[^0-9]/', '', $row['sale_tax_paid']);

    $data[] = [
        "id" => $row['item_id'],
        "text" => $row['item_desc'],
        "gst" => $gst
    ];
}

echo json_encode($data);
