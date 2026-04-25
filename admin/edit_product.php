<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) { header('Location: products.php'); exit; }

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
            $_SESSION['msg'] = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Product updated! <a href="products.php" style="color:inherit;text-decoration:underline;">Back to products</a></div>';
            header("Location: edit_product.php?id=$product_id");
            exit;
        } else {
            $message = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Error updating product.</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Please fill all fields correctly.</div>';
    }
}

if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { header('Location: products.php'); exit; }
$stmt->close();

$categories = [];
$cr = $conn->query("SELECT * FROM categories ORDER BY name ASC");
while ($r = $cr->fetch_assoc()) $categories[] = $r;

$unit_cost = $product['units_per_pack'] > 0 ? $product['cost_price'] / $product['units_per_pack'] : 0;
$margin = $product['price'] > 0 ? (($product['price'] - $unit_cost) / $product['price'] * 100) : 0;

// Stock history
$history = [];
$hr = $conn->query("SELECT sl.*, u.username FROM stock_log sl JOIN users u ON sl.user_id=u.id WHERE sl.product_id=$product_id ORDER BY sl.log_timestamp DESC LIMIT 8");
while ($r = $hr->fetch_assoc()) $history[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div>
                <div class="page-title">Edit Product</div>
                <div class="page-subtitle"><?= htmlspecialchars($product['name']) ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="products.php" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <div class="grid-2">
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-pencil-line"></i> Product Details</h3></div>
                <form action="edit_product.php?id=<?= $product_id ?>" method="POST">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control">
                                <option value="0">Uncategorized</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id']==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity in Stock</label>
                            <input type="number" name="quantity" class="form-control" value="<?= $product['quantity_in_stock'] ?>" required min="0">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Pack Cost (₦)</label>
                                <input type="number" name="cost_price" class="form-control" value="<?= $product['cost_price'] ?>" required min="0" step="0.01" id="editPackCost">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Units/Pack</label>
                                <input type="number" name="units_per_pack" class="form-control" value="<?= $product['units_per_pack'] ?>" required min="1" id="editUnits">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price/Unit (₦)</label>
                                <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required min="0" step="0.01" id="editPrice">
                            </div>
                        </div>
                        <div style="background:var(--surface-2);padding:0.75rem 1rem;border-radius:var(--radius-sm);display:flex;justify-content:space-between;font-size:0.85rem;">
                            <span>Profit Margin:</span>
                            <strong id="editMargin" style="color:<?= $margin>=25?'var(--success)':'var(--danger)' ?>"><?= number_format($margin,1) ?>%</strong>
                        </div>
                    </div>
                    <div class="card-footer" style="display:flex;gap:0.75rem;">
                        <button type="submit" class="btn btn-primary"><i class="ri-check-line"></i> Save Changes</button>
                        <a href="products.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Product Info & History -->
            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <!-- Quick Info -->
                <div class="card">
                    <div class="card-header"><h3><i class="ri-information-line"></i> Quick Info</h3></div>
                    <div class="card-body">
                        <div class="kpi-grid" style="grid-template-columns:1fr 1fr;">
                            <div class="kpi-card success" style="padding:1rem;">
                                <div class="kpi-label" style="font-size:0.65rem;">Unit Cost</div>
                                <div class="kpi-value" style="font-size:1.2rem;">₦<?= number_format($unit_cost,2) ?></div>
                            </div>
                            <div class="kpi-card primary" style="padding:1rem;">
                                <div class="kpi-label" style="font-size:0.65rem;">Sell Price</div>
                                <div class="kpi-value" style="font-size:1.2rem;">₦<?= number_format($product['price'],2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock History -->
                <div class="card">
                    <div class="card-header"><h3><i class="ri-history-line"></i> Stock History</h3></div>
                    <?php if (empty($history)): ?>
                        <div style="padding:1.5rem;text-align:center;color:var(--text-muted);">No stock changes yet.</div>
                    <?php else: ?>
                    <div class="activity-feed">
                        <?php foreach ($history as $h): ?>
                        <div class="activity-item">
                            <div class="activity-dot stock"><i class="ri-archive-line"></i></div>
                            <div class="activity-content">
                                <div class="activity-title"><?= $h['quantity_change'] > 0 ? '+' : '' ?><?= $h['quantity_change'] ?> units</div>
                                <div class="activity-meta"><?= htmlspecialchars($h['reason']) ?> • <?= $h['username'] ?> • <?= date('d M', strtotime($h['log_timestamp'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
['editPackCost','editUnits','editPrice'].forEach(id => document.getElementById(id)?.addEventListener('input', () => {
    const pack = parseFloat(document.getElementById('editPackCost').value) || 0;
    const units = parseInt(document.getElementById('editUnits').value) || 1;
    const price = parseFloat(document.getElementById('editPrice').value) || 0;
    const uc = pack / units;
    if (price > 0) {
        const m = ((price - uc) / price * 100).toFixed(1);
        const el = document.getElementById('editMargin');
        el.textContent = m + '%';
        el.style.color = m >= 25 ? 'var(--success)' : (m >= 10 ? 'var(--primary)' : 'var(--danger)');
    }
}));
</script>
</body>
</html>