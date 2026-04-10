<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Today's stats (excluding reversed sales)
$today_sql = "SELECT SUM(total_price) as rev, SUM(cost_price) as cost, COUNT(*) as cnt FROM sales WHERE sale_date = '$today' AND status = 'recorded'";
$today_stats = $conn->query($today_sql)->fetch_assoc();
$today_rev = (float)($today_stats['rev'] ?? 0);
$today_cost = (float)($today_stats['cost'] ?? 0);
$today_profit = $today_rev - $today_cost;
$today_cnt = (int)($today_stats['cnt'] ?? 0);
$today_margin = $today_rev > 0 ? ($today_profit / $today_rev) * 100 : 0;

// Yesterday for comparison
$yest_sql = "SELECT SUM(total_price) as rev, SUM(cost_price) as cost, COUNT(*) as cnt FROM sales WHERE sale_date = '$yesterday' AND status = 'recorded'";
$yest = $conn->query($yest_sql)->fetch_assoc();
$yest_rev = (float)($yest['rev'] ?? 0);
$yest_cnt = (int)($yest['cnt'] ?? 0);

// Trend vs yesterday
$rev_trend = $yest_rev > 0 ? (($today_rev - $yest_rev) / $yest_rev * 100) : 0;
$cnt_trend  = $yest_cnt > 0 ? (($today_cnt - $yest_cnt) / $yest_cnt * 100) : 0;

// Monthly revenue
$month_start = date('Y-m-01');
$month_sql = "SELECT SUM(total_price) as rev FROM sales WHERE sale_date BETWEEN '$month_start' AND '$today' AND status='recorded'";
$month_rev = (float)($conn->query($month_sql)->fetch_assoc()['rev'] ?? 0);

// Inventory stats
$inv_sql = "SELECT COUNT(*) as total, SUM(quantity_in_stock) as total_units, SUM((cost_price/units_per_pack)*quantity_in_stock) as inv_val FROM products";
$inv = $conn->query($inv_sql)->fetch_assoc();
$total_products = (int)($inv['total'] ?? 0);
$inv_value = (float)($inv['inv_val'] ?? 0);

// Low stock count
$low_stock_cnt = (int)($conn->query("SELECT COUNT(*) as c FROM products WHERE quantity_in_stock < 5")->fetch_assoc()['c'] ?? 0);

// Cash vs Transfer today
$payment_sql = "SELECT payment_method, SUM(total_price) as amt FROM sales WHERE sale_date='$today' AND status='recorded' GROUP BY payment_method";
$payment_res = $conn->query($payment_sql);
$payment_data = ['Cash' => 0, 'Transfer' => 0, 'Card' => 0];
while ($pr = $payment_res->fetch_assoc()) { $payment_data[$pr['payment_method']] = (float)$pr['amt']; }

// Last 7 days revenue (for sparkline)
$spark_labels = []; $spark_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $r = $conn->query("SELECT SUM(total_price) as r FROM sales WHERE sale_date='$d' AND status='recorded'")->fetch_assoc();
    $spark_labels[] = date('D', strtotime($d));
    $spark_data[] = (float)($r['r'] ?? 0);
}

// Today's top products
$top_sql = "SELECT p.name, SUM(s.quantity_sold) as qty, SUM(s.total_price) as rev FROM sales s JOIN products p ON s.product_id=p.id WHERE s.sale_date='$today' AND s.status='recorded' GROUP BY s.product_id ORDER BY rev DESC LIMIT 5";
$top_res = $conn->query($top_sql);
$top_products = [];
$max_rev = 1;
while ($r = $top_res->fetch_assoc()) {
    $top_products[] = $r;
    if ((float)$r['rev'] > $max_rev) $max_rev = (float)$r['rev'];
}

// Recent activity (last 8)
$act_sql = "(SELECT sl.log_timestamp as t, u.username, 'stock' as type, p.name as product, sl.quantity_change as qty, 0 as amount, sl.reason as detail
             FROM stock_log sl JOIN users u ON sl.user_id=u.id JOIN products p ON sl.product_id=p.id)
            UNION
            (SELECT s.sale_date as t, u.username, IF(s.status='recorded','sale',s.status) as type, p.name as product, s.quantity_sold as qty, s.total_price as amount, s.payment_method as detail
             FROM sales s JOIN users u ON s.user_id=u.id JOIN products p ON s.product_id=p.id)
            ORDER BY t DESC LIMIT 8";
$act_res = $conn->query($act_sql);
$activities = [];
while ($r = $act_res->fetch_assoc()) $activities[] = $r;

