<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Collect reservation data from your form
$name    = trim($_POST['name']);
$date    = $_POST['date'];
$time    = $_POST['time'];
$guests  = (int)$_POST['guests'];
$message = $_POST['message'] ?? null;

// Insert into DB
$stmt = $pdo->prepare("
    INSERT INTO reservations
    (user_id, name, date, time, guests, message, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
");

$stmt->execute([
    $user_id,
    $name,
    $date,
    $time,
    $guests,
    $message
]);

header("Location: my_reservations.php");
exit;
