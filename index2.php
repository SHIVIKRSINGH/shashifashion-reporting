<?php
// DB Connection
$host = 'localhost';
$db = 'softgen_db';
$user = 'shivendra';
$pass = '24199319@Shiv';
$conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);

// Default to today
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');

// Get invoices
$stmt = $conn->prepare("SELECT * FROM t_invoice_hdr WHERE invoice_dt BETWEEN :from AND :to ORDER BY invoice_no asc");
$stmt->execute([':from' => $from, ':to' => $to]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total
$totalStmt = $conn->prepare("SELECT SUM(net_amt_after_disc) as total FROM t_invoice_hdr WHERE invoice_dt BETWEEN :from AND :to");
$totalStmt->execute([':from' => $from, ':to' => $to]);
$total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Invoice List</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>

<body>

    <h2>Invoice Report</h2>

    <form method="get">
        <label>From Date:</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        <label>To Date:</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        <button type="submit">Search</button>
    </form>

    <br>

    <table id="invoiceTable" class="display">
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

    <h3>Total Sale: Rs. <?= number_format($total, 2) ?></h3>

    <script>
        $(document).ready(function() {
            $('#invoiceTable').DataTable();
        });
    </script>

</body>

</html>