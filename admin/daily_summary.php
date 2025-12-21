<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$safe_report_date = mysqli_real_escape_string($conn, $report_date);

// === GET SUMMARY DATA ===
$sql_summary = "SELECT 
                    SUM(total_price) as total_revenue,
                    SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_revenue,
                    SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_revenue,
                    SUM(CASE WHEN payment_method = 'Card' THEN total_price ELSE 0 END) as card_revenue
                FROM sales
                WHERE sale_date = '" . $safe_report_date . "'";
$summary_result = $conn->query($sql_summary);
$summary = $summary_result->fetch_assoc();

// === NEW: GET TOP SELLING PRODUCTS FOR THE DAY ===
$top_products = array();
$sql_top = "SELECT p.name, SUM(s.quantity_sold) as total_quantity 
            FROM sales s JOIN products p ON s.product_id = p.id
            WHERE s.sale_date = '" . $safe_report_date . "' 
            GROUP BY s.product_id 
            ORDER BY total_quantity DESC LIMIT 5";
$top_result = $conn->query($sql_top);
if ($top_result) { while ($row = $top_result->fetch_assoc()) { $top_products[] = $row; } }

// === GET DETAILED LOG ===
$sales_log = array();
$sql_log = "SELECT p.name as product_name, s.quantity_sold, s.total_price, s.payment_method, u.username as staff_name 
            FROM sales s JOIN products p ON s.product_id = p.id JOIN users u ON s.user_id = u.id
            WHERE s.sale_date = '" . $safe_report_date . "' ORDER BY s.id DESC";
$sales_result = $conn->query($sql_log);
if ($sales_result) { while ($row = $sales_result->fetch_assoc()) { $sales_log[] = $row; } }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Daily Summary Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header"><h2>Select Date for Daily Summary</h2></div>
            <form action="daily_summary.php" method="GET" class="filter-form">
                <div class="form-group"><label for="report_date">Report Date</label><input type="date" id="report_date" name="report_date" value="<?php echo htmlspecialchars($report_date); ?>"></div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Summary for <?php echo date("F d, Y", strtotime($report_date)); ?></h2>
                <a href="export_daily_csv.php?report_date=<?php echo $report_date; ?>" class="btn btn-success">Download This Day's Report</a>
            </div>
            <div class="report-cards">
                <div class="report-card"><h3>Total Revenue</h3><p class="value">₦<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></p></div>
                <div class="report-card"><h3>Cash Sales</h3><p class="value">₦<?php echo number_format($summary['cash_revenue'] ?? 0, 2); ?></p></div>
                <div class="report-card"><h3>Transfer/Card</h3><p class="value">₦<?php echo number_format(($summary['transfer_revenue'] ?? 0) + ($summary['card_revenue'] ?? 0), 2); ?></p></div>
            </div>
        </div>

        <!-- NEW: Top Selling Products Card -->
        <div class="card">
            <div class="card-header"><h2>Top Selling Products for <?php echo date("F d, Y", strtotime($report_date)); ?></h2></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Product Name</th><th>Total Quantity Sold</th></tr></thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr><td colspan="2" style="text-align: center;">No sales recorded for this date.</td></tr>
                        <?php else: foreach ($top_products as $product): ?>
                            <tr>
                                <td data-label="Product"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td data-label="Quantity Sold"><?php echo $product['total_quantity']; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Transactions for <?php echo date("F d, Y", strtotime($report_date)); ?></h2></div>
            <div class="table-wrapper">
                <table class="content-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Total Price</th><th>Payment</th><th>Sold By</th></tr></thead>
                    <tbody>
                        <?php if (empty($sales_log)): ?>
                            <tr><td colspan="5" style="text-align: center;">No sales recorded for this date.</td></tr>
                        <?php else: foreach ($sales_log as $log): ?>
                            <tr>
                                <td data-label="Product"><?php echo htmlspecialchars($log['product_name']); ?></td>
                                <td data-label="Qty"><?php echo htmlspecialchars($log['quantity_sold']); ?></td>
                                <td data-label="Total Price">₦<?php echo number_format($log['total_price'], 2); ?></td>
                                <td data-label="Payment"><?php echo htmlspecialchars($log['payment_method']); ?></td>
                                <td data-label="Sold By"><?php echo htmlspecialchars($log['staff_name']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>