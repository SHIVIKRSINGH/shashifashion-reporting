<?php
require_once __DIR__ . '/config.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$role_id   = $_SESSION['role_id'] ?? '';
$role_name = $_SESSION['role_name'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? 0;

// Fetch menu items based on role
$sql = "SELECT m.*
        FROM m_menu m
        JOIN m_permission p ON m.menu_id = p.menu_id
        WHERE p.role_id = ?
        ORDER BY m.parent_id, m.sort_order";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $role_id);
$stmt->execute();
$menus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build menu hierarchy
$tree = [];
foreach ($menus as $m) {
    if (is_null($m['parent_id'])) {
        $tree[$m['menu_id']] = $m + ['children' => []];
    }
}
foreach ($menus as $m) {
    if (!is_null($m['parent_id']) && isset($tree[$m['parent_id']])) {
        $tree[$m['parent_id']]['children'][] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ShashiFashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #343a40;
            color: white;
            padding-top: 20px;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
        }

        .sidebar a:hover {
            background: #495057;
            color: white;
        }

        .sidebar .menu-title {
            font-size: 14px;
            text-transform: uppercase;
            padding: 10px 20px;
            color: #ced4da;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            background: #f8f9fa;
        }

        .topbar {
            background: #fff;
            padding: 10px 20px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <a class="navbar-brand text-white fw-bold mb-4 d-block" href="dashboard.php">Shashi Fashion</a>
        <?php foreach ($tree as $parent): ?>
            <?php if (empty($parent['children'])): ?>
                <a href="<?= htmlspecialchars($parent['url']) ?>"><i class="<?= $parent['icon'] ?>"></i> <?= $parent['name'] ?></a>
            <?php else: ?>
                <div class="menu-title"><i class="<?= $parent['icon'] ?>"></i> <?= $parent['name'] ?></div>
                <?php foreach ($parent['children'] as $child): ?>
                    <a class="ms-3" href="<?= htmlspecialchars($child['url']) ?>"><i class="<?= $child['icon'] ?>"></i> <?= $child['name'] ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <h5>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h5>
            <a class="nav-link text-danger" href="logout.php">Logout</a>
        </div>