<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contact = trim($_POST['contact']); // can be email or phone
    $password = $_POST['password'];

    try {
        // Fetch user from DB by email or phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$contact, $contact]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['profile_pic'] = $user['profile_pic'] ?? "https://cdn-icons-png.flaticon.com/512/149/149071.png";

            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php");
            }
            exit;
        } else {
            $error = "Invalid email/phone or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Liwanag Cafe</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="assets/restaurantlogo.jpg" />
    <style>
        .bg {
            background-image: url("assets/frontpic.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body class="bg flex items-center justify-center min-h-screen bg-yellow-100">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 font-serif">Login</h1>

        <?php if (isset($_GET['reset_success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                Password updated successfully. Please log in again.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="text-red-600 mb-4"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Contact Input (Email or Phone) -->
        <label class="block mb-2 font-semibold">Email or Phone</label>
        <input name="contact" type="text" required
            class="w-full p-2 mb-4 border rounded"
            value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" />

        <!-- Password -->
        <label class="block mb-2 font-semibold">Password</label>
        <div class="relative mb-2">
            <input id="password" name="password" type="password" required class="w-full p-2 border rounded pr-10" />
            <button type="button" id="togglePassword"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-600 hover:text-gray-900 focus:outline-none"
                aria-label="Toggle password visibility">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        </div>

        <!-- Forgot Password link -->
        <div class="text-right mb-6">
            <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline font-medium">
                Forgot Password?
            </a>
        </div>

        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 font-bold py-2 rounded">
            Login
        </button>

        <!-- Google Login onload (leave hidden) -->
        <div id="g_id_onload"
            data-client_id="728060544644-q3i2jmek9a6juo3gqf3o2k1knorpi81o.apps.googleusercontent.com"
            data-context="signin"
            data-ux_mode="popup"
            data-login_uri="http://localhost/liwanagcafe/google_callback.php"
            data-auto_prompt="false">
        </div>

        <!-- Separator -->
        <div class="my-4 text-center text-sm text-gray-400">⸻⸺ Or Continue With ⸻⸺</div>

        <!-- Google Sign-In Button Centered -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">

            <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                <div class="g_id_signin"
                    data-type="icon"
                    data-theme="outline"
                    data-shape="circle"
                    data-size="large"
                    style="transform: scale(0.7);">
                </div>
            </div>

            <div class="text-center text-sm text-gray-600">
                Continue with Google
            </div>

            <p class="text-center text-sm text-gray-700">
                Don't have an account?
                <a href="register.php" class="text-blue-600 hover:underline font-semibold">register here</a>
            </p>

        </div>


    </form>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });
    </script>
</body>

</html>