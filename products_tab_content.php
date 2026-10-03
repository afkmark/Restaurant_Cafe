<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ================= Handle Add Product =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = floatval($_POST['price']);
    $available = (int)($_POST['available'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $image_name = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['image']['tmp_name'];
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($tmp_name, 'assets/' . $image_name);
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, available, status, image, sold) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$name, $category, $price, $available, $status, $image_name]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ================= Handle AJAX Update/Delete =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success' => false, 'message' => 'Unknown error'];
    $id = (int)($_POST['product_id'] ?? 0);

    if ($id > 0) {
        // ===== Update =====
        if (isset($_POST['update'])) {
            $name = $_POST['name'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = floatval($_POST['price']);
            $available = (int)($_POST['available'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            $image_name = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['image']['tmp_name'];
                $image_name = time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($tmp_name, 'assets/' . $image_name);
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, available=?, status=?, image=? WHERE id=?");
                $stmt->execute([$name, $category, $price, $available, $status, $image_name, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, available=?, status=? WHERE id=?");
                $stmt->execute([$name, $category, $price, $available, $status, $id]);
            }

            $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'product' => $product];
        }

        // ===== Delete =====
        if (isset($_POST['delete'])) {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id=?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product && !empty($product['image']) && file_exists('assets/' . $product['image'])) {
                unlink('assets/' . $product['image']);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'deleted' => true, 'id' => $id];
        }
    } else {
        $response['message'] = 'Invalid product ID';
    }

    echo json_encode($response);
    exit;
}

// ================= Fetch all products =================
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h3 {
            margin-bottom: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field.full-width {
            grid-column: span 2;
        }

        .form-control {
            padding: 5px;
        }

        .btn {
            padding: 5px 10px;
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }

        .delete-button {
            background: #f44336;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        table th {
            background-color: #f2f2f2;
        }

        img {
            max-height: 50px;
        }

        /* Modal styles */
        #editModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10;
        }

        #editModal .modal-content {
            background: #fff;
            padding: 20px;
            margin: 50px auto;
            width: 400px;
            position: relative;
            border-radius: 5px;
        }

        #editModal h3 {
            margin-top: 0;
        }

        #editModal label {
            display: block;
            margin: 10px 0;
        }

        #editModal input,
        #editModal select {
            width: 100%;
            padding: 5px;
        }

        #currentImageContainer img {
            max-height: 80px;
            display: block;
            margin: 5px 0;
        }

        #editModal button {
            margin: 5px 5px 0 0;
            padding: 5px 10px;
        }
    </style>
</head>

<body>

    <div class="products-panel">
        <h3>Add Product</h3>
        <form method="POST" enctype="multipart/form-data" class="add-product-form">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-field"><label>Name: <input class="form-control" type="text" name="name" required></label></div>
                <div class="form-field"><label>Category: <input class="form-control" type="text" name="category"></label></div>
                <div class="form-field"><label>Price: <input class="form-control" type="number" step="0.01" name="price" required></label></div>
                <div class="form-field"><label>Image: <input class="form-control" type="file" name="image" accept="image/*"></label></div>
                <div class="form-field">
                    <label>Status:
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>
                </div>
                <div class="form-field"><label>Available: <input class="form-control" type="number" name="available" value="0"></label></div>
                <div class="form-field.full-width"><button type="submit" class="btn">Add Product</button></div>
            </div>
        </form>

        <h3>Products List</h3>
        <table id="productsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Sold</th>
                    <th>Status</th>
                    <th>Available</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr id="row-<?= $p['id'] ?>">
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td><?php if ($p['image']): ?><img src="assets/<?= htmlspecialchars($p['image']) ?>" alt=""><?php endif; ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td>₱<?= number_format($p['price'], 2) ?></td>
                        <td><?= htmlspecialchars($p['sold']) ?></td>
                        <td><?= htmlspecialchars($p['status']) ?></td>
                        <td><?= htmlspecialchars($p['available']) ?></td>
                        <td>
                            <button class="btn edit-btn"
                                data-id="<?= $p['id'] ?>"
                                data-name="<?= htmlspecialchars($p['name']) ?>"
                                data-category="<?= htmlspecialchars($p['category']) ?>"
                                data-price="<?= $p['price'] ?>"
                                data-available="<?= $p['available'] ?>"
                                data-status="<?= $p['status'] ?>"
                                data-image="<?= $p['image'] ?>">Edit</button>
                            <button class="delete-btn btn delete-button" data-id="<?= $p['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="editModal">
        <div class="modal-content">
            <h3>Edit Product</h3>
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_id">
                <label>Name: <input type="text" name="name" id="edit_name" required></label>
                <label>Category: <input type="text" name="category" id="edit_category"></label>
                <label>Price: <input type="number" step="0.01" name="price" id="edit_price" required></label>
                <label>Available: <input type="number" name="available" id="edit_available"></label>
                <label>Status:
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <div id="currentImageContainer"></div>
                <label>Change Image: <input type="file" name="image" accept="image/*"></label><br><br>
                <button type="submit">Update</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const currentImageContainer = document.getElementById('currentImageContainer');

        function closeModal() {
            modal.style.display = 'none';
        }

        function attachRowEvents(row) {
            // Edit
            row.querySelector('.edit-btn')?.addEventListener('click', e => {
                const btn = e.target;
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_category').value = btn.dataset.category;
                document.getElementById('edit_price').value = btn.dataset.price;
                document.getElementById('edit_available').value = btn.dataset.available;
                document.getElementById('edit_status').value = btn.dataset.status;
                currentImageContainer.innerHTML = btn.dataset.image ? `<img src="assets/${btn.dataset.image}" alt="Current Image">` : '';
                modal.style.display = 'block';
            });

            // Delete
            row.querySelector('.delete-btn')?.addEventListener('click', e => {
                if (!confirm('Delete this product?')) return;
                const id = e.target.dataset.id;
                const formData = new FormData();
                formData.append('ajax', true);
                formData.append('delete', true);
                formData.append('product_id', id);

                fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.deleted) {
                            row.remove();
                        } else alert('Delete failed');
                    });
            });
        }

        // Attach events to existing rows
        document.querySelectorAll('#productsTable tbody tr').forEach(row => attachRowEvents(row));

        // AJAX update
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', true);
            formData.append('update', true);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const p = data.product;
                        const row = document.getElementById('row-' + p.id);
                        row.innerHTML = `
                <td>${p.id}</td>
                <td>${p.image? `<img src="assets/${p.image}">` : ''}</td>
                <td>${p.name}</td>
                <td>${p.category}</td>
                <td>₱${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.sold}</td>
                <td>${p.status}</td>
                <td>${p.available}</td>
                <td>
                    <button class="btn edit-btn" data-id="${p.id}" data-name="${p.name}" data-category="${p.category}" data-price="${p.price}" data-available="${p.available}" data-status="${p.status}" data-image="${p.image}">Edit</button>
                    <button class="delete-btn btn delete-button" data-id="${p.id}">Delete</button>
                </td>
            `;
                        attachRowEvents(row);
                        closeModal();
                    } else alert(data.message || 'Update failed');
                });
        });

        window.onclick = e => {
            if (e.target == modal) closeModal();
        };
    </script>

</body>

</html>