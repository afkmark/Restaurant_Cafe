<?php
include 'db.php';
session_start();
$isLoggedIn = isset($_SESSION['username']);

// Fetch best sellers and all products
$bestSellersStmt = $pdo->prepare("SELECT * FROM products ORDER BY sold DESC LIMIT 5");
$bestSellersStmt->execute();
$bestSellers = $bestSellersStmt->fetchAll(PDO::FETCH_ASSOC);

$allProductsStmt = $pdo->prepare("SELECT * FROM products");
$allProductsStmt->execute();
$allProducts = $allProductsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Menu | Liwanag Café</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fffaf2;
        }

        h2,
        h4 {
            font-family: 'Playfair Display', serif;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .carousel-container {
            overflow: hidden;
            width: 100%;
        }

        .active-btn {
            background-color: #000;
            color: #FFD700;
            border: 2px solid #FFD700;
        }

        .filter-btn {
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="text-black">
    <?php include 'includes/navbar.php'; ?>

    <!-- Best Sellers -->
    <section class="py-12 bg-gradient-to-r from-yellow-50 via-white to-yellow-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-yellow-800 mb-6 flex items-center">
                <i class="fas fa-crown text-yellow-500 mr-2"></i> Liwanag Café Best Sellers
            </h2>
            <div class="carousel-container relative">
                <div id="carousel-track" class="carousel-track w-fit">
                    <?php foreach ($bestSellers as $item): ?>
                        <?php

                        $imagePath = htmlspecialchars($item['image']);
                        if (!str_starts_with($imagePath, 'assets/')) {
                            $imagePath = 'assets/' . $imagePath;
                        }
                        ?>
                        <div class="min-w-[250px] max-w-[250px] p-4 flex-shrink-0 carousel-item">
                            <div class="bg-white p-4 rounded-2xl shadow-lg hover:shadow-xl text-center h-full flex flex-col transition">
                                <img src="<?= $imagePath ?>"
                                    onerror="this.src='assets/placeholder.jpg';"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="w-full h-48 object-cover rounded-lg mb-3 cursor-pointer hover:scale-105 transition" />
                                <h4 class="text-lg font-bold text-yellow-700 mb-1"><?= htmlspecialchars($item['name']) ?></h4>
                                <p class="text-gray-600 mb-2">₱<?= number_format($item['price'], 2) ?></p>
                                <button onclick="addToCart(<?= $item['id'] ?>)" class="mt-auto bg-yellow-600 text-white px-3 py-2 rounded-lg hover:bg-yellow-700 font-semibold">Add to Order</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Filters -->
    <section class="py-6 bg-white border-t border-b border-yellow-200">
        <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-center gap-4">
            <?php
            $categories = ['All', 'Filipino Favorites', 'Asian Fusion', 'Drinks'];
            foreach ($categories as $cat) {
                $id = strtolower(str_replace(' ', '-', $cat));
                echo "<button id='btn-$id' onclick=\"filterMenu('$cat')\" class=\"filter-btn bg-yellow-500 text-white px-5 py-2 rounded-full font-medium capitalize\">$cat</button>";
            }
            ?>
        </div>
    </section>

    <!-- Menu + Cart Section -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-3 gap-8">
            <!-- Menu Items -->
            <div id="menu-items" class="md:col-span-2 grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"></div>

            <!-- Cart -->
            <div class="bg-white border border-yellow-300 p-5 rounded-xl shadow sticky top-6 h-fit" id="cart-wrapper">
                <h3 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-shopping-cart text-yellow-500 mr-2"></i> Your Order
                </h3>
                <div id="cart-body" class="space-y-3 text-sm">
                    <div id="cart-items"></div>
                    <div class="flex justify-between items-center border-t pt-3">
                        <span class="font-bold">Total: ₱<span id="cart-total">0.00</span></span>
                        <select id="payment-method" class="border px-2 py-1 rounded">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                    <div id="receipt-upload" class="hidden mt-2">
                        <label class="block text-sm font-medium text-gray-700">GCash Receipt</label>
                        <input type="file" id="receipt" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button onclick="checkout()" class="bg-black text-yellow-400 px-4 py-2 rounded hover:text-yellow-300">Checkout</button>
                        <button onclick="clearCart()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Confirmation Modal -->
    <div id="order-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex justify-center items-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-96 text-center relative transform scale-95 opacity-0 transition-all duration-300 ease-out" id="order-modal-content">
            <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-500 hover:text-black text-xl">
                <i class="fas fa-times"></i>
            </button>
            <div class="flex justify-center mb-4">
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-check text-green-600 text-4xl"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Confirmed!</h2>
            <p class="text-gray-600 mb-1">Thank you for ordering at</p>
            <p class="font-semibold text-yellow-600 text-lg mb-4">Liwanag Café</p>
            <p class="text-gray-700 mb-6">Your total is <strong>₱<span id="modal-total">0.00</span></strong></p>
            <button onclick="closeModal()" class="bg-black text-yellow-400 px-6 py-2 rounded-lg font-medium hover:text-yellow-300 transition">Continue</button>
        </div>
    </div>


    <!-- Image Preview Modal -->
    <div id="image-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden justify-center items-center z-50">
        <img id="preview-image" src="" alt="Preview" class="max-w-full max-h-[90vh] rounded shadow-lg" />
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-3xl"><i class="fas fa-times"></i></button>
    </div>
    <!-- Login Required Modal -->
    <div id="login-required-modal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden flex justify-center items-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-80 text-center relative transform scale-95 opacity-0 transition-all duration-300"
            id="login-required-content">
            <button onclick="closeLoginModal()" class="absolute top-3 right-3 text-gray-500 hover:text-black text-xl">
                <i class="fas fa-times"></i>
            </button>

            <div class="flex justify-center mb-3">
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-lock text-red-600 text-3xl"></i>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-2">Login Required</h2>
            <p class="text-gray-600 mb-4">You need to login before placing an order.</p>

            <button onclick="window.location.href='login.php'"
                class="bg-black text-yellow-400 px-6 py-2 rounded-lg font-medium hover:text-yellow-300 transition">
                Login Now
            </button>
        </div>
    </div>

    <!--  -->
    <script>
        function showLoginModal() {
            const modal = document.getElementById("login-required-modal");
            const content = document.getElementById("login-required-content");

            modal.classList.remove("hidden");

            setTimeout(() => {
                content.classList.remove("scale-95", "opacity-0");
                content.classList.add("scale-100", "opacity-100");
            }, 50);
        }

        function closeLoginModal() {
            const modal = document.getElementById("login-required-modal");
            const content = document.getElementById("login-required-content");

            content.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 200);
        }
    </script>

    <script>
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const products = <?= json_encode($allProducts) ?>;
        let cart = [];

        function normalizeImage(path) {
            // Ensure path starts with "assets/"
            if (!path.startsWith('assets/')) return 'assets/' + path;
            return path;
        }

        function filterMenu(category) {
            document.querySelectorAll(".filter-btn").forEach(btn => btn.classList.remove("active-btn"));
            const id = 'btn-' + category.toLowerCase().replaceAll(' ', '-');
            document.getElementById(id).classList.add("active-btn");

            const items = category === 'All' ? products : products.filter(p => p.category === category);
            const container = document.getElementById('menu-items');
            container.innerHTML = '';
            items.forEach(item => {
                container.innerHTML += `
            <div class="bg-white p-4 rounded-xl shadow hover:shadow-lg flex flex-col transition">
                <img src="${normalizeImage(item.image)}" onerror="this.src='assets/placeholder.jpg'" class="w-full h-40 object-cover rounded mb-3 cursor-pointer hover:scale-105 transition" />
                <h4 class="text-lg font-bold text-yellow-700 mb-1">${item.name}</h4>
                <p class="text-gray-600 mb-4">₱${parseFloat(item.price).toFixed(2)}</p>
                <button onclick="addToCart(${item.id})" class="mt-auto bg-yellow-600 text-white px-3 py-2 rounded hover:bg-yellow-700">Add to Order</button>
            </div>`;
            });
        }

        function addToCart(id) {
            if (!isLoggedIn) {
                showLoginModal();
                return;
            }

            const item = products.find(p => p.id == id);
            const exists = cart.find(c => c.id == id);

            if (exists) exists.qty++;
            else cart.push({
                ...item,
                qty: 1
            });

            renderCart();
        }


        function renderCart() {
            const wrapper = document.getElementById('cart-items');
            wrapper.innerHTML = '';
            let total = 0;
            cart.forEach(item => {
                const subtotal = item.qty * item.price;
                total += subtotal;
                wrapper.innerHTML += `
            <div class="flex justify-between border-b pb-1">
                <div>${item.name} x ${item.qty}
                    <button onclick="updateQty(${item.id}, 1)" class="text-green-600 ml-2"><i class="fas fa-plus"></i></button>
                    <button onclick="updateQty(${item.id}, -1)" class="text-red-600 ml-1"><i class="fas fa-minus"></i></button>
                </div>
                <div>₱${subtotal.toFixed(2)}</div>
            </div>`;
            });
            document.getElementById("cart-total").textContent = total.toFixed(2);
        }

        function updateQty(id, change) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            item.qty += change;
            if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        function toggleReceipt() {
            const method = document.getElementById("payment-method").value;
            document.getElementById("receipt-upload").classList.toggle("hidden", method !== "gcash");
        }

        async function checkout() {
            if (!isLoggedIn) {
                showLoginModal();
                return;
            }

            const method = document.getElementById("payment-method").value;
            const total = cart.reduce((t, i) => t + i.qty * i.price, 0).toFixed(2);

            if (cart.length === 0) {
                alert("Cart is empty.");
                return;
            }

            const formData = new FormData();
            formData.append("payment", method);
            formData.append("total", total);
            formData.append("cart", JSON.stringify(cart));

            if (method === "gcash") {
                const receiptInput = document.getElementById("receipt");
                if (!receiptInput.files[0]) {
                    return alert("Please upload your GCash receipt.");
                }
                formData.append("receipt", receiptInput.files[0]);
            }

            const response = await fetch("checkout.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                clearCart();
                showModal(total);
            } else alert(result.error || "Order failed.");
        }


        function showModal(total) {
            document.getElementById("modal-total").textContent = parseFloat(total).toFixed(2);
            document.getElementById("order-modal").classList.remove("hidden");
        }

        function closeModal() {
            document.getElementById("order-modal").classList.add("hidden");
        }

        function closeImageModal() {
            document.getElementById("image-modal").classList.add("hidden");
            document.getElementById("preview-image").src = "";
        }

        document.addEventListener("click", function(e) {
            if (e.target.tagName === "IMG" && e.target.closest(".carousel-item, #menu-items")) {
                const src = e.target.src;
                document.getElementById("preview-image").src = src;
                document.getElementById("image-modal").classList.remove("hidden");
            }
        });

        document.getElementById("payment-method").addEventListener("change", toggleReceipt);

        document.addEventListener("DOMContentLoaded", () => {
            filterMenu('All');

            // Auto carousel
            const track = document.getElementById("carousel-track");
            const slides = [...track.children];
            const slideWidth = 250;
            let index = 0;
            slides.forEach(s => track.appendChild(s.cloneNode(true)));
            setInterval(() => {
                index++;
                track.style.transition = 'transform 0.5s ease-in-out';
                track.style.transform = `translateX(-${index * slideWidth}px)`;
                if (index >= slides.length) {
                    setTimeout(() => {
                        track.style.transition = 'none';
                        track.style.transform = 'translateX(0px)';
                        index = 0;
                    }, 500);
                }
            }, 3000);
        });

        function showModal(total) {
            const modal = document.getElementById("order-modal");
            const content = document.getElementById("order-modal-content");
            document.getElementById("modal-total").textContent = parseFloat(total).toFixed(2);
            modal.classList.remove("hidden");

            // Animate pop-in
            setTimeout(() => {
                content.classList.remove("scale-95", "opacity-0");
                content.classList.add("scale-100", "opacity-100");
            }, 50);
        }

        function closeModal() {
            const modal = document.getElementById("order-modal");
            const content = document.getElementById("order-modal-content");
            content.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 200);
        }
    </script>

</body>

</html>