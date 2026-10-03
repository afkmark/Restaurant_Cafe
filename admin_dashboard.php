<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ===============================
// PAYMENT APPROVAL (GCash)
// ===============================
if (isset($_POST['order_id'], $_POST['payment_status'])) {
    $orderId = (int)$_POST['order_id'];
    $paymentAction = $_POST['payment_status']; // "approved" or "rejected"

    if ($paymentAction === 'approved') {
        $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            status = CASE
                WHEN status = 'pending' THEN 'confirmed'
                ELSE status
            END
        WHERE id = ?
    ");
        $stmt->execute([$orderId]);
    } elseif ($paymentAction === 'rejected') {
        $stmt = $pdo->prepare("
            UPDATE orders
            SET payment_status = 'rejected',
                status = 'cancelled'
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
    }

    // Redirect back to dashboard after update
    header("Location: admin_dashboard.php");
    exit;
}


// ===============================
// ORDER STATUS UPDATE
// Preparing → Ready → Completed
// ===============================
if (isset($_POST['order_id'], $_POST['new_status'])) {
    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = ?
        WHERE id = ?
    ");
    $stmt->execute([$_POST['new_status'], $_POST['order_id']]);

    header("Location: admin_dashboard.php");
    exit;
}

// Fetch pending orders count for notification
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS pending_count FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingOrders = $result['pending_count'];
} catch (PDOException $e) {
    $pendingOrders = 0;
}

// Fetch orders with customer name
$orders = $pdo->query("
    SELECT 
        o.*, 
        u.name AS customer_name
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);


$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$reservations = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Order status summary (normalize variants like 'cancel', 'cancelled', 'rejected')
$statusCounts = array_fill_keys(['pending', 'confirmed', 'preparing', 'completed', 'cancelled'], 0);
$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM orders GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = strtolower($row['status']);
    if (in_array($key, ['cancel', 'cancelled', 'rejected'], true)) $key = 'cancelled';
    if ($key === 'ready') $key = 'preparing';
    if (isset($statusCounts[$key])) $statusCounts[$key] = $row['count'];
}




// Reservations update
if (isset($_POST['reservation_id'], $_POST['reservation_status'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['reservation_status'], $_POST['reservation_id']]);
    header("Location: admin_dashboard.php");
    exit;
}

// Products actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['product_id'] ?? null;

    if ($action === 'add') {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/' . $image);
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, image, status, available, sold) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$_POST['name'], $_POST['category'], $_POST['price'], $image, $_POST['status'], $_POST['available']]);
    } elseif ($action === 'edit' && $id) {
        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], 'assets/' . $image);
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, image=?, status=?, available=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['category'], $_POST['price'], $image, $_POST['status'], $_POST['available'], $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, status=?, available=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['category'], $_POST['price'], $_POST['status'], $_POST['available'], $id]);
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Delete product
if (isset($_POST['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_POST['delete_product']]);
    header("Location: admin_dashboard.php");
    exit;
}

?>

<?php include 'admin_header.php'; ?>
<link rel="stylesheet" href="admin.css">
<style>
    #scrollTopBtn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 99999;
        font-size: 24px;
        background-color: #222;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        cursor: pointer;
        display: none;
        /* Hidden initially */
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        transition: background-color 0.3s, transform 0.3s;
    }

    #scrollTopBtn:hover {
        background-color: #444;
        transform: translateY(-2px);
    }

    .tab-content {
        height: calc(100vh - 100px);
        overflow-y: auto;
        padding: 20px;
    }
</style>
<!-- Global Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top">&#8679;</button>
<!-- Tabs -->
<div class="tab-menu">
    <button onclick="showTab('dashboard')" id="tab-dashboard" class="active-tab">Dashboard</button>
    <button onclick="showTab('orders')" id="tab-orders">Orders</button>
    <button onclick="showTab('products')" id="tab-products">Manage Products</button>
    <button onclick="showTab('reservations')" id="tab-reservations">Reservations</button>
</div>

