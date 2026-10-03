<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $contact_type = $_POST['contact_type'];
    $contact = trim($_POST['contact']);
    $password = $_POST['password'];

    // Validate contact
    if ($contact_type === 'email' && !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($contact_type === 'phone' && !preg_match('/^[0-9]{10,15}$/', $contact)) {
        $error = "Invalid phone number.";
    }

    if (empty($error)) {
        try {
            // Check if email/phone already exists
            if ($contact_type === 'email') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            }
            $stmt->execute([$contact]);
            if ($stmt->rowCount() > 0) {
                $error = $contact_type === 'email' ? "An account with this email already exists." : "An account with this phone number already exists.";
            }

            if (empty($error)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $name = $firstname . ' ' . $lastname;
                $role = 'user';
                $profile_pic = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                $created_at = date('Y-m-d H:i:s');

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, profile_pic, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name,
                    $contact_type === 'email' ? $contact : null,
                    $contact_type === 'phone' ? $contact : null,
                    $hashedPassword,
                    $role,
                    $profile_pic,
                    $created_at
                ]);

                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Liwanag Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="assets/restaurantlogo.jpg" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff7ed;
        }

        .font-serif {
            font-family: 'Playfair Display', serif !important;
        }

        .bg {
            background-image: url("assets/frontpic.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body class="bg min-h-screen flex items-center justify-center px-4">

    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-3xl font-serif font-bold mb-6 text-yellow-600">Register for Liwanag Cafe</h1>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">
            <!-- Name Fields -->
            <div class="flex gap-2">
                <input required type="text" name="firstname" placeholder="First Name" class="w-1/2 border border-gray-300 rounded px-3 py-2 focus:outline-yellow-400" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" />
                <input required type="text" name="lastname" placeholder="Last Name" class="w-1/2 border border-gray-300 rounded px-3 py-2 focus:outline-yellow-400" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" />
            </div>

            <!-- Contact Type Toggle -->
            <div class="flex gap-2">
                <select id="contact_type" name="contact_type" onchange="toggleContactInput()" class="w-1/3 border border-gray-300 rounded px-3 py-2 focus:outline-yellow-400">
                    <option value="email" <?= ($_POST['contact_type'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="phone" <?= ($_POST['contact_type'] ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
                </select>
                <input required type="<?= ($_POST['contact_type'] ?? '') === 'phone' ? 'tel' : 'email' ?>" name="contact" id="contact_input" placeholder="<?= ($_POST['contact_type'] ?? '') === 'phone' ? 'Phone Number' : 'Email Address' ?>" class="w-2/3 border border-gray-300 rounded px-3 py-2 focus:outline-yellow-400" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" />
            </div>

            <!-- Password -->
            <input required type="password" name="password" placeholder="Password" class="border border-gray-300 rounded px-3 py-2 focus:outline-yellow-400" />

            <button type="submit" class="bg-yellow-400 text-black font-bold py-2 rounded hover:bg-yellow-300 transition">Register</button>
        </form>

        <p class="mt-6 text-center text-gray-600">
            Already have an account?
            <a href="login.php" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>

    <script>
        function toggleContactInput() {
            const type = document.getElementById('contact_type').value;
            const input = document.getElementById('contact_input');
            input.placeholder = type === 'phone' ? 'Phone Number' : 'Email Address';
            input.type = type === 'phone' ? 'tel' : 'email';
            input.value = '';
        }
        window.addEventListener("DOMContentLoaded", () => toggleContactInput());
    </script>

</body>

</html>