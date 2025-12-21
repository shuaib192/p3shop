<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$view = isset($_GET['view']) ? $_GET['view'] : 'weekly';
$preset = isset($_GET['preset']) ? $_GET['preset'] : '';

// Handle Presets
if ($preset == 'today') {
    $from = date('Y-m-d');
    $to = date('Y-m-d');
} elseif ($preset == 'week') {
    $from = date('Y-m-d', strtotime('-7 days'));
    $to = date('Y-m-d');
} elseif ($preset == 'month') {
    $from = date('Y-m-01');
    $to = date('Y-m-t');
} elseif ($preset == 'year') {
    $from = date('Y-01-01');
    $to = date('Y-12-31');
} else {
    $from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
    $to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
}

// --- DATA FETCHING LOGIC ---

$chartData = [];
$chartLabels = [];
$stats = [
    'revenue' => 0, 'cost' => 0, 'profit' => 0, 'qty' => 0, 
    'sales_count' => 0, 'avg_value' => 0, 'margin' => 0,
    'stock_value' => 0, 'potential_rev' => 0
];

// Global stats for the selected period
$global_sql = "SELECT SUM(total_price) as rev, SUM(cost_price) as cost, SUM(quantity_sold) as qty, COUNT(*) as count 
               FROM sales 
               WHERE sale_date BETWEEN '$from' AND '$to'";
$global_res = $conn->query($global_sql)->fetch_assoc();
if ($global_res) {
    $stats['revenue'] = (float)$global_res['rev'];
    $stats['cost'] = (float)$global_res['cost'];
    $stats['profit'] = $stats['revenue'] - $stats['cost'];
    $stats['qty'] = (int)$global_res['qty'];
    $stats['sales_count'] = (int)$global_res['count'];
    $stats['avg_value'] = $stats['sales_count'] > 0 ? $stats['revenue'] / $stats['sales_count'] : 0;
    $stats['margin'] = $stats['revenue'] > 0 ? ($stats['profit'] / $stats['revenue']) * 100 : 0;
}

// Stock stats (always current)
$stock_state = $conn->query("SELECT SUM(cost_price/units_per_pack * quantity_in_stock) as val, SUM(price * quantity_in_stock) as pot FROM products")->fetch_assoc();
$stats['stock_value'] = (float)$stock_state['val'];
$stats['potential_rev'] = (float)$stock_state['pot'];

