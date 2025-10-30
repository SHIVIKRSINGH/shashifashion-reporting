<?php
require_once "../includes/config.php";

$error = '';
$login_type = $_POST['login_type'] ?? 'admin'; // default admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password'])); // MD5 hash

    if ($login_type === 'employee') {
        // ✅ Employee Login (from employee_master)
        $stmt = $con->prepare("
            SELECT emp_id, emp_name, username, password, branch_id, role, is_active
            FROM employee_master
            WHERE username = ? AND password = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $emp = $res->fetch_assoc();
            session_regenerate_id(true);

            $_SESSION['emp_id'] = $emp['emp_id'];
            $_SESSION['emp_name'] = $emp['emp_name'];
            $_SESSION['branch_id'] = $emp['branch_id'];
            $_SESSION['role_name'] = $emp['role']; // 'Employee' or 'Admin'

            // Redirect to attendance dashboard
            header("Location: attendance_punch.php");
            exit;
        } else {
            $error = "❌ Invalid employee credentials.";
        }
    } else {
        // ✅ Admin / Manager Login (existing)
        $stmt = $con->prepare("
            SELECT u.user_id, u.username, u.password, u.role_id, u.branch_id, r.role_name
            FROM m_user u
            JOIN m_role r ON r.role_id = u.role_id
            WHERE u.username = ? AND u.password = ? AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['branch_id'] = $user['branch_id'];

            // Manager: Load their branch DB config
            if (!empty($user['branch_id']) && strtolower($user['role_name']) === 'manager') {
                $bstmt = $con->prepare("
                    SELECT db_host, db_user, db_password, db_name
                    FROM m_branch_sync_config
                    WHERE branch_id = ?
                ");
                $bstmt->bind_param("s", $user['branch_id']);
                $bstmt->execute();
                $branchRes = $bstmt->get_result();

                if ($branchRes->num_rows === 1) {
                    $branchDb = $branchRes->fetch_assoc();
                    $_SESSION['branch_db'] = [
                        'host'     => $branchDb['db_host'],
                        'user'     => $branchDb['db_user'],
                        'password' => $branchDb['db_password'],
                        'name'     => $branchDb['db_name']
                    ];
                }
            }

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "❌ Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Shashi Fashion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f9f6f1;
        }

        .container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            width: 360px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 40px 30px 60px;
            position: relative;
        }

        .top-art {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 170px;
            background: linear-gradient(135deg, #da5b79, #a164dd, #f2c94c);
            clip-path: ellipse(140% 100% at 50% 0%);
        }

        .login-card h2 {
            margin-top: 120px;
            font-weight: 600;
            color: #333;
            text-align: left;
            position: relative;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }

        input:focus {
            border-color: #a164dd;
        }

        .signin-btn {
            background: #a164dd;
            color: white;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .signin-btn:hover {
            background: #8a4ec0;
        }

        .toggle-links {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            font-size: 14px;
        }

        .toggle-links button {
            background: none;
            border: none;
            color: #a164dd;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-card">
            <div class="top-art"></div>
            <h2>Welcome</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="login_type" id="login_type" value="admin">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="signin-btn">&#10148;</button>
                </div>

                <div class="toggle-links">
                    <button type="button" onclick="switchLogin('admin')">Admin/Manager</button> |
                    <button type="button" onclick="switchLogin('employee')">Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchLogin(type) {
            document.getElementById('login_type').value = type;
            alert('Switched to ' + type + ' login.');
        }
    </script>
</body>

</html>