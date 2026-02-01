<?php
require_once "../includes/config.php";

/* ===========================
   BRANCH HANDLING
=========================== */
$branch = $_GET['branch'] ?? 'SHASHI-ND';
$showMilk = ($branch === 'SHIVI-ND');

/* ===========================
   HARDCODED MILK ITEMS
=========================== */
$milk_items = [
    'GOLD_1L' => ['name' => 'GOLD 1 LTR', 'cost' => 811, 'mrp' => 828],
    'GOLD_2L' => ['name' => 'GOLD 2 LTR', 'cost' => 823, 'mrp' => 840],
    'TOND_1L' => ['name' => 'TOND 1 LTR', 'cost' => 668, 'mrp' => 684],
    'TOND_2L' => ['name' => 'TOND 2 LTR', 'cost' => 680, 'mrp' => 696],
    'COW_1L'  => ['name' => 'COW 1 LTR',  'cost' => 684, 'mrp' => 708],
    'BUFF_1L' => ['name' => 'BUFFALO 1 LTR', 'cost' => 876, 'mrp' => 900],
    'CURD_400' => ['name' => 'CURD 400 GM', 'cost' => 990, 'mrp' => 1050],
    'CURD_1K' => ['name' => 'CURD 1 KG', 'cost' => 894, 'mrp' => 924],
];

/* ===========================
   SAVE SESSION
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showMilk) {

    $milk_date = $_POST['milk_date'];
    $session   = $_POST['session'];

    $totCost = $totMrp = 0;

    foreach ($_POST['qty'] as $code => $qty) {
        if ($qty > 0) {
            $totCost += $qty * $milk_items[$code]['cost'];
            $totMrp  += $qty * $milk_items[$code]['mrp'];
        }
    }

    $profit = $totMrp - $totCost;

    $stmt = $con->prepare("
        INSERT INTO milk_session_hdr
        (branch_id, milk_date, session, total_cost_amt, total_mrp_amt, net_profit)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "sssddd",
        $branch,
        $milk_date,
        $session,
        $totCost,
        $totMrp,
        $profit
    );
    $stmt->execute();

    $session_id = $stmt->insert_id;

    foreach ($_POST['qty'] as $code => $qty) {
        if ($qty > 0) {
            $c = $milk_items[$code]['cost'];
            $m = $milk_items[$code]['mrp'];

            $stmt = $con->prepare("
                INSERT INTO milk_session_det
                (session_id, item_code, qty, cost_rate, mrp_rate, cost_amt, mrp_amt)
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "isidddd",
                $session_id,
                $code,
                $qty,
                $c,
                $m,
                $qty * $c,
                $qty * $m
            );
            $stmt->execute();
        }
    }
}

/* ===========================
   PAYMENT SUMMARY
=========================== */
$yest_even = $today_morn = null;

if ($showMilk) {
    $yest = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');

    $q = $con->prepare("
        SELECT * FROM milk_session_hdr
        WHERE branch_id='SHIVI-ND' AND milk_date=? AND session='EVENING'
    ");
    $q->bind_param("s", $yest);
    $q->execute();
    $yest_even = $q->get_result()->fetch_assoc();

    $q = $con->prepare("
        SELECT * FROM milk_session_hdr
        WHERE branch_id='SHIVI-ND' AND milk_date=? AND session='MORNING'
    ");
    $q->bind_param("s", $today);
    $q->execute();
    $today_morn = $q->get_result()->fetch_assoc();
}

$totalCost = ($yest_even['total_cost_amt'] ?? 0) + ($today_morn['total_cost_amt'] ?? 0);
$totalMrp  = ($yest_even['total_mrp_amt'] ?? 0)  + ($today_morn['total_mrp_amt'] ?? 0);
$netProfit = $totalMrp - $totalCost;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Milk Order Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-4">

        <form method="get" class="mb-4">
            <select name="branch" class="form-select w-25" onchange="this.form.submit()">
                <option value="SHASHI-ND" <?= $branch == 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                <option value="SHIVI-ND" <?= $branch == 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
            </select>
        </form>

        <?php if ($showMilk): ?>

            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="date" name="milk_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <select name="session" class="form-select" required>
                            <option value="MORNING">MORNING</option>
                            <option value="EVENING">EVENING</option>
                        </select>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>MRP</th>
                            <th>Cost Amt</th>
                            <th>MRP Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($milk_items as $code => $m): ?>
                            <tr>
                                <td><?= $m['name'] ?></td>
                                <td><input type="number" step="0.01" name="qty[<?= $code ?>]" class="form-control qty"></td>
                                <td><?= $m['cost'] ?></td>
                                <td><?= $m['mrp'] ?></td>
                                <td class="cost">0.00</td>
                                <td class="mrp">0.00</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button class="btn btn-success">SAVE SESSION</button>
            </form>

            <hr>

            <div class="card p-3">
                <h5>1 Day Payment Summary</h5>
                <p>Total Cost : <b><?= number_format($totalCost, 2) ?></b></p>
                <p>Total MRP : <b><?= number_format($totalMrp, 2) ?></b></p>
                <p>Net Profit : <b><?= number_format($netProfit, 2) ?></b></p>
            </div>

        <?php endif; ?>

    </div>

    <script>
        const items = <?= json_encode($milk_items) ?>;
        document.querySelectorAll('.qty').forEach((el, i) => {
            el.addEventListener('input', () => {
                let tr = el.closest('tr');
                let code = Object.keys(items)[i];
                let q = parseFloat(el.value) || 0;
                tr.querySelector('.cost').innerText = (q * items[code].cost).toFixed(2);
                tr.querySelector('.mrp').innerText = (q * items[code].mrp).toFixed(2);
            });
        });
    </script>

</body>

</html>