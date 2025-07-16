<?php
require_once __DIR__ . '/../includes/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "../includes/header.php";

// Determine today's date and first of current month
$today = date('Y-m-d');
$firstDay = date('Y-m-01');

// Get user-selected date range or use default
$from = $_GET['from'] ?? $firstDay;
$to = $_GET['to'] ?? $today;

// --- Fetch Sales Data for Chart ---
$sql_sales = "
    SELECT DATE(invoice_dt) AS date, SUM(net_amt_after_disc) AS total_sale
    FROM t_invoice_hdr
    WHERE DATE(invoice_dt) BETWEEN ? AND ?
    GROUP BY DATE(invoice_dt)
";
$stmt_sales = $con->prepare($sql_sales);
$stmt_sales->bind_param("ss", $from, $to);
$stmt_sales->execute();
$res_sales = $stmt_sales->get_result();

// --- Fetch Sale Return Data for Chart ---
$sql_returns = "
    SELECT DATE(sr_dt) AS date, SUM(net_amt) AS total_return
    FROM t_sr_hdr
    WHERE DATE(sr_dt) BETWEEN ? AND ?
    GROUP BY DATE(sr_dt)
";
$stmt_returns = $con->prepare($sql_returns);
$stmt_returns->bind_param("ss", $from, $to);
$stmt_returns->execute();
$res_returns = $stmt_returns->get_result();

// --- Build Combined Chart Data ---
$data = [];
while ($row = $res_sales->fetch_assoc()) {
    $data[$row['date']]['sales'] = $row['total_sale'];
}
while ($row = $res_returns->fetch_assoc()) {
    $data[$row['date']]['returns'] = $row['total_return'];
}

// --- Fill missing days for chart ---
$labels = [];
$salesData = [];
$returnsData = [];

$start = new DateTime($from);
$end = new DateTime($to);
$end->modify('+1 day');

for ($date = $start; $date < $end; $date->modify('+1 day')) {
    $d = $date->format('Y-m-d');
    $labels[] = $d;
    $salesData[] = $data[$d]['sales'] ?? 0;
    $returnsData[] = $data[$d]['returns'] ?? 0;
}

// --- Summary Cards (based on date range or today's data) ---
$summary_from = ($_GET['from'] ?? null) ? $from : $today;
$summary_to = ($_GET['to'] ?? null) ? $to : $today;

$sql_summary = "
    SELECT 
        (SELECT COALESCE(SUM(net_amt_after_disc), 0) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ?) AS total_sales,
        (SELECT COALESCE(SUM(net_amt), 0) FROM t_sr_hdr WHERE DATE(sr_dt) BETWEEN ? AND ?) AS total_returns,
        (SELECT COUNT(*) FROM t_invoice_hdr WHERE DATE(invoice_dt) BETWEEN ? AND ?) AS invoice_count
";
$stmt_summary = $con->prepare($sql_summary);
$stmt_summary->bind_param("ssssss", $summary_from, $summary_to, $summary_from, $summary_to, $summary_from, $summary_to);
$stmt_summary->execute();
$res_summary = $stmt_summary->get_result();
$summary = $res_summary->fetch_assoc();

$total_sales = $summary['total_sales'];
$total_returns = $summary['total_returns'];
$invoice_count = $summary['invoice_count'];
$net_total = $total_sales - $total_returns;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sales Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        canvas {
            min-width: 600px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-title {
            font-size: 0.9rem;
            color: #888;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 5px;
        }

        @media (max-width: 767.98px) {
            .summary-card {
                margin-bottom: 15px;
            }

            canvas {
                max-height: 300px;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="container py-4">
        <h2 class="mb-3">Sales & Return Overview</h2>

        <!-- Date Filter -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Apply</button>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="summary-card text-center">
                    <div class="summary-title">Net Sales</div>
                    <div class="summary-value text-success">₹ <?= number_format($total_sales, 2) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="summary-card text-center">
                    <div class="summary-title">Sale Returns</div>
                    <div class="summary-value text-danger">₹ <?= number_format($total_returns, 2) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="summary-card text-center">
                    <div class="summary-title">Net Total</div>
                    <div class="summary-value text-primary">₹ <?= number_format($net_total, 2) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="summary-card text-center">
                    <div class="summary-title">Total Invoices</div>
                    <div class="summary-value text-dark"><?= $invoice_count ?></div>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="dualBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('dualBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                        label: 'Net Sales (₹)',
                        data: <?= json_encode($salesData) ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderRadius: 5
                    },
                    {
                        label: 'Sale Returns (₹)',
                        data: <?= json_encode($returnsData) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => '₹ ' + ctx.formattedValue
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sales vs Returns per Day',
                        font: {
                            size: 18
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            },
                            color: '#333'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₹ ' + value,
                            font: {
                                size: 10
                            },
                            color: '#333'
                        },
                        grid: {
                            color: '#eee'
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>