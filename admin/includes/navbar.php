<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="header">
    <div class="header-logo">
        <i class="ri-store-2-fill"></i>
        <h1>P3 Shop <span>Pro</span></h1>
    </div>
    <div class="header-user">
        <span><i class="ri-user-smile-line"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="../logout.php" class="logout-btn"><i class="ri-logout-box-r-line"></i> Logout</a>
    </div>
</div>

<nav class="main-nav">
    <ul>
        <li><a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>"><i class="ri-dashboard-line"></i> Dashboard</a></li>
        <li><a href="products.php" class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>"><i class="ri-shopping-basket-2-line"></i> Products</a></li>
        <li><a href="manage_categories.php" class="<?php echo $currentPage == 'manage_categories.php' ? 'active' : ''; ?>"><i class="ri-price-tag-3-line"></i> Categories</a></li>
        <li><a href="summaries.php" class="<?php echo $currentPage == 'summaries.php' ? 'active' : ''; ?>"><i class="ri-bar-chart-2-line"></i> Business Insights</a></li>
        <li><a href="activity_log.php" class="<?php echo $currentPage == 'activity_log.php' ? 'active' : ''; ?>"><i class="ri-history-line"></i> Staff Activity</a></li>
        <li><a href="manage_staff.php" class="<?php echo $currentPage == 'manage_staff.php' ? 'active' : ''; ?>"><i class="ri-group-line"></i> Manage Staff</a></li>
    </ul>
</nav>
