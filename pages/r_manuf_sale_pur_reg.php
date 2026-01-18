<?php
require_once "../includes/config.php";
include "../includes/header.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =====================================================
   AJAX: Manufacturer Search (Branch-wise)
===================================================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'manuf_search') {

    $term   = $_GET['term'] ?? '';
    $branch = $_GET['branch'] ?? '';

    if ($branch === '' || $term === '') {
        echo json_encode([]);
        exit;
    }

    $stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode([]);
        exit;
    }

    $cfg = $res->fetch_assoc();

    $db = new mysqli(
        $cfg['db_host'],
        $cfg['db_user'],
        $cfg['db_password'],
        $cfg['db_name']
    );

    if ($db->connect_error) {
        echo json_encode([]);
        exit;
    }

    $sql = "
        SELECT manuf_id, manuf_name
        FROM m_manuf
        WHERE manuf_name LIKE CONCAT('%', ?, '%')
           OR manuf_id LIKE CONCAT('%', ?, '%')
        ORDER BY manuf_name
        LIMIT 20
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'label' => $row['manuf_name'] . ' (' . $row['manuf_id'] . ')',
            'value' => $row['manuf_id']
        ];
    }

    echo json_encode($data);
    exit;
}

/* =====================================================
   NORMAL PAGE LOGIC
===================================================== */

$from   = $_GET['from'] ?? date('Y-m-d');
$to     = $_GET['to'] ?? date('Y-m-d');
$manuf  = $_GET['manuf'] ?? '';
$branch = $_GET['branch'] ?? 'SHASHI-ND';

$rows = [];

$stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
$stmt->bind_param("s", $branch);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("❌ Branch config not found");
}

$config = $res->fetch_assoc();

$branch_db = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name']
);

if ($branch_db->connect_error) {
    die("❌ Branch DB connection failed");
}

$branch_db->set_charset('utf8mb4');
$branch_db->query("SET time_zone = '+05:30'");

/* =====================================================
   REPORT QUERY
===================================================== */
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
    SELECT B.item_id, SUM(B.qty) pur_qty, SUM(B.net_amt) pur_amt
    FROM t_receipt_hdr A
    JOIN t_receipt_det B ON A.receipt_id = B.receipt_id
    WHERE A.receipt_date BETWEEN ? AND ?
    GROUP BY B.item_id
) P ON P.item_id = I.item_id

LEFT JOIN (
    SELECT item_id, SUM(qty) pur_ret_qty, SUM(net_amt) pur_ret_amt
    FROM t_pur_ret_det
    WHERE ret_dt BETWEEN ? AND ?
    GROUP BY item_id
) PR ON PR.item_id = I.item_id

LEFT JOIN (
    SELECT item_id, SUM(qty) sal_qty, SUM(net_amt) sal_amt
    FROM t_invoice_det
    WHERE invoice_dt BETWEEN ? AND ?
    GROUP BY item_id
) S ON S.item_id = I.item_id

LEFT JOIN (
    SELECT B.item_id, SUM(B.qty) sal_ret_qty, SUM(B.net_amt) sal_ret_amt
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

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MANUFACTURE WISE SALE PURCHASE REPORT</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <h3 class="mb-4 text-uppercase">Manufacture Wise Sale Purchase Report</h3>

        <form method="get" class="row g-3 mb-4" id="searchForm">

            <div class="col-md-3">
                <label>Branch</label>
                <select name="branch" id="branch" class="form-select">
                    <option value="SHASHI-ND" <?= $branch == 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                    <option value="SHIVI-ND" <?= $branch == 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Manufacturer</label>
                <input type="text" id="manuf_search" class="form-control" placeholder="Type & select manufacturer">
                <input type="hidden" name="manuf" id="manuf" value="<?= htmlspecialchars($manuf) ?>">
            </div>

            <div class="col-md-2">
                <label>From Date</label>
                <input type="date" name="from" class="form-control" value="<?= $from ?>">
            </div>

            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" name="to" class="form-control" value="<?= $to ?>">
            </div>

            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>

        </form>

        <table id="reportTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Item ID</th>
                    <th>Description</th>
                    <th class="text-end">Purchase Qty</th>
                    <th class="text-end">Purchase Amt</th>
                    <th class="text-end">Sale Qty</th>
                    <th class="text-end">Sale Amt</th>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {

            let selectedManuf = false;

            $('#manuf_search').autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.getJSON('r_manuf_sale_pur_reg.php', {
                        ajax: 'manuf_search',
                        term: request.term,
                        branch: $('#branch').val()
                    }, response);
                },
                select: function(event, ui) {
                    $('#manuf_search').val(ui.item.label);
                    $('#manuf').val(ui.item.value);
                    selectedManuf = true;
                    return false;
                },
                change: function() {
                    if (!selectedManuf) {
                        $('#manuf').val('');
                    }
                }
            });

            /* 🔥 CRITICAL FIX: Force selection on submit */
            $('#searchForm').on('submit', function(e) {

                if ($('#manuf').val() === '' && $('#manuf_search').val() !== '') {

                    e.preventDefault();

                    $.getJSON('r_manuf_sale_pur_reg.php', {
                        ajax: 'manuf_search',
                        term: $('#manuf_search').val(),
                        branch: $('#branch').val()
                    }, function(data) {

                        if (data.length === 1) {
                            $('#manuf').val(data[0].value);
                            $('#manuf_search').val(data[0].label);
                            $('#searchForm')[0].submit();
                        } else {
                            alert('Please select manufacturer from list');
                        }
                    });
                }
            });

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