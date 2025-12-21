<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sale_id']) && isset($_POST['reason']) && isset($_POST['action'])) {
    $sale_id = (int)$_POST['sale_id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $action = $_POST['action']; // 'voided' or 'returned'
    $user_id = $_SESSION['user_id'];

    if (!in_array($action, ['voided', 'returned'])) {
        die("Invalid action");
    }

    // Get sale details
    $sale_sql = "SELECT * FROM sales WHERE id = $sale_id AND status = 'recorded'";
    $sale_res = $conn->query($sale_sql);
    $sale = $sale_res->fetch_assoc();

    if ($sale) {
        $product_id = $sale['product_id'];
        $qty = $sale['quantity_sold'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Update sale status
            $conn->query("UPDATE sales SET status = '$action', void_reason = '$reason' WHERE id = $sale_id");

            // 2. Add back stock
            $conn->query("UPDATE products SET quantity_in_stock = quantity_in_stock + $qty WHERE id = $product_id");

            // 3. Log the stock change
            $log_reason = "$action: " . $reason . " (Sale #$sale_id)";
            $conn->query("INSERT INTO stock_log (product_id, user_id, quantity_change, reason) VALUES ($product_id, $user_id, $qty, '$log_reason')");

            $conn->commit();
            header("Location: index.php?success=Sale+reversed+successfully");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: index.php?error=Error+reversing+sale");
        }
    } else {
        header("Location: index.php?error=Sale+not+found+or+already+reversed");
    }
} else {
    header("Location: index.php");
}
?>