<!-- Dashboard Tab -->
<div id="dashboard-tab" class="tab-content">
    <ul class="box-info">
        <li><i class='bx bx-loader-circle'></i><span class="text">
                <h3 id="pending-count"><?= $statusCounts['pending'] ?></h3>
                <p>Pending</p>
            </span></li>
        <li><i class='bx bx-check-shield'></i><span class="text">
                <h3 id="confirmed-count"><?= $statusCounts['confirmed'] ?></h3>
                <p>Confirmed</p>
            </span></li>
        <li><i class='bx bx-time-five'></i><span class="text">
                <h3 id="preparing-count"><?= $statusCounts['preparing'] ?></h3>
                <p>Preparing</p>
            </span></li>
        <li><i class='bx bx-check-circle'></i><span class="text">
                <h3 id="completed-count"><?= $statusCounts['completed'] ?></h3>
                <p>Completed</p>
            </span></li>
        <li><i class='bx bx-x-circle'></i><span class="text">
                <h3 id="cancel-count"><?= $statusCounts['cancelled'] ?></h3>
                <p>Cancelled</p>
            </span></li>
    </ul>
</div>

<!-- Orders Tab -->
<div id="orders-tab" class="tab-content" style="display: none;">
    <div class="orders-filter-search">
        <!-- Payment Method Filter -->
        <select id="payment-filter" class="filter-input">
            <option value="">All Payments</option>
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
        </select>

        <!-- Customer Name Search -->
        <div class="search-wrapper">
            <input type="text" id="customer-search" class="search-input" placeholder="Search customer name..." />
            <span class="search-icon">&#128269;</span> <!-- magnifying glass icon -->
        </div>
    </div>


    <div class="table-data">
        <div class="order">
            <div class="head">
                <h3>Orders</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pickup Code</th>
                        <th>User</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Receipt</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Action</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $statusNorm = strtolower($o['status']);
                        if (in_array($statusNorm, ['cancel', 'cancelled', 'rejected'], true)) $statusNorm = 'cancelled';
                        $cls = match ($statusNorm) {
                            'pending' => 'pending',
                            'confirmed' => 'confirmed',
                            'preparing' => 'preparing',
                            'completed' => 'completed',
                            'cancelled' => 'cancelled',
                            default => ''
                        };

                        // Fetch order items
                        $itemsStmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
                        $itemsStmt->execute([$o['id']]);
                        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['pickup_code']) ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td>₱<?= number_format($o['total_amount'], 2) ?></td>
                            <td><?= strtoupper($o['payment_method']) ?></td>

                            <td>
                                <?php $receiptPath = 'receipts/' . $o['receipt'];
                                if (strtolower($o['payment_method']) === 'gcash' && !empty($o['receipt']) && file_exists($receiptPath)):
                                ?>
                                    <button
                                        type="button"
                                        class="btn secondary view-receipt-btn"
                                        data-image="<?= $receiptPath ?>">
                                        View Receipt
                                    </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <?php $paymentStatus = $o['payment_status'] ?: 'unverified'; ?>
                            <td>
                                <span class="status <?= $paymentStatus ?>">
                                    <?= ucfirst($paymentStatus) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status <?= $cls ?>">
                                    <?= ucfirst($o['status']) ?>
                                </span>
                            </td>

                            <td>
                                <button type="button" class="view-items-btn btn secondary" data-items='<?= json_encode($orderItems) ?>'>
                                    View Items
                                </button>
                            </td>
                            <td>

                            <td>
                                <?php if ($o['payment_method'] === 'gcash' && $o['payment_status'] === 'unverified'): ?>
                                    <!-- GCash payment approval only -->
                                    <form method="POST" style="display:flex; gap:4px;">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button name="payment_status" value="approved" class="order-action complete">
                                            Approve Payment
                                        </button>
                                        <button name="payment_status" value="rejected" class="order-action cancel">
                                            Reject
                                        </button>
                                    </form>
                                <?php elseif (!in_array($statusNorm, ['completed', 'cancelled'])): ?>
                                    <!-- General status actions -->
                                    <form method="POST" style="display:flex; gap:4px;">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">

                                        <?php if ($statusNorm === 'pending'): ?>
                                            <button name="new_status" value="confirmed" class="order-action">
                                                Confirm
                                            </button>
                                            <button name="new_status" value="cancelled" class="order-action cancel">
                                                Cancel
                                            </button>
                                        <?php elseif ($statusNorm === 'confirmed'): ?>
                                            <button name="new_status" value="preparing" class="order-action">
                                                Preparing
                                            </button>
                                            <button name="new_status" value="cancelled" class="order-action cancel">
                                                Cancel
                                            </button>
                                        <?php elseif ($statusNorm === 'preparing'): ?>
                                            <button name="new_status" value="ready" class="order-action ready">
                                                Mark as Ready
                                            </button>
                                            <button name="new_status" value="cancelled" class="order-action cancel">
                                                Cancel
                                            </button>
                                        <?php elseif ($statusNorm === 'ready'): ?>
                                            <button name="new_status" value="completed" class="order-action complete">
                                                Complete
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Order Items Modal (modern) -->
<div id="items-modal" class="modal modern-items-modal" aria-hidden="true" style="display:none;">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="items-modal-title">
        <header class="modal-header">
            <h3 id="items-modal-title">Order Items</h3>
            <button id="close-items-modal" class="modal-close" aria-label="Close">&times;</button>
        </header>

        <div class="modal-body">
            <ul id="items-list" class="items-list">
                <!-- items populated here -->
            </ul>
        </div>

        <footer class="modal-footer">
            <div class="totals">
                <div class="totals-row"><span class="label">Items</span><span id="items-count">0</span></div>
                <div class="totals-row"><strong class="label">Total</strong><strong id="items-total">₱0.00</strong></div>
            </div>
        </footer>
    </div>
