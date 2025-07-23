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

$stmt = $con->prepare("
    SELECT 
        A.receipt_id, A.receipt_date, A.order_no, A.bill_no, A.bill_date, A.supp_id,
        C.supp_name, C.address_1, C.address_2, C.address_3,
        A.gross_amt, A.net_amt, A.ent_by
    FROM t_receipt_hdr A
    JOIN m_supplier C ON A.supp_id = C.supp_id
    WHERE A.receipt_id = ? AND TRIM(A.branch_id) = ?
");
$stmt->bind_param("ss", $receipt_id, $branch_id);
$stmt->execute();
$result = $stmt->get_result();
$receipt = $result->fetch_assoc();
$stmt->close();

$stmt = $con->prepare("
    SELECT 
        B.sl_no, B.bar_code, B.item_id, D.hsn_code, D.item_desc AS item_name,
        B.qty, B.free_item_yn, B.pur_rate, B.item_amt, B.disc_per, B.disc_amt,
        B.vat_per, B.vat_amt, B.net_rate, B.net_amt AS d_net_amt,
        B.mrp, B.sales_price, B.cess_perc, B.cess_amt,
        B.on_item_id,
        (SELECT item_desc FROM m_item_hdr WHERE item_id = B.on_item_id LIMIT 1) AS on_item_name
    FROM t_receipt_det B
    JOIN m_item_hdr D ON B.item_id = D.item_id
    WHERE B.receipt_id = ?
    ORDER BY CAST(B.sl_no AS SIGNED)
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
                        <strong>Address:</strong> <?= htmlspecialchars($receipt['address_1']) ?>, <?= htmlspecialchars($receipt['address_2']) ?>, <?= htmlspecialchars($receipt['address_3']) ?><br>
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
                        <th>VAT%</th>
                        <th>Net Amt</th>
                        <th>MRP</th>
                        <th>Sales Price</th>
                        <th>Cess%</th>
                        <th>On Item</th>
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
                            <td><?= $row['d_net_amt'] ?></td>
                            <td><?= $row['mrp'] ?></td>
                            <td><?= $row['sales_price'] ?></td>
                            <td><?= $row['cess_perc'] ?></td>
                            <td><?= htmlspecialchars($row['on_item_name'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>