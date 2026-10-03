<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(50));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Save token in DB (ensure column names match your DB)
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            // Create reset link
            $resetLink = "http://localhost/LiwanagCafe/reset_password.php?token=$token";

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'MAIL_USERNAME'; // Your Gmail
                $mail->Password   = 'MAIL_PASSWORD';      // 16-char app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('MAIL_USERNAME@gmail.com', 'Liwanag Cafe');
                $mail->addAddress($email, $user['name'] ?? 'Customer');

                // 
                $mail->SMTPDebug = 0;

                // 
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - Liwanag Cafe';
                $mail->Body = "
                    <div style='font-family:Arial,sans-serif;padding:20px;background-color:#fff3cd;border:1px solid #ffeeba;border-radius:10px;max-width:500px;margin:auto'>
                        <h2 style='color:#856404;text-align:center;'>Liwanag Cafe Password Reset</h2>
                        <p>Hello <strong>{$user['name']}</strong>,</p>
                        <p>We received a request to reset your password. Click the button below to reset it:</p>
                        <div style='text-align:center;margin:20px 0'>
                            <a href='$resetLink' style='background-color:#ffca28;color:#000;padding:10px 20px;border-radius:5px;text-decoration:none;font-weight:bold;'>Reset Password</a>
                        </div>
                        <p>If you did not request this, just ignore this email.</p>
                        <p style='font-size:12px;color:#666;'>This link will expire in 1 hour.</p>
                    </div>
                ";

                $mail->send();
                $message = "<div class='bg-green-100 text-green-800 p-3 rounded mt-4'>A reset link has been sent to your email.</div>";
            } catch (Exception $e) {
                $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4'>
        Unable to send the reset email. Please try again later.
    </div>";
            }
        } else {
            $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4'>No account found with that email.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4'>
        Something went wrong. Please try again later.
    </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Liwanag Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-image: url("assets/frontpic.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen bg-black bg-opacity-50">
    <form method="POST" class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-center text-yellow-700 mb-4">Forgot Password</h1>
        <p class="text-sm text-gray-600 text-center mb-6">Enter your email and we'll send you a link to reset your password.</p>

        <label class="block font-semibold mb-2">Email Address</label>
        <input type="email" name="email" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="example@email.com">

        <button type="submit" class="w-full mt-4 bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded">Send Reset Link</button>

        <?= $message ?>

        <p class="mt-6 text-center text-sm text-gray-700">
            Remember your password?
            <a href="login.php" class="text-blue-600 hover:underline font-semibold">Back to Login</a>
        </p>
    </form>
</body>

</html>