if ($view == 'weekly') {
    $sql = "SELECT sale_date, SUM(total_price) as revenue, SUM(cost_price) as cost, SUM(total_price - cost_price) as profit 
            FROM sales 
            WHERE sale_date BETWEEN '$from' AND '$to'
            GROUP BY sale_date 
            ORDER BY sale_date ASC";
    $result = $conn->query($sql);
    $summaries = [];
    while ($row = $result->fetch_assoc()) {
        $summaries[] = $row;
        $chartLabels[] = date('M d', strtotime($row['sale_date']));
        $chartData['revenue'][] = (float)$row['revenue'];
        $chartData['profit'][] = (float)$row['profit'];
    }
} elseif ($view == 'category') {
    $sql = "SELECT c.name as cat_name, SUM(s.total_price) as revenue, SUM(s.cost_price) as cost, SUM(s.total_price - s.cost_price) as profit, SUM(s.quantity_sold) as qty 
            FROM sales s 
            LEFT JOIN products p ON s.product_id = p.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE s.sale_date BETWEEN '$from' AND '$to'
            GROUP BY p.category_id 
            ORDER BY revenue DESC";
    $result = $conn->query($sql);
    $summaries = [];
    while ($row = $result->fetch_assoc()) {
        $name = $row['cat_name'] ?: 'Uncategorized';
        $row['cat_name'] = $name;
        $summaries[] = $row;
        $chartLabels[] = $name;
        $chartData['revenue'][] = (float)$row['revenue'];
        $chartData['profit'][] = (float)$row['profit'];
        $chartData['qty'][] = (int)$row['qty'];
    }
} elseif ($view == 'product') {
    $sql = "SELECT p.name, p.cost_price as pack_cost, p.units_per_pack, SUM(s.total_price) as revenue, SUM(s.cost_price) as cost, SUM(s.total_price - s.cost_price) as profit, SUM(s.quantity_sold) as qty 
            FROM sales s 
            JOIN products p ON s.product_id = p.id 
            WHERE s.sale_date BETWEEN '$from' AND '$to'
            GROUP BY s.product_id 
            ORDER BY revenue DESC LIMIT 15";
    $result = $conn->query($sql);
    $summaries = [];
    while ($row = $result->fetch_assoc()) {
        $summaries[] = $row;
        $chartLabels[] = $row['name'];
        $chartData['revenue'][] = (float)$row['revenue'];
        $chartData['profit'][] = (float)$row['profit'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Business Summaries</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div class="preset-filters" style="display: flex; gap: 0.5rem;">
                    <a href="?preset=today&view=<?php echo $view; ?>" class="btn <?php echo $preset == 'today' ? 'btn-primary' : 'btn-info'; ?> btn-sm">Today</a>
                    <a href="?preset=week&view=<?php echo $view; ?>" class="btn <?php echo $preset == 'week' ? 'btn-primary' : 'btn-info'; ?> btn-sm">This Week</a>
                    <a href="?preset=month&view=<?php echo $view; ?>" class="btn <?php echo $preset == 'month' ? 'btn-primary' : 'btn-info'; ?> btn-sm">This Month</a>
                    <a href="?preset=year&view=<?php echo $view; ?>" class="btn <?php echo $preset == 'year' ? 'btn-primary' : 'btn-info'; ?> btn-sm">This Year</a>
                </div>
                
                <form method="GET" class="filter-form" style="display: flex; align-items: flex-end; gap: 1rem; margin-bottom: 0;">
                    <input type="hidden" name="view" value="<?php echo $view; ?>">
                    <div class="form-group" style="margin-bottom: 0;"><label style="font-size: 0.75rem;">From Date</label><input type="date" name="from" value="<?php echo $from; ?>" style="padding: 0.5rem;"></div>
                    <div class="form-group" style="margin-bottom: 0;"><label style="font-size: 0.75rem;">To Date</label><input type="date" name="to" value="<?php echo $to; ?>" style="padding: 0.5rem;"></div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;"><i class="ri-filter-3-line"></i> Filter</button>
                    
                    <div style="display: flex; gap: 0.5rem; border-left: 1px solid var(--glass-border); padding-left: 1rem;">
                        <a title="Master Comprehensive Report (Sales + Inventory)" href="export_csv.php?type=unified&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;"><i class="ri-file-list-3-line"></i> Master Report</a>
                        <a title="Export Sales Only" href="export_csv.php?type=sales&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="btn btn-success" style="padding: 0.5rem 1rem;"><i class="ri-file-excel-line"></i> Sales Only</a>
                        <a title="Export Inventory Movement" href="export_csv.php?type=inventory&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="btn btn-warning" style="padding: 0.5rem 1rem;"><i class="ri-arrow-left-right-line"></i> Inv. Movement</a>
                        
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <label style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Daily Snapshot</label>
                            <div style="display: flex; gap: 0.4rem;">
                                <input type="date" id="hist_inv_date" value="<?php echo date('Y-m-d'); ?>" style="padding: 4px; font-size: 0.8rem; background: var(--glass-bg); color: white; border: 1px solid var(--glass-border); border-radius: 4px;">
                                <button type="button" onclick="downloadHistInventory()" class="btn btn-info btn-sm" title="Download Day Snapshot"><i class="ri-history-line"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function downloadHistInventory() {
                const date = document.getElementById('hist_inv_date').value;
                window.location.href = `export_csv.php?type=inventory&snapshot_date=${date}`;
            }
        </script>

        <div class="report-cards" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="report-card"><h3>Revenue</h3><div class="value">₦<?php echo number_format($stats['revenue'], 2); ?></div></div>
            <div class="report-card"><h3>Profit</h3><div class="value" style="color: var(--primary-light)">₦<?php echo number_format($stats['profit'], 2); ?></div></div>
            <div class="report-card"><h3>Margin</h3><div class="value"><?php echo number_format($stats['margin'], 1); ?>%</div></div>
            <div class="report-card"><h3>Total Units</h3><div class="value"><?php echo number_format($stats['qty']); ?></div></div>
            <div class="report-card"><h3>Avg Order</h3><div class="value">₦<?php echo number_format($stats['avg_value'], 2); ?></div></div>
            <div class="report-card"><h3>Transactions</h3><div class="value"><?php echo $stats['sales_count']; ?></div></div>
            <div class="report-card" style="background: rgba(59, 130, 246, 0.1);"><h3>Stock Asset</h3><div class="value">₦<?php echo number_format($stats['stock_value'], 2); ?></div></div>
            <div class="report-card" style="background: rgba(16, 185, 129, 0.1);"><h3>Pot. Revenue</h3><div class="value">₦<?php echo number_format($stats['potential_rev'], 2); ?></div></div>
        </div>

        <div class="tabs" style="margin-top: 2rem;">
            <a href="?view=weekly&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab-btn <?php echo $view == 'weekly' ? 'active' : ''; ?>"><i class="ri-calendar-line"></i> Weekly Trend</a>
            <a href="?view=category&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab-btn <?php echo $view == 'category' ? 'active' : ''; ?>"><i class="ri-folders-line"></i> By Category</a>
            <a href="?view=product&from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="tab-btn <?php echo $view == 'product' ? 'active' : ''; ?>"><i class="ri-medal-line"></i> Top Products</a>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3 style="margin-bottom: 1rem; color: var(--text-muted);"><?php echo ucfirst($view); ?> Performance Chart</h3>
                <canvas id="summaryChart"></canvas>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="ri-list-check"></i> Detail View</h2>
                </div>
                <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th><?php echo $view == 'weekly' ? 'Date' : ($view == 'category' ? 'Category' : 'Product'); ?></th>
                                <th>Revenue</th>
                                <th>Profit</th>
                                <th>Qty</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaries as $row): 
                                $row_margin = $row['revenue'] > 0 ? (($row['revenue'] - $row['cost'])/$row['revenue'])*100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $view == 'weekly' ? date('d M', strtotime($row['sale_date'])) : ($view == 'category' ? htmlspecialchars($row['cat_name']) : htmlspecialchars($row['name'])); ?></td>
                                <td>₦<?php echo number_format($row['revenue'], 0); ?></td>
                                <td style="color: var(--primary-light)">₦<?php echo number_format($row['profit'], 0); ?></td>
                                <td><?php echo $row['qty'] ?? 'N/A'; ?></td>
                                <td><?php echo number_format($row_margin, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('summaryChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: '<?php echo $view == 'category' ? 'doughnut' : 'bar'; ?>',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($chartData['revenue']); ?>,
                    backgroundColor: <?php echo $view == 'category' ? 
                        "['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#f97316']" : 
                        "'rgba(16, 185, 129, 0.7)'"; ?>,
                    borderRadius: 8
                },
                <?php if ($view != 'category'): ?>
                {
                    label: 'Profit',
                    data: <?php echo json_encode($chartData['profit']); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 8
                }
                <?php endif; ?>
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#94a3b8' } }
            },
            scales: <?php echo $view == 'category' ? 'null' : '{ y: { beginAtZero: true, grid: { color: "rgba(255,255,255,0.05)" }, ticks: { color: "#94a3b8" } }, x: { grid: { display: false }, ticks: { color: "#94a3b8" } } }'; ?>
        }
    });
    </script>
</body>
</html>
