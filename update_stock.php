<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) { header('Location: products.php'); exit; }

// === HANDLE STOCK UPDATE FORM ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity_change = (int)$_POST['quantity_change'];
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));
    $admin_id = (int)$_SESSION['user_id'];

    if ($quantity_change != 0 && !empty($reason)) {
        // Get current stock
        $result = $conn->query("SELECT quantity_in_stock FROM products WHERE id = " . $product_id);
        $product = $result->fetch_assoc();
        $current_stock = (int)$product['quantity_in_stock'];

        $new_stock = $current_stock + $quantity_change;

        // Update product stock
        $conn->query("UPDATE products SET quantity_in_stock = " . $new_stock . " WHERE id = " . $product_id);

        // Log the change
        $conn->query("INSERT INTO stock_log (product_id, user_id, quantity_change, reason) VALUES ({$product_id}, {$admin_id}, {$quantity_change}, '{$reason}')");
        
        $message = "<div class='alert alert-success'>Stock updated successfully! <a href='products.php'>Return to products list.</a></div>";
    } else {
        $message = "<div class='alert alert-danger'>Please enter a quantity and a reason.</div>";
    }
}

// Fetch product details
$result = $conn->query("SELECT * FROM products WHERE id = " . $product_id);
$product = $result->fetch_assoc();
if (!$product) { header('Location: products.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Update Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <i class="ri-store-2-fill"></i>
            <h1>P3 Shop <span>Pro</span></h1>
        </div>
        <div class="header-user">
            <span><i class="ri-user-smile-line"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="logout-btn"><i class="ri-logout-box-r-line"></i> Logout</a>
        </div>
    </div>
    <div class="main-nav">
        <ul>
            <li><a href="admin/index.php"><i class="ri-dashboard-line"></i> Dashboard</a></li>
            <li><a href="admin/products.php" class="active"><i class="ri-shopping-basket-2-line"></i> Products</a></li>
        </ul>
    </div>
    <div class="container">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header"><h2>Update Stock for: <?php echo htmlspecialchars($product['name']); ?></h2></div>
            <p style="text-align: center; font-size: 1.5em; margin: 10px 0;">Current Stock: <strong><?php echo $product['quantity_in_stock']; ?></strong></p>
            <form action="update_stock.php?id=<?php echo $product_id; ?>" method="POST">
                <div class="form-group">
                    <label for="quantity_change">Quantity to Add/Remove</label>
                    <input type="number" id="quantity_change" name="quantity_change" required placeholder="e.g., 50 to add, -10 to remove">
                </div>
                <div class="form-group">
                    <label for="reason">Reason for Change</label>
                    <input type="text" id="reason" name="reason" required placeholder="e.g., New shipment, Damaged goods">
                </div>
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </form>
        </div>
    </div>
</body>
</html>