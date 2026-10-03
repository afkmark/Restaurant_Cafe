<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['pending' => 0]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status = 'pending'
");
$stmt->execute();

echo json_encode([
    'pending' => (int) $stmt->fetchColumn()
]);
