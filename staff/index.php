<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') { header('Location: ../login.php'); exit; }

$message = '';
$staff_id = (int)$_SESSION['user_id'];
$today_date = date('Y-m-d');
$username = $_SESSION['username'];

// Handle sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_sale'])) {
    $cart = json_decode($_POST['cart_data'] ?? '[]', true);
    $payment = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'Cash');

    if (!empty($cart) && !empty($payment)) {
        $all_ok = true;
        $receipt_items = [];

        foreach ($cart as $item) {
            $pid = (int)$item['id'];
            $qty = (int)$item['qty'];
            if ($pid <= 0 || $qty <= 0) continue;

            $p = $conn->query("SELECT * FROM products WHERE id=$pid")->fetch_assoc();
            if (!$p || (int)$p['quantity_in_stock'] < $qty) { $all_ok = false; break; }

            $new_stock = (int)$p['quantity_in_stock'] - $qty;
            $total = (float)$p['price'] * $qty;
            $unit_cost = (float)$p['cost_price'] / (int)$p['units_per_pack'];
            $cost = $unit_cost * $qty;
            $safe_pay = mysqli_real_escape_string($conn, $payment);

            $conn->query("UPDATE products SET quantity_in_stock=$new_stock WHERE id=$pid");
            $conn->query("INSERT INTO sales (product_id, user_id, quantity_sold, total_price, cost_price, sale_date, payment_method) VALUES ($pid, $staff_id, $qty, $total, $cost, '$today_date', '$safe_pay')");

            $receipt_items[] = ['name' => $p['name'], 'qty' => $qty, 'price' => $p['price'], 'total' => $total];
        }

        if ($all_ok && !empty($receipt_items)) {
            $receipt_total = array_sum(array_column($receipt_items, 'total'));
            $message = 'success';
        } else {
            $message = 'error';
        }
    }
}

// Fetch products
$products_in_stock = [];
$res = $conn->query("SELECT p.id, p.name, p.quantity_in_stock, p.price, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY c.name ASC, p.name ASC");
while ($r = $res->fetch_assoc()) {
    $cat = $r['category_name'] ?: 'Others';
    $products_in_stock[$cat][] = $r;
}

// Today's sales
$todays_sales = [];
$sr = $conn->query("SELECT s.id, p.name, s.quantity_sold, s.total_price, s.payment_method, s.status, s.void_reason FROM sales s JOIN products p ON s.product_id=p.id WHERE s.user_id=$staff_id AND s.sale_date='$today_date' ORDER BY s.id DESC");
while ($r = $sr->fetch_assoc()) $todays_sales[] = $r;

// Today's totals
$today_stats = $conn->query("SELECT SUM(total_price) as rev, COUNT(*) as cnt FROM sales WHERE user_id=$staff_id AND sale_date='$today_date' AND status='recorded'")->fetch_assoc();
$my_rev = (float)($today_stats['rev']??0);
$my_cnt = (int)($today_stats['cnt']??0);

$all_categories = array_keys($products_in_stock);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal — P3 Shop Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:var(--bg);">

<!-- Staff Top Bar -->
<div class="topbar" style="position:sticky;top:0;z-index:100;">
    <div class="topbar-left">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:36px;height:36px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.1rem;">
                <i class="ri-store-2-fill"></i>
            </div>
            <div>
                <div class="page-title">POS Terminal</div>
                <div class="page-subtitle"><?= htmlspecialchars($username) ?> • <?= date('d M Y') ?></div>
            </div>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-time" id="live-clock"></span>
        <a href="../logout.php" class="btn btn-danger btn-sm"><i class="ri-logout-box-r-line"></i> Logout</a>
    </div>
</div>

