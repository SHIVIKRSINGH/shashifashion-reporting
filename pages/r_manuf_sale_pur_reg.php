<?php
require_once "../includes/config.php"; // MySQLi config
include "../includes/header.php";

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Defaults
$from        = $_GET['from'] ?? date('Y-m-d');
$to          = $_GET['to'] ?? date('Y-m-d');
$manuf       = $_GET['manuf'] ?? '';
$branch      = $_GET['branch'] ?? 'SHASHI-ND';

$role_name       = $_SESSION['role_name'];
$session_branch  = $_SESSION['branch_id'] ?? '';
$selected_branch = $_GET['branch'] ?? ($_SESSION['selected_branch_id'] ?? $session_branch);

// Results
$rows = [];

/* 🔌 Connect to branch DB dynamically */
$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
$stmt->bind_param("s", $selected_branch);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("❌ Branch config not found for '$selected_branch'");
}
$config = $res->fetch_assoc();

$branch_db = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name']
);
if ($branch_db->connect_error) {
    die("❌ Branch DB connection failed: " . $branch_db->connect_error);
}
$branch_db->set_charset('utf8mb4');
$branch_db->query("SET time_zone = '+05:30'");

/* 🔍 Fetch Report Data */
if (!empty($manuf)) {

    $sql = "
    SELECT 
        I.item_id,
        I.item_desc,

        IFNULL(P.pur_qty,0) - IFNULL(PR.pur_ret_qty,0) AS pur_qty,
        IFNULL(P.pur_amt,0) - IFNULL(PR.pur_ret_amt,0) AS pur_amt,

        IFNULL(S.sal_qty,0) - IFNULL(SR.sal_ret_qty,0) AS sal_qty,
        IFNULL(S.sal_amt,0) - IFNULL(SR.sal_ret_amt,0) AS sal_amt

    FROM M_ITEM_HDR I

    LEFT JOIN (
        SELECT B.item_id,
               SUM(B.qty) AS pur_qty,
               SUM(B.net_amt) AS pur_amt
        FROM t_receipt_hdr A
        JOIN t_receipt_det B ON A.receipt_id = B.receipt_id
        WHERE A.receipt_date BETWEEN ? AND ?
        GROUP BY B.item_id
    ) P ON P.item_id = I.item_id

    LEFT JOIN (
        SELECT item_id,
               SUM(qty) AS pur_ret_qty,
               SUM(net_amt) AS pur_ret_amt
        FROM t_pur_ret_det
        WHERE ret_dt BETWEEN ? AND ?
        GROUP BY item_id
    ) PR ON PR.item_id = I.item_id

    LEFT JOIN (
        SELECT item_id,
               SUM(qty) AS sal_qty,
               SUM(net_amt) AS sal_amt
        FROM t_invoice_det
        WHERE invoice_dt BETWEEN ? AND ?
        GROUP BY item_id
    ) S ON S.item_id = I.item_id

    LEFT JOIN (
        SELECT B.item_id,
               SUM(B.qty) AS sal_ret_qty,
               SUM(B.net_amt) AS sal_ret_amt
        FROM t_sr_hdr A
        JOIN t_sr_det B ON A.sr_no = B.sr_no
        WHERE A.sr_dt BETWEEN ? AND ?
        GROUP BY B.item_id
    ) SR ON SR.item_id = I.item_id

    WHERE I.MANUF_ID = ?
    HAVING pur_qty <> 0 OR sal_qty <> 0
    ORDER BY I.item_desc
    ";

    $stmt = $branch_db->prepare($sql);
    $stmt->bind_param(
        "ssssssssss",
        $from,
        $to,
        $from,
        $to,
        $from,
        $to,
        $from,
        $to,
        $manuf
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MANUFACTURE WISE SALE PURCHASE REPORT</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">

        <h2 class="mb-4 text-uppercase">Manufacture Wise Sale Purchase Report</h2>

        <!-- 🔍 Search Form -->
        <form method="get" class="row g-3 mb-4">

            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branch" class="form-select">
                    <option value="SHASHI-ND" <?= $branch === 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                    <option value="SHIVI-ND" <?= $branch === 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Manufacturer</label>
                <input type="text"
                    name="manuf"
                    class="form-control"
                    placeholder="Enter Manufacturer Code"
                    value="<?= htmlspecialchars($manuf) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>

            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>

        <!-- 📊 Report Table -->
        <table id="reportTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Item ID</th>
                    <th>Item Description</th>
                    <th class="text-end">Purchase Qty</th>
                    <th class="text-end">Purchase Amount</th>
                    <th class="text-end">Sale Qty</th>
                    <th class="text-end">Sale Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['item_id']) ?></td>
                        <td><?= htmlspecialchars($r['item_desc']) ?></td>
                        <td class="text-end"><?= number_format($r['pur_qty'], 3) ?></td>
                        <td class="text-end"><?= number_format($r['pur_amt'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['sal_qty'], 3) ?></td>
                        <td class="text-end"><?= number_format($r['sal_amt'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 25,
                order: [
                    [1, 'asc']
                ]
            });
        });
    </script>

</body>

</html>