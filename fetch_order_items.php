<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    http_response_code(400);
    exit('Invalid order ID');
}

$stmt = $pdo->prepare("
    SELECT p.name, oi.quantity, oi.subtotal 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($items);
