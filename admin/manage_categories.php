<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        if (!empty($name)) {
            if ($conn->query("INSERT INTO categories (name) VALUES ('$name')")) {
                $message = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Category added!</div>';
            } else { $message = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Category may already exist.</div>'; }
        }
    }
    if (isset($_POST['delete_category'])) {
        $did = (int)$_POST['delete_id'];
        $conn->query("UPDATE products SET category_id=0 WHERE category_id=$did");
        $conn->query("DELETE FROM categories WHERE id=$did");
        $message = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Category removed.</div>';
    }
}

$cats = [];
$cr = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) as product_count,
    (SELECT SUM(s.total_price) FROM sales s JOIN products p ON s.product_id=p.id WHERE p.category_id=c.id AND s.status='recorded') as total_rev
    FROM categories c ORDER BY c.name ASC");
while ($r = $cr->fetch_assoc()) $cats[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div><div class="page-title">Categories</div><div class="page-subtitle"><?= count($cats) ?> categories</div></div>
        </div>
        <div class="topbar-right">
            <button onclick="document.getElementById('addCatModal').classList.add('open')" class="btn btn-primary btn-sm"><i class="ri-add-line"></i> Add Category</button>
        </div>
    </div>
    <div class="page-content">
        <?= $message ?>
        <div class="kpi-grid mb-3" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
            <?php foreach ($cats as $c): ?>
            <div class="kpi-card primary">
                <div class="kpi-label"><i class="ri-price-tag-3-line"></i> <?= htmlspecialchars($c['name']) ?></div>
                <div class="kpi-value"><?= $c['product_count'] ?> <span style="font-size:0.9rem;font-weight:500;color:var(--text-muted);">products</span></div>
                <div class="kpi-meta text-muted">Revenue: ₦<?= number_format($c['total_rev']??0,0) ?></div>
                <form method="POST" style="position:absolute;top:0.75rem;right:0.75rem;" onsubmit="return confirm('Delete this category?');">
                    <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="delete_category" class="btn btn-ghost btn-sm" style="color:var(--danger);padding:0.25rem;"><i class="ri-delete-bin-line"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php if (empty($cats)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="ri-price-tag-3-line" style="font-size:3rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
                No categories yet. Create one to organize your products.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<div class="modal-overlay" id="addCatModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="ri-price-tag-3-line" style="color:var(--primary)"></i> Add Category</h3>
            <button class="modal-close" onclick="document.getElementById('addCatModal').classList.remove('open')"><i class="ri-close-line"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Beverages">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addCatModal').classList.remove('open')">Cancel</button>
                <button type="submit" name="add_category" class="btn btn-primary"><i class="ri-check-line"></i> Create</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
