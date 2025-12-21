<?php
require_once '../includes/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { exit; }

$type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$from = (isset($_GET['from']) && !empty($_GET['from'])) ? $_GET['from'] : date('Y-m-d');
$to = (isset($_GET['to']) && !empty($_GET['to'])) ? $_GET['to'] : date('Y-m-d');

$filename = "P3_Shop_Master_Report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// --- PART 1: SALES REPORT SECTION ---
if ($type == 'sales' || $type == 'unified') {
    fputcsv($output, ['SALES TRANSACTION LOG', "$from to $to"]);
    fputcsv($output, ['Report Period', 'Date', 'Product', 'Category', 'Qty Sold', 'Unit Price', 'Total Price', 'Unit Cost', 'Total Cost', 'Gross Profit', 'Margin %', 'Payment', 'Staff']);

    $sql = "SELECT s.*, p.name as product_name, c.name as cat_name, u.username as staff_name 
            FROM sales s 
            JOIN products p ON s.product_id = p.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            JOIN users u ON s.user_id = u.id 
            WHERE s.sale_date BETWEEN '$from' AND '$to' 
            ORDER BY s.id DESC";

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $unit_cost_at_sale = $row['quantity_sold'] > 0 ? $row['cost_price'] / $row['quantity_sold'] : 0;
        $total_cost = $row['cost_price'];
        $profit = (float)$row['total_price'] - $total_cost;
        $margin = (float)$row['total_price'] > 0 ? ($profit / $row['total_price']) * 100 : 0;
        
        fputcsv($output, [
            "$from to $to", $row['sale_date'], $row['product_name'], $row['cat_name'] ?: 'N/A',
            $row['quantity_sold'], number_format($row['quantity_sold'] > 0 ? $row['total_price'] / $row['quantity_sold'] : 0, 2),
            number_format($row['total_price'], 2), number_format($unit_cost_at_sale, 2),
            number_format($total_cost, 2), number_format($profit, 2),
            number_format($margin, 1) . '%', $row['payment_method'], $row['staff_name']
        ]);
    }

    if ($type == 'unified') {
        fputcsv($output, []);
        fputcsv($output, []);
    }
}

// --- PART 2: CATEGORY SUMMARY SECTION ---
if ($type == 'unified') {
    fputcsv($output, ['CATEGORY PERFORMANCE SUMMARY', "$from to $to"]);
    fputcsv($output, ['Category', 'Items Count', 'Opening Value', 'Closing Value', 'Total Sold Qty', 'Total Cost', 'Total Revenue', 'Gross Profit']);

    $c_sql = "SELECT c.id, c.name FROM categories c ORDER BY c.name ASC";
    $categories = $conn->query($c_sql);
    
    while ($cat = $categories->fetch_assoc()) {
        $cid = $cat['id'];
        $items_res = $conn->query("SELECT id, cost_price, units_per_pack, price, quantity_in_stock FROM products WHERE category_id = $cid");
        
        $cat_opening_val = 0; $cat_closing_val = 0; $cat_sold_qty = 0; $cat_cost = 0; $cat_rev = 0; $count = 0;

        while ($p = $items_res->fetch_assoc()) {
            $pid = $p['id']; $count++;
            $u_cost = (float)$p['cost_price'] / ($p['units_per_pack'] ?: 1);
            $s_data = $conn->query("SELECT SUM(quantity_sold) as q, SUM(total_price) as r, SUM(cost_price) as c FROM sales WHERE product_id = $pid AND sale_date BETWEEN '$from' AND '$to'")->fetch_assoc();
            $cat_sold_qty += (int)$s_data['q']; $cat_rev += (float)$s_data['r']; $cat_cost += (float)$s_data['c'];

            $sales_since_start = (int)$conn->query("SELECT SUM(quantity_sold) as s FROM sales WHERE product_id = $pid AND sale_date >= '$from'")->fetch_assoc()['s'];
            $adj_since_start = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND log_timestamp >= '$from 00:00:00'")->fetch_assoc()['a'];
            $opening = (int)$p['quantity_in_stock'] + $sales_since_start - $adj_since_start;
            
            $add_in = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND quantity_change > 0 AND log_timestamp BETWEEN '$from 00:00:00' AND '$to 23:59:59'")->fetch_assoc()['a'];
            $rem_in = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND quantity_change < 0 AND log_timestamp BETWEEN '$from 00:00:00' AND '$to 23:59:59'")->fetch_assoc()['a'];
            $closing = $opening + $add_in + $rem_in - (int)$s_data['q'];

            $cat_opening_val += ($opening * $u_cost);
            $cat_closing_val += ($closing * $u_cost);
        }

        fputcsv($output, [
            "$from to $to", $cat['name'], $count, number_format($cat_opening_val, 2), number_format($cat_closing_val, 2),
            $cat_sold_qty, number_format($cat_cost, 2), number_format($cat_rev, 2), number_format($cat_rev - $cat_cost, 2)
        ]);
    }
    fputcsv($output, []);
    fputcsv($output, []);
}

