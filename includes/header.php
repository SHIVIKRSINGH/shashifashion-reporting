<?php
require_once __DIR__ . '/../config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) session_start();

// Fetch user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch menu items allowed for this user
$sql = "SELECT m.*
        FROM m_menus m
        JOIN m_permissions p ON m.id = p.menu_id
        WHERE p.user_id = ?
        ORDER BY m.parent_id, m.sort_order";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$menus = [];
while ($row = $result->fetch_assoc()) {
    $menus[] = $row;
}

// Organize menus: parent → children
$menu_tree = [];
foreach ($menus as $menu) {
    if ($menu['parent_id'] == NULL) {
        $menu_tree[$menu['id']] = $menu;
        $menu_tree[$menu['id']]['children'] = [];
    }
}
foreach ($menus as $menu) {
    if ($menu['parent_id']) {
        $menu_tree[$menu['parent_id']]['children'][] = $menu;
    }
}
?>
<!-- Modern Bootstrap-based Layout -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ShashiFashion Dashboard</title>
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
        <h4 class="text-center">🧵 ShashiFashion</h4>
        <?php foreach ($menu_tree as $menu): ?>
            <?php if (empty($menu['children'])): ?>
                <a href="<?= $menu['url'] ?>"><i class="<?= $menu['icon'] ?>"></i> <?= $menu['name'] ?></a>
            <?php else: ?>
                <div class="menu-title"><i class="<?= $menu['icon'] ?>"></i> <?= $menu['name'] ?></div>
                <?php foreach ($menu['children'] as $child): ?>
                    <a class="ms-3" href="<?= $child['url'] ?>"><i class="<?= $child['icon'] ?>"></i> <?= $child['name'] ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <h5>Welcome, <?= $_SESSION['name'] ?? 'User' ?></h5>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>