<?php
/**
 * Product Details Page
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    $page_title = 'Product Not Found';
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    echo '<div class="container text-center" style="padding: 6rem 1rem;">
            <h2>Product Not Found</h2>
            <p class="text-muted" style="margin: 1rem 0 2rem;">The product you are looking for may have been removed or is currently unavailable.</p>
            <a href="products.php" class="btn btn-primary">Browse All Clothing</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Product Calculations
$price = (float)$product['price'];
$discount = (float)$product['discount'];
$discountedPrice = getDiscountedPrice($price, $discount);
$inStock = (int)$product['stock'] > 0;

$sizes = !empty($product['sizes']) ? array_map('trim', explode(',', $product['sizes'])) : ['M'];
$colors = !empty($product['colors']) ? array_map('trim', explode(',', $product['colors'])) : [];

// Fetch Related Products from same category
$relatedStmt = $db->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND status = 'active' LIMIT 4");
$relatedStmt->execute([$product['category'], $productId]);
$relatedProducts = $relatedStmt->fetchAll();

$page_title = $product['name'];
$page_desc = mb_strimwidth(strip_tags($product['description'] ?? ''), 0, 160, '...');
$og_image = $product['image_url'] ?: 'images/placeholder.png';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section" style="padding-top: 2rem;">
    <div class="container">
      
      <!-- Breadcrumb -->
      <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="index.php" style="color: inherit; text-decoration: none;">Home</a>
        <span>/</span>
        <a href="products.php" style="color: inherit; text-decoration: none;">Shop</a>
        <span>/</span>
        <a href="products.php?category=<?= urlencode($product['category']) ?>" style="color: inherit; text-decoration: none;"><?= e($product['category']) ?></a>
        <span>/</span>
        <span style="color: var(--primary-color); font-weight: 500;"><?= e($product['name']) ?></span>
      </div>

      <!-- Main Product Details Grid -->
      <div class="product-details-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem; align-items: start;">
        
        <!-- Product Image Gallery -->
        <div class="product-gallery">
          <div style="border-radius: 12px; overflow: hidden; position: relative; box-shadow: var(--shadow-sm); background: var(--bg-alt, #f8f9fa);">
            <?php if ($discount > 0): ?>
              <span style="position: absolute; top: 16px; left: 16px; background: var(--accent-color); color: #FFFFFF; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; z-index: 2; font-family: 'Outfit', sans-serif;">-<?= (int)$discount ?>% OFF</span>
            <?php endif; ?>
            <img id="mainProductImage" src="<?= e($product['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($product['name']) ?>" style="width: 100%; height: auto; max-height: 560px; object-fit: cover; display: block;">
          </div>
        </div>

        <!-- Product Purchase Information -->
        <div class="product-info-column">
          <p style="color: var(--accent-color); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem; margin-bottom: 0.5rem;">
            <?= e($product['category']) ?> <?= !empty($product['gender']) && $product['gender'] !== 'unisex' ? '• ' . ucfirst($product['gender']) : '' ?>
          </p>
          
          <h1 style="font-size: 2.4rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem;"><?= e($product['name']) ?></h1>

          <!-- Price Display -->
          <div class="price-box" style="display: flex; align-items: baseline; gap: 1rem; margin-bottom: 1.5rem;">
            <?php if ($discount > 0): ?>
              <span style="font-size: 2rem; font-weight: 800; color: var(--accent-color);"><?= formatPrice($discountedPrice) ?></span>
              <span style="font-size: 1.25rem; text-decoration: line-through; color: var(--text-muted);"><?= formatPrice($price) ?></span>
              <span style="background: rgba(159,93,68,0.1); color: var(--accent-color); padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">Save <?= formatPrice($price - $discountedPrice) ?></span>
            <?php else: ?>
              <span style="font-size: 2rem; font-weight: 800; color: var(--primary-color);"><?= formatPrice($price) ?></span>
            <?php endif; ?>
          </div>

          <!-- Stock Status -->
          <div style="margin-bottom: 1.5rem;">
            <?php if ($inStock): ?>
              <span style="display: inline-flex; align-items: center; gap: 0.4rem; color: #2E7D32; font-weight: 600; font-size: 0.95rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #2E7D32;"></span> In Stock (<?= (int)$product['stock'] ?> available)
              </span>
            <?php else: ?>
              <span style="display: inline-flex; align-items: center; gap: 0.4rem; color: #C62828; font-weight: 600; font-size: 0.95rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #C62828;"></span> Out of Stock
              </span>
            <?php endif; ?>
          </div>

          <!-- Add To Cart Form -->
          <form action="cart.php" method="POST" id="addToCartForm" style="margin-bottom: 2rem;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

            <!-- Size Selector -->
            <?php if (!empty($sizes)): ?>
              <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.6rem; font-size: 0.95rem;">Select Size:</label>
                <div class="size-options-group" style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                  <?php foreach ($sizes as $idx => $sz): ?>
                    <label class="size-radio-label" style="cursor: pointer;">
                      <input type="radio" name="size" value="<?= e($sz) ?>" <?= $idx === 0 ? 'checked' : '' ?> style="display: none;">
                      <span class="size-pill <?= $idx === 0 ? 'active' : '' ?>" style="display: inline-block; padding: 8px 18px; border: 1.5px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;">
                        <?= e($sz) ?>
                      </span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <input type="hidden" name="size" value="Free Size">
            <?php endif; ?>

            <!-- Color Selector (if present) -->
            <?php if (!empty($colors)): ?>
              <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.6rem; font-size: 0.95rem;">Available Colors:</label>
                <div class="color-options-group" style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                  <?php foreach ($colors as $idx => $col): ?>
                    <label class="color-radio-label" style="cursor: pointer;">
                      <input type="radio" name="color" value="<?= e($col) ?>" <?= $idx === 0 ? 'checked' : '' ?> style="display: none;">
                      <span class="color-pill <?= $idx === 0 ? 'active' : '' ?>" title="<?= e($col) ?>" style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background-color: <?= e($col) ?>; border: 2px solid var(--border-color); transition: all 0.2s;"></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <input type="hidden" name="color" value="Standard">
            <?php endif; ?>

            <!-- Quantity Selector & Action Buttons -->
            <div style="display: flex; gap: 1rem; align-items: stretch; margin-top: 1.5rem; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; border: 1.5px solid var(--border-color); border-radius: 6px; overflow: hidden; background: var(--card-bg);">
                <button type="button" class="qty-btn-minus" style="padding: 10px 16px; background: none; border: none; font-size: 1.2rem; cursor: pointer;">-</button>
                <input type="number" name="quantity" id="quantityInput" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>" style="width: 50px; text-align: center; border: none; font-weight: 600; font-size: 1rem; -moz-appearance: textfield; background: transparent;">
                <button type="button" class="qty-btn-plus" style="padding: 10px 16px; background: none; border: none; font-size: 1.2rem; cursor: pointer;">+</button>
              </div>

              <?php if ($inStock): ?>
                <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 180px; padding: 14px 28px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; letter-spacing: 0.02em;">
                  <span>Add to Cart</span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                  </svg>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-secondary" style="flex: 1; min-width: 180px; padding: 14px 28px; font-size: 0.95rem; opacity: 0.7; cursor: not-allowed; border-radius: 8px;" disabled>
                  Sold Out
                </button>
              <?php endif; ?>

              <!-- Wishlist Toggle -->
              <button type="button" class="btn btn-secondary card-wishlist-btn" data-id="<?= $product['id'] ?>" aria-label="Add to Wishlist" style="padding: 14px 18px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
              </button>
            </div>
          </form>

          <!-- Description Section -->
          <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.75rem;">Product Description</h3>
            <div style="color: var(--text-color); line-height: 1.7; font-size: 0.98rem; opacity: 0.9;">
              <?= nl2br(e($product['description'] ?: 'Modern, comfortable and crafted with fine fabrics for long-lasting quality and timeless style.')) ?>
            </div>
          </div>

          <!-- Feature Bullets -->
          <div style="margin-top: 1.5rem; background: var(--bg-alt, rgba(0,0,0,0.02)); padding: 1.25rem; border-radius: 8px; font-size: 0.9rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
              <span>✓</span> <strong>Islandwide Delivery:</strong> Orders dispatched within 24-48 hours.
            </div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
              <span>✓</span> <strong>Easy Returns:</strong> 7-day hassle-free size exchange policy.
            </div>
            <div style="display: flex; align-items: center; gap: 0.6rem;">
              <span>✓</span> <strong>Secure Checkout:</strong> Cash on delivery & Bank deposit accepted.
            </div>
          </div>

        </div>
      </div>

      <!-- Related Products Section -->
      <?php if (!empty($relatedProducts)): ?>
        <div style="margin-top: 6rem; border-top: 1px solid var(--border-color); padding-top: 4rem;">
          <div class="text-center" style="margin-bottom: 3rem;">
            <h2 style="font-size: 2rem; font-weight: 700;">You May Also Like</h2>
            <p class="text-muted">More popular pieces from the <?= e($product['category']) ?> collection.</p>
          </div>

          <div class="product-grid">
            <?php foreach ($relatedProducts as $rel): 
              $relPrice = (float)$rel['price'];
              $relDiscount = (float)$rel['discount'];
              $relDiscPrice = getDiscountedPrice($relPrice, $relDiscount);
            ?>
              <article class="product-card" style="position: relative;">
                <a href="product-details.php?id=<?= $rel['id'] ?>" class="product-img-wrap">
                  <?php if ($relDiscount > 0): ?>
                    <span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2;">-<?= (int)$relDiscount ?>%</span>
                  <?php endif; ?>
                  <img src="<?= e($rel['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($rel['name']) ?>" class="product-img">
                </a>
                <div class="product-info">
                  <p class="product-category"><?= e($rel['category']) ?></p>
                  <h3 class="product-title"><a href="product-details.php?id=<?= $rel['id'] ?>"><?= e($rel['name']) ?></a></h3>
                  <p class="product-price">
                    <?php if ($relDiscount > 0): ?>
                      <span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.85rem; margin-right: 0.4rem;"><?= formatPrice($relPrice) ?></span>
                      <span style="font-weight: 600; color: var(--accent-color); font-size: 1.05rem;"><?= formatPrice($relDiscPrice) ?></span>
                    <?php else: ?>
                      <span style="font-weight: 600; font-size: 1.05rem;"><?= formatPrice($relPrice) ?></span>
                    <?php endif; ?>
                  </p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Size selection styling
  document.querySelectorAll('.size-radio-label input').forEach(input => {
    input.addEventListener('change', () => {
      document.querySelectorAll('.size-pill').forEach(pill => pill.classList.remove('active'));
      const pill = input.nextElementSibling;
      if (pill) pill.classList.add('active');
    });
  });

  // Color selection styling
  document.querySelectorAll('.color-radio-label input').forEach(input => {
    input.addEventListener('change', () => {
      document.querySelectorAll('.color-pill').forEach(pill => pill.classList.remove('active'));
      const pill = input.nextElementSibling;
      if (pill) pill.classList.add('active');
    });
  });

  // Quantity Counter
  const qtyInput = document.getElementById('quantityInput');
  const minusBtn = document.querySelector('.qty-btn-minus');
  const plusBtn = document.querySelector('.qty-btn-plus');

  if (qtyInput && minusBtn && plusBtn) {
    minusBtn.addEventListener('click', () => {
      let val = parseInt(qtyInput.value) || 1;
      if (val > 1) qtyInput.value = val - 1;
    });
    plusBtn.addEventListener('click', () => {
      let val = parseInt(qtyInput.value) || 1;
      let max = parseInt(qtyInput.max) || 99;
      if (val < max) qtyInput.value = val + 1;
    });
  }
});
</script>

<style>
.size-pill.active {
  background: var(--primary-color) !important;
  color: #fff !important;
  border-color: var(--primary-color) !important;
}
.color-pill.active {
  transform: scale(1.15);
  box-shadow: 0 0 0 2px var(--accent-color);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
