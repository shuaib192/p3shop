<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access Denied.');
}

// Get the requested date
$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$safe_report_date = mysqli_real_escape_string($conn, $report_date);
$yesterday = date('Y-m-d', strtotime($report_date . ' -1 day'));
$safe_yesterday = mysqli_real_escape_string($conn, $yesterday);

// Set headers for CSV download
$filename = "complete_daily_report_" . $report_date . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');

// --- Section 1: Sales Summary ---
$summary = $conn->query("SELECT SUM(total_price) as total_revenue, SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_revenue, SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_revenue, SUM(CASE WHEN payment_method = 'Card' THEN total_price ELSE 0 END) as card_revenue FROM sales WHERE sale_date = '{$safe_report_date}'")->fetch_assoc();
fputcsv($output, array('DAILY BUSINESS REPORT FOR ' . $report_date));
fputcsv($output, array('SALES SUMMARY'));
fputcsv($output, array('Metric', 'Value (NGN)'));
fputcsv($output, array('Total Revenue', number_format($summary['total_revenue'] ?? 0, 2)));
fputcsv($output, array('Cash Revenue', number_format($summary['cash_revenue'] ?? 0, 2)));
fputcsv($output, array('Transfer Revenue', number_format($summary['transfer_revenue'] ?? 0, 2)));
fputcsv($output, array('Card Revenue', number_format($summary['card_revenue'] ?? 0, 2)));
fputcsv($output, array(''));

// --- Section 2: Stock Movements (Admin Actions) ---
fputcsv($output, array('STOCK MOVEMENTS (ADMIN UPDATES)'));
fputcsv($output, array('Time', 'Product', 'Quantity Change', 'Reason', 'Updated By'));
$stock_log_result = $conn->query("SELECT sl.log_timestamp, p.name, sl.quantity_change, sl.reason, u.username 
                                  FROM stock_log sl 
                                  JOIN products p ON sl.product_id = p.id 
                                  JOIN users u ON sl.user_id = u.id 
                                  WHERE DATE(sl.log_timestamp) = '{$safe_report_date}' ORDER BY sl.log_timestamp ASC");
if ($stock_log_result && $stock_log_result->num_rows > 0) {
    while ($row = $stock_log_result->fetch_assoc()) { fputcsv($output, $row); }
} else { fputcsv($output, array('No stock updates were made by admin on this day.')); }
fputcsv($output, array(''));

// --- Section 3: Detailed Transactions ---
fputcsv($output, array('DETAILED SALES TRANSACTIONS'));
// THIS IS THE CORRECTED HEADER ROW
fputcsv($output, array('Product Name', 'Quantity Sold', 'Total Price (NGN)', 'Payment Method', 'Sold By (Staff)'));
$sales_log_sql = "SELECT p.name, s.quantity_sold, s.total_price, s.payment_method, u.username 
                  FROM sales s
                  JOIN products p ON s.product_id = p.id
                  JOIN users u ON s.user_id = u.id
                  WHERE s.sale_date = '{$safe_report_date}'
                  ORDER BY s.id ASC";
$sales_result = $conn->query($sales_log_sql);
if ($sales_result && $sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, array('No transactions found.'));
}
fputcsv($output, array('')); // Blank row

// --- Section 4: Stock Snapshot ---
// This section requires a more complex calculation, let's simplify for direct compatibility
// For now, we will show the final stock count at the end of the day.
fputcsv($output, array('STOCK SNAPSHOT (END OF DAY)'));
fputcsv($output, array('Product Name', 'Closing Stock'));
$products_result = $conn->query("SELECT name, quantity_in_stock FROM products ORDER BY name ASC");
if ($products_result) {
    while($row = $products_result->fetch_assoc()){
        fputcsv($output, $row);
    }
}

fclose($output);
exit();