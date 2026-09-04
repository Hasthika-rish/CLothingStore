<?php
/**
 * Navigation Bar Component
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/functions.php';

$cart_count = getCartCount();
$current_page = basename($_SERVER['PHP_SELF']);
$current_cat = $_GET['category'] ?? '';
$site_name = getSetting('site_name', SITE_NAME);

// Fetch categories from DB if connected
$nav_categories = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT name, slug FROM categories ORDER BY display_order ASC, id ASC");
    $nav_categories = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback static categories if DB error
    $nav_categories = [
        ['name' => 'Women', 'slug' => 'women'],
        ['name' => 'Men', 'slug' => 'men'],
        ['name' => 'Other', 'slug' => 'other'],
        ['name' => 'Kids Section', 'slug' => 'kids']
    ];
}
?>
<!-- Navigation -->
<nav class="navbar" id="mainNavbar">
  <div class="container">
    <a href="index.php" class="logo"><?= e($site_name) ?></a>
    
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" class="<?= ($current_page === 'index.php') ? 'active' : '' ?>">Home</a></li>
      <li><a href="products.php" class="<?= ($current_page === 'products.php' && empty($current_cat)) ? 'active' : '' ?>">Shop</a></li>
      
      <?php foreach ($nav_categories as $cat): ?>
        <li>
          <a href="products.php?category=<?= urlencode($cat['name']) ?>" 
             class="<?= ($current_page === 'products.php' && strtolower($current_cat) === strtolower($cat['name'])) ? 'active' : '' ?>">
            <?= e($cat['name']) ?>
          </a>
        </li>
      <?php endforeach; ?>
      
      <li><a href="your-orders.php" class="<?= ($current_page === 'your-orders.php') ? 'active' : '' ?>">Your Orders</a></li>
      
      <?php if (isAdminLoggedIn()): ?>
        <li><a href="admin/index.php" style="color: var(--accent-color); font-weight: 600;">⚡ Admin Panel</a></li>
      <?php endif; ?>
    </ul>

    <div class="nav-icons">
      <!-- Theme Toggle -->
      <button class="icon-btn theme-toggle" id="themeToggleBtn" aria-label="Toggle Theme" title="Toggle Theme">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
      </button>

      <!-- Search Trigger -->
      <button class="icon-btn search-btn" id="searchToggleBtn" aria-label="Search" title="Search Products">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </button>

      <!-- Wishlist -->
      <a href="wishlist.php" class="icon-btn" aria-label="Wishlist" title="Wishlist">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        <span class="wishlist-badge" id="navWishlistBadge" style="display: none;">0</span>
      </a>

      <!-- Shopping Cart -->
      <a href="cart.php" class="icon-btn" aria-label="Cart" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span class="cart-badge" id="navCartBadge" style="<?= $cart_count > 0 ? 'display:flex;' : 'display:none;' ?>"><?= $cart_count ?></span>
      </a>

      <!-- Mobile Menu Hamburger -->
      <button class="icon-btn mobile-menu-btn" id="mobileMenuBtn" aria-label="Open Navigation Menu" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
    </div>
  </div>

  <!-- Expandable Search Bar Overlay -->
  <div class="search-overlay" id="searchOverlay" style="display: none;">
    <div class="container">
      <form action="products.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search for dresses, shirts, jackets, styles..." autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
        <button type="button" class="btn btn-secondary" id="closeSearchBtn">✕</button>
      </form>
    </div>
  </div>
</nav>
