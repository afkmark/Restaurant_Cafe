<?php
session_start();
require 'db.php';

$defaultProfilePic = "https://cdn-icons-png.flaticon.com/512/149/149071.png";
$profilePic = $_SESSION['profile_pic'] ?? $defaultProfilePic;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

$success = '';

// Handle reservation form directly here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'], $_POST['time'])) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $name = trim($_POST['name']);
    $email = $_SESSION['email'] ?? null;
    $guests = intval($_POST['guests']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $message = trim($_POST['message']);
    $status = 'pending';

    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (user_id, name, email, guests, date, time, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $name, $email, $guests, $date, $time, $message, $status]);

    $success = "Reservation submitted successfully!";
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>LiwanagCafe Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="assets/restaurantlogo.jpg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .font-serif {
            font-family: 'Playfair Display', serif !important;
        }

        .dropdown-content {
            display: none;
        }

        .dropdown.active .dropdown-content {
            display: block;
        }
    </style>
</head>

<body class="min-h-screen bg-white">

    <!-- Sticky Navbar -->
    <nav class="sticky top-0 z-50 bg-white shadow-md w-full px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <a href="/liwanagcafe/home.php" class="flex items-center gap-3 hover:opacity-90 transition">
                    <img src="assets/restaurantlogo.jpg" alt="icon" class="w-12 h-12 rounded-lg shadow-md border border-white/30 object-cover" />
                    <span class="text-yellow-500 font-serif italic font-bold text-xl">Liwanag Cafe</span>
                </a>
            </div>

            <!-- Nav Links -->
            <ul class="hidden md:flex gap-6 bg-black rounded-full px-6 py-2 text-white text-sm font-mono">
                <li><a href="#" class="nav-link px-4 py-1 block rounded-full bg-white text-black">Home</a></li>
                <li><a href="menu.php" class="px-4 py-1 block hover:text-yellow-400">Menu</a></li>
                <li><a href="#about" class="nav-link px-4 py-1 block rounded-full">About</a></li>
                <li><a href="#reservation" class="nav-link px-4 py-1 block rounded-full">Reservations</a></li>
                <li><a href="#gallery" class="nav-link px-4 py-1 block rounded-full">Gallery</a></li>
                <li><a href="#contact" class="nav-link px-4 py-1 block rounded-full">Contact</a></li>
            </ul>

            <!-- User Dropdown -->
            <div class="relative dropdown">
                <?php if ($username): ?>
                    <button onclick="toggleDropdown()" class="flex items-center gap-2 focus:outline-none">
                        <i class="fas fa-user-circle text-2xl text-black"></i>
                        <span class="hidden md:inline text-black font-semibold"><?= htmlspecialchars($username) ?></span>
                        <i class="fas fa-caret-down ml-1 text-black"></i>
                    </button>
                    <div class="dropdown-content absolute right-0 mt-3 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                        <a href="my_orders.php" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">
                            <i class="fas fa-utensils mr-2 text-yellow-500"></i> My Orders
                        </a>
                        <a href="my_reservations.php" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">
                            <i class="fas fa-book mr-2 text-yellow-500"></i> My Reservations
                        </a>
                        <?php if ($role === 'admin'): ?>
                            <a href="admin_dashboard.php" class="block px-4 py-2 text-sm text-yellow-600 font-semibold hover:bg-gray-100">
                                <i class="fas fa-cogs mr-2"></i> Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-semibold text-black hover:text-yellow-500">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="w-full bg-white py-20 px-6">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-12">
            <!-- Text -->
            <div class="md:w-1/2 flex flex-col justify-center h-full text-center md:text-left">
                <h1 class="text-5xl md:text-6xl font-serif font-bold mb-6 leading-tight text-gray-900">
                    Sip. Relax. Unwind<br />at Liwanag Cafe.
                </h1>
                <p class="text-lg md:text-xl text-yellow-600 mb-8 font-mono leading-relaxed">
                    Discover a modern tea house experience where flavor, comfort, and atmosphere come together.
                </p>
                <div class="flex flex-wrap justify-center md:justify-start gap-4">
                    <a href="menu.php" class="bg-yellow-400 text-black text-base md:text-lg font-bold font-mono px-6 py-3 rounded-full hover:bg-yellow-300 transition italic">
                        View Menu
                    </a>
                    <?php if ($username): ?>
                        <a href="#reservation" class="border border-yellow-400 text-black text-base md:text-lg font-bold font-mono px-6 py-3 rounded-full hover:bg-yellow-50 transition italic">
                            Reserve a Table
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="border border-yellow-400 text-black text-base md:text-lg font-bold font-mono px-6 py-3 rounded-full hover:bg-yellow-50 transition italic">
                            Login to Reserve
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Image -->
            <div class="md:w-1/2 flex justify-center">
                <img src="assets/sizzling.jpg" alt="Sizzling" class="rounded-xl rotate-6 shadow-lg max-w-full h-auto object-cover w-[420px]" />
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-white text-black my-28 scroll-mt-24">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="text-center mb-10">
                <h2 class="text-4xl font-bold text-black mb-4">About Liwanag Cafe</h2>
                <p class="text-lg text-gray-700">Where good food meets great company — discover the story behind your favorite neighborhood spot.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div><img src="assets/frontpic.jpg" alt="Inside LiwanagCafe" class="rounded-2xl shadow-xl w-full"></div>
                <div>
                    <h3 class="text-2xl font-semibold text-yellow-600 mb-4">Our Journey</h3>
                    <p class="text-gray-800 mb-4">
                        Established in 2017, Liwanag Café is a cozy neighborhood spot that brings people together over quality coffee and comforting food.
                    </p>
                    <p class="text-gray-800 mb-4">
                        Inspired by the Filipino word “Liwanag” (light), our café aims to brighten each guest’s day with warm service, a peaceful ambiance, and homegrown flavors.
                    </p>
                    <p class="text-gray-800">Join us and taste the tradition.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Reservation Section -->
    <section id="reservation" class="py-20 bg-black text-white scroll-mt-24">
        <div class="container mx-auto px-4 max-w-3xl text-center">
            <h2 class="text-4xl font-bold text-yellow-500 mb-6">Reserve a Table</h2>
            <p class="mb-8 text-gray-300">Plan your perfect meal with us. Fill in the form and we’ll save your seat.</p>

            <?php if ($username): ?>
                <form method="POST" action="" class="grid gap-6 md:grid-cols-2 text-left">
                    <input type="text" name="name" placeholder="Full Name" required class="p-3 rounded bg-gray-800 text-white border border-yellow-600">
                    <input type="date" name="date" required class="p-3 rounded bg-gray-800 text-white border border-yellow-600">
                    <input type="time" name="time" required class="p-3 rounded bg-gray-800 text-white border border-yellow-600">
                    <input type="number" name="guests" placeholder="Number of Guests" required class="p-3 rounded bg-gray-800 text-white border border-yellow-600">
                    <textarea name="message" placeholder="Special Requests" rows="3" class="p-3 rounded bg-gray-800 text-white border border-yellow-600 md:col-span-2"></textarea>
                    <button type="submit" class="md:col-span-2 bg-yellow-500 text-black font-bold py-3 rounded-full hover:bg-yellow-400 transition">
                        Submit Reservation
                    </button>
                </form>
            <?php else: ?>
                <p class="text-gray-300">Please login to make a reservation.</p>
            <?php endif; ?>
        </div>
    </section>
    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-xl p-6 shadow-lg text-center max-w-sm w-full">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Reservation Submitted!</h3>
            <p class="text-gray-600">Your table has been successfully reserved.</p>
        </div>
    </div>


    <!-- Gallery Section -->
    <section id="gallery" class="py-16 bg-yellow-50 text-black my-20 scroll-mt-24">
        <div class="container mx-auto px-4 max-w-6xl text-center">
            <h2 class="text-4xl font-bold text-yellow-700 mb-8">Gallery</h2>
            <p class="text-gray-700 mb-12">A glimpse of the flavors, moments, and memories we share at Liwanag Cafe.</p>

            <div class="grid gap-6 md:grid-cols-3">
                <?php for ($i = 1; $i <= 15; $i++): ?>
                    <img src="img/<?= $i ?>.jpg" alt="Gallery Image <?= $i ?>" class="rounded-xl shadow-md hover:scale-105 transition">
                <?php endfor; ?>
            </div>
        </div>
    </section>


    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-black text-white scroll-mt-24">
        <div class="container mx-auto px-4 max-w-6xl">
            <h2 class="text-4xl font-bold text-yellow-500 text-center mb-12">Contact Us</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
                <!-- Left: Contact Info -->
                <div class="space-y-6">
                    <!-- Location -->
                    <div>
                        <h3 class="text-2xl font-semibold text-yellow-400 mb-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4.5 8-10a8 8 0 10-16 0c0 5.5 8 10 8 10z" />
                            </svg>
                            Location
                        </h3>
                        <p class="text-gray-300">Congressional Rd<br>General Mariano Alvarez, 4117 Cavite</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <h3 class="text-2xl font-semibold text-yellow-400 mb-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m0 0l4-4m-4 4l4 4m8-10a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-4h12a2 2 0 002-2V6z" />
                            </svg>
                            Email
                        </h3>
                        <p class="text-gray-300">info@restaurant.com</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <h3 class="text-2xl font-semibold text-yellow-400 mb-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.586a1 1 0 01.707.293l1.414 1.414a1 1 0 010 1.414L9.414 8.586a16.016 16.016 0 006.586 6.586l2.293-2.293a1 1 0 011.414 0l1.414 1.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Phone
                        </h3>
                        <p class="text-gray-300">+63 945 587 3640</p>
                    </div>

                    <!-- CTA Button -->
                    <a href="https://www.google.com/maps/dir/?api=1&destination=General+Mariano+Alvarez,+Philippines"
                        target="_blank"
                        class="inline-flex items-center gap-2 bg-yellow-500 text-black py-3 px-6 rounded font-semibold hover:bg-yellow-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-4.586-4.586a1 1 0 00-1.415 1.415l3.879 3.879-3.879 3.879a1 1 0 001.415 1.415l4.586-4.586a1 1 0 000-1.415z" />
                        </svg>
                        Get Directions
                    </a>
                </div>

                <!-- Right: Google Map -->
                <div>
                    <div class="rounded-lg overflow-hidden border border-yellow-600 shadow-lg w-full h-[350px]">
                        <iframe
                            src="https://www.google.com/maps?q=Congressional+Rd,+General+Mariano+Alvarez,+Cavite&output=embed"
                            width="100%"
                            height="100%"
                            class="w-full h-full"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer class="bg-black text-white py-4 border-t border-gray-700">
        <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-sm">


            <div class="md:w-1/3 text-left text-gray-500 mb-2 md:mb-0">
                &copy; <?= date('Y') ?> BS INFO-TECH. All rights reserved.
            </div>


            <div class="md:w-1/3 text-center mb-2 md:mb-0">
                <span class="text-yellow-500 text-lg font-serif italic font-bold block">Liwanag Cafe</span>
                <span class="text-gray-400 text-xs">Comfort food & community since 2017</span>
            </div>


            <div class="md:w-1/3 flex justify-end gap-4 text-yellow-500 text-lg">
                <a href="https://www.facebook.com/liwanagcafe/" target="_blank" class="hover:text-yellow-400 transition" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="mailto:info@restaurant.com" class="hover:text-yellow-400 transition" aria-label="Email">
                    <i class="fas fa-envelope"></i>
                </a>
                <a href="https://www.instagram.com/liwanagcafe/" target="_blank" class="hover:text-yellow-400 transition" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.tiktok.com/@liwanagcafe" target="_blank" class="hover:text-yellow-400 transition" aria-label="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>


        </div>
    </footer>

    <!-- JS -->
    <script>
        function toggleDropdown() {
            const dropdown = document.querySelector('.dropdown');
            dropdown.classList.toggle('active');
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.dropdown');
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Navigation highlight on click
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('bg-white', 'text-black'));
                link.classList.add('bg-white', 'text-black');
            });
        });
    </script>
    <script>
        <?php if (!empty($success)): ?>
            // Show modal
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');

            // Hide after 3 seconds
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 3000);
        <?php endif; ?>
    </script>

</body>

</html>