<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch Orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Order status summary
$statusCounts = array_fill_keys(['pending', 'confirmed', 'preparing', 'completed', 'cancel'], 0);
$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM orders GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = strtolower($row['status']);
    if (isset($statusCounts[$key])) $statusCounts[$key] = $row['count'];
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['order_id']]);
    header("Location: admin_dashboard.php");
    exit;
}

// Handle reservation status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id'], $_POST['res_status'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['res_status'], $_POST['res_id']]);
    header("Location: admin_dashboard.php");
    exit;
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $status = $_POST['status'];
        $available = $_POST['available'];
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/' . $image);

        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, image, status, available, sold) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$name, $category, $price, $image, $status, $available]);
        header("Location: admin_dashboard.php");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $status = $_POST['status'];
        $available = $_POST['available'];

        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], 'assets/' . $image);
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, image=?, status=?, available=? WHERE id=?");
            $stmt->execute([$name, $category, $price, $image, $status, $available, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, status=?, available=? WHERE id=?");
            $stmt->execute([$name, $category, $price, $status, $available, $id]);
        }
        header("Location: admin_dashboard.php");
        exit;
    }

    if (isset($_POST['delete_product'])) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['delete_product']]);
        header("Location: admin_dashboard.php");
        exit;
    }
}
?>

<?php include 'admin_header.php'; ?>
<link rel="stylesheet" href="admin.css">

<!-- Tabs -->
<div class="tab-menu">
    <button onclick="showTab('dashboard')" id="tab-dashboard" class="active-tab">Dashboard</button>
    <button onclick="showTab('orders')" id="tab-orders">Orders</button>
    <button onclick="showTab('products')" id="tab-products">Manage Products</button>
    <button onclick="showTab('reservations')" id="tab-reservations">Reservations</button>
</div>

<!-- Dashboard Tab -->
<div id="dashboard-tab" class="tab-content">
    <ul class="box-info">
        <li><i class='bx bx-loader-circle'></i><span class="text">
                <h3><?= $statusCounts['pending'] ?></h3>
                <p>Pending</p>
            </span></li>
        <li><i class='bx bx-check-shield'></i><span class="text">
                <h3><?= $statusCounts['confirmed'] ?></h3>
                <p>Confirmed</p>
            </span></li>
        <li><i class='bx bx-time-five'></i><span class="text">
                <h3><?= $statusCounts['preparing'] ?></h3>
                <p>Preparing</p>
            </span></li>
        <li><i class='bx bx-check-circle'></i><span class="text">
                <h3><?= $statusCounts['completed'] ?></h3>
                <p>Completed</p>
            </span></li>
        <li><i class='bx bx-x-circle'></i><span class="text">
                <h3><?= $statusCounts['cancel'] ?></h3>
                <p>Cancelled</p>
            </span></li>
    </ul>
</div>

<!-- Orders Tab -->
<div id="orders-tab" class="tab-content" style="display: none;">

</div>

<!-- Products Tab -->
<div id="products-tab" class="tab-content" style="display: none;">

</div>

<!-- Reservations Tab -->
<div id="reservations-tab" class="tab-content" style="display: none;">
    <?php
    $resStmt = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC");
    $reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php include 'reservations_tab_content.php'; ?>
</div>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.getElementById(tabId + '-tab').style.display = 'block';
        document.querySelectorAll('.tab-menu button').forEach(btn => btn.classList.remove('active-tab'));
        document.getElementById('tab-' + tabId).classList.add('active-tab');
    }
</script>

<?php include 'admin_footer.php'; ?>