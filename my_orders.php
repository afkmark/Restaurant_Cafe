<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch orders
$orderStmt = $pdo->prepare("
    SELECT id, total_amount, payment_method, status, receipt, created_at, pickup_code
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$orderStmt->execute([$user_id]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT order_id, product_name, quantity
    FROM order_items
    WHERE order_id = ?
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>|My Orders</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #fff;
            color: #333;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        header h1 {
            color: #b28b32;
            font-family: 'Playfair Display', serif;
            margin: 0;
            font-size: 28px;
        }

        .home-icon {
            color: #b28b32;
            font-size: 24px;
            text-decoration: none;
        }

        .orders-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px 30px;
        }

        .order-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            padding: 20px;
            flex: 1 1 300px;
            position: relative;
        }

        .order-info {
            font-size: 14px;
            line-height: 1.6;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 10px;
            font-weight: 600;
            color: #fff;
            display: inline-block;
            font-size: 13px;
        }

        .pending {
            background: #d4a017;
        }

        .confirmed {
            background: #f8c951;
        }

        .preparing {
            background: #007bff;
        }

        .ready {
            background: #28a745;
        }

        .completed {
            background: #6c757d;
        }

        .cancelled {
            background: #dc3545;
        }

        .pickup-ready {
            margin-top: 10px;
            padding: 10px;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            border-radius: 8px;
            text-align: center;
        }

        .receipt-btn {
            margin-top: 12px;
            background: #b28b32;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <header>
        <h1>| My Orders</h1>
        <a href="home.php" class="home-icon"><i class="fi fi-rr-home"></i></a>
    </header>

    <div class="orders-container">

        <?php if (empty($orders)): ?>
            <p style="width:100%; text-align:center;">You have no orders yet.</p>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $status = strtolower($order['status']);
            $statusClass = match ($status) {
                'pending' => 'pending',        // yellow
                'confirmed' => 'confirmed',    // gold
                'preparing' => 'preparing',    // blue
                'ready' => 'ready',            // green
                'completed' => 'completed',    // gray
                'cancelled' => 'cancelled',    // red
                default => 'cancelled'
            };

            $itemStmt->execute([$order['id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="order-card">
                <h2>Order #<?= $order['id'] ?></h2>

                <div class="order-info">
                    <div><strong>Date:</strong> <?= date('F d, Y g:i a', strtotime($order['created_at'])) ?></div>
                    <div><strong>Payment:</strong> <?= htmlspecialchars(ucfirst($order['payment_method'])) ?></div>

                    <div style="margin-top:6px;">
                        <strong>Status:</strong>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= htmlspecialchars(ucfirst($order['status'])) ?>
                        </span>
                    </div>

                    <!-- Pickup code for cash orders -->
                    <?php if ($order['payment_method'] === 'cash'): ?>
                        <div style="margin-top:6px;">
                            <strong>Pickup Code:</strong> <?= htmlspecialchars($order['pickup_code'] ?? 'N/A') ?>
                        </div>
                    <?php endif; ?>

                    <!-- Items -->
                    <div style="margin-top:10px;">
                        <strong>Items Ordered:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <?php foreach ($items as $item): ?>
                                <li><?= htmlspecialchars($item['product_name']) ?> × <?= (int)$item['quantity'] ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div style="margin-top:8px;"><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2) ?></div>

                    <?php if ($status === 'ready'): ?>
                        <div class="pickup-ready">✅ READY TO PICK UP</div>
                    <?php endif; ?>

                    <?php if ($order['payment_method'] === 'gcash' && !empty($order['receipt'])): ?>
                        <button class="receipt-btn"
                            onclick="window.open('receipts/<?= htmlspecialchars($order['receipt']) ?>','_blank')">
                            View GCash Receipt
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

</body>

</html>