</div>

<!-- Receipt Modal  -->
<div id="receipt-modal" class="modal" style="display:none;">
    <div class="modal-card receipt-modal-card">
        <header class="modal-header">
            <h3>GCash Receipt</h3>
            <button id="close-receipt" class="modal-close">&times;</button>
        </header>

        <div class="modal-body" style="text-align:center;">
            <img id="receipt-image" style="width:100%; object-fit:contain; border-radius:8px;">
        </div>
    </div>
</div>
</div>


<script>
    (function() {
        const paymentFilter = document.getElementById('payment-filter');
        const customerSearch = document.getElementById('customer-search');
        const tableRows = document.querySelectorAll('#orders-tab tbody tr');

        function filterOrders() {
            const paymentValue = paymentFilter.value.toLowerCase();
            const searchValue = customerSearch.value.toLowerCase();

            tableRows.forEach(row => {
                const paymentMethod = row.cells[4].textContent.toLowerCase(); // Payment column
                const customerName = row.cells[2].textContent.toLowerCase(); // Customer Name column

                const paymentMatch = !paymentValue || paymentMethod === paymentValue;
                const searchMatch = !searchValue || customerName.includes(searchValue);

                if (paymentMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        paymentFilter.addEventListener('change', filterOrders);
        customerSearch.addEventListener('input', filterOrders);
    })();
</script>


<script>
    (function() {
        const modal = document.getElementById('receipt-modal');
        const img = document.getElementById('receipt-image');
        const closeBtn = document.getElementById('close-receipt');

        // Global onerror for real image load failures
        img.onerror = () => {
            alert('Receipt image could not be loaded.');
            img.src = 'receipts/no-receipt.png'; // fallback placeholder
        };

        // Open receipt modal
        document.querySelectorAll('.view-receipt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const src = btn.dataset.image || '';
                if (!src) {
                    alert('Receipt not found.');
                    return;
                }

                // Set image source (triggers onerror only if the file is actually missing)
                img.src = src;

                // Show modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        });

        // Close modal safely
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // DO NOT clear img.src here — prevents the false "could not be loaded" alert
        }

        // Close button
        closeBtn.addEventListener('click', closeModal);

        // Click outside modal content
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });
    })();
</script>


<script>
    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m];
        });
    }

    (function() {
        const itemsModal = document.getElementById('items-modal');
        const itemsList = document.getElementById('items-list');
        const closeBtn = document.getElementById('close-items-modal');
        const title = document.getElementById('items-modal-title');
        const countEl = document.getElementById('items-count');
        const totalEl = document.getElementById('items-total');

        function openModal() {
            itemsModal.classList.add('open');
            itemsModal.style.display = 'flex';
            itemsModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            itemsModal.classList.remove('open');
            itemsModal.style.display = 'none';
            itemsModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.view-items-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const raw = btn.getAttribute('data-items') || '[]';
                let items;
                try {
                    items = JSON.parse(raw);
                } catch (e) {
                    items = [];
                }
                itemsList.innerHTML = '';
                let total = 0;

                items.forEach(i => {
                    const qty = parseInt(i.quantity, 10) || 0;
                    const price = parseFloat(i.price) || 0;
                    const subtotal = qty * price;
                    total += subtotal;
                    const initials = (i.product_name || '').trim().charAt(0).toUpperCase() || '?';

                    itemsList.innerHTML += `
            <li class="item">
              <div class="thumb">${escapeHtml(initials)}</div>
              <div class="meta">
                <div class="name">${escapeHtml(i.product_name)}</div>
                <div class="meta-row">
                  <span class="qty">Qty: ${qty}</span>
                  <span class="price">₱${price.toFixed(2)}</span>
                </div>
              </div>
              <div class="subtotal">₱${subtotal.toFixed(2)}</div>
            </li>
          `;
                });

                title.textContent = 'Order Items';
                countEl.textContent = items.length;
                totalEl.textContent = '₱' + total.toFixed(2);
                openModal();
            });
        });

        closeBtn.addEventListener('click', closeModal);
        itemsModal.addEventListener('click', (e) => {
            if (e.target === itemsModal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && itemsModal.classList.contains('open')) closeModal();
        });
    })();