// --- PART 3: DETAILED INVENTORY MOVEMENT (GROUPED BY CATEGORY) ---
if ($type == 'inventory' || $type == 'unified') {
    $is_period = ($from != $to || $type == 'unified');
    fputcsv($output, [$is_period ? 'DETAILED INVENTORY MOVEMENT' : 'DETAILED INVENTORY SNAPSHOT', "$from to $to"]);

    $c_sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $categories = $conn->query($c_sql);

    while ($cat = $categories->fetch_assoc()) {
        $cid = $cat['id'];
        fputcsv($output, []);
        fputcsv($output, ['CATEGORY', strtoupper($cat['name'])]);
        
        if ($is_period) {
            fputcsv($output, ['Report Period', 'Product ID', 'Name', 'Opening Stock', 'Qty Added (+)', 'Qty Removed (-)', 'Qty Sold', 'Closing Stock', 'Unit Cost (₦)', 'Closing Value (₦)']);
        } else {
            fputcsv($output, ['Report Period', 'Product ID', 'Name', 'Stock Level', 'Pack Cost (₦)', 'Units/Pack', 'Unit Cost (₦)', 'Price/Unit (₦)', 'Inv. Value (Cost) (₦)']);
        }

        $p_sql = "SELECT * FROM products WHERE category_id = $cid ORDER BY name ASC";
        $products = $conn->query($p_sql);

        while ($row = $products->fetch_assoc()) {
            $pid = $row['id']; $current = (int)$row['quantity_in_stock'];
            $unit_cost = (float)$row['cost_price'] / ($row['units_per_pack'] ?: 1);

            $sales_since = (int)$conn->query("SELECT SUM(quantity_sold) as s FROM sales WHERE product_id = $pid AND sale_date >= '$from'")->fetch_assoc()['s'];
            $adj_since = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND log_timestamp >= '$from 00:00:00'")->fetch_assoc()['a'];
            $opening_stock = $current + $sales_since - $adj_since;

            if ($is_period) {
                $sold_in = (int)$conn->query("SELECT SUM(quantity_sold) as s FROM sales WHERE product_id = $pid AND sale_date BETWEEN '$from' AND '$to'")->fetch_assoc()['s'];
                $added_in = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND quantity_change > 0 AND log_timestamp BETWEEN '$from 00:00:00' AND '$to 23:59:59'")->fetch_assoc()['a'];
                $removed_in = (int)$conn->query("SELECT SUM(quantity_change) as a FROM stock_log WHERE product_id = $pid AND quantity_change < 0 AND log_timestamp BETWEEN '$from 00:00:00' AND '$to 23:59:59'")->fetch_assoc()['a'];
                $closing_stock = $opening_stock + $added_in + $removed_in - $sold_in;

                fputcsv($output, [
                    "$from to $to", $pid, $row['name'], $opening_stock, $added_in, abs($removed_in), $sold_in, $closing_stock,
                    number_format($unit_cost, 2), number_format($unit_cost * $closing_stock, 2)
                ]);
            } else {
                fputcsv($output, [
                    "$from to $to", $pid, $row['name'], $opening_stock, number_format($row['cost_price'], 2), $row['units_per_pack'],
                    number_format($unit_cost, 2), number_format($row['price'], 2),
                    number_format($unit_cost * $opening_stock, 2)
                ]);
            }
        }
    }
}

fclose($output);
exit;