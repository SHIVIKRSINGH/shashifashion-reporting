<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_utils.php';
include "../includes/header.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$role_name = $_SESSION['role_name'];
$session_branch = $_SESSION['branch_id'];

// Filters
$branch = $_GET['branch'] ?? $session_branch;
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// Defaults
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$summary_from = $from ?: $today;
$summary_to = $to ?: $today;
$chart_from = $from ?: $month_start;
$chart_to = $to ?: $today;

// ✅ Get branch DB connection dynamically
$branchCon = getBranchConnection($branch);

// 🔸 Sales data
$stmt = $branchCon->prepare("
    SELECT DATE(invoice_dt) AS date, SUM(net_amt_after_disc) AS total_sale
    FROM t_invoice_hdr
    WHERE DATE(invoice_dt) BETWEEN ? AND ?
    GROUP BY DATE(invoice_dt)
");
$stmt->bind_param("ss", $chart_from, $chart_to);
$stmt->execute();
$res_sales = $stmt->get_result();

// 🔸 Return data
$stmt = $branchCon->prepare("
    SELECT DATE(sr_dt) AS date, SUM(net_amt) AS total_return
    FROM t_sr_hdr
    WHERE DATE(sr_dt) BETWEEN ? AND ?
    GROUP BY DATE(sr_dt)
");
$stmt->bind_param("ss", $chart_from, $chart_to);
$stmt->execute();
$res_returns = $stmt->get_result();

// Merge data for chart
$data = [];
while ($row = $res_sales->fetch_assoc()) {
    $data[$row['date']]['sales'] = $row['total_sale'];
}
while ($row = $res_returns->fetch_assoc()) {
    $data[$row['date']]['returns'] = $row['total_return'];
}

// Prepare chart labels
$labels = [];
$salesData = [];
$returnsData = [];
$start = new DateTime($chart_from);
$end = new DateTime($chart_to);
$end->modify('+1 day');

for ($d = $start; $d < $end; $d->modify('+1 day')) {
    $date = $d->format('Y-m-d');
    $labels[] = $date;
    $salesData[] = $data[$date]['sales'] ?? 0;
    $returnsData[] = $data[$date]['returns'] ?? 0;
}

// Summary totals
$stmt = $branchCon->prepare("
    SELECT 
        (SELECT COALESCE(SUM(net_amt_after_disc), 0) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ?) AS total_sales,
        (SELECT COALESCE(SUM(net_amt), 0) FROM t_sr_hdr WHERE DATE(sr_dt) BETWEEN ? AND ?) AS total_returns,
        (SELECT COUNT(*) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ?) AS invoice_count
");
$stmt->bind_param("ssssss", $summary_from, $summary_to, $summary_from, $summary_to, $summary_from, $summary_to);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

$total_sales = $summary['total_sales'];
$total_returns = $summary['total_returns'];
$invoice_count = $summary['invoice_count'];
$net_total = $total_sales - $total_returns;

// Branch list for Admin
$branches = [];
if ($role_name === 'Admin') {
    $res = $con->query("SELECT branch_id FROM m_branch_sync_config");
    while ($row = $res->fetch_assoc()) {
        $branches[] = $row['branch_id'];
    }
} else {
    $branches[] = $session_branch;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <div class="container py-4">
        <h2 class="mb-4">Sales & Return Dashboard</h2>

        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <label>Branch</label>
                <select name="branch" class="form-select">
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b ?>" <?= $b === $branch ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label>To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Total Sales</div>
                    <h5 class="text-success">₹ <?= number_format($total_sales, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Sale Returns</div>
                    <h5 class="text-danger">₹ <?= number_format($total_returns, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Net Total</div>
                    <h5 class="text-primary">₹ <?= number_format($net_total, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Invoices</div>
                    <h5><?= $invoice_count ?></h5>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <canvas id="chart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('chart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                        label: 'Net Sales (₹)',
                        data: <?= json_encode($salesData) ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)'
                    },
                    {
                        label: 'Sale Returns (₹)',
                        data: <?= json_encode($returnsData) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => '₹ ' + ctx.formattedValue
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₹ ' + value
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>