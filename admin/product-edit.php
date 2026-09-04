<?php
/**
 * Edit Product Form
 * Anjiana Clothing Store
 */
$admin_page_title = 'Edit Product';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header("Location: products.php");
    exit;
}

$errors = [];

// Fetch Categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY display_order ASC, name ASC");
$categories = $catStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryName = trim($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $gender = trim($_POST['gender'] ?? 'unisex');
    $tag = trim($_POST['tag'] ?? 'everyday');
    $description = trim($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = trim($_POST['status'] ?? 'active');
    
    // Sizes
    $sizesArr = $_POST['sizes'] ?? [];
    $sizesStr = !empty($sizesArr) ? implode(',', array_map('trim', $sizesArr)) : 'XS,S,M,L,XL';

    // Colors
    $colorsStr = trim($_POST['colors'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Product name is required.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }
    if ($discount < 0 || $discount > 100) {
        $errors[] = 'Discount must be between 0 and 100 percent.';
    }
    if (empty($categoryName)) {
        $errors[] = 'Please select a category.';
    }

    // Image Upload Handling
    $imageUrl = $product['image_url'];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploaded = handleImageUpload($_FILES['product_image'], PRODUCT_UPLOAD_DIR, 'uploads/products/');
            if ($uploaded) $imageUrl = $uploaded;
        } catch (Exception $e) {
            $errors[] = 'Image upload failed: ' . $e->getMessage();
        }
    } elseif (!empty($_POST['custom_image_url'])) {
        $imageUrl = trim($_POST['custom_image_url']);
    }

    if (empty($errors)) {
        try {
            $slug = generateSlug($name);
            
            // Find category_id
            $catId = null;
            $catFind = $db->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
            $catFind->execute([$categoryName]);
            $catRow = $catFind->fetch();
            if ($catRow) $catId = $catRow['id'];

            $updateStmt = $db->prepare("
                UPDATE products SET
                    name = ?, slug = ?, category_id = ?, category = ?, gender = ?, tag = ?,
                    price = ?, discount = ?, stock = ?, sizes = ?, colors = ?,
                    description = ?, image_url = ?, is_featured = ?, status = ?
                WHERE id = ?
            ");

            $updateStmt->execute([
                $name, $slug, $catId, $categoryName, $gender, $tag,
                $price, $discount, $stock, $sizesStr, $colorsStr,
                $description, $imageUrl, $isFeatured, $status,
                $productId
            ]);

            setFlash('success', "Product '{$name}' updated successfully!");
            header("Location: products.php");
            exit;

        } catch (Exception $e) {
            $errors[] = 'Failed to update product: ' . $e->getMessage();
        }
    }
}

$currentSizes = !empty($product['sizes']) ? array_map('trim', explode(',', $product['sizes'])) : [];
?>

<div style="max-width: 850px; background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2.5rem; box-shadow: var(--shadow-sm); margin: 0 auto;">
  
  <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2 style="font-size: 1.4rem; font-weight: 800;">Edit Product #<?= $product['id'] ?></h2>
      <p class="text-muted" style="font-size: 0.88rem;">Update pricing, inventory levels, sizes, and images</p>
    </div>
    <a href="products.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">← Back to List</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 2rem;">
      <strong style="display: block; margin-bottom: 0.3rem;">Please check the form errors:</strong>
      <ul style="margin-left: 1.5rem; font-size: 0.9rem;">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="product-edit.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data">
    
    <!-- Row 1: Product Name -->
    <div style="margin-bottom: 1.5rem;">
      <label for="name" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Product Title / Name *</label>
      <input type="text" name="name" id="name" class="form-control" required value="<?= e($_POST['name'] ?? $product['name']) ?>">
    </div>

    <!-- Row 2: Category, Gender, Tag -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem;">
      <div>
        <label for="category" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Category *</label>
        <select name="category" id="category" class="form-control" required>
          <?php 
          $selectedCategory = $_POST['category'] ?? $product['category'];
          foreach ($categories as $cat): 
          ?>
            <option value="<?= e($cat['name']) ?>" <?= $selectedCategory === $cat['name'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="gender" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Demographic</label>
        <?php $selectedGender = $_POST['gender'] ?? $product['gender']; ?>
        <select name="gender" id="gender" class="form-control">
          <option value="women" <?= $selectedGender === 'women' ? 'selected' : '' ?>>Women</option>
          <option value="men" <?= $selectedGender === 'men' ? 'selected' : '' ?>>Men</option>
          <option value="kids" <?= $selectedGender === 'kids' ? 'selected' : '' ?>>Kids</option>
          <option value="unisex" <?= $selectedGender === 'unisex' ? 'selected' : '' ?>>Unisex</option>
        </select>
      </div>

      <div>
        <label for="tag" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Style Tag</label>
        <?php $selectedTag = $_POST['tag'] ?? $product['tag']; ?>
        <select name="tag" id="tag" class="form-control">
          <option value="everyday" <?= $selectedTag === 'everyday' ? 'selected' : '' ?>>Everyday Casual</option>
          <option value="essentials" <?= $selectedTag === 'essentials' ? 'selected' : '' ?>>Wardrobe Essentials</option>
          <option value="nightout" <?= $selectedTag === 'nightout' ? 'selected' : '' ?>>Night Out & Party</option>
          <option value="occasion" <?= $selectedTag === 'occasion' ? 'selected' : '' ?>>Special Occasion / Formal</option>
        </select>
      </div>
    </div>

    <!-- Row 3: Price, Discount, Stock -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem;">
      <div>
        <label for="price" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Price ($ USD) *</label>
        <input type="number" step="0.01" name="price" id="price" class="form-control" required value="<?= e($_POST['price'] ?? $product['price']) ?>">
      </div>

      <div>
        <label for="discount" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Discount %</label>
        <input type="number" step="1" name="discount" id="discount" class="form-control" min="0" max="100" value="<?= e($_POST['discount'] ?? $product['discount']) ?>">
      </div>

      <div>
        <label for="stock" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Stock Quantity *</label>
        <input type="number" name="stock" id="stock" class="form-control" required min="0" value="<?= e($_POST['stock'] ?? $product['stock']) ?>">
      </div>
    </div>

    <!-- Row 4: Sizes selection -->
    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.5rem;">Available Sizes</label>
      <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
        <?php
        $availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2Y', '4Y', '6Y', '8Y', '10Y', 'Free Size'];
        $checkedSizes = $_POST['sizes'] ?? $currentSizes;
        foreach ($availableSizes as $sz):
        ?>
          <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
            <input type="checkbox" name="sizes[]" value="<?= $sz ?>" <?= in_array($sz, $checkedSizes) ? 'checked' : '' ?>>
            <span><?= $sz ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Row 5: Color Hex Codes -->
    <div style="margin-bottom: 1.5rem;">
      <label for="colors" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Colors (Hex Codes comma-separated)</label>
      <input type="text" name="colors" id="colors" class="form-control" value="<?= e($_POST['colors'] ?? $product['colors']) ?>">
    </div>

    <!-- Row 6: Image Upload & Preview -->
    <div style="margin-bottom: 1.5rem; background: var(--bg-alt, #f8f9fa); padding: 1.25rem; border-radius: 8px; border: 1px dashed var(--border-color);">
      <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
        <div>
          <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Current Image:</span>
          <img src="../<?= e($product['image_url'] ?: 'images/placeholder.png') ?>" alt="Current Product Image" style="width: 70px; height: 90px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
        </div>
        
        <div style="flex: 1; min-width: 240px;">
          <label for="product_image" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">
            Upload New Image (Leave empty to keep current):
          </label>
          <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*" style="padding: 6px;">
          
          <input type="text" name="custom_image_url" class="form-control" placeholder="Or custom path: images/..." value="<?= e($product['image_url']) ?>" style="margin-top: 0.5rem; font-size: 0.85rem;">
        </div>
      </div>
    </div>

    <!-- Row 7: Description -->
    <div style="margin-bottom: 1.5rem;">
      <label for="description" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 0.4rem;">Description & Fabric Details</label>
      <textarea name="description" id="description" class="form-control" rows="4"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
    </div>

    <!-- Row 8: Featured and Status -->
    <div style="display: flex; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap; align-items: center;">
      <label style="display: inline-flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 600;">
        <input type="checkbox" name="is_featured" value="1" <?= (isset($_POST['is_featured']) ? (bool)$_POST['is_featured'] : (bool)$product['is_featured']) ? 'checked' : '' ?>>
        <span>★ Featured / Trending on Homepage</span>
      </label>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label for="status" style="font-weight: 600; font-size: 0.9rem;">Status:</label>
        <select name="status" id="status" class="form-control" style="width: auto; padding: 4px 10px;">
          <option value="active" <?= ($product['status'] === 'active') ? 'selected' : '' ?>>Active (Visible)</option>
          <option value="inactive" <?= ($product['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
        </select>
      </div>
    </div>

    <!-- Submit Button -->
    <div style="display: flex; gap: 1rem;">
      <button type="submit" class="btn btn-primary" style="padding: 14px 28px; font-weight: 700; font-size: 1rem;">
        Update Product
      </button>
      <a href="products.php" class="btn btn-secondary" style="padding: 14px 24px;">Cancel</a>
    </div>

  </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
