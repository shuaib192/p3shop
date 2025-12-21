<?php
require_once '../includes/db.php';

// Security check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$admin_id = (int)$_SESSION['user_id'];

// === HANDLE ADD PRODUCT FORM ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    if (!empty($name) && $quantity >= 0 && $price >= 0) {
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $cost_price = (float)$_POST['cost_price'];
        $units_per_pack = (int)$_POST['units_per_pack'] > 0 ? (int)$_POST['units_per_pack'] : 1;
        
        // Insert the product
        $insert_product_sql = "INSERT INTO products (name, description, quantity_in_stock, price, category_id, cost_price, units_per_pack) VALUES ('{$name}', '{$description}', {$quantity}, {$price}, {$category_id}, {$cost_price}, {$units_per_pack})";
        
        if ($conn->query($insert_product_sql)) {
            // Get the ID of the new product we just created
            $new_product_id = $conn->insert_id;
            
            // Log this initial stock addition
            $reason = "Initial stock for new product";
            $log_stock_sql = "INSERT INTO stock_log (product_id, user_id, quantity_change, reason) VALUES ({$new_product_id}, {$admin_id}, {$quantity}, '{$reason}')";
            $conn->query($log_stock_sql);

            $message = "<div class='alert alert-success'>Product added and initial stock logged successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding product. Please check for duplicates or errors.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please fill in all required fields correctly.</div>";
    }
}

// === FETCH ALL PRODUCTS ===
$products = array();
$result = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// === FETCH ALL CATEGORIES ===
$categories = array();
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header"><h2>Add New Product</h2></div>
            <form action="products.php" method="POST">
                <div class="form-group"><label for="name">Product Name</label><input type="text" id="name" name="name" required></div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="0">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="description">Description (Optional)</label><textarea id="description" name="description" rows="3"></textarea></div>
                <div class="form-group"><label for="quantity">Initial Quantity in Stock</label><input type="number" id="quantity" name="quantity" required min="0"></div>
                <div class="grid-3">
                    <div class="form-group"><label for="cost_price">Pack Cost (₦)</label><input type="number" id="cost_price" name="cost_price" required min="0" step="0.01"></div>
                    <div class="form-group"><label for="units_per_pack">Units/Pack</label><input type="number" id="units_per_pack" name="units_per_pack" required min="1" value="1"></div>
                    <div class="form-group"><label for="price">Selling Price/Unit (₦)</label><input type="number" id="price" name="price" required min="0" step="0.01"></div>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header"><h2>Existing Products</h2></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Name</th><th>Category</th><th>Stock</th><th>Pack Cost</th><th>Unit Cost</th><th>Price/Unit</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="4" style="text-align: center;">No products found. Add one above to get started.</td></tr>
                        <?php else: foreach ($products as $product): ?>
                            <tr>
                                <td data-label="Name"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td data-label="Category"><?php echo $product['category_name'] ? htmlspecialchars($product['category_name']) : 'N/A'; ?></td>
                                <td data-label="Stock"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                                <td data-label="Pack Cost">₦<?php echo htmlspecialchars(number_format($product['cost_price'], 2)); ?> (x<?php echo $product['units_per_pack']; ?>)</td>
                                <td data-label="Unit Cost">₦<?php echo htmlspecialchars(number_format($product['cost_price'] / $product['units_per_pack'], 2)); ?></td>
                                <td data-label="Price">₦<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                <td data-label="Actions" class="table-actions">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-info">Edit</a>
                                    <a href="../update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">Stock</a>
                                    <!-- This delete button will be handled in a separate file -->
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>