<div style="padding:1rem 1.5rem;">
    <?php if ($message === 'success'): ?>
        <div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Sale recorded successfully!</div>
    <?php elseif ($message === 'error'): ?>
        <div class="alert alert-danger"><i class="ri-error-warning-line"></i> Sale failed. Check stock levels.</div>
    <?php endif; ?>

    <!-- Summary Bar -->
    <div class="staff-summary-bar">
        <div class="staff-summary-item"><div class="s-value">₦<?= number_format($my_rev,0) ?></div><div class="s-label">My Sales Today</div></div>
        <div class="staff-summary-item"><div class="s-value"><?= $my_cnt ?></div><div class="s-label">Transactions</div></div>
        <div class="staff-summary-item"><div class="s-value"><?= $my_cnt > 0 ? '₦'.number_format($my_rev/$my_cnt,0) : '—' ?></div><div class="s-label">Avg Order</div></div>
    </div>

    <!-- POS Layout -->
    <div class="pos-layout">
        <!-- Products Grid -->
        <div class="pos-products">
            <!-- Category Tabs -->
            <div class="pos-cat-tabs">
                <button class="pos-cat-btn active" onclick="filterCat('all', this)">All</button>
                <?php foreach ($all_categories as $cat): ?>
                <button class="pos-cat-btn" onclick="filterCat('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <div class="search-box" style="max-width:none;">
                <i class="ri-search-line"></i>
                <input type="text" placeholder="Search products..." id="posSearch" oninput="searchProducts()">
            </div>

            <!-- Product Buttons -->
            <div class="pos-product-grid" id="productGrid">
                <?php foreach ($products_in_stock as $cat => $items): foreach ($items as $p):
                    $out = (int)$p['quantity_in_stock'] <= 0;
                ?>
                <button class="pos-product-btn <?= $out ? 'out-of-stock' : '' ?>"
                        data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>"
                        data-price="<?= $p['price'] ?>" data-stock="<?= $p['quantity_in_stock'] ?>"
                        data-cat="<?= htmlspecialchars($cat) ?>"
                        onclick="addToCart(this)" <?= $out ? 'disabled' : '' ?>>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-price">₦<?= number_format($p['price'],0) ?></div>
                    <div class="product-stock"><?= $out ? 'Out of Stock' : $p['quantity_in_stock'].' left' ?></div>
                </button>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Cart Panel -->
        <div class="card cart-panel">
            <div class="cart-header">
                <h3><i class="ri-shopping-cart-2-line"></i> Cart</h3>
                <span class="cart-count" id="cartCount">0</span>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="cart-empty" id="cartEmpty">
                    <i class="ri-shopping-cart-line"></i>
                    <p>Tap a product to add it</p>
                </div>
            </div>

            <div class="cart-totals" id="cartTotals" style="display:none;">
                <div class="cart-total-row">
                    <span>Subtotal</span><span id="subtotalVal">₦0</span>
                </div>
                <div class="cart-total-row grand">
                    <span>Total</span><span id="grandTotal">₦0</span>
                </div>
            </div>

            <div class="cart-checkout" id="cartCheckout" style="display:none;">
                <form method="POST" id="saleForm">
                    <input type="hidden" name="cart_data" id="cartDataInput">
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                    <button type="submit" name="process_sale" class="btn btn-success btn-block btn-lg">
                        <i class="ri-check-double-line"></i> Complete Sale
                    </button>
                    <button type="button" onclick="clearCart()" class="btn btn-ghost btn-block btn-sm mt-1">Clear Cart</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Today's Sales -->
    <div class="card mt-3">
        <div class="card-header">
            <h3><i class="ri-history-line"></i> Today's Sales</h3>
            <span class="badge badge-primary"><?= count($todays_sales) ?> transactions</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($todays_sales)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-muted);">No sales yet today</td></tr>
                <?php else: foreach ($todays_sales as $sale): ?>
                    <tr>
                        <td data-label="Product"><strong><?= htmlspecialchars($sale['name']) ?></strong></td>
                        <td data-label="Qty"><?= $sale['quantity_sold'] ?></td>
                        <td data-label="Total" class="money fw-700">₦<?= number_format($sale['total_price'],0) ?></td>
                        <td data-label="Status">
                            <span class="badge badge-<?= $sale['status']==='recorded'?'success':'danger' ?>">
                                <?= ucfirst($sale['status']) ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <?php if ($sale['status'] === 'recorded'): ?>
                            <button onclick="reverseSale(<?= $sale['id'] ?>)" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);">
                                <i class="ri-arrow-go-back-line"></i> Void
                            </button>
                            <?php else: ?>
                            <span style="font-size:0.72rem;color:var(--text-muted);font-style:italic;"><?= htmlspecialchars($sale['void_reason']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Void Form -->
<form id="reversal-form" action="void_sale.php" method="POST" style="display:none;">
    <input type="hidden" name="sale_id" id="reversal-id">
    <input type="hidden" name="reason" id="reversal-reason">
    <input type="hidden" name="action" id="reversal-action">
</form>

<script>
// Clock
function updateClock() {
    document.getElementById('live-clock').textContent = new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
}
setInterval(updateClock,1000); updateClock();

// Cart State
let cart = [];

function addToCart(btn) {
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const price = parseFloat(btn.dataset.price);
    const stock = parseInt(btn.dataset.stock);

    const existing = cart.find(i => i.id == id);
    if (existing) {
        if (existing.qty < stock) existing.qty++;
        else { alert('Not enough stock!'); return; }
    } else {
        cart.push({ id, name, price, stock, qty: 1 });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('cartEmpty');
    const totals = document.getElementById('cartTotals');
    const checkout = document.getElementById('cartCheckout');
    const countEl = document.getElementById('cartCount');

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty" id="cartEmpty"><i class="ri-shopping-cart-line"></i><p>Tap a product to add it</p></div>';
        totals.style.display = 'none';
        checkout.style.display = 'none';
        countEl.textContent = '0';
        return;
    }

    countEl.textContent = cart.reduce((s,i) => s+i.qty, 0);
    let html = '';
    let total = 0;

    cart.forEach((item, idx) => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        html += `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₦${item.price.toLocaleString()} × ${item.qty} = ₦${itemTotal.toLocaleString()}</div>
            </div>
            <div class="qty-control">
                <button class="qty-btn" onclick="changeQty(${idx},-1)">−</button>
                <span class="qty-num">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty(${idx},1)">+</button>
            </div>
            <button class="cart-remove" onclick="removeItem(${idx})"><i class="ri-close-line"></i></button>
        </div>`;
    });

    container.innerHTML = html;
    document.getElementById('subtotalVal').textContent = '₦' + total.toLocaleString();
    document.getElementById('grandTotal').textContent = '₦' + total.toLocaleString();
    totals.style.display = 'block';
    checkout.style.display = 'block';
}

