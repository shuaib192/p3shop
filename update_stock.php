<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }

$message = '';
$admin_id = (int)$_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) { header('Location: admin/products.php'); exit; }

$product = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=$product_id")->fetch_assoc();
if (!$product) { header('Location: admin/products.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $change = (int)$_POST['quantity_change'];
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));

    if ($change != 0 && !empty($reason)) {
        $new_qty = (int)$product['quantity_in_stock'] + $change;
        if ($new_qty < 0) { $new_qty = 0; $change = -(int)$product['quantity_in_stock']; }

        $conn->query("UPDATE products SET quantity_in_stock = $new_qty WHERE id = $product_id");
        $conn->query("INSERT INTO stock_log (product_id, user_id, quantity_change, reason) VALUES ($product_id, $admin_id, $change, '$reason')");

        $_SESSION['msg'] = '<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Stock updated! New level: <strong>' . $new_qty . ' units</strong></div>';
    } else {
        $_SESSION['msg'] = '<div class="alert alert-danger"><i class="ri-error-warning-line"></i> Enter a quantity and reason.</div>';
    }
    header("Location: update_stock.php?id=$product_id");
    exit;
}

$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Stock history
$history = [];
$hr = $conn->query("SELECT sl.*, u.username FROM stock_log sl JOIN users u ON sl.user_id=u.id WHERE sl.product_id=$product_id ORDER BY sl.log_timestamp DESC LIMIT 15");
while ($r = $hr->fetch_assoc()) $history[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock — P3 Shop Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include 'admin/includes/navbar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div>
                <div class="page-title">Stock Adjustment</div>
                <div class="page-subtitle"><?= htmlspecialchars($product['name']) ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="admin/products.php" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Back</a>
        </div>
    </div>

    <div class="page-content">
        <?= $message ?>

        <div class="grid-2">
            <!-- Update Form -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-add-circle-line"></i> Adjust Stock</h3></div>
                <div class="card-body">
                    <!-- Current Status -->
                    <div class="kpi-grid mb-3" style="grid-template-columns:1fr 1fr 1fr;">
                        <div class="kpi-card info" style="padding:1rem;">
                            <div class="kpi-label" style="font-size:0.65rem;">Current Stock</div>
                            <div class="kpi-value" style="font-size:1.5rem;"><?= $product['quantity_in_stock'] ?></div>
                        </div>
                        <div class="kpi-card primary" style="padding:1rem;">
                            <div class="kpi-label" style="font-size:0.65rem;">Category</div>
                            <div style="font-weight:700;font-size:0.85rem;"><?= htmlspecialchars($product['category_name']??'N/A') ?></div>
                        </div>
                        <div class="kpi-card success" style="padding:1rem;">
                            <div class="kpi-label" style="font-size:0.65rem;">Price</div>
                            <div class="kpi-value" style="font-size:1.2rem;">₦<?= number_format($product['price'],0) ?></div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Quantity Change</label>
                            <input type="number" name="quantity_change" class="form-control" required placeholder="e.g. +24 or -5" id="qtyInput">
                            <div class="form-hint">Positive = add stock, Negative = remove stock</div>
                        </div>
                        <div style="background:var(--surface-2);padding:0.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;display:flex;justify-content:space-between;font-size:0.85rem;">
                            <span>New Stock Level:</span>
                            <strong id="newLevel"><?= $product['quantity_in_stock'] ?> units</strong>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason *</label>
                            <select name="reason" class="form-control" id="reasonSelect" onchange="if(this.value==='other')document.getElementById('customReason').style.display='block'; else document.getElementById('customReason').style.display='none';">
                                <option value="">Select a reason...</option>
                                <option value="New stock delivery">New stock delivery</option>
                                <option value="Restock from supplier">Restock from supplier</option>
                                <option value="Stock count correction">Stock count correction</option>
                                <option value="Damaged goods removed">Damaged goods removed</option>
                                <option value="Expired stock removed">Expired stock removed</option>
                                <option value="Internal use">Internal use</option>
                                <option value="other">Other (type below)...</option>
                            </select>
                            <input type="text" id="customReason" class="form-control mt-1" style="display:none;" placeholder="Enter custom reason..." onchange="document.getElementById('reasonSelect').name='';this.name='reason';">
                        </div>
                        <button type="submit" name="update_stock" class="btn btn-primary btn-block btn-lg">
                            <i class="ri-refresh-line"></i> Update Stock
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stock History -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-history-line"></i> Stock History</h3></div>
                <?php if (empty($history)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--text-muted);">No history yet.</div>
                <?php else: ?>
                <div class="activity-feed">
                    <?php foreach ($history as $h):
                        $positive = $h['quantity_change'] > 0;
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot <?= $positive ? 'sale' : 'void' ?>">
                            <i class="ri-<?= $positive ? 'arrow-up' : 'arrow-down' ?>-line"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= $positive ? '+' : '' ?><?= $h['quantity_change'] ?> units</div>
                            <div class="activity-meta"><?= htmlspecialchars($h['reason']) ?> • <?= $h['username'] ?></div>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;">
                            <?= date('d M', strtotime($h['log_timestamp'])) ?><br>
                            <?= date('H:i', strtotime($h['log_timestamp'])) ?>
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

<script>
document.getElementById('qtyInput')?.addEventListener('input', function() {
    const change = parseInt(this.value) || 0;
    const current = <?= $product['quantity_in_stock'] ?>;
    const newLevel = Math.max(0, current + change);
    document.getElementById('newLevel').textContent = newLevel + ' units';
});
</script>
</body>
</html>