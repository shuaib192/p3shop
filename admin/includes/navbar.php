<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Calculate relative path to admin folder
// This works whether the navbar is included from root or from within the admin folder.
$isInAdmin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$toAdmin = $isInAdmin ? '' : 'admin/';
$toRoot = $isInAdmin ? '../' : '';

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

        <a href="<?= $toAdmin ?>index.php" class="sidebar-link <?= $currentPage == 'index.php' ? 'active' : '' ?>">
            <i class="ri-dashboard-3-line"></i> Dashboard
        </a>

        <span class="sidebar-section-label">Inventory</span>

        <a href="<?= $toAdmin ?>products.php" class="sidebar-link <?= $currentPage == 'products.php' || $currentPage == 'edit_product.php' ? 'active' : '' ?>">
            <i class="ri-archive-2-line"></i> Products
            <?php if ($low_stock_count > 0): ?>
                <span class="badge-count"><?= $low_stock_count ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $toAdmin ?>manage_categories.php" class="sidebar-link <?= $currentPage == 'manage_categories.php' ? 'active' : '' ?>">
            <i class="ri-price-tag-3-line"></i> Categories
        </a>

        <span class="sidebar-section-label">Analytics</span>

        <a href="<?= $toAdmin ?>summaries.php" class="sidebar-link <?= $currentPage == 'summaries.php' ? 'active' : '' ?>">
            <i class="ri-line-chart-line"></i> Business Insights
        </a>

        <a href="<?= $toAdmin ?>daily_summary.php" class="sidebar-link <?= $currentPage == 'daily_summary.php' ? 'active' : '' ?>">
            <i class="ri-calendar-check-line"></i> Daily Report
        </a>

        <a href="<?= $toAdmin ?>profit_loss.php" class="sidebar-link <?= $currentPage == 'profit_loss.php' ? 'active' : '' ?>">
            <i class="ri-funds-line"></i> Profit & Loss
        </a>

        <span class="sidebar-section-label">Operations</span>

        <a href="<?= $toAdmin ?>activity_log.php" class="sidebar-link <?= $currentPage == 'activity_log.php' ? 'active' : '' ?>">
            <i class="ri-shield-check-line"></i> Audit Log
        </a>

        <a href="<?= $toAdmin ?>manage_staff.php" class="sidebar-link <?= $currentPage == 'manage_staff.php' ? 'active' : '' ?>">
            <i class="ri-group-line"></i> Staff
        </a>

        <span class="sidebar-section-label">Exports</span>

        <a href="<?= $toAdmin ?>summaries.php" class="sidebar-link">
            <i class="ri-file-chart-line"></i> Reports & Exports
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $toRoot ?>logout.php" class="sidebar-user" style="text-decoration:none">
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
