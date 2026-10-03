<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$useremail = $_SESSION['email'] ?? '';

// Handle new reservation submission (from home.php form redirect or this page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $date    = $_POST['date'];
    $time    = $_POST['time'];
    $guests  = (int)$_POST['guests'];
    $message = $_POST['message'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO reservations
        (user_id, name, email, date, time, guests, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user_id, $name, $email, $date, $time, $guests, $message]);

    header("Location: my_reservations.php");
    exit;
}

// Handle cancel request
if (isset($_GET['cancel_id'])) {
    $cancel_id = (int)$_GET['cancel_id'];
    // Only allow cancelling pending reservations for this user
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$cancel_id, $user_id]);
    header("Location: my_reservations.php");
    exit;
}

// Fetch reservations for the current user
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Reservations | Liwanag Cafe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white font-sans">

    <div class="max-w-7xl mx-auto px-6 py-8">

        <a href="home.php" class="text-yellow-700 font-semibold mb-6 inline-block">← Home</a>
        <h1 class="text-3xl font-bold mb-8">My Reservations</h1>

        <!-- Reservation List -->
        <?php if (!$reservations): ?>
            <p class="text-gray-500 text-lg">You don’t have any reservations yet.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($reservations as $r):
                    $dateObj = new DateTime($r['date']);
                ?>
                    <div class="flex border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">

                        <!-- Date block -->
                        <div class="bg-yellow-500 text-white px-4 py-6 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold"><?= $dateObj->format('d') ?></span>
                            <span class="uppercase text-sm"><?= $dateObj->format('M') ?></span>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 p-4 flex flex-col justify-between">
                            <div class="space-y-1">
                                <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($r['name']) ?></h2>
                                <p><strong>Email:</strong> <?= htmlspecialchars($r['email']) ?></p>
                                <p><strong>Time:</strong> <?= substr($r['time'], 0, 5) ?></p>
                                <p><strong>Guests:</strong> <?= (int)$r['guests'] ?></p>
                                <?php if (!empty($r['message'])): ?>
                                    <p><strong>Message:</strong> <?= htmlspecialchars($r['message']) ?></p>
                                <?php endif; ?>
                                <p class="text-gray-500 text-sm flex items-center mt-1">
                                    Made On: <?= date('M d, Y H:i', strtotime($r['created_at'])) ?>
                                </p>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                <?= match ($r['status']) {
                                    'pending'  => 'bg-yellow-100 text-yellow-700',
                                    'approved' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-gray-300 text-gray-700',
                                    default    => 'bg-gray-200'
                                } ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>

                                <!-- Cancel button only if pending -->
                                <?php if ($r['status'] === 'pending'): ?>
                                    <a href="?cancel_id=<?= $r['id'] ?>"
                                        onclick="return confirm('Are you sure you want to cancel this reservation?')"
                                        class="ml-2 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-sm">
                                        Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>