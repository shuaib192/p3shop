<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$period = $_GET['period'] ?? 'month';
if ($period === 'year') { $from = date('Y-01-01'); $to = date('Y-12-31'); $label = 'Year ' . date('Y'); }
elseif ($period === 'quarter') {
    $q = ceil(date('n') / 3);
    $from = date('Y-' . str_pad(($q-1)*3+1, 2, '0', STR_PAD_LEFT) . '-01');
    $to = date('Y-m-t', strtotime($from . ' +2 months'));
    $label = 'Q' . $q . ' ' . date('Y');
} elseif ($period === 'week') {
    $from = date('Y-m-d', strtotime('monday this week'));
    $to = date('Y-m-d', strtotime('sunday this week'));
    $label = 'This Week';
} else {
    $from = date('Y-m-01');
    $to = date('Y-m-t');
    $label = date('F Y');
}

// Revenue & COGS
$r = $conn->query("SELECT SUM(total_price) as rev, SUM(cost_price) as cogs, COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status='recorded'")->fetch_assoc();
$revenue = (float)($r['rev']??0);
$cogs = (float)($r['cogs']??0);
$gross_profit = $revenue - $cogs;
$gross_margin = $revenue > 0 ? ($gross_profit/$revenue)*100 : 0;

// Refunds (voided/returned sales)
$vr = $conn->query("SELECT SUM(total_price) as refunds, COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status IN ('voided','returned')")->fetch_assoc();
$refunds = (float)($vr['refunds']??0);
$refund_cnt = (int)($vr['cnt']??0);

$net_revenue = $revenue - $refunds;
$net_profit = $net_revenue - $cogs;
$net_margin = $net_revenue > 0 ? ($net_profit/$net_revenue)*100 : 0;

// Monthly breakdown
$monthly = [];
$mr = $conn->query("SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(total_price) as rev, SUM(cost_price) as cost FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status='recorded' GROUP BY DATE_FORMAT(sale_date, '%Y-%m') ORDER BY month ASC");
while ($row = $mr->fetch_assoc()) $monthly[] = $row;

