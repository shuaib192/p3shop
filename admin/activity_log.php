<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$filter_type = $_GET['type'] ?? '';
$filter_staff = $_GET['staff'] ?? '';
$filter_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_to = $_GET['to'] ?? date('Y-m-d');

$where_extra = '';
if ($filter_type === 'stock') $where_extra = "AND activity_type = 'Stock Change'";
elseif ($filter_type === 'sale') $where_extra = "AND activity_type = 'Sale'";

$sql = "SELECT * FROM (
    (SELECT sl.id as ref_id, sl.log_timestamp as activity_time, u.username, u.role,
        'Stock Change' as activity_type, p.name as product, c.name as category,
        sl.quantity_change as qty, p.cost_price/p.units_per_pack as unit_cost,
        p.price as unit_price, (sl.quantity_change*(p.cost_price/p.units_per_pack)) as financial_impact,
        'Admin Panel' as source, sl.reason as details, 'N/A' as status
     FROM stock_log sl JOIN users u ON sl.user_id=u.id JOIN products p ON sl.product_id=p.id LEFT JOIN categories c ON p.category_id=c.id
     WHERE sl.log_timestamp BETWEEN '$filter_from 00:00:00' AND '$filter_to 23:59:59')
    UNION
    (SELECT s.id as ref_id, s.sale_date as activity_time, u.username, u.role,
        'Sale' as activity_type, p.name as product, c.name as category,
        s.quantity_sold as qty, s.cost_price/s.quantity_sold as unit_cost,
        s.total_price/s.quantity_sold as unit_price, s.total_price as financial_impact,
        'Staff Terminal' as source,
        CONCAT(s.payment_method, IF(s.status!='recorded',CONCAT(' (',s.status,': ',IFNULL(s.void_reason,''),')'),'' )) as details,
        s.status
     FROM sales s JOIN users u ON s.user_id=u.id JOIN products p ON s.product_id=p.id LEFT JOIN categories c ON p.category_id=c.id
     WHERE s.sale_date BETWEEN '$filter_from' AND '$filter_to')
) combined WHERE 1=1 $where_extra ORDER BY activity_time DESC LIMIT 500";

$result = $conn->query($sql);
$activities = [];
if ($result) while ($r = $result->fetch_assoc()) $activities[] = $r;

// Staff list for filter
$staff_list = [];
$sr = $conn->query("SELECT DISTINCT username FROM users ORDER BY username");
while ($r = $sr->fetch_assoc()) $staff_list[] = $r['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log — P3 Shop Pro</title>
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
                <div class="page-title">Audit Log</div>
                <div class="page-subtitle">Activity tracking (13-point)</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="export_logs.php" class="btn btn-primary btn-sm"><i class="ri-download-2-line"></i> Download CSV</a>
        </div>
    </div>

    <div class="page-content">
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                <form method="GET" style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Type</label>
                        <select name="type" class="form-control" style="width:auto;padding:0.4rem;">
                            <option value="">All Types</option>
                            <option value="sale" <?= $filter_type==='sale'?'selected':'' ?>>Sales</option>
                            <option value="stock" <?= $filter_type==='stock'?'selected':'' ?>>Stock Changes</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">From</label>
                        <input type="date" name="from" value="<?= $filter_from ?>" class="form-control" style="width:auto;padding:0.4rem;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">To</label>
                        <input type="date" name="to" value="<?= $filter_to ?>" class="form-control" style="width:auto;padding:0.4rem;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
                    <a href="activity_log.php" class="btn btn-ghost btn-sm">Reset</a>
                </form>
            </div>
        </div>

        <!-- Activity Table -->
        <div class="card">
            <div class="table-tools">
                <div class="search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search activities..." id="actSearch" onkeyup="filterAct()">
                </div>
                <div class="table-count"><?= count($activities) ?> records</div>
            </div>
            <div class="table-container" style="max-height:70vh; overflow-y:auto;">
                <table class="data-table" id="actTable">
                    <thead>
                        <tr>
                            <th>Ref</th><th>Date/Time</th><th>Staff</th><th>Type</th>
                            <th>Category</th><th>Product</th><th>Qty</th>
                            <th>Unit Cost</th><th>Unit Price</th><th>Impact</th>
                            <th>Source</th><th>Details</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activities as $a):
                        $is_sale = $a['activity_type'] === 'Sale';
                        $is_void = in_array($a['status'], ['voided','returned']);
                    ?>
                        <tr style="<?= $is_void?'opacity:0.6;':'' ?>">
                            <td data-label="Ref">#<?= $a['ref_id'] ?></td>
                            <td data-label="Date" class="nowrap">
                                <div style="font-weight:600;"><?= date('Y-m-d', strtotime($a['activity_time'])) ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= date('H:i:s', strtotime($a['activity_time'])) ?></div>
                            </td>
                            <td data-label="Staff">
                                <div style="font-weight:600;"><?= htmlspecialchars($a['username']) ?></div>
                                <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;"><?= $a['role'] ?></div>
                            </td>
                            <td data-label="Type">
                                <span class="badge badge-<?= $is_sale ? 'success' : 'primary' ?>">
                                    <?= $a['activity_type'] ?>
                                </span>
                            </td>
                            <td data-label="Category"><?= htmlspecialchars($a['category']??'—') ?></td>
                            <td data-label="Product"><strong><?= htmlspecialchars($a['product']) ?></strong></td>
                            <td data-label="Qty" class="num fw-700"><?= $a['qty'] ?></td>
                            <td data-label="Unit Cost" class="num">₦<?= number_format($a['unit_cost'],2) ?></td>
                            <td data-label="Unit Price" class="num">₦<?= number_format($a['unit_price'],2) ?></td>
                            <td data-label="Impact" class="<?= (float)$a['financial_impact']>=0?'money':'money-neg' ?> fw-700">₦<?= number_format($a['financial_impact'],2) ?></td>
                            <td data-label="Source"><span class="badge badge-neutral"><?= $a['source'] ?></span></td>
                            <td data-label="Details" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($a['details']) ?></td>
                            <td data-label="Status">
                                <?php if ($a['status'] !== 'N/A'): ?>
                                <span class="badge badge-<?= $a['status']==='recorded'?'success':($a['status']==='voided'?'danger':'warning') ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                                <?php else: echo '—'; endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                        <tr><td colspan="13" style="text-align:center;padding:2rem;color:var(--text-muted);">No activity found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function filterAct() {
    const q = document.getElementById('actSearch').value.toLowerCase();
    document.querySelectorAll('#actTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
