<?php
require_once __DIR__ . '/../includes/config.php';
include "../includes/header.php";

$role = strtolower($_SESSION['role_name'] ?? 'guest');
$branch_id = $_SESSION['branch_id'] ?? '';

if (!in_array($role, ['admin', 'manager'])) {
    die("Access denied");
}

// Branch list
$branches = [];
if ($role === 'admin') {
    $res = $con->query("SELECT branch_id FROM m_branch_sync_config ORDER BY branch_id");
    while ($r = $res->fetch_assoc()) $branches[] = $r['branch_id'];
} elseif ($role === 'manager' && $branch_id) {
    $branches[] = $branch_id;
}

// Filters
$selected_branch = $_GET['branch_id'] ?? ($branches[0] ?? '');
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Summary query
$sql = "
SELECT 
    e.branch_id,
    a.att_date,
    SUM(CASE WHEN COALESCE(a.admin_status, a.system_status) = 'Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN COALESCE(a.admin_status, a.system_status) = 'Half Day' THEN 1 ELSE 0 END) AS halfday_count,
    SUM(CASE WHEN COALESCE(a.admin_status, a.system_status) = 'Absent' THEN 1 ELSE 0 END) AS absent_count
FROM attendance a
JOIN employee_master e ON e.emp_id = a.emp_id
WHERE e.is_active = 1
";
if ($role === 'manager') $sql .= " AND e.branch_id = '".$con->real_escape_string($branch_id)."'";
if (!empty($selected_branch)) $sql .= " AND e.branch_id = '".$con->real_escape_string($selected_branch)."'";
$sql .= " AND a.att_date BETWEEN '".$con->real_escape_string($from_date)."' AND '".$con->real_escape_string($to_date)."'";
$sql .= " GROUP BY e.branch_id, a.att_date ORDER BY a.att_date DESC";

$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Attendance Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container-fluid">
    <h3 class="mb-4">📅 Daily Attendance Summary</h3>

    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label>Branch</label>
            <select name="branch_id" class="form-select">
                <option value="">All</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= ($selected_branch==$b)?'selected':'' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="form-control">
        </div>
        <div class="col-md-3 align-self-end">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    <table id="summaryTable" class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Present</th>
                <th>Half Day</th>
                <th>Absent</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while($row=$result->fetch_assoc()): 
                $total = $row['present_count'] + $row['halfday_count'] + $row['absent_count'];
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['att_date']) ?></td>
                <td><?= htmlspecialchars($row['branch_id']) ?></td>
                <td class="text-success fw-bold"><?= $row['present_count'] ?></td>
                <td class="text-warning fw-bold"><?= $row['halfday_count'] ?></td>
                <td class="text-danger fw-bold"><?= $row['absent_count'] ?></td>
                <td class="fw-bold"><?= $total ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(document).ready(function(){
    $('#summaryTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['excelHtml5', 'csvHtml5'],
        pageLength: 25
    });
});
</script>
</body>
</html>
