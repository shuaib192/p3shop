<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$staff_id = (int)$_SESSION['user_id'];
$today_date = date('Y-m-d');

// === HANDLE SALE (ULTRA-COMPATIBLE, SAFE VERSION) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_sale'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity_sold = isset($_POST['quantity_sold']) ? (int)$_POST['quantity_sold'] : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if ($product_id > 0 && $quantity_sold > 0 && !empty($payment_method)) {
        // Get product details
        $product_sql = "SELECT * FROM products WHERE id = " . $product_id;
        $product_result = $conn->query($product_sql);
        $product = $product_result->fetch_assoc();

        if ($product && (int)$product['quantity_in_stock'] >= $quantity_sold) {
            $new_stock = (int)$product['quantity_in_stock'] - $quantity_sold;
            $total_price = (float)$product['price'] * $quantity_sold;
            $unit_cost = (float)$product['cost_price'] / (int)$product['units_per_pack'];
            $cost_price = $unit_cost * $quantity_sold;
            $safe_payment_method = mysqli_real_escape_string($conn, $payment_method);

            // Update product stock
            $conn->query("UPDATE products SET quantity_in_stock = " . $new_stock . " WHERE id = " . $product_id);

            // Insert sale record
            $insert_sql = "INSERT INTO sales (product_id, user_id, quantity_sold, total_price, cost_price, sale_date, payment_method) 
                           VALUES (" . $product_id . ", " . $staff_id . ", " . $quantity_sold . ", " . $total_price . ", " . $cost_price . ", '" . $today_date . "', '" . $safe_payment_method . "')";
            
            if ($conn->query($insert_sql)) {
                $message = "<div class='alert alert-success'>Sale recorded successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error: Could not save the sale.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Sale failed. Not enough stock available.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please select a product, quantity, and payment method.</div>";
    }
}

// === FETCH DATA (ULTRA-COMPATIBLE VERSION) ===
$products_in_stock = array();
$result_products = $conn->query("SELECT p.id, p.name, p.quantity_in_stock, p.price, c.name as category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                WHERE p.quantity_in_stock > 0 
                                ORDER BY c.name ASC, p.name ASC");
if ($result_products) { 
    while ($row = $result_products->fetch_assoc()) { 
        $cat = $row['category_name'] ? $row['category_name'] : 'Others';
        $products_in_stock[$cat][] = $row; 
    } 
}

$todays_sales = array();
$sales_sql = "SELECT p.name, s.quantity_sold, s.total_price, s.payment_method 
              FROM sales s 
              JOIN products p ON s.product_id = p.id 
              WHERE s.user_id = " . $staff_id . " AND s.sale_date = '" . $today_date . "' 
              ORDER BY s.id DESC";
$sales_result = $conn->query($sales_sql);
if ($sales_result) { while ($row = $sales_result->fetch_assoc()) { $todays_sales[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <i class="ri-store-2-fill"></i>
            <h1>P3 Shop <span>Pro</span></h1>
        </div>
        <div class="header-user">
            <span><i class="ri-user-smile-line"></i> <?php echo htmlspecialchars($_SESSION['username']); ?> (Staff)</span>
            <a href="../logout.php" class="logout-btn"><i class="ri-logout-box-r-line"></i> Logout</a>
        </div>
    </div>
    <div class="container">
        <?php echo $message; ?>
        
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h2><i class="ri-shopping-cart-2-line"></i> Process New Sale</h2>
            </div>
            
            <form action="index.php" method="POST" id="sale-form">
                <input type="hidden" id="product_id" name="product_id" required>
                
                <div class="form-group">
                    <label>Product Category</label>
                    <select id="category-select" style="font-size: 1.1rem; padding: 1rem;">
                        <option value="all">-- All Categories --</option>
                        <?php foreach (array_keys($products_in_stock) as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Product</label>
                    <select id="product-select" style="font-size: 1.1rem; padding: 1rem;">
                        <option value="">-- Choose Category First --</option>
                        <?php foreach ($products_in_stock as $category => $items): ?>
                            <?php foreach ($items as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-category="<?php echo htmlspecialchars($category); ?>" 
                                        data-price="<?php echo $product['price']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-stock="<?php echo $product['quantity_in_stock']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['quantity_in_stock']; ?>) - ₦<?php echo number_format($product['price'], 0); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" id="quantity_sold" name="quantity_sold" required min="1" value="1" style="font-size: 1.2rem; padding: 1rem;">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" required style="font-size: 1.1rem; padding: 1rem;">
                            <option value="Cash">Cash</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                </div>

                <div class="live-total-display" style="text-align: center; margin-bottom: 1.5rem;">
                    <p style="font-size: 0.9rem; color: var(--text-muted);">TOTAL PRICE</p>
                    <span id="live-total">₦0.00</span>
                </div>

                <button type="submit" name="process_sale" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1.3rem; padding: 1.2rem;">
                    <i class="ri-check-double-line"></i> Complete Sale
                </button>
            </form>
        </div>

        <div class="card" style="margin-top: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            <div class="card-header"><h3>Recent Sales Today</h3></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php if (empty($todays_sales)): ?>
                            <tr><td colspan="3" style="text-align: center;">No sales yet</td></tr>
                        <?php else: foreach (array_slice($todays_sales, 0, 5) as $sale): ?>
                            <tr>
                                <td data-label="Product"><?php echo htmlspecialchars($sale['name']); ?></td>
                                <td data-label="Qty"><?php echo $sale['quantity_sold']; ?></td>
                                <td data-label="Total">₦<?php echo number_format($sale['total_price'], 0); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const categorySelect = document.getElementById('category-select');
        const productSelect = document.getElementById('product-select');
        const productIdInput = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity_sold');
        const totalDisplay = document.getElementById('live-total');
        
        const allProductOptions = Array.from(productSelect.options).slice(1); // Keep references to all options

        function updateProductList() {
            const selectedCat = categorySelect.value;
            productSelect.innerHTML = '<option value="">-- Select Product --</option>';
            
            allProductOptions.forEach(opt => {
                if (selectedCat === 'all' || opt.dataset.category === selectedCat) {
                    productSelect.appendChild(opt.cloneNode(true));
                }
            });
            
            calculateTotal();
        }

        function calculateTotal() {
            const selectedOpt = productSelect.options[productSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                productIdInput.value = selectedOpt.value;
                const price = parseFloat(selectedOpt.dataset.price);
                const qty = parseInt(quantityInput.value) || 0;
                const total = price * qty;
                totalDisplay.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
            } else {
                productIdInput.value = '';
                totalDisplay.textContent = '₦0.00';
            }
        }

        categorySelect.addEventListener('change', updateProductList);
        productSelect.addEventListener('change', calculateTotal);
        quantityInput.addEventListener('input', calculateTotal);
        
        // Initial load
        updateProductList();
    </script>
</body>
</html>