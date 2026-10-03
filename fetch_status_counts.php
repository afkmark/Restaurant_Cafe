<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$statuses = ['pending', 'confirmed', 'preparing', 'completed', 'cancelled'];
$data = array_fill_keys($statuses, 0);

$stmt = $pdo->query("
    SELECT status, COUNT(*) as total 
    FROM orders 
    GROUP BY status
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = strtolower($row['status']);
    if (in_array($key, ['cancel', 'rejected'])) {
        $key = 'cancelled';
    }
    if (isset($data[$key])) {
        $data[$key] = (int) $row['total'];
    }
}

echo json_encode($data);
