<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$message = '';
$admin_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $cost_price = (float)$_POST['cost_price'];
    $units_per_pack = (int)$_POST['units_per_pack'] > 0 ? (int)$_POST['units_per_pack'] : 1;

    if (!empty($name) && $quantity >= 0 && $price >= 0) {
        $sql = "INSERT INTO products (name, description, quantity_in_stock, price, category_id, cost_price, units_per_pack) VALUES ('$name', '$description', $quantity, $price, $category_id, $cost_price, $units_per_pack)";
        if ($conn->query($sql)) {
            $pid = $conn->insert_id;
            $conn->query("INSERT INTO stock_log (product_id, user_id, quantity_change, reason) VALUES ($pid, $admin_id, $quantity, 'Initial stock for new product')");
            $_SESSION['msg'] = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Product added successfully!</div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Error adding product.</div>';
        }
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Please fill all required fields.</div>';
    }
    header('Location: products.php');
    exit;
}

$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$products = [];
$res = $conn->query("SELECT p.*, c.name as category_name,
    (SELECT SUM(quantity_sold) FROM sales WHERE product_id=p.id AND status='recorded') as total_sold,
    (SELECT SUM(total_price) FROM sales WHERE product_id=p.id AND status='recorded') as total_rev
    FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.name ASC");
while ($r = $res->fetch_assoc()) $products[] = $r;

$categories = [];
$cr = $conn->query("SELECT * FROM categories ORDER BY name ASC");
while ($r = $cr->fetch_assoc()) $categories[] = $r;

$cat_filter = $_GET['cat'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — P3 Shop Pro</title>
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
                <div class="page-title">Product Management</div>
                <div class="page-subtitle"><?= count($products) ?> products</div>
            </div>
        </div>
        <div class="topbar-right">
            <button onclick="document.getElementById('addModal').classList.add('open')" class="btn btn-primary btn-sm">
                <i class="ri-add-line"></i> New Product
            </button>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <!-- Products Table -->
        <div class="card">
            <div class="table-tools">
                <div class="search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search products..." id="productSearch" onkeyup="filterProducts()">
                </div>
                <select class="form-control" style="width:auto;padding:0.4rem 0.75rem;" id="catFilter" onchange="filterProducts()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="table-count" id="productCount"><?= count($products) ?> products</div>
            </div>
            <div class="table-container">
                <table class="data-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Pack Cost</th>
                            <th>Unit Cost</th>
                            <th>Price/Unit</th>
                            <th>Margin</th>
                            <th>Total Sold</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p):
                        $unit_cost = $p['units_per_pack'] > 0 ? $p['cost_price'] / $p['units_per_pack'] : 0;
                        $margin = $p['price'] > 0 ? (($p['price'] - $unit_cost) / $p['price'] * 100) : 0;
                        $stk = (int)$p['quantity_in_stock'];
                        $stk_cls = $stk == 0 ? 'danger' : ($stk < 5 ? 'warning' : ($stk < 10 ? 'info' : 'success'));
                        $stk_lbl = $stk == 0 ? 'Out' : ($stk < 5 ? 'Critical' : ($stk < 10 ? 'Low' : 'Good'));
                    ?>
                    <tr data-cat="<?= htmlspecialchars($p['category_name']?:'') ?>">
                        <td data-label="Product">
                            <div style="font-weight:700;"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if ($p['description']): ?>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?= htmlspecialchars($p['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Category"><span class="badge badge-neutral"><?= htmlspecialchars($p['category_name']?:'N/A') ?></span></td>
                        <td data-label="Stock">
                            <span class="badge badge-<?= $stk_cls ?>"><?= $stk ?> — <?= $stk_lbl ?></span>
                        </td>
                        <td data-label="Pack Cost">₦<?= number_format($p['cost_price'],0) ?> <span style="color:var(--text-muted);font-size:0.72rem;">(x<?= $p['units_per_pack'] ?>)</span></td>
                        <td data-label="Unit Cost" class="num">₦<?= number_format($unit_cost,2) ?></td>
                        <td data-label="Price" class="num fw-700">₦<?= number_format($p['price'],2) ?></td>
                        <td data-label="Margin">
                            <span class="<?= $margin >= 25 ? 'text-success' : ($margin >= 10 ? 'text-primary' : 'text-danger') ?> fw-700">
                                <?= number_format($margin,1) ?>%
                            </span>
                        </td>
                        <td data-label="Sold"><?= number_format($p['total_sold']??0) ?></td>
                        <td data-label="Revenue" class="money">₦<?= number_format($p['total_rev']??0,0) ?></td>
                        <td data-label="Actions">
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm"><i class="ri-pencil-line"></i></a>
                                <a href="update_stock.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm"><i class="ri-add-circle-line"></i></a>
                                <a href="delete_product.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product permanently?')"><i class="ri-delete-bin-line"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Add Product Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal" style="max-width:540px;">
        <div class="modal-header" style="padding:1rem 1.25rem;">
            <h3 style="font-size:0.95rem;"><i class="ri-add-circle-line" style="color:var(--primary)"></i> Add New Product</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')"><i class="ri-close-line"></i></button>
        </div>
        <form method="POST" action="products.php">
            <div class="modal-body" style="padding:1rem;">
                <!-- Row 1: Name + Category -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Product Name *</label>
                        <input type="text" name="name" class="form-control" style="padding:0.5rem 0.75rem;" required placeholder="e.g. Coca-Cola 50cl">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Category</label>
                        <select name="category_id" class="form-control" style="padding:0.5rem 0.75rem;">
                            <option value="0">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Row 2: Pack Cost + Units/Pack + Sell Price -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Pack Cost (₦) *</label>
                        <input type="number" name="cost_price" class="form-control" style="padding:0.5rem 0.75rem;" required min="0" step="0.01" placeholder="0.00" id="packCostInput">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Units/Pack *</label>
                        <input type="number" name="units_per_pack" class="form-control" style="padding:0.5rem 0.75rem;" required min="1" value="1" id="unitsPackInput">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Sell Price/Unit (₦) *</label>
                        <input type="number" name="price" class="form-control" style="padding:0.5rem 0.75rem;" required min="0" step="0.01" placeholder="0.00" id="sellPriceInput">
                    </div>
                </div>
                <!-- Row 3: Qty + Description -->
                <div style="display:grid;grid-template-columns:120px 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Initial Qty *</label>
                        <input type="number" name="quantity" class="form-control" style="padding:0.5rem 0.75rem;" required min="0" placeholder="0">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.75rem;">Description</label>
                        <input type="text" name="description" class="form-control" style="padding:0.5rem 0.75rem;" placeholder="Optional">
                    </div>
                </div>
                <!-- Margin Preview -->
                <div style="background:var(--surface-2);padding:0.5rem 0.75rem;border-radius:var(--radius-sm);display:flex;justify-content:space-between;font-size:0.8rem;">
                    <span style="color:var(--text-muted);">Estimated Margin:</span>
                    <strong id="marginCalc" style="color:var(--success)">—</strong>
                </div>
            </div>
            <div class="modal-footer" style="padding:0.75rem 1rem;">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" name="add_product" class="btn btn-primary btn-sm"><i class="ri-check-line"></i> Add Product</button>
            </div>
        </form>
    </div>
</div>


<script>
function filterProducts() {
    const q = document.getElementById('productSearch').value.toLowerCase();
    const cat = document.getElementById('catFilter').value;
    let visible = 0;
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        const matchQ = row.textContent.toLowerCase().includes(q);
        const matchC = !cat || row.dataset.cat === cat;
        row.style.display = (matchQ && matchC) ? '' : 'none';
        if (matchQ && matchC) visible++;
    });
    document.getElementById('productCount').textContent = visible + ' products';
}

// Live margin calculator
['packCostInput','unitsPackInput','sellPriceInput'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', calcMargin);
});
function calcMargin() {
    const pack = parseFloat(document.getElementById('packCostInput').value) || 0;
    const units = parseInt(document.getElementById('unitsPackInput').value) || 1;
    const price = parseFloat(document.getElementById('sellPriceInput').value) || 0;
    const unitCost = pack / units;
    if (price > 0) {
        const margin = ((price - unitCost) / price * 100).toFixed(1);
        const el = document.getElementById('marginCalc');
        el.textContent = margin + '% (Unit cost: ₦' + unitCost.toFixed(2) + ')';
        el.style.color = margin >= 25 ? 'var(--success)' : (margin >= 10 ? 'var(--primary)' : 'var(--danger)');
    }
}
</script>
</body>
</html>