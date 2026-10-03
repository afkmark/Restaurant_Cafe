<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

/* ===============================
   1. BASIC SECURITY CHECKS
================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

/* ===============================
   2. GET & VALIDATE DATA
================================ */

$cart    = json_decode($_POST['cart'] ?? '[]', true);
$total   = isset($_POST['total']) ? (float) $_POST['total'] : null;
$payment = $_POST['payment'] ?? null;

if (!is_array($cart) || empty($cart) || !$total || !in_array($payment, ['cash', 'gcash'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order data']);
    exit;
}

/* ===============================
   3. ORDER RULES (RESTAURANT LOGIC)
================================ */

// Order status
$status = 'pending';

// Payment status
$payment_status = ($payment === 'gcash') ? 'unverified' : 'paid';

// Pickup code only for CASH
$pickupCode = null;
if ($payment === 'cash') {
    $pickupCode = 'LH-' . strtoupper(substr(uniqid(), -6));
}

/* ===============================
   4. HANDLE GCASH RECEIPT
================================ */

$receiptFilename = null;

if ($payment === 'gcash') {

    if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'GCash receipt is required']);
        exit;
    }

    $uploadDir = __DIR__ . '/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid receipt file type']);
        exit;
    }

    $receiptFilename = uniqid('receipt_', true) . '.' . $ext;

    if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadDir . $receiptFilename)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload receipt']);
        exit;
    }
}

/* ===============================
   5. SAVE ORDER (TRANSACTION)
================================ */

try {
    $pdo->beginTransaction();

    /* ---- Insert order ---- */
    $orderStmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            total_amount,
            payment_method,
            payment_status,
            receipt,
            pickup_code,
            status,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $orderStmt->execute([
        $user_id,
        $total,
        $payment,
        $payment_status,
        $receiptFilename,
        $pickupCode,
        $status
    ]);

    $orderId = $pdo->lastInsertId();

    /* ---- Insert order items ---- */
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_name,
            quantity,
            price
        )
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart as $item) {

        if (!isset($item['name'], $item['qty'], $item['price'])) {
            throw new Exception('Invalid cart item data');
        }

        $qty   = (int) $item['qty'];
        $price = (float) $item['price'];

        if ($qty <= 0 || $price <= 0) {
            throw new Exception('Invalid quantity or price');
        }

        $itemStmt->execute([
            $orderId,
            $item['name'],
            $qty,
            $price
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success'        => true,
        'message'        => ($payment === 'gcash')
            ? 'Order placed! Waiting for payment verification.'
            : 'Order placed successfully!',
        'order_id'       => $orderId,
        'pickup_code'    => $pickupCode,
        'payment_status' => $payment_status
    ]);
} catch (Exception $e) {

    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'error'   => 'Order failed',
        'message' => $e->getMessage()
    ]);
}
