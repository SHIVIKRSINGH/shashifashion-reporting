<?php
require_once __DIR__ . '/../includes/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "../includes/header.php";
// Default: 1st of this month to today
$today = date('Y-m-d');
$firstDay = date('Y-m-01');
$from = $_GET['from'] ?? $firstDay;
$to = $_GET['to'] ?? $today;

// Fetch sales data
$sql_sales = "
    SELECT date(invoice_dt) AS date, SUM(net_amt_after_disc) AS total_sale
    FROM t_invoice_hdr
    WHERE date(invoice_dt) BETWEEN ? AND ?
    GROUP BY invoice_dt
";
$stmt_sales = $con->prepare($sql_sales);
$stmt_sales->bind_param("ss", $from, $to);
$stmt_sales->execute();
$res_sales = $stmt_sales->get_result();

// Fetch return data
$sql_returns = "
    SELECT date(sr_dt) AS date, SUM(net_amt) AS total_return
    FROM t_sr_hdr
    WHERE date(sr_dt) BETWEEN ? AND ?
    GROUP BY sr_dt
";
$stmt_returns = $con->prepare($sql_returns);
$stmt_returns->bind_param("ss", $from, $to);
$stmt_returns->execute();
$res_returns = $stmt_returns->get_result();

// Combine both datasets
$data = [];
while ($row = $res_sales->fetch_assoc()) {
    $data[$row['date']]['sales'] = $row['total_sale'];
}
while ($row = $res_returns->fetch_assoc()) {
    $data[$row['date']]['returns'] = $row['total_return'];
}

// Normalize dates and fill missing days
$labels = [];
$salesData = [];
$returnsData = [];

$start = new DateTime($from);
$end = new DateTime($to);
$end->modify('+1 day'); // to include end date

for ($date = $start; $date < $end; $date->modify('+1 day')) {
    $d = $date->format('Y-m-d');
    $labels[] = $d;
    $salesData[] = $data[$d]['sales'] ?? 0;
    $returnsData[] = $data[$d]['returns'] ?? 0;
}
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
        canvas {
            max-height: 500px;
        }

        @media (max-width: 768px) {
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
                <label>From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
            </div>
            <div class="col-sm-6 col-md-3">
                <label>To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Apply</button>
            </div>
        </form>

        <!-- Chart Container -->
        <div class="card shadow-sm">
            <div class="card-body">
                <canvas id="dualBarChart"></canvas>
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
                        stacked: false,
                        ticks: {
                            color: '#333',
                            autoSkip: true,
                            maxRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₹ ' + value,
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