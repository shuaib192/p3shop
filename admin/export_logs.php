<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { exit; }

$filename = "P3_Shop_Activity_Log_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Headers for 13-point log
fputcsv($output, ['Ref ID', 'Timestamp', 'Staff', 'Role', 'Type', 'Product', 'Category', 'Quantity', 'Unit Cost', 'Unit Price', 'Financial Impact', 'Source', 'Details/Reason', 'Status']);

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
        ORDER BY activity_time DESC";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['ref_id'],
        $row['activity_time'],
        $row['username'],
        $row['role'],
        $row['activity_type'],
        $row['product'],
        $row['category'],
        $row['qty'],
        number_format($row['unit_cost'], 2),
        number_format($row['unit_price'], 2),
        number_format($row['financial_impact'], 2),
        $row['source'],
        $row['details'],
        $row['status']
    ]);
}

fclose($output);
exit;
?>
