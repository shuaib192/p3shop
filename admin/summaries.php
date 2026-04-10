<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$view = $_GET['view'] ?? 'trend';
$preset = $_GET['preset'] ?? '';

if ($preset === 'today') { $from = $to = date('Y-m-d'); }
elseif ($preset === 'week') { $from = date('Y-m-d', strtotime('-7 days')); $to = date('Y-m-d'); }
elseif ($preset === 'month') { $from = date('Y-m-01'); $to = date('Y-m-t'); }
elseif ($preset === 'year') { $from = date('Y-01-01'); $to = date('Y-12-31'); }
else {
    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $to   = $_GET['to']   ?? date('Y-m-d');
}

// Comparison period (same length, just before)
$days = max(1, (strtotime($to) - strtotime($from)) / 86400 + 1);
$comp_to   = date('Y-m-d', strtotime($from) - 86400);
$comp_from = date('Y-m-d', strtotime($comp_to) - ($days - 1) * 86400);

function getStats($conn, $from, $to) {
    $r = $conn->query("SELECT SUM(total_price) as rev, SUM(cost_price) as cost, SUM(quantity_sold) as qty, COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status='recorded'")->fetch_assoc();
    $rev = (float)($r['rev']??0); $cost = (float)($r['cost']??0);
    return ['rev'=>$rev,'cost'=>$cost,'profit'=>$rev-$cost,'qty'=>(int)($r['qty']??0),'cnt'=>(int)($r['cnt']??0),'margin'=>$rev>0?(($rev-$cost)/$rev)*100:0,'avg'=>(int)($r['cnt']??0)>0?$rev/(int)$r['cnt']:0];
}

$stats = getStats($conn, $from, $to);
$comp  = getStats($conn, $comp_from, $comp_to);

function trend($cur, $prev) {
    if ($prev == 0) return $cur > 0 ? 100 : 0;
    return (($cur - $prev) / $prev) * 100;
}

$stock_r = $conn->query("SELECT SUM((cost_price/units_per_pack)*quantity_in_stock) as val, SUM(price*quantity_in_stock) as pot FROM products")->fetch_assoc();
$stats['inv_val'] = (float)($stock_r['val']??0);
$stats['pot_rev'] = (float)($stock_r['pot']??0);

// Chart data
$chartLabels = []; $chartRevenue = []; $chartProfit = []; $chartQty = [];
$summaries = [];

if ($view === 'trend') {
    $sql = "SELECT sale_date, SUM(total_price) as rev, SUM(cost_price) as cost, SUM(total_price-cost_price) as profit, SUM(quantity_sold) as qty, COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status='recorded' GROUP BY sale_date ORDER BY sale_date ASC";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $summaries[] = $r;
        $chartLabels[] = date('M d', strtotime($r['sale_date']));
        $chartRevenue[] = (float)$r['rev'];
        $chartProfit[]  = (float)$r['profit'];
        $chartQty[]     = (int)$r['qty'];
    }
} elseif ($view === 'category') {
    $sql = "SELECT c.name as cat_name, SUM(s.total_price) as rev, SUM(s.cost_price) as cost, SUM(s.total_price-s.cost_price) as profit, SUM(s.quantity_sold) as qty, COUNT(*) as cnt FROM sales s LEFT JOIN products p ON s.product_id=p.id LEFT JOIN categories c ON p.category_id=c.id WHERE s.sale_date BETWEEN '$from' AND '$to' AND s.status='recorded' GROUP BY p.category_id ORDER BY rev DESC";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $r['cat_name'] = $r['cat_name'] ?? 'Uncategorized';
        $summaries[] = $r;
        $chartLabels[]  = $r['cat_name'];
        $chartRevenue[] = (float)$r['rev'];
        $chartProfit[]  = (float)$r['profit'];
        $chartQty[]     = (int)$r['qty'];
    }
} elseif ($view === 'product') {
    $sql = "SELECT p.name, SUM(s.total_price) as rev, SUM(s.cost_price) as cost, SUM(s.total_price-s.cost_price) as profit, SUM(s.quantity_sold) as qty, COUNT(*) as cnt FROM sales s JOIN products p ON s.product_id=p.id WHERE s.sale_date BETWEEN '$from' AND '$to' AND s.status='recorded' GROUP BY s.product_id ORDER BY rev DESC LIMIT 15";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $summaries[] = $r;
        $chartLabels[]  = $r['name'];
        $chartRevenue[] = (float)$r['rev'];
        $chartProfit[]  = (float)$r['profit'];
    }
} elseif ($view === 'payment') {
    $sql = "SELECT payment_method, SUM(total_price) as rev, SUM(cost_price) as cost, SUM(quantity_sold) as qty, COUNT(*) as cnt FROM sales WHERE sale_date BETWEEN '$from' AND '$to' AND status='recorded' GROUP BY payment_method";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $summaries[] = $r;
        $chartLabels[]  = $r['payment_method'];
        $chartRevenue[] = (float)$r['rev'];
    }
} elseif ($view === 'staff') {
    $sql = "SELECT u.username, SUM(s.total_price) as rev, SUM(s.cost_price) as cost, SUM(s.total_price-s.cost_price) as profit, SUM(s.quantity_sold) as qty, COUNT(*) as cnt FROM sales s JOIN users u ON s.user_id=u.id WHERE s.sale_date BETWEEN '$from' AND '$to' AND s.status='recorded' GROUP BY s.user_id ORDER BY rev DESC";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $summaries[] = $r;
        $chartLabels[]  = $r['username'];
        $chartRevenue[] = (float)$r['rev'];
        $chartProfit[]  = (float)$r['profit'];
    }
}

