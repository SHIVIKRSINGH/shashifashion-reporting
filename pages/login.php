<?php
require_once "../includes/config.php";
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = md5($_POST['password'] ?? '');

    $stmt = $con->prepare("
        SELECT u.user_id, u.username, u.role_id, u.branch_id, r.name AS role_name
        FROM m_user u
        JOIN m_role r ON r.user_id = u.role_id
        WHERE u.username = ? AND u.password = ?
    ");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['role_id']       = $user['role_id'];
        $_SESSION['role_name']  = $user['role_name'];

        // For Manager: fix branch; Admin works with central default
        if ($user['role_name'] === 'Manager') {
            $_SESSION['branch_id'] = $user['branch_id'];
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Shashi Fashion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap (optional, for consistent form controls) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body,
        html {
            height: 100%;
            font-family: 'Quicksand', sans-serif;
            background: #f9f6f1;
            margin: 0;
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
            overflow: hidden;
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
            z-index: 1;
        }

        .login-card h2 {
            margin-top: 120px;
            font-weight: 600;
            color: #333;
            position: relative;
            z-index: 2;
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        label {
            font-size: 14px;
            color: #555;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
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
            transition: 0.3s;
        }

        .signin-btn:hover {
            background: #8a4ec0;
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }

        .links a {
            text-decoration: none;
            color: #a164dd;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 20px;
            z-index: 2;
            position: relative;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-card">
            <div class="top-art"></div>
            <br></br>
            <h2>Welcome</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" required placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="signin-btn">&#10148;</button>
                </div>

                <div class="links">
                    <a href="#">Sign up</a>
                    <a href="#">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>