<?php
require_once __DIR__ . '/../includes/config.php';
include "../includes/header.php";

// Ensure session is active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];
$session_branch = $_SESSION['branch_id'];

// Default dates
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// Filters
$branch = $_GET['branch'] ?? $session_branch;
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// For summary cards: use today's data if filters are empty
$summary_from = $from ?: $today;
$summary_to = $to ?: $today;

// For chart: always show current month if filters are empty
$chart_from = $from ?: $month_start;
$chart_to = $to ?: $today;

// 🔸 Sales Data
$sql_sales = "
    SELECT DATE(invoice_dt) AS date, SUM(net_amt_after_disc) AS total_sale
    FROM t_invoice_hdr
    WHERE DATE(invoice_dt) BETWEEN ? AND ? AND branch_id = ?
    GROUP BY DATE(invoice_dt)";
$stmt_sales = $con->prepare($sql_sales);
$stmt_sales->bind_param("sss", $chart_from, $chart_to, $branch);
$stmt_sales->execute();
$res_sales = $stmt_sales->get_result();

// 🔸 Returns Data
$sql_returns = "
    SELECT DATE(sr_dt) AS date, SUM(net_amt) AS total_return
    FROM t_sr_hdr
    WHERE DATE(sr_dt) BETWEEN ? AND ? AND branch_id = ?
    GROUP BY DATE(sr_dt)";
$stmt_returns = $con->prepare($sql_returns);
$stmt_returns->bind_param("sss", $chart_from, $chart_to, $branch);
$stmt_returns->execute();
$res_returns = $stmt_returns->get_result();

// Build chart dataset
$data = [];
while ($row = $res_sales->fetch_assoc()) {
    $data[$row['date']]['sales'] = $row['total_sale'];
}
while ($row = $res_returns->fetch_assoc()) {
    $data[$row['date']]['returns'] = $row['total_return'];
}

$labels = [];
$salesData = [];
$returnsData = [];

$start = new DateTime($chart_from);
$end = new DateTime($chart_to);
$end->modify('+1 day');

for ($d = $start; $d < $end; $d->modify('+1 day')) {
    $date_str = $d->format('Y-m-d');
    $labels[] = $date_str;
    $salesData[] = $data[$date_str]['sales'] ?? 0;
    $returnsData[] = $data[$date_str]['returns'] ?? 0;
}

// Summary totals
$sql_summary = "
    SELECT 
        (SELECT COALESCE(SUM(net_amt_after_disc), 0) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ? AND branch_id = ?) AS total_sales,
        (SELECT COALESCE(SUM(net_amt), 0) FROM t_sr_hdr WHERE DATE(sr_dt) BETWEEN ? AND ? AND branch_id = ?) AS total_returns,
        (SELECT COUNT(*) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ? AND branch_id = ?) AS invoice_count";
$stmt_summary = $con->prepare($sql_summary);
$stmt_summary->bind_param("sssssssss", $summary_from, $summary_to, $branch, $summary_from, $summary_to, $branch, $summary_from, $summary_to, $branch);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

$total_sales = $summary['total_sales'];
$total_returns = $summary['total_returns'];
$invoice_count = $summary['invoice_count'];
$net_total = $total_sales - $total_returns;

// Get branch list for Admin
$branches = [];
if ($role_name === 'Admin') {
    $branch_sql = "SELECT id FROM m_branch";
    $branch_result = $con->query($branch_sql);
    while ($row = $branch_result->fetch_assoc()) {
        $branches[] = $row['id'];
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
        <h2 class="mb-3">Sales & Return Overview</h2>

        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="branch">Branch</label>
                <select name="branch" id="branch" class="form-select">
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b ?>" <?= $b === $branch ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="from">From Date</label>
                <input type="date" name="from" id="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="to">To Date</label>
                <input type="date" name="to" id="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Apply</button>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Net Sales</div>
                    <h5 class="text-success mt-2">₹ <?= number_format($total_sales, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Sale Returns</div>
                    <h5 class="text-danger mt-2">₹ <?= number_format($total_returns, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Net Total</div>
                    <h5 class="text-primary mt-2">₹ <?= number_format($net_total, 2) ?></h5>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white shadow-sm rounded text-center">
                    <div class="text-muted small">Invoices</div>
                    <h5 class="text-dark mt-2"><?= $invoice_count ?></h5>
                </div>
            </div>
        </div>

        <!-- Chart -->
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