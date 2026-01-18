<?php
require_once "../includes/config.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

$term   = $_GET['term'] ?? '';
$branch = $_GET['branch'] ?? '';

if ($term === '' || $branch === '') {
    echo json_encode([]);
    exit;
}

/* =========================================
   Get branch DB configuration (central DB)
========================================= */
$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
$stmt->bind_param("s", $branch);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$config = $res->fetch_assoc();

/* =========================================
   Connect to branch database
========================================= */
$branch_db = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name']
);

if ($branch_db->connect_error) {
    echo json_encode([]);
    exit;
}

$branch_db->set_charset('utf8mb4');

/* =========================================
   Manufacturer search query
========================================= */
$sql = "
    SELECT manuf_id, manuf_name
    FROM m_manuf
    WHERE manuf_name LIKE CONCAT('%', ?, '%')
       OR manuf_id   LIKE CONCAT('%', ?, '%')
    ORDER BY manuf_name
    LIMIT 20
";

$stmt = $branch_db->prepare($sql);
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$result = $stmt->get_result();

/* =========================================
   Format for jQuery UI Autocomplete
========================================= */
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'label' => $row['manuf_name'],  // what user sees
        'value' => $row['manuf_id']     // what gets submitted
    ];
}

echo json_encode($data);
