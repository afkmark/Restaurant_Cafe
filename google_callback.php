<?php
session_start();
require 'db.php'; // must set $pdo

$CLIENT_ID = '728060544644-q3i2jmek9a6juo3gqf3o2k1knorpi81o.apps.googleusercontent.com';

// Google posts id token in 'credential' when using GSI
$token = $_POST['credential'] ?? null;


if (!$token) {
    header('Location: login.php?google_error=1');
    exit;
}

// Verify token with Google endpoint
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
$resp = @file_get_contents($verifyUrl);
if (!$resp) {
    header('Location: login.php?google_error=2');
    exit;
}

$payload = json_decode($resp, true);

// Validate token
if (!isset($payload['aud']) || $payload['aud'] !== $CLIENT_ID) {
    header('Location: login.php?google_error=3');
    exit;
}
if (!($payload['email_verified'] ?? false)) {
    header('Location: login.php?google_error=4');
    exit;
}

$email = $payload['email'];
$name  = $payload['name'] ?? $email;
$picture = $payload['picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

try {
    // Check user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Create user without password (Google handles auth)
        $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, profile_pic, created_at) VALUES (?, ?, '', 'user', ?, NOW())");
        $ins->execute([$name, $email, $picture]);
        $userId = $pdo->lastInsertId();
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'user',
            'profile_pic' => $picture
        ];
    }

    // Set session

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['profile_pic'] = $user['profile_pic'] ?? $picture;

    // Redirect
    if ($_SESSION['role'] === 'admin') header('Location: admin_dashboard.php');
    else header('Location: home.php');
    exit;
} catch (Exception $e) {
    header('Location: login.php?google_error=5');
    exit;
}
