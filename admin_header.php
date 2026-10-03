<?php
if (!isset($_SESSION)) session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Set default profile picture
$defaultProfilePic = "https://cdn-icons-png.flaticon.com/512/149/149071.png";
$profilePic = $_SESSION['profile_pic'] ?? $defaultProfilePic;

// Count pending orders
$pendingOrdersCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Count pending reservations
$pendingReservationsCount = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();

// Total notification count
$totalNotifications = $pendingOrdersCount + $pendingReservationsCount;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Liwanag Cafe | Admin</title>
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="admin.css" />
</head>

<body class="adminpanel">
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class="bx bx-restaurant"></i>
            <span class="text">Liwanag Cafe</span>
        </a>
        <ul class="side-menu top">
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">
                <a href="admin_dashboard.php"><i class="bx bxs-dashboard"></i><span class="text">Dashboard</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                <a href="orders.php"><i class="bx bxs-shopping-bag-alt"></i><span class="text">Orders</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                <a href="analytics.php"><i class="bx bxs-doughnut-chart"></i><span class="text">Analytics</span></a>
            </li>
        </ul>
        <ul class="side-menu">

            <li><a href="logout.php" class="logout"><i class="bx bxs-log-out-circle"></i><span class="text">Logout</span></a></li>
        </ul>
    </section>

    <!-- CONTENT -->
    <section id="content">
        <nav>
            <i class="bx bx-menu"></i>
            <div class="nav-spacer"></div>
            <input type="checkbox" id="switch-mode" hidden />
            <label for="switch-mode" class="switch-mode"></label>
            <a href="admin_dashboard.php#tab-orders" class="notification">
                <i class="bx bxs-bell"></i>
                <?php if ($totalNotifications > 0): ?>
                    <span class="num"><?= $totalNotifications ?></span>
                <?php endif; ?>
            </a>
            <div class="profile">
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" title="Admin Profile" />
            </div>
        </nav>

        <main>