function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
    else if (cart[idx].qty > cart[idx].stock) { cart[idx].qty = cart[idx].stock; alert('Max stock reached!'); }
    renderCart();
}

function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function clearCart() { cart = []; renderCart(); }

// Submit sale
document.getElementById('saleForm').addEventListener('submit', function(e) {
    if (cart.length === 0) { e.preventDefault(); alert('Cart is empty!'); return; }
    document.getElementById('cartDataInput').value = JSON.stringify(cart.map(i => ({id:i.id, qty:i.qty})));
});

// Category filter
function filterCat(cat, btn) {
    document.querySelectorAll('.pos-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.pos-product-btn').forEach(b => {
        b.style.display = (cat === 'all' || b.dataset.cat === cat) ? '' : 'none';
    });
}

// Search products
function searchProducts() {
    const q = document.getElementById('posSearch').value.toLowerCase();
    document.querySelectorAll('.pos-product-btn').forEach(b => {
        b.style.display = b.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Void sale
function reverseSale(id) {
    const action = confirm("Mistake (Void) or Return?\n\nOK = Void, Cancel = Return") ? 'voided' : 'returned';
    const reason = prompt("Enter REASON for " + action.toUpperCase() + " (required):");
    if (reason && reason.trim().length > 3) {
        document.getElementById('reversal-id').value = id;
        document.getElementById('reversal-reason').value = reason;
        document.getElementById('reversal-action').value = action;
        document.getElementById('reversal-form').submit();
    } else if (reason !== null) {
        alert("A valid reason is required.");
    }
}
</script>
</body>
</html>