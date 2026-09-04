<?php
/**
 * Admin Sidebar Navigation
 * Anjiana Clothing Store
 */
$admin_current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
  <div class="admin-logo">
    <span style="color: var(--accent-color);">ANJIANA</span> ADMIN
  </div>

  <nav class="admin-nav">
    <a href="index.php" class="<?= ($admin_current_page === 'index.php') ? 'active' : '' ?>">
      <span>📊 Dashboard</span>
    </a>
    <a href="products.php" class="<?= in_array($admin_current_page, ['products.php', 'product-edit.php']) ? 'active' : '' ?>">
      <span>👗 Products</span>
    </a>
    <a href="product-add.php" class="<?= ($admin_current_page === 'product-add.php') ? 'active' : '' ?>">
      <span>➕ Add Product</span>
    </a>
    <a href="orders.php" class="<?= ($admin_current_page === 'orders.php') ? 'active' : '' ?>">
      <span>📦 Orders</span>
    </a>
    <a href="categories.php" class="<?= ($admin_current_page === 'categories.php') ? 'active' : '' ?>">
      <span>🏷️ Categories</span>
    </a>
    <a href="pos.php" class="<?= ($admin_current_page === 'pos.php') ? 'active' : '' ?>">
      <span>💳 In-Store POS</span>
    </a>
    
    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 1.5rem 0;">
    
    <a href="../index.php" target="_blank">
      <span>🌐 Visit Storefront</span>
    </a>
    <a href="logout.php" style="color: #FF8A80;" onclick="return confirm('Confirm sign out?');">
      <span>🚪 Sign Out</span>
    </a>
  </nav>
</aside>
