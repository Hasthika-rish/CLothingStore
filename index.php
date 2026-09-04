<?php
/**
 * Storefront Homepage
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Modern & Timeless Apparel';
$page_desc = 'Discover modern, premium clothing and stylish fashion at Anjiana Store. Shop dresses, jackets, shirts, and new arrivals today.';

$db = getDB();

// Fetch Categories
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY display_order ASC, id ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// Fetch Featured / Trending Products
$featured_products = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY is_featured DESC, id DESC LIMIT 8");
    $featured_products = $stmt->fetchAll();
} catch (Exception $e) {
    $featured_products = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <!-- Hero Slider Section -->
  <section class="hero-slider" id="heroSlider">
    <div class="slider-wrapper">
      
      <!-- Slide 1 -->
      <div class="slide active">
        <img src="images/slider_banner1.png" alt="Wear Your Vibe - Anjiana Store" class="slide-bg">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
          <h1>Wear Your Vibe</h1>
          <p class="slide-sub">MODANO — TRENDY • COMFY • YOU</p>
          <div class="action-buttons" style="justify-content: flex-start;">
            <a href="products.php" class="btn btn-primary">Shop New Arrivals</a>
            <a href="#featured" class="btn btn-secondary">Explore More</a>
          </div>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="slide">
        <img src="images/slider_banner2.png" alt="Redefine Menswear Style" class="slide-bg">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
          <h1>Redefine Menswear</h1>
          <p class="slide-sub">PREMIUM FABRICS & SHARP DESIGNS FOR EVERY DAY</p>
          <div class="action-buttons" style="justify-content: flex-start;">
            <a href="products.php?category=Men" class="btn btn-primary">Shop Men</a>
            <a href="#featured" class="btn btn-secondary">Explore More</a>
          </div>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="slide">
        <img src="images/slider_banner3.png" alt="Elegant Summer Collection" class="slide-bg">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
          <h1>Effortless Elegance</h1>
          <p class="slide-sub">LIGHTWEIGHT PIECES CRAFTED FOR ALL-DAY GRACE</p>
          <div class="action-buttons" style="justify-content: flex-start;">
            <a href="products.php?category=Women" class="btn btn-primary">Shop Women</a>
            <a href="#featured" class="btn btn-secondary">Explore More</a>
          </div>
        </div>
      </div>

    </div>

    <!-- Slider Controls -->
    <button class="slider-arrow prev" id="sliderPrev" aria-label="Previous Slide">‹</button>
    <button class="slider-arrow next" id="sliderNext" aria-label="Next Slide">›</button>

    <div class="slider-dots" id="sliderDots">
      <span class="dot active" data-slide="0"></span>
      <span class="dot" data-slide="1"></span>
      <span class="dot" data-slide="2"></span>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="section categories-section">
    <div class="container">
      <div class="text-center" style="margin-bottom: 3.5rem;">
        <h2 style="font-size: 2.5rem; font-weight: 700; letter-spacing: -0.5px;">Shop By Category</h2>
        <p class="text-muted" style="margin-top: 0.5rem;">Carefully curated collections for every mood and occasion.</p>
      </div>

      <div class="category-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem;">
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= urlencode($cat['name']) ?>" class="category-card" style="text-decoration: none; color: inherit;">
              <div class="category-img-wrap" style="border-radius: 12px; overflow: hidden; height: 320px; position: relative;">
                <img src="<?= e($cat['image'] ?: 'images/placeholder.png') ?>" alt="<?= e($cat['name']) ?>" class="category-img" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.05) 60%);"></div>
                <div style="position: absolute; bottom: 20px; left: 20px; right: 20px; color: #fff;">
                  <h3 style="font-size: 1.4rem; font-weight: 700; margin: 0;"><?= e($cat['name']) ?></h3>
                  <span style="font-size: 0.85rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Explore Collection →</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Trending / Featured Products Section -->
  <section class="section" id="featured" style="background: var(--bg-alt, rgba(0,0,0,0.02)); padding-top: 5rem; padding-bottom: 5rem;">
    <div class="container">
      <div class="text-center" style="margin-bottom: 3.5rem;">
        <span style="color: var(--accent-color); font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.85rem;">Trending Now</span>
        <h2 style="font-size: 2.5rem; font-weight: 700; margin-top: 0.3rem;">Handpicked Favorites</h2>
        <p class="text-muted" style="margin-top: 0.5rem;">Top selections from our latest apparel drops.</p>
      </div>

      <div class="product-grid" id="productGrid">
        <?php if (!empty($featured_products)): ?>
          <?php foreach ($featured_products as $product): 
            $price = (float)$product['price'];
            $discount = (float)$product['discount'];
            $discountedPrice = getDiscountedPrice($price, $discount);
            $inStock = (int)$product['stock'] > 0;
            $firstSize = 'M';
            if (!empty($product['sizes'])) {
              $sizeArr = explode(',', $product['sizes']);
              $firstSize = trim($sizeArr[0]);
            }
          ?>
            <article class="product-card" style="position: relative;">
              <!-- Wishlist Button -->
              <button class="card-wishlist-btn" data-id="<?= $product['id'] ?>" aria-label="Add to Wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
              </button>

              <!-- Image & Overlay Link -->
              <a href="product-details.php?id=<?= $product['id'] ?>" class="product-img-wrap" style="position: relative; display: block;">
                <?php if ($discount > 0): ?>
                  <span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-family: 'Outfit', sans-serif;">-<?= (int)$discount ?>%</span>
                <?php endif; ?>
                
                <img src="<?= e($product['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($product['name']) ?>" class="product-img" loading="lazy">
                
                <div class="product-add-overlay">
                  <?php if ($inStock): ?>
                    <form action="cart.php" method="POST" class="quick-add-form" style="width: 100%; display: flex; justify-content: center;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="add">
                      <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                      <input type="hidden" name="size" value="<?= e($firstSize) ?>">
                      <input type="hidden" name="quantity" value="1">
                      <button type="submit" class="btn-quick-add" aria-label="Quick Add <?= e($product['name']) ?> to Cart">
                        <span class="btn-quick-add-text">Quick Add</span>
                        <span class="btn-quick-add-icon">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                          </svg>
                        </span>
                      </button>
                    </form>
                  <?php else: ?>
                    <button type="button" class="btn-quick-add btn-quick-add-disabled" disabled>
                      <span class="btn-quick-add-text">Sold Out</span>
                    </button>
                  <?php endif; ?>
                </div>
              </a>

              <div class="product-info">
                <p class="product-category"><?= e($product['category']) ?></p>
                <h3 class="product-title"><a href="product-details.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
                <p class="product-price">
                  <?php if ($discount > 0): ?>
                    <span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.9rem; margin-right: 0.5rem;"><?= formatPrice($price) ?></span>
                    <span style="font-weight: 600; color: var(--accent-color); font-size: 1.1rem;"><?= formatPrice($discountedPrice) ?></span>
                  <?php else: ?>
                    <span style="font-weight: 600; font-size: 1.1rem;"><?= formatPrice($price) ?></span>
                  <?php endif; ?>
                </p>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 3rem;">No products available at the moment.</p>
        <?php endif; ?>
      </div>
      
      <div class="text-center" style="margin-top: 4rem;">
        <a href="products.php" class="btn btn-secondary" style="padding: 16px 44px; font-weight: 600;">View All Products</a>
      </div>
    </div>
  </section>

  <!-- Value Propositions Banner -->
  <section class="section" style="padding: 4rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; text-align: center;">
        <div>
          <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚚</div>
          <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem;">Islandwide Fast Delivery</h4>
          <p class="text-muted" style="font-size: 0.9rem;">Free delivery on all orders over $100</p>
        </div>
        <div>
          <div style="font-size: 2rem; margin-bottom: 0.5rem;">💎</div>
          <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem;">Premium Quality</h4>
          <p class="text-muted" style="font-size: 0.9rem;">Crafted with high-grade breathable fabrics</p>
        </div>
        <div>
          <div style="font-size: 2rem; margin-bottom: 0.5rem;">💳</div>
          <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem;">Flexible Payments</h4>
          <p class="text-muted" style="font-size: 0.9rem;">Cash on Delivery & Direct Bank Deposit</p>
        </div>
        <div>
          <div style="font-size: 2rem; margin-bottom: 0.5rem;">💬</div>
          <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem;">Dedicated Support</h4>
          <p class="text-muted" style="font-size: 0.9rem;">Direct WhatsApp support 7 days a week</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
