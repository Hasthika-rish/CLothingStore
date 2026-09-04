<?php
/**
 * Admin Dashboard Overview
 * Anjiana Clothing Store
 */
$admin_page_title = 'Dashboard Overview';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// 1. Statistics Queries
$totalRevenue = 0.0;
$totalOrders = 0;
$pendingOrders = 0;
$totalProducts = 0;
$lowStockCount = 0;

try {
    $revStmt = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'Cancelled'");
    $totalRevenue = (float)$revStmt->fetchColumn();

    $ordStmt = $db->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$ordStmt->fetchColumn();

    $pndStmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'");
    $pendingOrders = (int)$pndStmt->fetchColumn();

    $prdStmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $totalProducts = (int)$prdStmt->fetchColumn();

    $lowStmt = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'active'");
    $lowStockCount = (int)$lowStmt->fetchColumn();

    // 2. Recent Orders (Latest 6)
    $recentOrdersStmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6");
    $recentOrders = $recentOrdersStmt->fetchAll();

    // 3. Low Stock Items
    $lowStockItemsStmt = $db->query("SELECT * FROM products WHERE stock <= 5 AND status = 'active' ORDER BY stock ASC LIMIT 5");
    $lowStockItems = $lowStockItemsStmt->fetchAll();

} catch (Exception $e) {
    $recentOrders = [];
    $lowStockItems = [];
}
?>

<!-- Statistics Metric Cards -->
<div class="admin-stats-grid">
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-title">Total Revenue</div>
    <div class="stat-value"><?= formatPrice($totalRevenue) ?></div>
    <small class="text-muted" style="font-size: 0.8rem;">From completed & active orders</small>
  </div>

  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-title">Total Orders</div>
    <div class="stat-value"><?= $totalOrders ?></div>
    <small class="text-muted" style="font-size: 0.8rem;"><?= $pendingOrders ?> pending fulfillment</small>
  </div>

  <div class="stat-card">
    <div class="stat-icon">👗</div>
    <div class="stat-title">Active Products</div>
    <div class="stat-value"><?= $totalProducts ?></div>
    <small class="text-muted" style="font-size: 0.8rem;">Live on storefront</small>
  </div>

  <div class="stat-card" style="<?= $lowStockCount > 0 ? 'border-color: #FFCDD2; background: #FFF9F9;' : '' ?>">
    <div class="stat-icon">⚠️</div>
    <div class="stat-title">Low Stock Alert</div>
    <div class="stat-value" style="<?= $lowStockCount > 0 ? 'color: #C62828;' : '' ?>"><?= $lowStockCount ?></div>
    <small class="text-muted" style="font-size: 0.8rem;">Items with &le; 5 units left</small>
  </div>
</div>

<!-- Quick Actions Banner -->
<div style="display: flex; gap: 1rem; margin-bottom: 2.5rem; flex-wrap: wrap;">
  <a href="product-add.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 12px 20px;">
    <span>➕ Add New Product</span>
  </a>
  <a href="orders.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 12px 20px;">
    <span>📦 Manage Orders</span>
  </a>
  <a href="pos.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 12px 20px;">
    <span>💳 In-Store POS System</span>
  </a>
</div>

<!-- Recent Orders Section -->
<div style="margin-bottom: 3rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700;">Recent Customer Orders</h2>
    <a href="orders.php" style="color: var(--accent-color); font-weight: 600; font-size: 0.9rem; text-decoration: none;">View All Orders →</a>
  </div>

  <div class="admin-table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentOrders)): ?>
          <?php foreach ($recentOrders as $ord): 
            $status = $ord['order_status'];
            $badgeColor = '#FF9800';
            if ($status === 'Processing') $badgeColor = '#2196F3';
            if ($status === 'Shipped') $badgeColor = '#9C27B0';
            if ($status === 'Delivered') $badgeColor = '#4CAF50';
            if ($status === 'Cancelled') $badgeColor = '#F44336';
          ?>
            <tr>
              <td><strong style="font-family: monospace; font-size: 0.95rem;"><?= e($ord['order_number']) ?></strong></td>
              <td>
                <div style="font-weight: 600;"><?= e($ord['first_name'] . ' ' . $ord['last_name']) ?></div>
                <div style="color: var(--text-muted); font-size: 0.8rem;"><?= e($ord['phone']) ?></div>
              </td>
              <td><strong style="color: var(--accent-color);"><?= formatPrice($ord['total_amount']) ?></strong></td>
              <td>
                <span style="font-size: 0.85rem; background: var(--bg-alt, #f0f0f0); padding: 2px 8px; border-radius: 4px;">
                  <?= e($ord['payment_method']) ?>
                </span>
              </td>
              <td>
                <span style="display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 700; background: <?= $badgeColor ?>22; color: <?= $badgeColor ?>;">
                  ● <?= e($status) ?>
                </span>
              </td>
              <td style="color: var(--text-muted); font-size: 0.85rem;">
                <?= date('M d, H:i', strtotime($ord['created_at'])) ?>
              </td>
              <td>
                <a href="orders.php?order_id=<?= $ord['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                  Manage
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No customer orders placed yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Low Stock Warnings Section (if any) -->
<?php if (!empty($lowStockItems)): ?>
  <div style="margin-bottom: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <h2 style="font-size: 1.35rem; font-weight: 700; color: #C62828;">⚠️ Low Stock Inventory Alert</h2>
      <a href="products.php" style="color: var(--accent-color); font-weight: 600; font-size: 0.9rem; text-decoration: none;">Manage Inventory →</a>
    </div>

    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Current Stock</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lowStockItems as $lItem): ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <img src="../<?= e($lItem['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($lItem['name']) ?>" style="width: 36px; height: 44px; object-fit: cover; border-radius: 4px;">
                  <strong style="font-size: 0.92rem;"><?= e($lItem['name']) ?></strong>
                </div>
              </td>
              <td><?= e($lItem['category']) ?></td>
              <td><?= formatPrice($lItem['price']) ?></td>
              <td>
                <span style="background: #FFEBEE; color: #C62828; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                  <?= (int)$lItem['stock'] ?> left
                </span>
              </td>
              <td>
                <a href="product-edit.php?id=<?= $lItem['id'] ?>" class="btn btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;">
                  Update Stock
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
