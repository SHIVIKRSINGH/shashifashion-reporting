<?php
require_once "../includes/config.php";

/* ===============================
   BASIC CONTROLS
================================ */
$branch = $_GET['branch'] ?? 'SHASHI-ND';
$showMilk = ($branch === 'SHIVI-ND');

$mode = $_GET['mode'] ?? 'view'; // view | add | edit | delete
$session_id = $_GET['session_id'] ?? null;

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

/* ===============================
   HARDCODED ITEMS
================================ */
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

/* ===============================
   LOAD SESSION FOR EDIT
================================ */
$edit = null;
$editQty = [];

if ($showMilk && $mode === 'edit' && $session_id) {
    $stmt = $con->prepare("
        SELECT * FROM milk_session_hdr
        WHERE session_id=? AND branch_id='SHIVI-ND'
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();

    if ($edit) {
        $stmt = $con->prepare("
            SELECT item_code, qty
            FROM milk_session_det
            WHERE session_id=?
        ");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $editQty[$r['item_code']] = $r['qty'];
        }
    }
}

/* ===============================
   SAVE (ADD / UPDATE)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showMilk) {

    $milk_date = $_POST['milk_date'];
    $session   = $_POST['session'];
    $sid       = $_POST['session_id'] ?? null;

    $totCost = $totMrp = 0;

    foreach ($_POST['qty'] as $code => $qty) {
        if ($qty > 0) {
            $totCost += $qty * $milk_items[$code]['cost'];
            $totMrp  += $qty * $milk_items[$code]['mrp'];
        }
    }
    $profit = $totMrp - $totCost;

    $con->begin_transaction();

    try {
        if ($sid) {
            // UPDATE
            $stmt = $con->prepare("
                UPDATE milk_session_hdr
                SET milk_date=?, session=?, total_cost_amt=?, total_mrp_amt=?, net_profit=?
                WHERE session_id=?
            ");
            $stmt->bind_param("ssdddi", $milk_date, $session, $totCost, $totMrp, $profit, $sid);
            $stmt->execute();

            $con->query("DELETE FROM milk_session_det WHERE session_id=$sid");
        } else {
            // INSERT
            $stmt = $con->prepare("
                INSERT INTO milk_session_hdr
                (branch_id, milk_date, session, total_cost_amt, total_mrp_amt, net_profit)
                VALUES ('SHIVI-ND',?,?,?,?,?)
            ");
            $stmt->bind_param("ssddd", $milk_date, $session, $totCost, $totMrp, $profit);
            $stmt->execute();
            $sid = $stmt->insert_id;
        }

        foreach ($_POST['qty'] as $code => $qty) {
            if ($qty > 0) {
                $c = $milk_items[$code]['cost'];
                $m = $milk_items[$code]['mrp'];
                $ca = $qty * $c;
                $ma = $qty * $m;

                $stmt = $con->prepare("
                    INSERT INTO milk_session_det
                    (session_id, item_code, qty, cost_rate, mrp_rate, cost_amt, mrp_amt)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $stmt->bind_param("isidddd", $sid, $code, $qty, $c, $m, $ca, $ma);
                $stmt->execute();
            }
        }

        $con->commit();
    } catch (Exception $e) {
        $con->rollback();
        die("Error saving session");
    }

    // 🔁 ALWAYS GO BACK TO VIEW
    header("Location: r_milk_order_book.php?branch=SHIVI-ND");
    exit;
}

/* ===============================
   DELETE
================================ */
if ($showMilk && $mode === 'delete' && $session_id) {
    $stmt = $con->prepare("
        DELETE FROM milk_session_hdr
        WHERE session_id=? AND branch_id='SHIVI-ND'
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();

    header("Location: r_milk_order_book.php?branch=SHIVI-ND");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Milk Order Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-4">

        <!-- BRANCH SELECT -->
        <form method="get" class="mb-3">
            <select name="branch" class="form-select w-25" onchange="this.form.submit()">
                <option value="SHASHI-ND">SHASHI-ND</option>
                <option value="SHIVI-ND" <?= $branch === 'SHIVI-ND' ? 'selected' : '' ?>>SHIVI-ND</option>
            </select>
        </form>

        <?php if ($showMilk): ?>

            <!-- FILTER -->
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

            <?php if ($mode === 'view'): ?>

                <a href="?branch=SHIVI-ND&mode=add" class="btn btn-success mb-3">➕ Add New Session</a>

                <table class="table table-bordered table-sm">
                    <tr class="table-secondary">
                        <th>Date</th>
                        <th>Session</th>
                        <th>Cost</th>
                        <th>MRP</th>
                        <th>Profit</th>
                        <th>Action</th>
                    </tr>

                    <?php
                    $stmt = $con->prepare("
    SELECT *
    FROM milk_session_hdr
    WHERE branch_id='SHIVI-ND'
    AND milk_date BETWEEN ? AND ?
    ORDER BY milk_date DESC, FIELD(session,'EVENING','MORNING')
");
                    $stmt->bind_param("ss", $from_date, $to_date);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($r['milk_date'])) ?></td>
                            <td><?= $r['session'] ?></td>
                            <td><?= number_format($r['total_cost_amt'], 2) ?></td>
                            <td><?= number_format($r['total_mrp_amt'], 2) ?></td>
                            <td><?= number_format($r['net_profit'], 2) ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm"
                                    href="?branch=SHIVI-ND&mode=edit&session_id=<?= $r['session_id'] ?>">Edit</a>
                                <a class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this session?')"
                                    href="?branch=SHIVI-ND&mode=delete&session_id=<?= $r['session_id'] ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>

            <?php endif; ?>

            <?php if (in_array($mode, ['add', 'edit'])): ?>

                <form method="post">
                    <input type="hidden" name="session_id" value="<?= $edit['session_id'] ?? '' ?>">

                    <div class="row mb-2">
                        <div class="col-md-3">
                            <input type="date" name="milk_date" class="form-control"
                                value="<?= $edit['milk_date'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <select name="session" class="form-select" required>
                                <option value="MORNING" <?= ($edit['session'] ?? '') === 'MORNING' ? 'selected' : '' ?>>MORNING</option>
                                <option value="EVENING" <?= ($edit['session'] ?? '') === 'EVENING' ? 'selected' : '' ?>>EVENING</option>
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
                                <td><input type="number" step="0.01" name="qty[<?= $code ?>]"
                                        value="<?= $editQty[$code] ?? '' ?>" class="form-control"></td>
                                <td><?= $m['cost'] ?></td>
                                <td><?= $m['mrp'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <button class="btn btn-success"><?= $mode === 'edit' ? 'Update Session' : 'Save Session' ?></button>
                    <a href="?branch=SHIVI-ND" class="btn btn-secondary">Cancel</a>
                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</body>

</html>