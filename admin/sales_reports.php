<?php
require_once '../includes/db.php';

// Security check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// === SIMPLEST POSSIBLE QUERY TO GET ALL SALES ===
$sales_log = array();
$sql = "SELECT 
            s.sale_date, 
            p.name as product_name, 
            s.quantity_sold, 
            s.total_price, 
            u.username as staff_name 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.id DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sales_log[] = $row;
    }
} else {
    // If the query fails, we can show an error
    echo "Error fetching sales data: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sales Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="ri-file-list-3-line"></i> All Sales History</h2>
            <a href="export_csv.php" class="btn btn-success"><i class="ri-download-line"></i> Export CSV</a>
        </div>
        <div class="table-wrapper">
            <table class="content-table">
                <thead>
                    <tr><th>Date</th><th>Product</th><th>Qty</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Staff</th></tr>
                </thead>
                <tbody>
                    <?php
                    $sales_res = $conn->query("SELECT s.*, p.name as product_name, u.username 
                                             FROM sales s 
                                             JOIN products p ON s.product_id = p.id 
                                             JOIN users u ON s.user_id = u.id 
                                             ORDER BY s.id DESC LIMIT 200");
                    while($row = $sales_res->fetch_assoc()):
                        $profit = $row['total_price'] - $row['cost_price'];
                    ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo $row['quantity_sold']; ?></td>
                        <td>₦<?php echo number_format($row['total_price'], 2); ?></td>
                        <td>₦<?php echo number_format($row['cost_price'], 2); ?></td>
                        <td style="color: <?php echo $profit >= 0 ? 'var(--primary-light)' : 'var(--danger)'; ?>; font-weight: bold;">
                            ₦<?php echo number_format($profit, 2); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php exit; // Stop here as we replaced the whole body ?>