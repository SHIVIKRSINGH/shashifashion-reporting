<?php
require_once __DIR__ . '/../includes/config.php';
include "../includes/header.php";
// Role and branch restriction
$role = strtolower($_SESSION['role_name'] ?? 'guest');
$branch_id = $_SESSION['branch_id'] ?? '';

if (!in_array($role, ['admin', 'manager'])) {
    die("Access denied");
}

// Fetch branch list (Admin sees all)
$branches = [];
if ($role === 'admin') {
    $res = $con->query("SELECT branch_id FROM m_branch_sync_config ORDER BY branch_id");
    while ($r = $res->fetch_assoc()) $branches[] = $r['branch_id'];
} elseif ($role === 'manager' && $branch_id) {
    $branches[] = $branch_id;
}

// Filter values
$selected_branch = $_GET['branch_id'] ?? ($branches[0] ?? '');
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Attendance query
$sql = "
SELECT 
    a.*, 
    e.emp_name, 
    e.emp_code, 
    e.branch_id AS emp_branch 
FROM attendance a 
JOIN employee_master e ON a.emp_id = e.emp_id 
WHERE e.is_active = 1
";
if ($role === 'manager') $sql .= " AND e.branch_id = '" . $con->real_escape_string($branch_id) . "'";
if (!empty($selected_branch)) $sql .= " AND e.branch_id = '" . $con->real_escape_string($selected_branch) . "'";
if (!empty($selected_date)) $sql .= " AND a.att_date = '" . $con->real_escape_string($selected_date) . "'";
$sql .= " ORDER BY a.att_date DESC, e.emp_name ASC";

$data = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">
    <div class="container-fluid">
        <h3 class="mb-4">🕒 Attendance Management</h3>

        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3">
                <label>Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= ($selected_branch == $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" class="form-control">
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>

        <table id="attendanceTable" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Emp Code</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Lunch Out</th>
                    <th>Lunch In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>System Status</th>
                    <th>System Remarks</th>
                    <th>Admin Status</th>
                    <th>Admin Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                while ($row = $data->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['emp_code']) ?></td>
                        <td><?= htmlspecialchars($row['emp_name']) ?></td>
                        <td><?= htmlspecialchars($row['emp_branch']) ?></td>
                        <td><?= htmlspecialchars($row['att_date']) ?></td>
                        <td><?= $row['clock_in'] ?></td>
                        <td><?= $row['lunch_out'] ?></td>
                        <td><?= $row['lunch_in'] ?></td>
                        <td><?= $row['clock_out'] ?></td>
                        <td><?= $row['total_hours'] ?></td>
                        <td><?= $row['system_status'] ?></td>
                        <td><?= $row['system_remarks'] ?></td>
                        <td><?= $row['admin_status'] ?></td>
                        <td><?= $row['admin_remarks'] ?></td>
                        <td>
                            <button
                                class="btn btn-sm btn-warning editBtn"
                                data-id="<?= $row['id'] ?>"
                                data-status="<?= htmlspecialchars($row['admin_status']) ?>"
                                data-remarks="<?= htmlspecialchars($row['admin_remarks']) ?>">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editForm" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="att_id">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="admin_status" id="admin_status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Present">Present</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Remarks</label>
                        <textarea name="admin_remarks" id="admin_remarks" class="form-control" placeholder="Enter remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
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
        $(document).ready(function() {
            const table = $('#attendanceTable').DataTable({
                dom: 'Bfrtip',
                buttons: ['excelHtml5', 'csvHtml5'],
                pageLength: 25
            });

            // Edit button
            $('.editBtn').click(function() {
                $('#att_id').val($(this).data('id'));
                $('#admin_status').val($(this).data('status'));
                $('#admin_remarks').val($(this).data('remarks'));
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });

            // Save edit
            $('#editForm').submit(function(e) {
                e.preventDefault();
                $.post('attendance_update.php', $(this).serialize(), function(res) {
                    if (res.success) {
                        alert('✅ Updated successfully');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + res.message);
                    }
                }, 'json');
            });
        });
    </script>
</body>

</html>