</script>



<!-- Manage Products Tab -->
<div id="products-tab" class="tab-content" style="display: none;">
    <?php include 'products_tab_content.php'; ?>
</div>

<!-- Reservations Tab -->
<div id="reservations-tab" class="tab-content" style="display: none;">
    <?php include 'reservations_tab_content.php'; ?>
</div>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        document.getElementById(tabId + '-tab').style.display = 'block';
        document.querySelectorAll('.tab-menu button').forEach(btn => btn.classList.remove('active-tab'));
        document.getElementById('tab-' + tabId).classList.add('active-tab');
    }
</script>
<script>
    // Update notification badge
    (function() {
        const BADGE_SELECTOR = '.notification-badge';
        const STATUS_URL = 'fetch_status_counts.php';
        const BADGE_URL = 'fetch_orders_count.php';
        let lastPending = <?= (int)($pendingOrders ?? 0) ?>;

        function setBadge(elem, count) {
            if (!elem) return;
            if (count > 0) {
                elem.style.display = 'inline-block';
                elem.textContent = count;
            } else {
                elem.style.display = 'none';
            }
        }

        function updateAll() {
            // fetch both endpoints in parallel
            Promise.all([
                fetch(BADGE_URL, {
                    cache: 'no-store'
                }).then(r => r.json()),
                fetch(STATUS_URL, {
                    cache: 'no-store'
                }).then(r => r.json())
            ]).then(([badgeData, statusData]) => {
                const badge = document.querySelector(BADGE_SELECTOR);
                const pending = parseInt(badgeData.pending, 10) || 0;
                setBadge(badge, pending);

                // update dashboard counters
                const map = {
                    'pending': 'pending-count',
                    'confirmed': 'confirmed-count',
                    'preparing': 'preparing-count',
                    'completed': 'completed-count',
                    'cancelled': 'cancel-count'
                };
                Object.keys(map).forEach(k => {
                    const el = document.getElementById(map[k]);
                    if (el && statusData[k] !== undefined) el.textContent = statusData[k];
                });

                if (pending > lastPending) {
                    if (badge) {
                        badge.classList.add('pulse');
                        setTimeout(() => badge.classList.remove('pulse'), 1400);
                    }
                }
                lastPending = pending;
            }).catch(err => console.error('updateAll failed', err));
        }

        updateAll();
        setInterval(updateAll, 3000);
    })();
</script>

<script>
    (function() {
        const scrollBtn = document.getElementById('scrollTopBtn');
        if (!scrollBtn) return;

        function getVisibleTab() {
            return document.querySelector('.tab-content:not([style*="display: none"])');
        }

        function updateVisibility() {
            const visibleTab = getVisibleTab();
            let scrollY = window.scrollY;
            if (visibleTab) scrollY = visibleTab.scrollTop;
            scrollBtn.style.display = scrollY > 200 ? 'flex' : 'none';
        }

        // Listen to scroll events
        window.addEventListener('scroll', updateVisibility);
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.addEventListener('scroll', updateVisibility);
        });

        // Scroll to top
        scrollBtn.addEventListener('click', () => {
            const visibleTab = getVisibleTab();
            if (visibleTab) visibleTab.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Initial check
        updateVisibility();
    })();
</script>

<?php include 'admin_footer.php'; ?>