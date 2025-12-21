<?php
require_once '../includes/db.php';

// Security check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if ID is not valid
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Handle form submission for updating the product
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $cost_price = (float)$_POST['cost_price'];
    $units_per_pack = (int)$_POST['units_per_pack'] > 0 ? (int)$_POST['units_per_pack'] : 1;

    if (!empty($name) && $quantity >= 0 && $price >= 0) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, quantity_in_stock = ?, price = ?, category_id = ?, cost_price = ?, units_per_pack = ? WHERE id = ?");
        $stmt->bind_param("ssididii", $name, $description, $quantity, $price, $category_id, $cost_price, $units_per_pack, $product_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Product updated successfully! <a href='products.php'>Go back to products list</a>.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating product.</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Please fill in all required fields correctly.</div>";
    }
}

// Fetch the product details to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    // If no product found with that ID, redirect
    header('Location: products.php');
    exit;
}
$stmt->close();

// Fetch all categories for the dropdown
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header">
                <h2>Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>
            </div>
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="0">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity in Stock</label>
                    <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($product['quantity_in_stock']); ?>" required min="0">
                </div>
                <div class="form-group">
                    <label for="cost_price">Pack Cost (₦)</label>
                    <input type="number" id="cost_price" name="cost_price" value="<?php echo htmlspecialchars($product['cost_price']); ?>" required min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="units_per_pack">Units per Pack</label>
                    <input type="number" id="units_per_pack" name="units_per_pack" value="<?php echo htmlspecialchars($product['units_per_pack']); ?>" required min="1">
                </div>
                <div class="form-group">
                    <label for="price">Selling Price per Unit (₦)</label>
                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required min="0" step="0.01">
                </div>
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="products.php" class="btn btn-danger" style="background-color: #535c68;">Cancel</a>
            </form>
        </div>
    </div>

</body>
</html>