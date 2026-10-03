<?php
require 'db.php';

$message = '';
$token = $_GET['token'] ?? '';

// Check token validity
if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4 text-center'>Invalid or expired reset link.</div>";
        $token = null;
    }
} else {
    $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4 text-center'>No reset token provided.</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'], $_POST['token'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($newPassword !== $confirmPassword) {
        $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4 text-center'>Passwords do not match.</div>";
    } elseif (strlen($newPassword) < 6) {
        $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4 text-center'>Password must be at least 6 characters.</div>";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            header("Location: login.php?reset_success=1");
            exit;
        } else {
            $message = "<div class='bg-red-100 text-red-800 p-3 rounded mt-4 text-center'>Invalid or expired reset link.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Reset Password - Liwanag Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-image: url("assets/frontpic.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .input-group {
            position: relative;
        }

        .eye-button {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.4rem;
            height: 1.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #a16207;
            /* default */
            transition: color .15s ease;
        }

        .eye-button.active {
            color: #facc15;
        }

        /* gold when visible */
        .eye-svg {
            width: 1.4rem;
            height: 1.4rem;
            display: block;
        }

        .eye-svg.hidden {
            display: none;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen bg-black bg-opacity-60">
    <form method="POST" class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-center text-yellow-700 mb-4">Reset Password</h1>
        <p class="text-sm text-gray-600 text-center mb-6">Enter your new password below.</p>

        <?= $message ?>

        <?php if ($token && empty($message)): ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label class="block font-semibold mb-2">New Password</label>
            <div class="input-group mb-4">
                <input type="password" id="new_password" name="new_password" required
                    class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-yellow-500"
                    placeholder="Enter new password">
                <button type="button" class="eye-button" aria-label="Toggle new password visibility" id="btnNew">
                    <!-- Eye (visible) -->
                    <svg class="eye-svg eye-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <!-- Eye Off (hidden) -->
                    <svg class="eye-svg eye-eye-off hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.94 17.94A10.05 10.05 0 0 1 12 19c-7 0-11-7-11-7"></path>
                        <path d="M1 1l22 22"></path>
                        <path d="M9.88 9.88a3 3 0 104.24 4.24"></path>
                    </svg>
                </button>
            </div>

            <label class="block font-semibold mb-2">Confirm Password</label>
            <div class="input-group">
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-yellow-500"
                    placeholder="Confirm new password">
                <button type="button" class="eye-button" aria-label="Toggle confirm password visibility" id="btnConfirm">
                    <!-- Eye -->
                    <svg class="eye-svg eye-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <!-- Eye Off -->
                    <svg class="eye-svg eye-eye-off hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.94 17.94A10.05 10.05 0 0 1 12 19c-7 0-11-7-11-7"></path>
                        <path d="M1 1l22 22"></path>
                        <path d="M9.88 9.88a3 3 0 104.24 4.24"></path>
                    </svg>
                </button>
            </div>

            <button type="submit"
                class="w-full mt-6 bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded transition duration-200">
                Update Password
            </button>
        <?php endif; ?>

        <p class="mt-6 text-center text-sm text-gray-700">
            Remembered your password?
            <a href="login.php" class="text-blue-600 hover:underline font-semibold">Back to Login</a>
        </p>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnNew = document.getElementById('btnNew');
            const newInput = document.getElementById('new_password');
            const btnConfirm = document.getElementById('btnConfirm');
            const confirmInput = document.getElementById('confirm_password');

            function toggleField(button, input) {
                const eye = button.querySelector('.eye-eye');
                const eyeOff = button.querySelector('.eye-eye-off');

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';

                // toggle which svg is shown
                eye.classList.toggle('hidden', !isHidden);
                eyeOff.classList.toggle('hidden', isHidden);

                // toggle active color
                button.classList.toggle('active', isHidden);
            }

            btnNew.addEventListener('click', () => toggleField(btnNew, newInput));
            btnConfirm.addEventListener('click', () => toggleField(btnConfirm, confirmInput));
        });
    </script>
</body>

</html>