<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $date_added = $_POST['date_added'];
    $quantity = $_POST['quantity'];
    $type = $_POST['type'];

    $stmt = $conn->prepare("INSERT INTO products (product_name, date_added, quantity, type) VALUES (:product_name, :date_added, :quantity, :type)");
    $stmt->execute([
        ':product_name' => $product_name,
        ':date_added' => $date_added,
        ':quantity' => $quantity,
        ':type' => $type
    ]);
}

// Modify Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modify_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $date_added = $_POST['date_added'];
    $quantity = $_POST['quantity'];
    $type = $_POST['type'];

    $stmt = $conn->prepare("UPDATE products SET product_name = :product_name, date_added = :date_added, quantity = :quantity, type = :type WHERE id = :id");
    $stmt->execute([
        ':product_name' => $product_name,
        ':date_added' => $date_added,
        ':quantity' => $quantity,
        ':type' => $type,
        ':id' => $product_id
    ]);
}

// Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
}

// Search Products
$search_term = '';
$products = [];
if (isset($_POST['search']) && $_POST['search_term'] !== '') {
    $search_term = $_POST['search_term'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_name LIKE :search_term OR type LIKE :search_term");
    $stmt->execute([':search_term' => "%$search_term%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product types and their percentages for the chart
$stmt = $conn->query("SELECT type, SUM(quantity) as total_quantity FROM products GROUP BY type");
$product_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_quantity = array_sum(array_column($product_types, 'total_quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .wrapper { width: 80%; margin: 50px auto; background: #fff; padding: 20px; border-radius: 8px; }
        h1 { text-align: center; color: #333; }
        .tabs { text-align: center; margin-bottom: 20px; }
        .tab-button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .tab-button:hover { background: #45a049; }
        .tab-content { display: none; }
        .active-tab { display: block; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f2f2f2; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        button:hover { opacity: 0.9; }
        #modify-product-modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="wrapper">
    <h1>Stock Management Dashboard</h1>
    <div class="tabs">
        <button class="tab-button" onclick="showTab('add-product')">Add Product</button>
        <button class="tab-button" onclick="showTab('search-products')">Search Products</button>
        <button class="tab-button" onclick="showTab('product-list')">Product List</button>
        <button class="tab-button" onclick="showTab('product-chart')">Product Type Chart</button>
    </div>

    <div id="add-product" class="tab-content active-tab">
        <form action="stock.php" method="POST">
            <h3>Add Product</h3>
            <input type="text" name="product_name" placeholder="Product Name" required>
            <input type="date" name="date_added" required>
            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="text" name="type" placeholder="Product Type" required>
            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>

    <div id="search-products" class="tab-content">
        <form action="stock.php" method="POST">
            <h3>Search Products</h3>
            <input type="text" name="search_term" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search by name or type">
            <button type="submit" name="search">Search</button>
        </form>
    </div>

    <div id="product-list" class="tab-content">
        <h3>Products List</h3>
        <table>
            <thead>
            <tr>
                <th>Product Name</th>
                <th>Date Added</th>
                <th>Quantity</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                    <td><?= htmlspecialchars($product['date_added']) ?></td>
                    <td><?= htmlspecialchars($product['quantity']) ?></td>
                    <td><?= htmlspecialchars($product['type']) ?></td>
                    <td>
                        <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">Modify</button>
                        <form action="stock.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" name="delete_product">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="product-chart" class="tab-content">
        <h3>Product Type Distribution</h3>
        <canvas id="typeChart"></canvas>
    </div>
</div>

<div id="modify-product-modal">
    <form action="stock.php" method="POST">
        <h3>Modify Product</h3>
        <input type="hidden" name="product_id" id="edit-product-id">
        <input type="text" name="product_name" id="edit-product-name" placeholder="Product Name" required>
        <input type="date" name="date_added" id="edit-date-added" required>
        <input type="number" name="quantity" id="edit-quantity" placeholder="Quantity" required>
        <input type="text" name="type" id="edit-type" placeholder="Product Type" required>
        <button type="submit" name="modify_product">Save Changes</button>
        <button type="button" onclick="closeEditModal()">Cancel</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active-tab'));
        document.getElementById(tabId).classList.add('active-tab');
    }

    function editProduct(product) {
        document.getElementById('edit-product-id').value = product.id;
        document.getElementById('edit-product-name').value = product.product_name;
        document.getElementById('edit-date-added').value = product.date_added;
        document.getElementById('edit-quantity').value = product.quantity;
        document.getElementById('edit-type').value = product.type;
        document.getElementById('modify-product-modal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('modify-product-modal').style.display = 'none';
    }

    const ctx = document.getElementById('typeChart').getContext('2d');
    const chartData = {
        labels: <?= json_encode(array_column($product_types, 'type')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($product_types, 'total_quantity')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#FF9F40']
        }]
    };
    new Chart(ctx, { type: 'pie', data: chartData });
</script>

</body>
</html>
