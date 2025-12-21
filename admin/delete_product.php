<?php
require_once '../includes/db.php';

// Security check for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// IMPORTANT: Before deleting a product, we must delete related records
// in the 'sales' and 'stock_log' tables to avoid database errors.
$conn->query("DELETE FROM sales WHERE product_id = " . $product_id);
$conn->query("DELETE FROM stock_log WHERE product_id = " . $product_id);

// Now, we can safely delete the product itself
$conn->query("DELETE FROM products WHERE id = " . $product_id);

// Redirect back to the products list
header('Location: products.php');
exit;