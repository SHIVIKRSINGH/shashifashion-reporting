<?php
require_once "../includes/config.php";
include "../includes/header.php";

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);


$receipt_id = $_GET['receipt_id'] ?? '';
$branch_id = $_GET['branch_id'] ?? '';

if (!$receipt_id) {
    echo "<div class='alert alert-danger'>Missing receipt ID.</div>";
    exit;
}

$receipt = [];
$items = [];
$role_name       = $_SESSION['role_name'];
$session_branch  = $_SESSION['branch_id'] ?? '';
$selected_branch = $_GET['branch'] ?? ($_SESSION['selected_branch_id'] ?? $session_branch);


// 🔌 Connect to branch DB dynamically
$branch_db = null;
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


$stmt = $branch_db->prepare("
     SELECT 
    H.receipt_id,
    H.receipt_date,
    H.branch_id,
    H.pur_type,
    H.order_no,
    H.bill_no,
    H.bill_date,
    H.supp_id,
    S.supp_name,
    H.gross_amt AS hdr_gross_amt,
    H.net_amt AS hdr_net_amt,
    H.status,
    H.ent_by,
    H.ent_on FROM t_receipt_hdr H
JOIN m_supplier S ON H.supp_id = S.supp_id where H.receipt_id=?;
");
$stmt->bind_param("s", $receipt_id);
$stmt->execute();
$result = $stmt->get_result();
$receipt = $result->fetch_assoc();
$stmt->close();

$stmt = $branch_db->prepare("
    SELECT 
    B.sl_no,
    B.bar_code,
    B.item_id,
    C.hsn_code,
    C.item_desc AS item_name,
    B.qty,
    B.free_item_yn,
    B.pur_rate,
    B.item_amt,
    B.disc_per,
    B.disc_amt,
    B.vat_per,
    B.vat_amt,
    B.net_rate,
    B.net_amt AS d_net_amt,
    B.mrp,
    B.sales_price,
    B.cess_perc,
    B.cess_amt,

    -- ✅ Margin based on MRP
    CASE 
        WHEN B.net_rate > 0 THEN ROUND(((B.mrp - B.net_rate) * 100) / B.net_rate, 2)
        ELSE 0
    END AS margin,

    -- ✅ Margin based on Sales Price
    CASE 
        WHEN B.net_rate > 0 THEN ROUND(((B.sales_price - B.net_rate) * 100) / B.net_rate, 2)
        ELSE 0
    END AS margin_on_sp,

    -- ✅ Mark-down Margin (compared to MRP)
    CASE 
        WHEN B.mrp > 0 THEN ROUND(((B.mrp - B.net_rate) * 100) / B.mrp, 2)
        ELSE 0
    END AS mark_down_margin

FROM t_receipt_det B
JOIN m_item_hdr C ON TRIM(B.item_id) = TRIM(C.item_id)
WHERE TRIM(B.receipt_id) = ?
ORDER BY CAST(B.sl_no AS SIGNED);

");
$stmt->bind_param("s", $receipt_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>GRN View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container my-5">
        <h3>Purchase Details (GRN)</h3>

        <?php if (!$receipt): ?>
            <div class="alert alert-warning">No GRN found for this receipt ID.</div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Receipt #<?= htmlspecialchars($receipt['receipt_id']) ?></h5>
                    <p><strong>Supplier:</strong> <?= htmlspecialchars($receipt['supp_name']) ?><br>                        
                        <strong>Receipt Date:</strong> <?= $receipt['receipt_date'] ?><br>
                        <strong>Bill No:</strong> <?= $receipt['bill_no'] ?> (<?= $receipt['bill_date'] ?>)<br>
                        <strong>Entered By:</strong> <?= $receipt['ent_by'] ?><br>
                        <strong>Gross Amount:</strong> ₹<?= number_format($receipt['gross_amt'], 2) ?><br>
                        <strong>Net Amount:</strong> ₹<?= number_format($receipt['net_amt'], 2) ?>
                    </p>
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-secondary">
                    <tr>
                        <th>Sl</th>
                        <th>Item Name</th>
                        <th>HSN</th>
                        <th>Qty</th>
                        <th>Pur Rate</th>
                        <th>Disc%</th>
                        <th>GST%</th>
                        <th>Net Rate</th>
                        <th>Net Amt</th>
                        <th>MRP</th>
                        <th>Sales Price</th>
                        <th>Cess%</th>
                        <th>MARGIN ON MRP</th>
                        <th>MARGIN ON SP</th>
                        <th>MARK DOWN MARGIN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= $row['sl_no'] ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= $row['hsn_code'] ?></td>
                            <td><?= $row['qty'] ?></td>
                            <td><?= $row['pur_rate'] ?></td>
                            <td><?= $row['disc_per'] ?></td>
                            <td><?= $row['vat_per'] ?></td>
                            <td><?= $row['net_rate'] ?></td>
                            <td><?= $row['d_net_amt'] ?></td>
                            <td><?= $row['mrp'] ?></td>
                            <td><?= $row['sales_price'] ?></td>
                            <td><?= $row['cess_perc'] ?></td>
                            <td><?= $row['margin'] ?></td>
                            <td><?= $row['margin_on_sp'] ?></td>
                            <td><?= $row['mark_down_margin'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>