// Low stock products
$low_items = [];
$ls = $conn->query("SELECT name, quantity_in_stock FROM products WHERE quantity_in_stock < 10 ORDER BY quantity_in_stock ASC LIMIT 6");
while ($r = $ls->fetch_assoc()) $low_items[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-layout">
<?php include 'includes/navbar.php'; ?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" onclick="openSidebar()"><i class="ri-menu-line"></i></button>
            <div>
                <div class="page-title">Dashboard</div>
                <div class="page-subtitle"><?= date('l, F j, Y') ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="live-clock"></span>
            <a href="summaries.php" class="topbar-btn"><i class="ri-line-chart-line"></i> Analytics</a>
            <a href="../staff/index.php" class="topbar-btn" target="_blank"><i class="ri-store-line"></i> Staff View</a>
        </div>
    </div>

    <div class="page-content">
        <!-- KPI Grid -->
        <div class="kpi-grid">
            <div class="kpi-card primary">
                <div class="kpi-label"><i class="ri-money-pound-circle-line"></i> Today's Revenue</div>
                <div class="kpi-value">₦<?= number_format($today_rev, 0) ?></div>
                <div class="kpi-meta <?= $rev_trend >= 0 ? 'kpi-trend-up' : 'kpi-trend-down' ?>">
                    <i class="ri-arrow-<?= $rev_trend >= 0 ? 'up' : 'down' ?>-line"></i>
                    <?= abs(round($rev_trend, 1)) ?>% vs yesterday
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label"><i class="ri-bar-chart-fill"></i> Today's Profit</div>
                <div class="kpi-value">₦<?= number_format($today_profit, 0) ?></div>
                <div class="kpi-meta text-muted">Margin: <?= number_format($today_margin, 1) ?>%</div>
            </div>
            <div class="kpi-card info">
                <div class="kpi-label"><i class="ri-receipt-line"></i> Sales Today</div>
                <div class="kpi-value"><?= $today_cnt ?></div>
                <div class="kpi-meta <?= $cnt_trend >= 0 ? 'kpi-trend-up' : 'kpi-trend-down' ?>">
                    <i class="ri-arrow-<?= $cnt_trend >= 0 ? 'up' : 'down' ?>-line"></i>
                    <?= $cnt_trend >= 0 ? '+' : '' ?><?= abs(round($cnt_trend, 0)) ?>% vs yesterday
                </div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-label"><i class="ri-calendar-line"></i> Month Revenue</div>
                <div class="kpi-value">₦<?= number_format($month_rev, 0) ?></div>
                <div class="kpi-meta text-muted"><?= date('F Y') ?></div>
            </div>
            <div class="kpi-card primary">
                <div class="kpi-label"><i class="ri-archive-2-line"></i> Products</div>
                <div class="kpi-value"><?= $total_products ?></div>
                <div class="kpi-meta text-muted">Stock items</div>
            </div>
            <div class="kpi-card <?= $low_stock_cnt > 0 ? 'danger' : 'success' ?>">
                <div class="kpi-label"><i class="ri-alert-line"></i> Low Stock</div>
                <div class="kpi-value"><?= $low_stock_cnt ?></div>
                <div class="kpi-meta <?= $low_stock_cnt > 0 ? 'kpi-trend-down' : 'kpi-trend-up' ?>">
                    <?= $low_stock_cnt > 0 ? 'Needs attention' : 'All stocked' ?>
                </div>
            </div>
            <div class="kpi-card info">
                <div class="kpi-label"><i class="ri-stack-line"></i> Inv. Value</div>
                <div class="kpi-value">₦<?= number_format($inv_value, 0) ?></div>
                <div class="kpi-meta text-muted">At cost</div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label"><i class="ri-bank-card-line"></i> Cash Today</div>
                <div class="kpi-value">₦<?= number_format($payment_data['Cash'], 0) ?></div>
                <div class="kpi-meta text-muted">Transfer: ₦<?= number_format($payment_data['Transfer'], 0) ?></div>
            </div>
        </div>

        <!-- Charts + Top Products Row -->
        <div class="grid-2 mb-3">
            <div class="card">
                <div class="card-header">
                    <h3><i class="ri-line-chart-line"></i> 7-Day Revenue Trend</h3>
                    <a href="summaries.php" class="btn btn-ghost btn-sm">Full Report <i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="chart-wrapper">
                    <canvas id="sparklineChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="ri-pie-chart-line"></i> Payment Split Today</h3>
                </div>
                <div class="chart-wrapper" style="max-height:280px;">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products + Recent Activity -->
        <div class="grid-2 mb-3">
            <!-- Top Products -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="ri-medal-line"></i> Top Products Today</h3>
                    <span class="badge badge-primary"><?= date('d M') ?></span>
                </div>
                <?php if (empty($top_products)): ?>
                    <div style="padding:2rem; text-align:center; color:var(--text-muted);">
                        <i class="ri-shopping-bag-line" style="font-size:2.5rem; display:block; margin-bottom:0.5rem; opacity:0.3;"></i>
                        No sales recorded today yet.
                    </div>
                <?php else: ?>
                    <div style="padding: 1.25rem; display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach ($top_products as $i => $tp):
                            $pct = $max_rev > 0 ? ($tp['rev'] / $max_rev * 100) : 0;
                            $medals = ['🥇','🥈','🥉','4','5'];
                        ?>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-label">
                                    <span><?= $medals[$i] ?> <?= htmlspecialchars($tp['name']) ?> <span style="color:var(--text-muted);font-size:0.72rem;">(<?= $tp['qty'] ?> units)</span></span>
                                    <span style="font-weight:700; color:var(--success);">₦<?= number_format($tp['rev'], 0) ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card-footer flex-between">
                    <span style="font-size:0.78rem; color:var(--text-muted);">Showing top <?= count($top_products) ?> products</span>
                    <a href="summaries.php?view=product&preset=today" class="btn btn-ghost btn-sm">See All</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="ri-history-line"></i> Recent Activity</h3>
                    <a href="activity_log.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <div class="activity-feed">
                    <?php if (empty($activities)): ?>
                        <div style="padding:2rem; text-align:center; color:var(--text-muted);">No recent activity.</div>
                    <?php else: foreach ($activities as $act):
                        $is_sale = $act['type'] === 'sale';
                        $is_void = in_array($act['type'], ['voided', 'returned']);
                        $type_class = $is_sale ? 'sale' : ($is_void ? 'void' : 'stock');
                        $icon = $is_sale ? 'ri-shopping-cart-line' : ($is_void ? 'ri-arrow-go-back-line' : 'ri-archive-line');
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot <?= $type_class ?>">
                            <i class="<?= $icon ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= htmlspecialchars($act['product']) ?></div>
                            <div class="activity-meta">
                                <?= ucfirst($act['type']) ?> • <?= htmlspecialchars($act['username']) ?> •
                                <?= date('H:i', strtotime($act['t'])) ?>
                            </div>
                        </div>
                        <?php if ($act['amount'] > 0): ?>
                            <div class="activity-amount <?= $is_void ? 'neg' : '' ?>">
                                <?= $is_void ? '-' : '' ?>₦<?= number_format($act['amount'], 0) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Low Stock + Quick Actions -->
        <div class="grid-2">
            <!-- Low Stock -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="ri-alert-line" style="color:var(--warning)"></i> Low Stock Alerts</h3>
                    <a href="products.php" class="btn btn-warning btn-sm"><i class="ri-add-line"></i> Restock</a>
                </div>
                <?php if (empty($low_items)): ?>
                    <div style="padding:2rem; text-align:center; color:var(--success);">
                        <i class="ri-checkbox-circle-line" style="font-size:2.5rem; display:block; margin-bottom:0.5rem;"></i>
                        All products well stocked!
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead><tr><th>Product</th><th>Stock Left</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($low_items as $li):
                                    $s = $li['quantity_in_stock'];
                                    $cls = $s == 0 ? 'danger' : ($s < 5 ? 'warning' : 'success');
                                    $lbl = $s == 0 ? 'Out of Stock' : ($s < 5 ? 'Critical' : 'Low');
                                ?>
                                <tr>
                                    <td data-label="Product"><?= htmlspecialchars($li['name']) ?></td>
                                    <td data-label="Stock" class="num"><?= $s ?> units</td>
                                    <td data-label="Status"><span class="badge badge-<?= $cls ?>"><?= $lbl ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-lightning-line"></i> Quick Actions</h3></div>
                <div class="card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <a href="products.php" class="btn btn-primary btn-block btn-lg">
                        <i class="ri-add-circle-line"></i> Add Product
                    </a>
                    <a href="summaries.php" class="btn btn-success btn-block btn-lg">
                        <i class="ri-bar-chart-2-line"></i> Analytics
                    </a>
                    <a href="daily_summary.php" class="btn btn-outline btn-block btn-lg">
                        <i class="ri-calendar-todo-line"></i> Daily Report
                    </a>
                    <a href="profit_loss.php" class="btn btn-outline btn-block btn-lg">
                        <i class="ri-funds-box-line"></i> P&L Report
                    </a>
                    <a href="activity_log.php" class="btn btn-outline btn-block btn-lg">
                        <i class="ri-shield-check-line"></i> Audit Log
                    </a>
                    <a href="manage_staff.php" class="btn btn-outline btn-block btn-lg">
                        <i class="ri-group-line"></i> Manage Staff
                    </a>
                </div>
            </div>
        </div>
    </div> <!-- /page-content -->
</div> <!-- /main-content -->
</div> <!-- /app-layout -->

<script>
// Live clock
function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent = now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000); updateClock();

// 7-day sparkline
const sparkCtx = document.getElementById('sparklineChart').getContext('2d');
new Chart(sparkCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($spark_labels) ?>,
        datasets: [{
            label: 'Revenue (₦)',
            data: <?= json_encode($spark_data) ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79,70,229,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#4f46e5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            borderWidth: 2.5
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: v => '₦' + (v/1000).toFixed(0) + 'k' } }
        }
    }
});

// Payment split donut
const payCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(payCtx, {
    type: 'doughnut',
    data: {
        labels: ['Cash', 'Transfer', 'Card'],
        datasets: [{
            data: [<?= $payment_data['Cash'] ?>, <?= $payment_data['Transfer'] ?>, <?= $payment_data['Card'] ?>],
            backgroundColor: ['#4f46e5', '#059669', '#d97706'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, color: '#64748b', font: { size: 12 } } }
        }
    }
});
</script>
</body>
</html>