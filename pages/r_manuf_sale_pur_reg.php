<?php
require_once "../includes/config.php";
include "../includes/header.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =====================================================
    INPUTS (Cleaned of extra spaces)
===================================================== */
$from   = $_GET['from'] ?? date('Y-m-d');
$to     = $_GET['to'] ?? date('Y-m-d');
$manuf  = isset($_GET['manuf']) ? trim($_GET['manuf']) : '';
$branch = $_GET['branch'] ?? 'SHASHI-ND';

$rows = [];
$error_msg = "";

/* =====================================================
    GET BRANCH DB CONFIG
===================================================== */
$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
$stmt->bind_param("s", $branch);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $error_msg = "❌ Branch config not found for: " . htmlspecialchars($branch);
} else {
    $config = $res->fetch_assoc();

    /* =====================================================
        CONNECT TO BRANCH DB
    ===================================================== */
    $branch_db = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

    if ($branch_db->connect_error) {
        $error_msg = "❌ Branch DB connection failed: " . $branch_db->connect_error;
    } else {
        $branch_db->set_charset('utf8mb4');
        $branch_db->query("SET time_zone = '+05:30'");

        /* =====================================================
            REPORT QUERY (Using TRIM in WHERE clause)
        ===================================================== */
        if (!empty($manuf)) {
            $sql = "
            SELECT 
                I.item_id,
                I.item_desc,
                (IFNULL(P.pur_qty,0) - IFNULL(PR.pur_ret_qty,0)) AS pur_qty,
                (IFNULL(P.pur_amt,0) - IFNULL(PR.pur_ret_amt,0)) AS pur_amt,
                (IFNULL(S.sal_qty,0) - IFNULL(SR.sal_ret_qty,0)) AS sal_qty,
                (IFNULL(S.sal_amt,0) - IFNULL(SR.sal_ret_amt,0)) AS sal_amt
            FROM m_item_hdr I
            LEFT JOIN (
                SELECT B.item_id, SUM(B.qty) pur_qty, SUM(B.net_amt) pur_amt
                FROM t_receipt_hdr A
                JOIN t_receipt_det B ON A.receipt_id = B.receipt_id
                WHERE DATE(A.receipt_date) BETWEEN ? AND ?
                GROUP BY B.item_id
            ) P ON P.item_id = I.item_id
            LEFT JOIN (
                SELECT item_id, SUM(qty) pur_ret_qty, SUM(net_amt) pur_ret_amt
                FROM t_pur_ret_det
                WHERE DATE(ret_dt) BETWEEN ? AND ?
                GROUP BY item_id
            ) PR ON PR.item_id = I.item_id
            LEFT JOIN (
                SELECT item_id, SUM(qty) sal_qty, SUM(net_amt) sal_amt
                FROM t_invoice_det
                WHERE DATE(invoice_dt) BETWEEN ? AND ?
                GROUP BY item_id
            ) S ON S.item_id = I.item_id
            LEFT JOIN (
                SELECT B.item_id, SUM(B.qty) sal_ret_qty, SUM(B.net_amt) sal_ret_amt
                FROM t_sr_hdr A
                JOIN t_sr_det B ON A.sr_no = B.sr_no
                WHERE DATE(A.sr_dt) BETWEEN ? AND ?
                GROUP BY B.item_id
            ) SR ON SR.item_id = I.item_id
            WHERE TRIM(I.manuf_id) = ?
            AND (P.item_id IS NOT NULL OR S.item_id IS NOT NULL OR PR.item_id IS NOT NULL OR SR.item_id IS NOT NULL)
            ORDER BY I.item_desc";

            $stmt_rep = $branch_db->prepare($sql);
            $stmt_rep->bind_param("sssssssss", $from, $to, $from, $to, $from, $to, $from, $to, $manuf);
            $stmt_rep->execute();
            $result = $stmt_rep->get_result();

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Report - <?php echo $branch; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container-fluid py-4">
        <h4 class="mb-4">MANUFACTURE WISE SALE PURCHASE REPORT</h4>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="get" id="filterForm" class="card card-body shadow-sm mb-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch" id="branch" class="form-select">
                        <option value="SHASHI-ND" <?= $branch == 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                        <option value="SHIVI-ND" <?= $branch == 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" id="manuf_search" class="form-control" placeholder="Type to search..." autocomplete="off">
                    <input type="hidden" name="manuf" id="manuf" value="<?= htmlspecialchars($manuf) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="<?= $from ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="<?= $to ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Fetch Report</button>
                </div>
            </div>
        </form>

        <?php if (!empty($manuf) && empty($rows)): ?>
            <div class="alert alert-info">No transactions found for Manufacturer ID: <b><?= htmlspecialchars($manuf) ?></b> in the selected dates.</div>
        <?php endif; ?>

        <div class="table-responsive bg-white p-3 shadow-sm rounded">
            <table id="reportTable" class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Item ID</th>
                        <th>Description</th>
                        <th class="text-end">Pur Qty</th>
                        <th class="text-end">Pur Amt</th>
                        <th class="text-end">Sal Qty</th>
                        <th class="text-end">Sal Amt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['item_id']) ?></td>
                            <td><?= htmlspecialchars($r['item_desc']) ?></td>
                            <td class="text-end"><?= number_format($r['pur_qty'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['pur_amt'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['sal_qty'], 2) ?></td>
                            <td class="text-end"><?= number_format($r['sal_amt'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function() {
            // Autocomplete Logic
            $("#manuf_search").autocomplete({
                source: function(request, response) {
                    $.getJSON("manufacturer_search.php", {
                        term: request.term,
                        branch: $("#branch").val()
                    }, response);
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#manuf_search").val(ui.item.label);
                    $("#manuf").val(ui.item.value);
                    return false;
                }
            });

            // Keep Manufacturer ID visible for debugging if needed
            // If we have a manuf ID but no label, this helps track it
            var currentManuf = $("#manuf").val();
            if (currentManuf) {
                $("#manuf_search").attr("placeholder", "Manufacturer: " + currentManuf);
            }

            // Initialize DataTable
            $('#reportTable').DataTable({
                "pageLength": 50,
                "order": [
                    [1, "asc"]
                ]
            });
        });
    </script>
</body>

</html>