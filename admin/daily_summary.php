<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$report_date = $_GET['report_date'] ?? date('Y-m-d');
$prev_date = date('Y-m-d', strtotime($report_date . ' -1 day'));
$safe = mysqli_real_escape_string($conn, $report_date);
$safe_prev = mysqli_real_escape_string($conn, $prev_date);

// Today's stats
$sum = $conn->query("SELECT SUM(total_price) as rev, SUM(cost_price) as cost, SUM(quantity_sold) as qty, COUNT(*) as cnt,
    SUM(CASE WHEN payment_method='Cash' THEN total_price ELSE 0 END) as cash,
    SUM(CASE WHEN payment_method='Transfer' THEN total_price ELSE 0 END) as transfer,
    SUM(CASE WHEN payment_method='Card' THEN total_price ELSE 0 END) as card
    FROM sales WHERE sale_date='$safe' AND status='recorded'")->fetch_assoc();

$rev = (float)($sum['rev']??0); $cost = (float)($sum['cost']??0); $profit = $rev-$cost;
$qty = (int)($sum['qty']??0); $cnt = (int)($sum['cnt']??0);
$cash = (float)($sum['cash']??0); $transfer = (float)($sum['transfer']??0); $card_amt = (float)($sum['card']??0);

// Previous day for comparison
$prev = $conn->query("SELECT SUM(total_price) as rev FROM sales WHERE sale_date='$safe_prev' AND status='recorded'")->fetch_assoc();
$prev_rev = (float)($prev['rev']??0);
$trend = $prev_rev > 0 ? (($rev - $prev_rev)/$prev_rev*100) : 0;

// Top products
$top = []; $tr = $conn->query("SELECT p.name, SUM(s.quantity_sold) as qty, SUM(s.total_price) as rev FROM sales s JOIN products p ON s.product_id=p.id WHERE s.sale_date='$safe' AND s.status='recorded' GROUP BY s.product_id ORDER BY rev DESC LIMIT 8");
while ($r = $tr->fetch_assoc()) $top[] = $r;

// Staff breakdown
$staff_data = []; $sr = $conn->query("SELECT u.username, SUM(s.total_price) as rev, COUNT(*) as cnt FROM sales s JOIN users u ON s.user_id=u.id WHERE s.sale_date='$safe' AND s.status='recorded' GROUP BY s.user_id ORDER BY rev DESC");
while ($r = $sr->fetch_assoc()) $staff_data[] = $r;

// All transactions
$log = []; $lr = $conn->query("SELECT s.id, p.name as product, s.quantity_sold as qty, s.total_price as total, s.cost_price as cost, s.payment_method, u.username as staff, s.status, s.void_reason FROM sales s JOIN products p ON s.product_id=p.id JOIN users u ON s.user_id=u.id WHERE s.sale_date='$safe' ORDER BY s.id DESC");
while ($r = $lr->fetch_assoc()) $log[] = $r;

// Voided/returned count
$void_cnt = 0;
foreach ($log as $l) { if ($l['status'] !== 'recorded') $void_cnt++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-layout">
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div>
                <div class="page-title">Daily Report</div>
                <div class="page-subtitle"><?= date('l, F j, Y', strtotime($report_date)) ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="export_daily_csv.php?report_date=<?= $report_date ?>" class="btn btn-primary btn-sm"><i class="ri-download-2-line"></i> Download CSV</a>
        </div>
    </div>

    <div class="page-content">
        <!-- Date Picker -->
        <div class="card mb-3">
            <div class="card-body" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                <form method="GET" style="display:flex; gap:0.75rem; align-items:flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="report_date" value="<?= $report_date ?>" class="form-control" style="width:auto;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-calendar-check-line"></i> View Report</button>
                </form>
                <div style="display:flex; gap:0.4rem;">
                    <a href="?report_date=<?= date('Y-m-d') ?>" class="btn btn-outline btn-sm">Today</a>
                    <a href="?report_date=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-outline btn-sm">Yesterday</a>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid mb-3">
            <div class="kpi-card primary">
                <div class="kpi-label"><i class="ri-money-pound-circle-line"></i> Revenue</div>
                <div class="kpi-value">₦<?= number_format($rev,0) ?></div>
                <div class="kpi-meta <?= $trend>=0?'kpi-trend-up':'kpi-trend-down' ?>">
                    <i class="ri-arrow-<?= $trend>=0?'up':'down' ?>-line"></i> <?= abs(round($trend,1)) ?>% vs prev day
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label"><i class="ri-bar-chart-fill"></i> Profit</div>
                <div class="kpi-value">₦<?= number_format($profit,0) ?></div>
                <div class="kpi-meta text-muted">Margin: <?= $rev>0?number_format(($profit/$rev)*100,1):0 ?>%</div>
            </div>
            <div class="kpi-card info">
                <div class="kpi-label"><i class="ri-receipt-line"></i> Transactions</div>
                <div class="kpi-value"><?= $cnt ?></div>
                <div class="kpi-meta text-muted"><?= $qty ?> units sold</div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-label"><i class="ri-wallet-3-line"></i> Cash</div>
                <div class="kpi-value">₦<?= number_format($cash,0) ?></div>
                <div class="kpi-meta text-muted">Transfer: ₦<?= number_format($transfer,0) ?></div>
            </div>
        </div>

        <div class="grid-2 mb-3">
            <!-- Payment Chart -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-pie-chart-line"></i> Payment Breakdown</h3></div>
                <div class="chart-wrapper" style="height:240px;">
                    <canvas id="payChart"></canvas>
                </div>
            </div>

            <!-- Staff Performance -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-group-line"></i> Staff Sales</h3></div>
                <?php if (empty($staff_data)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--text-muted);">No sales this day.</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Staff</th><th>Sales</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($staff_data as $sd): ?>
                            <tr>
                                <td data-label="Staff"><strong><?= htmlspecialchars($sd['username']) ?></strong></td>
                                <td data-label="Sales"><?= $sd['cnt'] ?></td>
                                <td data-label="Revenue" class="money">₦<?= number_format($sd['rev'],0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="ri-medal-line"></i> Top Selling Products</h3></div>
            <?php if (empty($top)): ?>
                <div style="padding:2rem;text-align:center;color:var(--text-muted);">No products sold on this day.</div>
            <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($top as $i => $tp): ?>
                        <tr>
                            <td data-label="#"><?= $i+1 ?></td>
                            <td data-label="Product"><strong><?= htmlspecialchars($tp['name']) ?></strong></td>
                            <td data-label="Qty"><?= $tp['qty'] ?> units</td>
                            <td data-label="Revenue" class="money">₦<?= number_format($tp['rev'],0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Full Transaction Log -->
        <div class="card">
            <div class="card-header">
                <h3><i class="ri-list-check"></i> All Transactions</h3>
                <?php if ($void_cnt > 0): ?>
                    <span class="badge badge-danger"><?= $void_cnt ?> voided/returned</span>
                <?php endif; ?>
            </div>
            <div class="table-tools">
                <div class="search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search transactions..." id="txnSearch" onkeyup="filterTxns()">
                </div>
                <div class="table-count"><?= count($log) ?> transactions</div>
            </div>
            <div class="table-container">
                <table class="data-table" id="txnTable">
                    <thead><tr><th>ID</th><th>Product</th><th>Qty</th><th>Total</th><th>Profit</th><th>Payment</th><th>Staff</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($log as $l):
                        $lp = (float)$l['total'] - (float)$l['cost'];
                        $is_ok = $l['status'] === 'recorded';
                    ?>
                        <tr style="<?= !$is_ok?'opacity:0.6;':'' ?>">
                            <td data-label="ID">#<?= $l['id'] ?></td>
                            <td data-label="Product"><strong><?= htmlspecialchars($l['product']) ?></strong></td>
                            <td data-label="Qty"><?= $l['qty'] ?></td>
                            <td data-label="Total" class="<?= $is_ok?'money':'money-neg' ?>"><?= $is_ok?'':'(void) ' ?>₦<?= number_format($l['total'],0) ?></td>
                            <td data-label="Profit" class="money">₦<?= number_format($lp,0) ?></td>
                            <td data-label="Payment"><?= $l['payment_method'] ?></td>
                            <td data-label="Staff"><?= htmlspecialchars($l['staff']) ?></td>
                            <td data-label="Status">
                                <span class="badge badge-<?= $is_ok?'success':($l['status']==='voided'?'danger':'warning') ?>"><?= ucfirst($l['status']) ?></span>
                                <?php if (!$is_ok && $l['void_reason']): ?>
                                    <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.2rem;"><?= htmlspecialchars($l['void_reason']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($log)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No transactions for this date.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Payment chart
new Chart(document.getElementById('payChart'), {
    type: 'doughnut',
    data: {
        labels: ['Cash', 'Transfer', 'Card'],
        datasets: [{ data: [<?= $cash ?>,<?= $transfer ?>,<?= $card_amt ?>], backgroundColor: ['#4f46e5','#059669','#d97706'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: '#64748b' } } } }
});

// Search
function filterTxns() {
    const q = document.getElementById('txnSearch').value.toLowerCase();
    document.querySelectorAll('#txnTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>