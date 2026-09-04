<?php
/**
 * Category Management
 * Anjiana Clothing Store
 */
$admin_page_title = 'Category Management';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle New Category Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);
    $imageUrl = 'images/placeholder.png';

    if (empty($name)) {
        setFlash('error', 'Category name is required.');
    } else {
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploaded = handleImageUpload($_FILES['category_image'], ROOT_PATH . '/uploads/categories/', 'uploads/categories/');
                if ($uploaded) $imageUrl = $uploaded;
            } catch (Exception $e) {
                setFlash('error', 'Image upload: ' . $e->getMessage());
            }
        } elseif (!empty($_POST['custom_image_url'])) {
            $imageUrl = trim($_POST['custom_image_url']);
        }

        try {
            $slug = generateSlug($name);
            $stmt = $db->prepare("INSERT INTO categories (name, slug, image, display_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $imageUrl, $order]);
            setFlash('success', "Category '{$name}' created successfully!");
        } catch (Exception $e) {
            setFlash('error', 'Failed to create category: ' . $e->getMessage());
        }
    }
    header("Location: categories.php");
    exit;
}

// Handle Category Delete
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    if ($delId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$delId]);
            setFlash('success', 'Category removed.');
        } catch (Exception $e) {
            setFlash('error', 'Error removing category: ' . $e->getMessage());
        }
    }
    header("Location: categories.php");
    exit;
}

// Fetch all categories with product counts
$categories = [];
try {
    $stmt = $db->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON (p.category_id = c.id OR p.category = c.name) 
        GROUP BY c.id 
        ORDER BY c.display_order ASC, c.id ASC
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}
?>

<div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 2.5rem; align-items: start;">
  
  <!-- Add Category Form Card -->
  <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: var(--shadow-sm);">
    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">Add New Category</h3>

    <form action="categories.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_category">

      <div style="margin-bottom: 1.25rem;">
        <label for="name" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Category Name *</label>
        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Summer Specials">
      </div>

      <div style="margin-bottom: 1.25rem;">
        <label for="display_order" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Display Order (Number)</label>
        <input type="number" name="display_order" id="display_order" class="form-control" value="0">
      </div>

      <div style="margin-bottom: 1.5rem;">
        <label for="category_image" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Banner Image</label>
        <input type="file" name="category_image" id="category_image" class="form-control" accept="image/*" style="padding: 6px;">
        <input type="text" name="custom_image_url" class="form-control" placeholder="Or image path: images/..." style="margin-top: 0.5rem; font-size: 0.85rem;">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
        Create Category
      </button>
    </form>
  </div>

  <!-- Categories Table -->
  <div class="admin-table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 60px;">Image</th>
          <th>Category Name</th>
          <th>Slug</th>
          <th>Products</th>
          <th>Order</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <img src="../<?= e($cat['image'] ?: 'images/placeholder.png') ?>" alt="<?= e($cat['name']) ?>" style="width: 44px; height: 50px; object-fit: cover; border-radius: 4px; display: block;">
              </td>
              <td><strong><?= e($cat['name']) ?></strong></td>
              <td style="color: var(--text-muted); font-family: monospace; font-size: 0.85rem;"><?= e($cat['slug']) ?></td>
              <td>
                <span style="background: var(--bg-alt, #f0f0f0); padding: 3px 8px; border-radius: 4px; font-size: 0.85rem;">
                  <?= (int)$cat['product_count'] ?> items
                </span>
              </td>
              <td><?= (int)$cat['display_order'] ?></td>
              <td style="text-align: right;">
                <a href="categories.php?delete_id=<?= $cat['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem; color: #C62828; border-color: #FFCDD2; background: #FFF0F0;" onclick="return confirm('Delete category <?= e($cat['name']) ?>?');">
                  Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">No categories defined yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<style>
@media (max-width: 900px) {
  div[style*="grid-template-columns: 1fr 1.8fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
