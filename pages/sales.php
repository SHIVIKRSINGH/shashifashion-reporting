<?php
require_once "../includes/config.php"; // MySQLi config
include "../includes/header.php";

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Default date range
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$branch = $_GET['branch'] ?? 'SHASHI-ND'; // ✅ Default Branch

// Fetch invoices
$invoices = [];
$total = 0;

if ($stmt = $con->prepare("SELECT invoice_no, cust_id, invoice_dt, bill_time, net_amt_after_disc FROM t_invoice_hdr WHERE ltrim(rtrim(branch_id))=? and invoice_dt BETWEEN ? AND ? ORDER BY invoice_no desc")) {
    $stmt->bind_param("sss", $branch, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    $stmt->close();
}

// Fetch total amount
if ($stmt = $con->prepare("SELECT SUM(net_amt_after_disc) as total FROM t_invoice_hdr WHERE ltrim(rtrim(branch_id))=? and invoice_dt BETWEEN ? AND ?")) {
    $stmt->bind_param("sss", $branch, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalRow = $result->fetch_assoc();
    $total = $totalRow['total'] ?? 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h2 class="mb-4">Invoice Report</h2>

        <form method="get" class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <label for="branch">Branch</label>
                <select name="branch" id="branch" class="form-select">
                    <option value="SHASHI-ND" <?= $branch === 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                    <option value="SHIVI-ND" <?= $branch === 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
                    <!-- Add more branches here -->
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <table id="invoiceTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Customer</th>
                    <th>Invoice Date</th>
                    <th>Invoice Time</th>
                    <th>Net Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                        <td><?= htmlspecialchars($row['cust_id'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['invoice_dt']) ?></td>
                        <td><?= htmlspecialchars($row['bill_time']) ?></td>
                        <td><?= number_format($row['net_amt_after_disc'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-3">
            <h5>Total Sale: ₹ <?= number_format($total, 2) ?></h5>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#invoiceTable').DataTable();
        });
    </script>

</body>

</html>