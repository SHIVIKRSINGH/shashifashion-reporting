<?php
require_once "../includes/config.php";

/* ===========================
   BRANCH LOGIC
=========================== */
$branch = $_GET['branch'] ?? 'SHASHI-ND';
$showMilk = ($branch === 'SHIVI-ND');

/* ===========================
   DATE FILTERS
=========================== */
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date']   ?? date('Y-m-d');

/* ===========================
   HARDCODED MILK ITEMS
=========================== */
$milk_items = [
    'GOLD_1L' => ['name' => 'GOLD 1 LTR', 'cost' => 811, 'mrp' => 828],
    'GOLD_1/2L' => ['name' => 'GOLD 1/2 LTR', 'cost' => 823, 'mrp' => 840],
    'TOND_1L' => ['name' => 'TOND 1 LTR', 'cost' => 668, 'mrp' => 684],
    'TOND_1/2L' => ['name' => 'TOND 1/2 LTR', 'cost' => 680, 'mrp' => 696],
    'COW_1L'  => ['name' => 'COW 1 LTR',  'cost' => 684, 'mrp' => 708],
    'COW_1/2L'  => ['name' => 'COW 1/2 LTR',  'cost' => 692, 'mrp' => 720],
    'BUFF_1L' => ['name' => 'BUFFALO 1 LTR', 'cost' => 876, 'mrp' => 900],
    'CURD_400' => ['name' => 'CURD 400 GM', 'cost' => 990, 'mrp' => 1050],
    'CURD_1K' => ['name' => 'CURD 1 KG', 'cost' => 894, 'mrp' => 924],
    'CURD_10rs' => ['name' => 'CURD 10 RS', 'cost' => 9, 'mrp' => 10],
    'PANEER_200GM' => ['name' => 'PANEER 200GM', 'cost' => 82, 'mrp' => 92],
    'CHAACH' => ['name' => 'CHAACH', 'cost' => 336, 'mrp' => 360],
];

/* ===========================
   EDIT SESSION FETCH
=========================== */
$editSession = null;
$editQty = [];

