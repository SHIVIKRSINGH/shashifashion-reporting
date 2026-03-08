<?php

require_once "../../includes/config.php";

$branch_id = $_GET['branch_id'] ?? '';
$term = $_GET['term'] ?? '';

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

SELECT supp_id,supp_name
FROM m_supplier
WHERE branch_id=?
AND supp_name LIKE ?
LIMIT 20
";

$like = "%$term%";

$stmt = $branch_db->prepare($sql);
$stmt->bind_param("ss", $branch_id, $like);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {

    $data[] = [
        "id" => $row['supp_id'],
        "text" => $row['supp_id'] . " - " . $row['supp_name']
    ];
}

echo json_encode($data);
