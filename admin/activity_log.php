<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }

// Comprehensive Activity SQL
$sql = "(SELECT 
            sl.id as ref_id, 
            sl.log_timestamp as activity_time, 
            u.username, u.role, 
            'Stock Change' as activity_type, 
            p.name as product, 
            c.name as category,
            sl.quantity_change as qty, 
            p.cost_price / p.units_per_pack as unit_cost,
            p.price as unit_price,
            (sl.quantity_change * (p.cost_price / p.units_per_pack)) as financial_impact,
            'Admin Panel' as source,
            sl.reason as details,
            'N/A' as status
         FROM stock_log sl 
         JOIN users u ON sl.user_id = u.id 
         JOIN products p ON sl.product_id = p.id
         LEFT JOIN categories c ON p.category_id = c.id)
        UNION
        (SELECT 
            s.id as ref_id, 
            s.sale_date as activity_time, 
            u.username, u.role, 
            'Sale' as activity_type, 
            p.name as product, 
            c.name as category,
            s.quantity_sold as qty, 
            s.cost_price / s.quantity_sold as unit_cost,
            s.total_price / s.quantity_sold as unit_price,
            s.total_price as financial_impact,
            'Staff Terminal' as source,
            CONCAT(s.payment_method, IF(s.status != 'recorded', CONCAT(' (', s.status, ': ', s.void_reason, ')'), '')) as details,
            s.status
         FROM sales s 
         JOIN users u ON s.user_id = u.id 
         JOIN products p ON s.product_id = p.id
         LEFT JOIN categories c ON p.category_id = c.id)
        ORDER BY activity_time DESC LIMIT 200";

$result = $conn->query($sql);
$activities = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Audit Log - P3 Shop Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
    <div class="container" style="max-width: 1600px;">
        <div class="card">
            <div class="card-header" style="justify-content: space-between; display: flex; align-items: center;">
                <div>
                    <h2><i class="ri-shield-check-line"></i> Master Audit Log (13-Point Detail)</h2>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">Showing last 200 actions</span>
                </div>
                <a href="export_logs.php" class="btn btn-primary">
                    <i class="ri-download-cloud-2-line"></i> Download Activity Log (CSV)
                </a>
            </div>
            <div class="table-wrapper">
                <table class="content-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Date / Time</th>
                            <th>Staff (Role)</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Cost</th>
                            <th>Unit Price</th>
                            <th>Fin. Impact</th>
                            <th>Source</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                            <tr><td colspan="12" style="text-align: center;">No activity recorded.</td></tr>
                        <?php else: foreach ($activities as $act): ?>
                            <tr>
                                <td data-label="Ref ID">#<?php echo $act['ref_id']; ?></td>
                                <td data-label="Date/Time">
                                    <div style="font-weight: 600;"><?php echo date('Y-m-d', strtotime($act['activity_time'])); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('H:i:s', strtotime($act['activity_time'])); ?></div>
                                </td>
                                <td data-label="Staff">
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($act['username']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--primary-light); text-transform: uppercase;"><?php echo $act['role']; ?></div>
                                </td>
                                <td data-label="Type">
                                    <span class="badge <?php echo $act['activity_type'] == 'Sale' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $act['activity_type']; ?>
                                    </span>
                                </td>
                                <td data-label="Category"><?php echo htmlspecialchars($act['category'] ?: 'General'); ?></td>
                                <td data-label="Product" style="font-weight: 500;"><?php echo htmlspecialchars($act['product']); ?></td>
                                <td data-label="Qty"><?php echo ($act['qty'] > 0 ? '+' : '') . $act['qty']; ?></td>
                                <td data-label="Unit Cost">₦<?php echo number_format($act['unit_cost'], 2); ?></td>
                                <td data-label="Unit Price">₦<?php echo number_format($act['unit_price'], 2); ?></td>
                                <td data-label="Fin. Impact" style="font-weight: 700; color: <?php echo $act['financial_impact'] >= 0 ? 'var(--primary-light)' : 'var(--danger)'; ?>">
                                    ₦<?php echo number_format(abs($act['financial_impact']), 2); ?>
                                </td>
                                <td data-label="Source"><?php echo $act['source']; ?></td>
                                <td data-label="Details" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($act['details']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: var(--primary-light); }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
    </style>
</body>
</html>