// Category breakdown
$cat_data = [];
$cr = $conn->query("SELECT c.name, SUM(s.total_price) as rev, SUM(s.cost_price) as cost FROM sales s JOIN products p ON s.product_id=p.id LEFT JOIN categories c ON p.category_id=c.id WHERE s.sale_date BETWEEN '$from' AND '$to' AND s.status='recorded' GROUP BY p.category_id ORDER BY rev DESC");
while ($row = $cr->fetch_assoc()) $cat_data[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss — P3 Shop Pro</title>
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
            <div><div class="page-title">Profit & Loss Statement</div><div class="page-subtitle"><?= $label ?> (<?= $from ?> to <?= $to ?>)</div></div>
        </div>
        <div class="topbar-right no-print">
            <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="ri-printer-line"></i> Print</button>
        </div>
    </div>

    <div class="page-content">
        <!-- Period Selector -->
        <div class="card mb-3 no-print">
            <div class="card-body" style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                <?php foreach (['week'=>'This Week','month'=>'This Month','quarter'=>'This Quarter','year'=>'This Year'] as $k=>$v): ?>
                <a href="?period=<?= $k ?>" class="btn btn-sm <?= $period===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid-2 mb-3">
            <!-- P&L Statement -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-funds-line"></i> Income Statement</h3></div>
                <div class="card-body">
                    <table style="width:100%;border-collapse:collapse;">
                        <tbody>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.75rem 0;font-weight:700;color:var(--text);">Gross Revenue</td>
                                <td style="padding:0.75rem 0;text-align:right;font-weight:800;color:var(--primary);font-size:1.1rem;">₦<?= number_format($revenue,2) ?></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.75rem 0;color:var(--text-muted);">Less: Cost of Goods Sold (COGS)</td>
                                <td style="padding:0.75rem 0;text-align:right;color:var(--danger);font-weight:600;">(₦<?= number_format($cogs,2) ?>)</td>
                            </tr>
                            <tr style="border-bottom:2px solid var(--border);background:var(--success-bg);">
                                <td style="padding:0.875rem 0.5rem;font-weight:700;color:var(--success);">Gross Profit</td>
                                <td style="padding:0.875rem 0.5rem;text-align:right;font-weight:800;color:var(--success);font-size:1.1rem;">₦<?= number_format($gross_profit,2) ?></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.75rem 0;color:var(--text-muted);">Gross Margin</td>
                                <td style="padding:0.75rem 0;text-align:right;font-weight:600;"><?= number_format($gross_margin,1) ?>%</td>
                            </tr>

                            <tr><td colspan="2" style="padding:0.5rem 0;"></td></tr>

                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.75rem 0;color:var(--text-muted);">Less: Refunds & Returns</td>
                                <td style="padding:0.75rem 0;text-align:right;color:var(--danger);font-weight:600;">(₦<?= number_format($refunds,2) ?>) <span style="font-size:0.72rem;color:var(--text-light);"><?= $refund_cnt ?> items</span></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.75rem 0;font-weight:600;">Net Revenue</td>
                                <td style="padding:0.75rem 0;text-align:right;font-weight:700;">₦<?= number_format($net_revenue,2) ?></td>
                            </tr>

                            <tr style="background:var(--primary-bg);border-radius:var(--radius-sm);">
                                <td style="padding:1rem 0.5rem;font-weight:800;font-size:1.05rem;color:var(--primary-dark);">Net Profit</td>
                                <td style="padding:1rem 0.5rem;text-align:right;font-weight:800;font-size:1.25rem;color:<?= $net_profit>=0?'var(--success)':'var(--danger)' ?>;">₦<?= number_format($net_profit,2) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:0.5rem 0;color:var(--text-muted);font-size:0.85rem;">Net Margin</td>
                                <td style="padding:0.5rem 0;text-align:right;font-weight:600;font-size:0.85rem;"><?= number_format($net_margin,1) ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profit Visualization -->
            <div class="card">
                <div class="card-header"><h3><i class="ri-bar-chart-2-line"></i> Revenue vs Cost</h3></div>
                <div class="chart-wrapper" style="height:300px;">
                    <canvas id="plChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Category P&L -->
        <div class="card">
            <div class="card-header"><h3><i class="ri-folders-line"></i> Profit by Category</h3></div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Category</th><th>Revenue</th><th>COGS</th><th>Gross Profit</th><th>Margin</th></tr></thead>
                    <tbody>
                    <?php foreach ($cat_data as $cd):
                        $cp = (float)$cd['rev'] - (float)$cd['cost'];
                        $cm = (float)$cd['rev'] > 0 ? ($cp/(float)$cd['rev']*100) : 0;
                    ?>
                    <tr>
                        <td data-label="Category"><strong><?= htmlspecialchars($cd['name']??'Uncategorized') ?></strong></td>
                        <td data-label="Revenue" class="money">₦<?= number_format($cd['rev'],0) ?></td>
                        <td data-label="COGS">₦<?= number_format($cd['cost'],0) ?></td>
                        <td data-label="Profit" class="money fw-700">₦<?= number_format($cp,0) ?></td>
                        <td data-label="Margin"><span class="<?= $cm>=25?'text-success':($cm>=10?'text-primary':'text-danger') ?> fw-700"><?= number_format($cm,1) ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
new Chart(document.getElementById('plChart'), {
    type: 'bar',
    data: {
        labels: ['Revenue', 'COGS', 'Gross Profit', 'Refunds', 'Net Profit'],
        datasets: [{
            data: [<?= $revenue ?>, <?= $cogs ?>, <?= $gross_profit ?>, <?= $refunds ?>, <?= $net_profit ?>],
            backgroundColor: ['#4f46e5', '#dc2626', '#059669', '#d97706', '<?= $net_profit>=0?"#059669":"#dc2626" ?>'],
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#64748b' } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: v => '₦'+v.toLocaleString() } }
        }
    }
});
</script>
</body>
</html>
