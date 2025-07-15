<?php
require_once __DIR__ . '/../includes/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "../includes/header.php";
// Determine current month default range
$today = date('Y-m-d');
$firstDay = date('Y-m-01');

// If user selected dates, use them; else default to 1st–today
$from = $_GET['from'] ?? $firstDay;
$to = $_GET['to'] ?? $today;

// Fetch daily sales totals from DB
$sql = "
    SELECT invoice_dt, SUM(net_amt_after_disc) AS total_sale
    FROM t_invoice_hdr
    WHERE invoice_dt BETWEEN ? AND ?
    GROUP BY invoice_dt
    ORDER BY invoice_dt
";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();

// Prepare data for chart
$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['invoice_dt'];
    $data[] = $row['total_sale'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sales Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Sales Chart - <?= date("F Y", strtotime($from)) ?></h2>

        <!-- Date Filter Form -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <!-- Sales Chart -->
        <canvas id="salesChart" height="100"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Net Sales (₹)',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹ ' + context.formattedValue;
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Net Sales per Day',
                        color: '#333',
                        font: {
                            size: 18
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
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
                            color: '#333'
                        },
                        grid: {
                            color: '#ddd',
                            borderDash: [5, 5]
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>