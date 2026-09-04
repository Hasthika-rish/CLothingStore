<?php
/**
 * Admin Product Catalog Management
 * Anjiana Clothing Store
 */
$admin_page_title = 'Product Management';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Search & Filter
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $where[] = "category = ?";
    $params[] = $category;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $stmt = $db->prepare("SELECT * FROM products {$whereSQL} ORDER BY id DESC");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Fetch categories for filter dropdown
    $catStmt = $db->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {
    $products = [];
    $categories = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <!-- Search & Filter Form -->
  <form action="products.php" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
    <input type="text" name="search" class="form-control" placeholder="Search product..." value="<?= e($search) ?>" style="max-width: 250px; padding: 8px 14px;">
    
    <select name="category" class="form-control" style="width: auto; padding: 8px 14px;" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat['name']) ?>" <?= $category === $cat['name'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-secondary" style="padding: 8px 16px;">Filter</button>
    <?php if (!empty($search) || !empty($category)): ?>
      <a href="products.php" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.85rem;">Clear</a>
    <?php endif; ?>
  </form>

  <a href="product-add.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 10px 18px;">
    <span>➕ Add New Product</span>
  </a>
</div>

<div class="admin-table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width: 60px;">Image</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Sizes</th>
        <th>Featured</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $p): 
          $price = (float)$p['price'];
          $discount = (float)$p['discount'];
          $stock = (int)$p['stock'];

          $stockBadge = '';
          if ($stock > 10) {
            $stockBadge = '<span style="background:#E8F5E9; color:#2E7D32; padding:3px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;">In Stock (' . $stock . ')</span>';
          } elseif ($stock > 0) {
            $stockBadge = '<span style="background:#FFF3E0; color:#E65100; padding:3px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;">Low Stock (' . $stock . ')</span>';
          } else {
            $stockBadge = '<span style="background:#FFEBEE; color:#C62828; padding:3px 8px; border-radius:4px; font-size:0.8rem; font-weight:600;">Out of Stock</span>';
          }
        ?>
          <tr>
            <td>
              <img src="../<?= e($p['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($p['name']) ?>" style="width: 44px; height: 55px; object-fit: cover; border-radius: 4px; display: block;">
            </td>
            <td>
              <strong style="font-size: 0.95rem;"><?= e($p['name']) ?></strong>
              <div style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">ID: #<?= $p['id'] ?></div>
            </td>
            <td>
              <span style="background: var(--bg-alt, #f0f0f0); padding: 3px 8px; border-radius: 4px; font-size: 0.85rem;">
                <?= e($p['category']) ?>
              </span>
            </td>
            <td>
              <?php if ($discount > 0): ?>
                <div><strong><?= formatPrice(getDiscountedPrice($price, $discount)) ?></strong></div>
                <div style="font-size: 0.8rem; text-decoration: line-through; color: var(--text-muted);"><?= formatPrice($price) ?> (-<?= (int)$discount ?>%)</div>
              <?php else: ?>
                <strong><?= formatPrice($price) ?></strong>
              <?php endif; ?>
            </td>
            <td><?= $stockBadge ?></td>
            <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 120px;">
              <?= e($p['sizes'] ?: 'All Sizes') ?>
            </td>
            <td>
              <?= $p['is_featured'] ? '<span style="color:#F57F17; font-weight:700;">★ Yes</span>' : '<span style="color:#aaa;">No</span>' ?>
            </td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 0.4rem;">
                <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                  Edit
                </a>
                <a href="product-delete.php?id=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; color: #C62828; border-color: #FFCDD2; background: #FFF0F0;" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                  Delete
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 4rem;">
            No products found matching your search.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
