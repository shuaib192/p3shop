<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Build absolute base path: works on both XAMPP (/p3shop) and Hostinger (site root)
$scriptDir  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']); // e.g. /p3shop/update_stock.php
$shopRoot   = rtrim(str_replace('/admin/includes/navbar.php', '',
               str_replace('\\', '/', __FILE__)), '/'); // absolute FS path to shop root
// URL root: strip everything after the first segment that matches p3shop or public_html/shop
// Simplest reliable method: walk up from SCRIPT_NAME to find the shop root URL segment
$parts      = explode('/', trim($scriptDir, '/'));
// On Hostinger script is /update_stock.php or /admin/products.php  (shop is domain root)
// On XAMPP  script is /p3shop/update_stock.php or /p3shop/admin/products.php
// We detect by checking if first segment is 'p3shop'
$baseUrl    = (isset($parts[0]) && $parts[0] === 'p3shop') ? '/p3shop' : '';
$adminBase  = $baseUrl . '/admin';

// Count low stock for badge
$low_stock_count = 0;
$ls_res = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE quantity_in_stock < 5");
if ($ls_res) { $row = $ls_res->fetch_assoc(); $low_stock_count = $row['cnt']; }
$username = $_SESSION['username'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';
$initial = strtoupper(substr($username, 0, 1));
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="ri-store-2-fill"></i></div>
        <div class="sidebar-brand-text">
            <h1>P3 Shop <span style="color:var(--primary-light)">Pro</span></h1>
            <span>Admin Panel</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="sidebar-section-label">Overview</span>

        <a href="<?= $adminBase ?>/index.php" class="sidebar-link <?= $currentPage == 'index.php' ? 'active' : '' ?>">
            <i class="ri-dashboard-3-line"></i> Dashboard
        </a>

        <span class="sidebar-section-label">Inventory</span>

        <a href="<?= $adminBase ?>/products.php" class="sidebar-link <?= $currentPage == 'products.php' || $currentPage == 'edit_product.php' ? 'active' : '' ?>">
            <i class="ri-archive-2-line"></i> Products
            <?php if ($low_stock_count > 0): ?>
                <span class="badge-count"><?= $low_stock_count ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $adminBase ?>/manage_categories.php" class="sidebar-link <?= $currentPage == 'manage_categories.php' ? 'active' : '' ?>">
            <i class="ri-price-tag-3-line"></i> Categories
        </a>

        <span class="sidebar-section-label">Analytics</span>

        <a href="<?= $adminBase ?>/summaries.php" class="sidebar-link <?= $currentPage == 'summaries.php' ? 'active' : '' ?>">
            <i class="ri-line-chart-line"></i> Business Insights
        </a>

        <a href="<?= $adminBase ?>/daily_summary.php" class="sidebar-link <?= $currentPage == 'daily_summary.php' ? 'active' : '' ?>">
            <i class="ri-calendar-check-line"></i> Daily Report
        </a>

        <a href="<?= $adminBase ?>/profit_loss.php" class="sidebar-link <?= $currentPage == 'profit_loss.php' ? 'active' : '' ?>">
            <i class="ri-funds-line"></i> Profit & Loss
        </a>

        <span class="sidebar-section-label">Operations</span>

        <a href="<?= $adminBase ?>/activity_log.php" class="sidebar-link <?= $currentPage == 'activity_log.php' ? 'active' : '' ?>">
            <i class="ri-shield-check-line"></i> Audit Log
        </a>

        <a href="<?= $adminBase ?>/manage_staff.php" class="sidebar-link <?= $currentPage == 'manage_staff.php' ? 'active' : '' ?>">
            <i class="ri-group-line"></i> Staff
        </a>

        <span class="sidebar-section-label">Exports</span>

        <a href="<?= $adminBase ?>/summaries.php" class="sidebar-link">
            <i class="ri-file-chart-line"></i> Reports & Exports
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-user" style="text-decoration:none">
            <div class="sidebar-avatar"><?= $initial ?></div>
            <div class="sidebar-user-info">
                <strong><?= htmlspecialchars($username) ?></strong>
                <span><?= $role ?></span>
            </div>
            <i class="ri-logout-box-r-line logout-icon"></i>
        </a>
    </div>
</aside>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
</script>
