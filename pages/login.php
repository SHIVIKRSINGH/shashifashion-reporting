<?php
require_once "../includes/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM m_users WHERE username='$username' AND password='$password'";
    $res = $con->query($sql);

    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!-- HTML same as before -->
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login - Shashi Fashion</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        body {
            background: #f4f6f9;
        }

        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px #ccc;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h4 class="text-center">Shashi Fashion Login</h4>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required />
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required />
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>

</html>
<link rel="stylesheet" href="../assets/bootstrap.min.css">