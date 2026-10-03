<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/*
 |--------------------------------------------------
 | Fetch orders with user name (FIXED JOIN)
 |--------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT 
        orders.id,
        orders.total_amount,
        orders.payment_method,
        orders.status,
        orders.created_at,
        users.name
    FROM orders
    JOIN users ON orders.user_id = users.id
    ORDER BY orders.created_at DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'admin_header.php'; ?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>All Orders</h1>
        </div>
    </div>

    <div class="table-data">
        <div class="order">
            <div class="head">
                <h3>Order History</h3>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $status_class = match (strtolower($order['status'])) {
                                'approved', 'completed' => 'completed',
                                'confirmed' => 'confirmed',
                                'preparing' => 'preparing',
                                'rejected' => 'rejected',
                                'pending' => 'pending',
                                'cancelled' => 'cancelled',
                                default => ''
                            };
                            ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['name']) ?></td>
                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= ucfirst($order['payment_method']) ?></td>
                                <td>
                                    <span class="status <?= $status_class ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'admin_footer.php'; ?>