if ($showMilk && isset($_GET['edit_date'], $_GET['edit_session'])) {
    $stmt = $con->prepare("
        SELECT * FROM milk_session_hdr
        WHERE branch_id='SHIVI-ND'
        AND milk_date=? AND session=?
    ");
    $stmt->bind_param("ss", $_GET['edit_date'], $_GET['edit_session']);
    $stmt->execute();
    $editSession = $stmt->get_result()->fetch_assoc();

    if ($editSession) {
        $stmt = $con->prepare("
            SELECT item_code, qty
            FROM milk_session_det
            WHERE session_id=?
        ");
        $stmt->bind_param("i", $editSession['session_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $editQty[$r['item_code']] = $r['qty'];
        }
    }
}

/* ===========================
   SAVE / UPDATE SESSION
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showMilk) {

    $milk_date = $_POST['milk_date'];
    $session   = $_POST['session'];
    $session_id = $_POST['session_id'] ?? null;

    $totCost = $totMrp = 0;

    foreach ($_POST['qty'] as $code => $qty) {
        if ($qty > 0) {
            $totCost += $qty * $milk_items[$code]['cost'];
            $totMrp  += $qty * $milk_items[$code]['mrp'];
        }
    }

    $profit = $totMrp - $totCost;

    if ($session_id) {
        // UPDATE
        $stmt = $con->prepare("
            UPDATE milk_session_hdr
            SET total_cost_amt=?, total_mrp_amt=?, net_profit=?
            WHERE session_id=?
        ");
        $stmt->bind_param("dddi", $totCost, $totMrp, $profit, $session_id);
        $stmt->execute();

        $con->query("DELETE FROM milk_session_det WHERE session_id=$session_id");
    } else {
        // INSERT
        $stmt = $con->prepare("
            INSERT INTO milk_session_hdr
            (branch_id, milk_date, session, total_cost_amt, total_mrp_amt, net_profit)
            VALUES ('SHIVI-ND',?,?,?,?,?)
        ");
        $stmt->bind_param("ssddd", $milk_date, $session, $totCost, $totMrp, $profit);
        $stmt->execute();
        $session_id = $stmt->insert_id;
    }

    foreach ($_POST['qty'] as $code => $qty) {
        if ($qty > 0) {

            $c = $milk_items[$code]['cost'];
            $m = $milk_items[$code]['mrp'];

            // ✅ MUST be variables (not expressions)
            $cost_amt = $qty * $c;
            $mrp_amt  = $qty * $m;

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
                $cost_amt,
                $mrp_amt
            );

            $stmt->execute();
        }
    }
}

/* ===========================
   SESSION LIST (FROM–TO)
=========================== */
$sessions = [];
if ($showMilk) {
    $stmt = $con->prepare("
        SELECT *
        FROM milk_session_hdr
        WHERE branch_id='SHIVI-ND'
        AND milk_date BETWEEN ? AND ?
        ORDER BY milk_date DESC, FIELD(session,'EVENING','MORNING')
    ");
    $stmt->bind_param("ss", $from_date, $to_date);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ===========================
   PAYMENT SUMMARY
=========================== */
$yest = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

function getAmt($con, $d, $s)
{
    $q = $con->prepare("
        SELECT total_cost_amt,total_mrp_amt
        FROM milk_session_hdr
        WHERE branch_id='SHIVI-ND'
        AND milk_date=? AND session=?
    ");
    $q->bind_param("ss", $d, $s);
    $q->execute();
    return $q->get_result()->fetch_assoc();
}

$ye = $showMilk ? getAmt($con, $yest, 'EVENING') : [];
$tm = $showMilk ? getAmt($con, $today, 'MORNING') : [];

$totalCost = ($ye['total_cost_amt'] ?? 0) + ($tm['total_cost_amt'] ?? 0);
$totalMrp  = ($ye['total_mrp_amt']  ?? 0) + ($tm['total_mrp_amt']  ?? 0);
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

        <!-- BRANCH -->
        <form method="get" class="mb-3">
            <select name="branch" class="form-select w-25" onchange="this.form.submit()">
                <option value="SHASHI-ND" <?= $branch == 'SHASHI-ND' ? 'selected' : '' ?>>SHASHI-ND</option>
                <option value="SHIVI-ND" <?= $branch == 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
            </select>
        </form>

        <?php if ($showMilk): ?>

            <!-- DATE FILTER -->
            <form method="get" class="row g-2 mb-3">
                <input type="hidden" name="branch" value="SHIVI-ND">
                <div class="col-md-3">
                    <input type="date" name="from_date" value="<?= $from_date ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" value="<?= $to_date ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">View</button>
                </div>
            </form>

            <!-- SESSION LIST -->
            <?php if ($sessions): ?>
                <table class="table table-sm table-bordered mb-4">
                    <tr class="table-secondary">
                        <th>Date</th>
                        <th>Session</th>
                        <th>Cost</th>
                        <th>MRP</th>
                        <th>Profit</th>
                        <th>Edit</th>
                    </tr>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($s['milk_date'])) ?></td>
                            <td><?= $s['session'] ?></td>
                            <td><?= number_format($s['total_cost_amt'], 2) ?></td>
                            <td><?= number_format($s['total_mrp_amt'], 2) ?></td>
                            <td><?= number_format($s['net_profit'], 2) ?></td>
                            <td>
                                <a class="btn btn-sm btn-warning"
                                    href="?branch=SHIVI-ND&edit_date=<?= $s['milk_date'] ?>&edit_session=<?= $s['session'] ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <!-- ENTRY / EDIT FORM -->
            <form method="post">
                <?php if ($editSession): ?>
                    <input type="hidden" name="session_id" value="<?= $editSession['session_id'] ?>">
                <?php endif; ?>

                <div class="row mb-2">
                    <div class="col-md-3">
                        <input type="date" name="milk_date" class="form-control"
                            value="<?= $editSession['milk_date'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <select name="session" class="form-select" required>
                            <option value="MORNING" <?= ($editSession['session'] ?? '') == 'MORNING' ? 'selected' : '' ?>>MORNING</option>
                            <option value="EVENING" <?= ($editSession['session'] ?? '') == 'EVENING' ? 'selected' : '' ?>>EVENING</option>
                        </select>
                    </div>
                </div>

                <table class="table table-bordered">
                    <tr class="table-dark">
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>MRP</th>
                    </tr>
                    <?php foreach ($milk_items as $code => $m): ?>
                        <tr>
                            <td><?= $m['name'] ?></td>
                            <td>
                                <input type="number" step="0.01"
                                    name="qty[<?= $code ?>]"
                                    value="<?= $editQty[$code] ?? '' ?>"
                                    class="form-control">
                            </td>
                            <td><?= $m['cost'] ?></td>
                            <td><?= $m['mrp'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <button class="btn btn-success">
                    <?= $editSession ? 'UPDATE SESSION' : 'SAVE SESSION' ?>
                </button>
            </form>

            <hr>

            <div class="card p-3">
                <h5>1 Day Payment (Yesterday Evening + Today Morning)</h5>
                <p>Total Cost : <b><?= number_format($totalCost, 2) ?></b></p>
                <p>Total MRP : <b><?= number_format($totalMrp, 2) ?></b></p>
                <p>Net Profit : <b><?= number_format($netProfit, 2) ?></b></p>
            </div>

        <?php endif; ?>

    </div>
</body>

</html>