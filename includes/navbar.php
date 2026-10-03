<?php
$defaultProfilePic = "https://cdn-icons-png.flaticon.com/512/149/149071.png";
$profilePic = $_SESSION['profile_pic'] ?? $defaultProfilePic;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';
?>

<!-- Sticky Navbar -->
<nav class="sticky top-0 z-50 bg-white shadow-md w-full px-6 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
            <a href="/liwanagcafe/home.php" class="flex items-center gap-3 hover:opacity-90 transition">
                <img src="/liwanagcafe/assets/restaurantlogo.jpg"
                    alt="Liwanag Cafe Logo"
                    class="w-10 h-10 object-cover rounded-full shadow-md" />
                <span class="text-yellow-500 font-serif italic font-bold text-2xl">Liwanag Cafe</span>
            </a>
        </div>

        <!-- Nav Links -->
        <ul class="hidden md:flex gap-6 bg-black rounded-full px-6 py-2 text-white text-sm font-mono">
            <li><a href="home.php" class="hover:text-yellow-400 px-4 py-1 block <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'bg-white text-black rounded-full' : '' ?>">Home</a></li>
            <li><a href="menu.php" class="hover:text-yellow-400 px-4 py-1 block <?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'bg-white text-black rounded-full' : '' ?>">Menu</a></li>
            <li><a href="home.php#about" class="hover:text-yellow-400 px-4 py-1 block">About</a></li>
            <li><a href="home.php#reservation" class="hover:text-yellow-400 px-4 py-1 block">Reservations</a></li>
            <li><a href="home.php#gallery" class="hover:text-yellow-400 px-4 py-1 block">Gallery</a></li>
            <li><a href="home.php#contact" class="hover:text-yellow-400 px-4 py-1 block">Contact</a></li>
        </ul>

        <!-- User Dropdown (matching home.php UI) -->
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

<style>
    /* ensure dropdown works consistently */
    .dropdown-content {
        display: none;
    }

    .dropdown.active .dropdown-content {
        display: block;
    }
</style>

<script>
    function toggleDropdown() {
        const dropdown = document.querySelector('.dropdown');
        if (!dropdown) return;
        dropdown.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.dropdown');
        if (!dropdown) return;
        if (!dropdown.contains(e.target)) dropdown.classList.remove('active');
    });
</script>