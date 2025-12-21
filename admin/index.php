<?php
// Connect to the database and start the session
require_once '../includes/db.php';

// Security check: Make sure the user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="dashboard-hero">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Your shop is performing great today. Here's a quick overview of your business.</p>
        </div>
        
        <div class="report-cards">
            <?php
            // Quick stats
            $today = date('Y-m-d');
            $stats_sql = "SELECT 
                            SUM(total_price) as revenue, 
                            SUM(total_price - cost_price) as profit,
                            COUNT(*) as sales_count 
                          FROM sales WHERE sale_date = '$today'";
            $stats_res = $conn->query($stats_sql);
            $stats = $stats_res->fetch_assoc();
            ?>
            <div class="report-card">
                <h3>Today's Revenue</h3>
                <div class="value">₦<?php echo number_format($stats['revenue'] ?? 0, 2); ?></div>
            </div>
            <div class="report-card">
                <h3>Today's Profit</h3>
                <div class="value" style="color: var(--primary-light)">₦<?php echo number_format($stats['profit'] ?? 0, 2); ?></div>
            </div>
            <div class="report-card">
                <h3>Sales Count</h3>
                <div class="value"><?php echo $stats['sales_count'] ?? 0; ?></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="products.php" class="btn btn-primary"><i class="ri-add-line"></i> Add Product</a>
                    <a href="summaries.php" class="btn btn-info"><i class="ri-pie-chart-line"></i> View Reports</a>
                    <a href="manage_staff.php" class="btn btn-warning"><i class="ri-user-settings-line"></i> Manage Staff</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Low Stock Alerts</h2>
                </div>
                <div class="table-wrapper">
                    <table class="content-table">
                        <thead>
                            <tr><th>Product</th><th>Stock</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $low_stock = $conn->query("SELECT name, quantity_in_stock FROM products WHERE quantity_in_stock < 10 ORDER BY quantity_in_stock ASC LIMIT 5");
                            while($item = $low_stock->fetch_assoc()):
                            ?>
                            <tr>
                                <td data-label="Product"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td data-label="Stock" style="color: var(--danger); font-weight: bold;"><?php echo $item['quantity_in_stock']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>