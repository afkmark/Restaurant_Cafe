<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Daily Sales from completed orders
$sales = $pdo->query("
    SELECT DATE(created_at) AS date, SUM(total_amount) AS total 
    FROM orders 
    WHERE status = 'completed' 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$salesLabels = array_column($sales, 'date');
$salesData = array_column($sales, 'total');

// Top 5 selling products based on order_items
$topProducts = $pdo->query("
    SELECT product_name, SUM(quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY product_name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$productLabels = array_column($topProducts, 'product_name');
$productData = array_column($topProducts, 'total_sold');

?>

<?php include 'admin_header.php'; ?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>Analytics</h1>
            <p class="subtitle">Sales and Product Trends</p>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 2rem;">
        <!-- Daily Sales Chart -->
        <div style="flex: 1; min-width: 400px; background: #fff; padding: 1rem; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <h3 style="color: #000;">📈 Daily Sales</h3>
            <canvas id="salesChart" height="150"></canvas>
        </div>

        <!-- Top Selling Products Chart -->
        <div style="flex: 1; min-width: 400px; background: #fff; padding: 1rem; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <h3 style="color: #000;">🔥 Top Selling Products</h3>
            <canvas id="topProductsChart" height="150"></canvas>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($salesLabels) ?>,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: <?= json_encode($salesData) ?>,
                backgroundColor: 'rgba(255, 215, 0, 0.2)',
                borderColor: '#000',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales in ₱'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });

    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($productLabels) ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?= json_encode($productData) ?>,
                backgroundColor: '#FFD700',
                borderColor: '#000',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity Sold'
                    }
                }
            }
        }
    });
</script>

<?php include 'admin_footer.php'; ?>