$catColors = ['#4f46e5','#059669','#d97706','#dc2626','#7c3aed','#0284c7','#db2777','#065f46','#92400e'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Analytics — P3 Shop Pro</title>
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
                <div class="page-title">Business Analytics</div>
                <div class="page-subtitle"><?= $from ?> to <?= $to ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="export_csv.php?type=unified&from=<?= $from ?>&to=<?= $to ?>" class="btn btn-primary btn-sm">
                <i class="ri-file-excel-2-line"></i> Export Report
            </a>
            <a href="export_logs.php" class="btn btn-outline btn-sm">
                <i class="ri-download-2-line"></i> Audit Log
            </a>
        </div>
    </div>

    <div class="page-content">
        <!-- Filter Bar -->
        <div class="card mb-3">
            <div class="card-body" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                    <?php foreach (['today'=>'Today','week'=>'Last 7 Days','month'=>'This Month','year'=>'This Year'] as $k=>$lbl): ?>
                    <a href="?preset=<?= $k ?>&view=<?= $view ?>" class="btn btn-sm <?= $preset==$k ? 'btn-primary' : 'btn-outline' ?>"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>
                <form method="GET" style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="view" value="<?= $view ?>">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">From</label>
                        <input type="date" name="from" value="<?= $from ?>" class="form-control" style="width:auto;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">To</label>
                        <input type="date" name="to" value="<?= $to ?>" class="form-control" style="width:auto;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-3-line"></i> Apply</button>
                </form>
                <!-- Daily snapshot export -->
                <div style="display:flex; align-items:flex-end; gap:0.5rem; padding-left:1rem; border-left:1px solid var(--border);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Day Snapshot</label>
                        <input type="date" id="snap_date" value="<?= date('Y-m-d') ?>" class="form-control" style="width:auto;">
                    </div>
                    <button onclick="window.location='export_csv.php?type=inventory&snapshot_date='+document.getElementById('snap_date').value" class="btn btn-outline btn-sm">
                        <i class="ri-history-line"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="kpi-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
            <?php
            $kpis = [
                ['Revenue','ri-money-pound-circle-line','primary','₦'.number_format($stats['rev'],0),trend($stats['rev'],$comp['rev']),'vs prev period'],
                ['Profit','ri-bar-chart-box-line','success','₦'.number_format($stats['profit'],0),trend($stats['profit'],$comp['profit']),'vs prev period'],
                ['Margin','ri-percent-line','info',number_format($stats['margin'],1).'%',0,'gross margin'],
                ['Units Sold','ri-shopping-bag-line','warning',number_format($stats['qty']),trend($stats['qty'],$comp['qty']),'vs prev period'],
                ['Transactions','ri-receipt-line','primary',$stats['cnt'],trend($stats['cnt'],$comp['cnt']),'vs prev period'],
                ['Avg Order','ri-price-tag-line','info','₦'.number_format($stats['avg'],0),0,'per transaction'],
                ['Inv. Value','ri-stack-line','success','₦'.number_format($stats['inv_val'],0),0,'current stock'],
                ['Potential Rev','ri-rocket-line','warning','₦'.number_format($stats['pot_rev'],0),0,'if all sold'],
            ];
            $cls=['primary','success','info','warning','danger'];
            foreach ($kpis as $i=>$kpi): $t=$kpi[4]; $has_comp=$kpi[4]!==0;?>
            <div class="kpi-card <?= $kpi[2] ?>">
                <div class="kpi-label"><i class="<?= $kpi[1] ?>"></i> <?= $kpi[0] ?></div>
                <div class="kpi-value"><?= $kpi[3] ?></div>
                <div class="kpi-meta <?= $has_comp ? ($t>=0?'kpi-trend-up':'kpi-trend-down') : 'text-muted' ?>">
                    <?php if($has_comp): ?><i class="ri-arrow-<?= $t>=0?'up':'down' ?>-line"></i> <?= abs(round($t,1)) ?>%<?php else: ?><?= $kpi[5] ?><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- View Tabs + Main Content -->
        <div class="card mb-3">
            <div class="tab-nav">
                <?php foreach (['trend'=>['ri-line-chart-line','Revenue Trend'],'category'=>['ri-folders-line','By Category'],'product'=>['ri-medal-line','Top Products'],'payment'=>['ri-bank-card-line','Payment Split'],'staff'=>['ri-group-line','Staff Performance']] as $v=>$meta): ?>
                <a href="?view=<?= $v ?>&from=<?= $from ?>&to=<?= $to ?>" class="tab-btn <?= $view==$v?'active':'' ?>">
                    <i class="<?= $meta[0] ?>"></i> <?= $meta[1] ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="grid-2 p-0" style="padding:0;">
                <!-- Chart -->
                <div style="border-right:1px solid var(--border);">
                    <div class="chart-wrapper" style="height:360px;">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>

                <!-- Detail Table -->
                <div class="table-container" style="max-height:400px; overflow-y:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?= $view==='trend'?'Date':($view==='category'?'Category':($view==='product'?'Product':($view==='payment'?'Method':'Staff'))) ?></th>
                                <th>Revenue</th>
                                <?php if ($view !== 'payment'): ?><th>Profit</th><th>Margin</th><?php endif; ?>
                                <th>Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaries as $s):
                                $nm = $view==='trend' ? date('d M',strtotime($s['sale_date'])) : ($view==='category' ? ($s['cat_name']??'—') : ($view==='product' ? $s['name'] : ($view==='payment' ? $s['payment_method'] : $s['username'])));
                                $margin = isset($s['rev'],$s['cost']) && $s['rev']>0 ? (($s['rev']-$s['cost'])/$s['rev']*100) : 0;
                            ?>
                            <tr>
                                <td data-label="Name"><strong><?= htmlspecialchars($nm) ?></strong></td>
                                <td data-label="Revenue" class="money">₦<?= number_format($s['rev'],0) ?></td>
                                <?php if ($view !== 'payment'): ?>
                                <td data-label="Profit" class="money">₦<?= number_format($s['profit']??0,0) ?></td>
                                <td data-label="Margin"><?= number_format($margin,1) ?>%</td>
                                <?php endif; ?>
                                <td data-label="Units"><?= number_format($s['qty']??0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($summaries)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No data for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<script>
const ctx = document.getElementById('mainChart').getContext('2d');
const view = '<?= $view ?>';
const labels = <?= json_encode($chartLabels) ?>;
const revenue = <?= json_encode($chartRevenue) ?>;
const profit  = <?= json_encode($chartProfit) ?>;
const colors  = <?= json_encode($catColors) ?>;

let chartConfig;

if (view === 'trend') {
    chartConfig = {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue', data: revenue, backgroundColor: 'rgba(79,70,229,0.7)', borderRadius: 6, borderSkipped: false },
                { label: 'Profit',  data: profit,  backgroundColor: 'rgba(5,150,105,0.7)',  borderRadius: 6, borderSkipped: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { color: '#64748b', padding: 16 } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: v => '₦' + (v/1000).toFixed(0) + 'k' } }
            }
        }
    };
} else if (view === 'category' || view === 'payment') {
    chartConfig = {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: revenue, backgroundColor: colors, borderWidth: 0, hoverOffset: 8 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: { legend: { position: 'right', labels: { color: '#64748b', padding: 12 } } }
        }
    };
} else {
    chartConfig = {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue', data: revenue, backgroundColor: 'rgba(79,70,229,0.7)', borderRadius: 6, borderSkipped: false },
                { label: 'Profit',  data: profit,  backgroundColor: 'rgba(5,150,105,0.7)',  borderRadius: 6, borderSkipped: false }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { color: '#64748b' } } },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: v => '₦' + (v/1000).toFixed(0) + 'k' } },
                y: { grid: { display: false }, ticks: { color: '#64748b' } }
            }
        }
    };
}

new Chart(ctx, chartConfig);
</script>
</body>
</html>
