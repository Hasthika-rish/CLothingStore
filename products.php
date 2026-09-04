<?php
/**
 * Shop Products Catalog Page with Multi-Attribute Filters
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// -------------------------------------------------------------
// 1. Fetch Catalog Metadata for Dynamic Filters
// -------------------------------------------------------------
$currencySymbol = getSetting('currency_symbol', 'Rs.');

// Price bounds across active products
$priceBounds = $db->query("SELECT MIN(price) as min_p, MAX(price) as max_p FROM products WHERE status = 'active'")->fetch();
$dbMinPrice = floor((float)($priceBounds['min_p'] ?? 0));
$dbMaxPrice = ceil((float)($priceBounds['max_p'] ?? 200));
if ($dbMaxPrice <= $dbMinPrice) {
    $dbMaxPrice = $dbMinPrice + 100;
}

// Distinct Brands with product counts
$brandsQuery = $db->query("
    SELECT brand, COUNT(*) as count 
    FROM products 
    WHERE status = 'active' AND brand IS NOT NULL AND brand != '' 
    GROUP BY brand 
    ORDER BY brand ASC
");
$allBrands = $brandsQuery->fetchAll();

// Distinct Categories with product counts
$categoriesQuery = $db->query("
    SELECT category, COUNT(*) as count 
    FROM products 
    WHERE status = 'active' AND category IS NOT NULL AND category != '' 
    GROUP BY category 
    ORDER BY category ASC
");
$allCategories = $categoriesQuery->fetchAll();

// Standard normalized sizes available in store
$availableSizesList = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size', '2Y', '4Y', '6Y', '8Y'];

// -------------------------------------------------------------
// 2. Parse Incoming Filter & Search Parameters
// -------------------------------------------------------------
$category = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'featured');
$tag = trim($_GET['tag'] ?? '');
$inStockOnly = !empty($_GET['in_stock']) && $_GET['in_stock'] == '1';

// Price range
$minPrice = isset($_GET['min_price']) ? max(0, (float)$_GET['min_price']) : $dbMinPrice;
$maxPrice = isset($_GET['max_price']) ? max(0, (float)$_GET['max_price']) : $dbMaxPrice;
if ($minPrice > $maxPrice) {
    $temp = $minPrice;
    $minPrice = $maxPrice;
    $maxPrice = $temp;
}

// Sizes
$selectedSizes = [];
if (!empty($_GET['sizes']) && is_array($_GET['sizes'])) {
    $selectedSizes = array_values(array_filter(array_map('trim', $_GET['sizes'])));
} elseif (!empty($_GET['size'])) {
    $selectedSizes = [trim($_GET['size'])];
}

// Brands
$selectedBrands = [];
if (!empty($_GET['brands']) && is_array($_GET['brands'])) {
    $selectedBrands = array_values(array_filter(array_map('trim', $_GET['brands'])));
} elseif (!empty($_GET['brand'])) {
    $selectedBrands = [trim($_GET['brand'])];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------
// 3. Construct Dynamic Parametric SQL Query
// -------------------------------------------------------------
$where = ["status = 'active'"];
$params = [];

if (!empty($category)) {
    $where[] = "(category = ? OR gender = ?)";
    $params[] = $category;
    $params[] = strtolower($category);
}

if (!empty($tag)) {
    $where[] = "tag = ?";
    $params[] = $tag;
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR description LIKE ? OR category LIKE ? OR brand LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Price Range filter
$isPriceFiltered = ($minPrice > $dbMinPrice || $maxPrice < $dbMaxPrice);
if ($isPriceFiltered) {
    $where[] = "price >= ? AND price <= ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
}

// Size Wise filter
if (!empty($selectedSizes)) {
    $sizeConditions = [];
    foreach ($selectedSizes as $sz) {
        $sizeConditions[] = "(sizes LIKE ? OR sizes LIKE ? OR sizes LIKE ? OR sizes = ?)";
        $params[] = "%,{$sz},%";
        $params[] = "{$sz},%";
        $params[] = "%,{$sz}";
        $params[] = $sz;
    }
    $where[] = "(" . implode(' OR ', $sizeConditions) . ")";
}

// Brand Wise filter
if (!empty($selectedBrands)) {
    $placeholders = implode(',', array_fill(0, count($selectedBrands), '?'));
    $where[] = "brand IN ({$placeholders})";
    $params = array_merge($params, $selectedBrands);
}

// In stock filter
if ($inStockOnly) {
    $where[] = "stock > 0";
}

$whereSQL = implode(' AND ', $where);

// Sorting
$orderBy = "is_featured DESC, id DESC";
switch ($sort) {
    case 'price-low':
        $orderBy = "price ASC";
        break;
    case 'price-high':
        $orderBy = "price DESC";
        break;
    case 'newest':
        $orderBy = "id DESC";
        break;
    case 'name-asc':
        $orderBy = "name ASC";
        break;
}

// Count total matching products for pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE {$whereSQL}");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch paginated products
$productQuery = "SELECT * FROM products WHERE {$whereSQL} ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($productQuery);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Dynamic title
$pageTitleText = 'All Products';
if (!empty($category)) {
    $pageTitleText = ucfirst($category) . ' Collection';
} elseif (!empty($search)) {
    $pageTitleText = 'Search: "' . $search . '"';
}

$page_title = $pageTitleText . ' | Shop';
$page_desc = "Explore our premium selection in {$pageTitleText} at Anjiana Store. Modern clothing and timeless quality.";

// Active filter count
$activeFilterCount = 0;
if (!empty($category)) $activeFilterCount++;
if (!empty($search)) $activeFilterCount++;
if ($isPriceFiltered) $activeFilterCount++;
if (!empty($selectedSizes)) $activeFilterCount += count($selectedSizes);
if (!empty($selectedBrands)) $activeFilterCount += count($selectedBrands);
if ($inStockOnly) $activeFilterCount++;

// Helper to build URL with modified parameter
if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl($removeKey = null, $removeValue = null) {
        $params = $_GET;
        unset($params['page']); // Reset page on filter changes
        
        if ($removeKey) {
            if ($removeValue !== null && isset($params[$removeKey]) && is_array($params[$removeKey])) {
                $params[$removeKey] = array_values(array_diff($params[$removeKey], [$removeValue]));
                if (empty($params[$removeKey])) {
                    unset($params[$removeKey]);
                }
            } else {
                unset($params[$removeKey]);
            }
        }
        return 'products.php' . (!empty($params) ? '?' . http_build_query($params) : '');
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section">
    <div class="container">
      
      <!-- Header Toolbar -->
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
        <div>
          <h1 style="font-size: 2.5rem; font-weight: 800; letter-spacing: -0.5px;"><?= e($pageTitleText) ?></h1>
          <p class="text-muted" style="margin-top: 0.25rem;">
            Showing <?= count($products) ?> of <?= $totalProducts ?> <?= $totalProducts === 1 ? 'item' : 'items' ?>
          </p>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
          <!-- Mobile Filter Trigger -->
          <button type="button" class="mobile-filter-trigger" id="mobileFilterTrigger">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="4" y1="21" x2="4" y2="14"></line>
              <line x1="4" y1="10" x2="4" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12" y2="3"></line>
              <line x1="20" y1="21" x2="20" y2="16"></line>
              <line x1="20" y1="12" x2="20" y2="3"></line>
              <line x1="1" y1="14" x2="7" y2="14"></line>
              <line x1="9" y1="8" x2="15" y2="8"></line>
              <line x1="17" y1="16" x2="23" y2="16"></line>
            </svg>
            <span>Filters</span>
            <?php if ($activeFilterCount > 0): ?>
              <span class="filter-active-count"><?= $activeFilterCount ?></span>
            <?php endif; ?>
          </button>

          <!-- Sorting Selector -->
          <form action="products.php" method="GET" style="display: flex; gap: 0.75rem; align-items: center;" id="sortForm">
            <?php foreach ($_GET as $k => $v): ?>
              <?php if ($k !== 'sort' && $k !== 'page'): ?>
                <?php if (is_array($v)): ?>
                  <?php foreach ($v as $subVal): ?>
                    <input type="hidden" name="<?= e($k) ?>[]" value="<?= e($subVal) ?>">
                  <?php endforeach; ?>
                <?php else: ?>
                  <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                <?php endif; ?>
              <?php endif; ?>
            <?php endforeach; ?>

            <label for="sortSelect" style="font-weight: 500; font-size: 0.9rem;">Sort By:</label>
            <select name="sort" id="sortSelect" class="form-control" style="width: auto; padding: 8px 16px; border-radius: 6px;" onchange="this.form.submit()">
              <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
              <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="name-asc" <?= $sort === 'name-asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Main Catalog Layout (Sidebar + Product Grid) -->
      <div class="catalog-layout">
        
        <!-- ============================================== -->
        <!-- Desktop Sticky Filter Sidebar                  -->
        <!-- ============================================== -->
        <aside class="catalog-sidebar">
          <form action="products.php" method="GET" id="desktopFilterForm">
            <?php if (!empty($search)): ?>
              <input type="hidden" name="search" value="<?= e($search) ?>">
            <?php endif; ?>
            <?php if (!empty($category)): ?>
              <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>
            <?php if (!empty($sort)): ?>
              <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>

            <div class="filter-card">
              <div class="filter-card-header">
                <h2 class="filter-card-title">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                  </svg>
                  <span>Filters</span>
                  <?php if ($activeFilterCount > 0): ?>
                    <span class="filter-active-count"><?= $activeFilterCount ?></span>
                  <?php endif; ?>
                </h2>
                <?php if ($activeFilterCount > 0): ?>
                  <a href="products.php<?= !empty($category) ? '?category=' . urlencode($category) : '' ?>" class="filter-reset-link">Reset All</a>
                <?php endif; ?>
              </div>

              <!-- 1. Price Range Bar -->
              <div class="filter-section">
                <div class="filter-section-title">
                  <span>Price Range</span>
                  <span style="font-weight: 500; font-size: 0.75rem; color: var(--text-muted);"><?= e($currencySymbol) ?></span>
                </div>
                
                <div class="price-slider-wrap">
                  <div class="price-slider-track-bg"></div>
                  <div class="price-slider-track-fill" id="desktopTrackFill"></div>
                  
                  <div class="price-range-inputs">
                    <input type="range" 
                           id="desktopMinPriceSlider" 
                           name="min_price" 
                           min="<?= (int)$dbMinPrice ?>" 
                           max="<?= (int)$dbMaxPrice ?>" 
                           value="<?= (int)$minPrice ?>" 
                           step="1"
                           class="price-range-input">
                    <input type="range" 
                           id="desktopMaxPriceSlider" 
                           name="max_price" 
                           min="<?= (int)$dbMinPrice ?>" 
                           max="<?= (int)$dbMaxPrice ?>" 
                           value="<?= (int)$maxPrice ?>" 
                           step="1"
                           class="price-range-input">
                  </div>
                </div>

                <div class="price-readout-row">
                  <div class="price-readout-box">
                    <span class="price-readout-label">Min Price</span>
                    <span class="price-readout-val" id="desktopMinPriceDisplay"><?= formatPrice($minPrice) ?></span>
                  </div>
                  <span style="color: var(--text-muted); font-weight: 600;">—</span>
                  <div class="price-readout-box">
                    <span class="price-readout-label">Max Price</span>
                    <span class="price-readout-val" id="desktopMaxPriceDisplay"><?= formatPrice($maxPrice) ?></span>
                  </div>
                </div>
              </div>

              <!-- 2. Size-Wise Filter -->
              <div class="filter-section">
                <div class="filter-section-title">
                  <span>Size</span>
                  <?php if (!empty($selectedSizes)): ?>
                    <span class="filter-active-count"><?= count($selectedSizes) ?></span>
                  <?php endif; ?>
                </div>
                <div class="size-chips-grid">
                  <?php foreach ($availableSizesList as $sz): ?>
                    <?php $isChecked = in_array($sz, $selectedSizes); ?>
                    <div class="size-chip-item">
                      <input type="checkbox" 
                             name="sizes[]" 
                             value="<?= e($sz) ?>" 
                             id="desktop_sz_<?= e($sz) ?>" 
                             class="size-chip-checkbox" 
                             <?= $isChecked ? 'checked' : '' ?>>
                      <label for="desktop_sz_<?= e($sz) ?>" class="size-chip-label">
                        <?= e($sz) ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- 3. Brand-Wise Filter -->
              <div class="filter-section">
                <div class="filter-section-title">
                  <span>Brand</span>
                  <?php if (!empty($selectedBrands)): ?>
                    <span class="filter-active-count"><?= count($selectedBrands) ?></span>
                  <?php endif; ?>
                </div>
                <div class="brand-filter-list">
                  <?php if (!empty($allBrands)): ?>
                    <?php foreach ($allBrands as $b): ?>
                      <?php $isBrandChecked = in_array($b['brand'], $selectedBrands); ?>
                      <label class="brand-item-label">
                        <div class="brand-item-left">
                          <input type="checkbox" 
                                 name="brands[]" 
                                 value="<?= e($b['brand']) ?>" 
                                 class="brand-item-checkbox" 
                                 <?= $isBrandChecked ? 'checked' : '' ?>>
                          <span><?= e($b['brand']) ?></span>
                        </div>
                        <span class="brand-count-badge"><?= $b['count'] ?></span>
                      </label>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-muted" style="font-size: 0.85rem;">No brands found</p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- 4. Category Quick Links -->
              <div class="filter-section">
                <div class="filter-section-title">
                  <span>Category</span>
                </div>
                <div class="category-filter-list">
                  <a href="products.php" class="category-filter-item <?= empty($category) ? 'active' : '' ?>">
                    <span>All Products</span>
                    <span class="brand-count-badge"><?= array_sum(array_column($allCategories, 'count')) ?></span>
                  </a>
                  <?php foreach ($allCategories as $cat): ?>
                    <?php 
                      $isCatActive = strtolower($category) === strtolower($cat['category']);
                      $catUrlParams = $_GET;
                      $catUrlParams['category'] = $cat['category'];
                      unset($catUrlParams['page']);
                    ?>
                    <a href="products.php?<?= http_build_query($catUrlParams) ?>" 
                       class="category-filter-item <?= $isCatActive ? 'active' : '' ?>">
                      <span><?= e($cat['category']) ?></span>
                      <span class="brand-count-badge"><?= $cat['count'] ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- 5. Availability -->
              <div class="filter-section">
                <label class="instock-row">
                  <span>In Stock Only</span>
                  <input type="checkbox" 
                         name="in_stock" 
                         value="1" 
                         class="brand-item-checkbox" 
                         <?= $inStockOnly ? 'checked' : '' ?>>
                </label>
              </div>

              <button type="submit" class="filter-submit-btn">Apply Filters</button>
            </div>
          </form>
        </aside>

        <!-- ============================================== -->
        <!-- Main Catalog Right Column                      -->
        <!-- ============================================== -->
        <div>
          <!-- Active Filter Tags Pills -->
          <?php if ($activeFilterCount > 0): ?>
            <div class="active-filters-bar">
              <span class="active-filters-label">Active:</span>

              <?php if (!empty($category)): ?>
                <a href="<?= buildFilterUrl('category') ?>" class="active-filter-tag" title="Remove category filter">
                  <span>Category: <strong><?= e($category) ?></strong></span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endif; ?>

              <?php if (!empty($search)): ?>
                <a href="<?= buildFilterUrl('search') ?>" class="active-filter-tag" title="Remove search filter">
                  <span>Search: "<strong><?= e($search) ?></strong>"</span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endif; ?>

              <?php if ($isPriceFiltered): ?>
                <a href="<?= buildFilterUrl('min_price') . '&' . http_build_query(array_diff_key($_GET, ['min_price'=>1, 'max_price'=>1, 'page'=>1])) ?>" 
                   class="active-filter-tag" title="Reset price filter">
                  <span>Price: <strong><?= formatPrice($minPrice) ?> – <?= formatPrice($maxPrice) ?></strong></span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endif; ?>

              <?php foreach ($selectedSizes as $sz): ?>
                <a href="<?= buildFilterUrl('sizes', $sz) ?>" class="active-filter-tag" title="Remove size <?= e($sz) ?>">
                  <span>Size: <strong><?= e($sz) ?></strong></span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endforeach; ?>

              <?php foreach ($selectedBrands as $b): ?>
                <a href="<?= buildFilterUrl('brands', $b) ?>" class="active-filter-tag" title="Remove brand <?= e($b) ?>">
                  <span>Brand: <strong><?= e($b) ?></strong></span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endforeach; ?>

              <?php if ($inStockOnly): ?>
                <a href="<?= buildFilterUrl('in_stock') ?>" class="active-filter-tag" title="Remove in-stock filter">
                  <span>In Stock Only</span>
                  <span class="active-filter-tag-close">×</span>
                </a>
              <?php endif; ?>

              <a href="products.php" class="clear-all-tag">Clear All</a>
            </div>
          <?php endif; ?>

          <!-- Products Grid -->
          <div class="product-grid" id="productGrid">
            <?php if (!empty($products)): ?>
              <?php foreach ($products as $product): 
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
                    
                    <!-- Modernized Floating Quick Add Overlay -->
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
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                      <p class="product-category" style="margin-bottom: 0;"><?= e($product['category']) ?></p>
                      <?php if (!empty($product['brand'])): ?>
                        <span style="font-size: 0.72rem; color: var(--accent-color); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;"><?= e($product['brand']) ?></span>
                      <?php endif; ?>
                    </div>
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
              <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem; background: var(--surface-color); border-radius: 12px; border: 1px dashed var(--border-color);">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔍</div>
                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">No matching products found</h3>
                <p class="text-muted" style="max-width: 420px; margin: 0 auto 1.5rem;">
                  We couldn't find any items matching your selected filters. Try widening your price range or clearing some attributes.
                </p>
                <a href="products.php" class="btn btn-primary" style="padding: 12px 28px;">Clear All Filters</a>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 4rem; gap: 0.5rem; flex-wrap: wrap;">
              <?php 
                $queryParams = $_GET;
                if ($page > 1):
                  $queryParams['page'] = $page - 1;
              ?>
                <a href="products.php?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 10px 16px;">← Prev</a>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $totalPages; $i++): 
                $queryParams['page'] = $i;
              ?>
                <a href="products.php?<?= http_build_query($queryParams) ?>" 
                   class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>" 
                   style="padding: 10px 16px; min-width: 44px; text-align: center;">
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages): 
                $queryParams['page'] = $page + 1;
              ?>
                <a href="products.php?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 10px 16px;">Next →</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>
</main>

<!-- ============================================== -->
<!-- Mobile Filter Off-Canvas Drawer                -->
<!-- ============================================== -->
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay">
  <div class="mobile-drawer-content" id="mobileDrawerContent">
    <div class="mobile-drawer-header">
      <h3 style="font-size: 1.2rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
        </svg>
        <span>Filter Products</span>
      </h3>
      <button type="button" id="mobileDrawerCloseBtn" style="background: none; border: none; font-size: 1.8rem; line-height: 1; cursor: pointer; color: var(--primary-color);">×</button>
    </div>
    
    <div class="mobile-drawer-body">
      <form action="products.php" method="GET" id="mobileFilterForm">
        <?php if (!empty($search)): ?>
          <input type="hidden" name="search" value="<?= e($search) ?>">
        <?php endif; ?>
        <?php if (!empty($category)): ?>
          <input type="hidden" name="category" value="<?= e($category) ?>">
        <?php endif; ?>
        <?php if (!empty($sort)): ?>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <?php endif; ?>

        <!-- Price Range -->
        <div class="filter-section">
          <div class="filter-section-title">
            <span>Price Range</span>
          </div>
          <div class="price-slider-wrap">
            <div class="price-slider-track-bg"></div>
            <div class="price-slider-track-fill" id="mobileTrackFill"></div>
            
            <div class="price-range-inputs">
              <input type="range" 
                     id="mobileMinPriceSlider" 
                     name="min_price" 
                     min="<?= (int)$dbMinPrice ?>" 
                     max="<?= (int)$dbMaxPrice ?>" 
                     value="<?= (int)$minPrice ?>" 
                     step="1"
                     class="price-range-input">
              <input type="range" 
                     id="mobileMaxPriceSlider" 
                     name="max_price" 
                     min="<?= (int)$dbMinPrice ?>" 
                     max="<?= (int)$dbMaxPrice ?>" 
                     value="<?= (int)$maxPrice ?>" 
                     step="1"
                     class="price-range-input">
            </div>
          </div>

          <div class="price-readout-row">
            <div class="price-readout-box">
              <span class="price-readout-label">Min</span>
              <span class="price-readout-val" id="mobileMinPriceDisplay"><?= formatPrice($minPrice) ?></span>
            </div>
            <span style="color: var(--text-muted); font-weight: 600;">—</span>
            <div class="price-readout-box">
              <span class="price-readout-label">Max</span>
              <span class="price-readout-val" id="mobileMaxPriceDisplay"><?= formatPrice($maxPrice) ?></span>
            </div>
          </div>
        </div>

        <!-- Sizes -->
        <div class="filter-section">
          <div class="filter-section-title">
            <span>Size</span>
          </div>
          <div class="size-chips-grid">
            <?php foreach ($availableSizesList as $sz): ?>
              <?php $isChecked = in_array($sz, $selectedSizes); ?>
              <div class="size-chip-item">
                <input type="checkbox" 
                       name="sizes[]" 
                       value="<?= e($sz) ?>" 
                       id="mob_sz_<?= e($sz) ?>" 
                       class="size-chip-checkbox" 
                       <?= $isChecked ? 'checked' : '' ?>>
                <label for="mob_sz_<?= e($sz) ?>" class="size-chip-label">
                  <?= e($sz) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Brands -->
        <div class="filter-section">
          <div class="filter-section-title">
            <span>Brand</span>
          </div>
          <div class="brand-filter-list">
            <?php foreach ($allBrands as $b): ?>
              <?php $isBrandChecked = in_array($b['brand'], $selectedBrands); ?>
              <label class="brand-item-label">
                <div class="brand-item-left">
                  <input type="checkbox" 
                         name="brands[]" 
                         value="<?= e($b['brand']) ?>" 
                         class="brand-item-checkbox" 
                         <?= $isBrandChecked ? 'checked' : '' ?>>
                  <span><?= e($b['brand']) ?></span>
                </div>
                <span class="brand-count-badge"><?= $b['count'] ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- In Stock -->
        <div class="filter-section">
          <label class="instock-row">
            <span>In Stock Only</span>
            <input type="checkbox" 
                   name="in_stock" 
                   value="1" 
                   class="brand-item-checkbox" 
                   <?= $inStockOnly ? 'checked' : '' ?>>
          </label>
        </div>

        <button type="submit" class="filter-submit-btn">Apply Filters</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
