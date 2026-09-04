<?php
/**
 * Wishlist Page
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$page_title = 'My Saved Wishlist';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section">
    <div class="container">
      
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.3rem;">My Wishlist</h1>
          <p class="text-muted">Saved items you love and want to shop later.</p>
        </div>
        <button id="clearWishlistBtn" class="btn btn-secondary" style="display: none; padding: 8px 16px; font-size: 0.85rem; color: #C62828;">Clear Wishlist</button>
      </div>

      <div class="product-grid" id="wishlistProductGrid">
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
          <div style="font-size: 3rem; margin-bottom: 1rem;">💖</div>
          <h3>Your wishlist is currently empty</h3>
          <p class="text-muted" style="margin: 0.5rem 0 2rem;">Click the heart icon on any piece while browsing to save it here!</p>
          <a href="products.php" class="btn btn-primary">Browse All Clothing</a>
        </div>
      </div>

    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const wishlist = JSON.parse(localStorage.getItem('anjiana_wishlist') || '[]');
  const grid = document.getElementById('wishlistProductGrid');
  const clearBtn = document.getElementById('clearWishlistBtn');

  if (wishlist.length === 0) {
    return;
  }

  if (clearBtn) {
    clearBtn.style.display = 'block';
    clearBtn.addEventListener('click', () => {
      if (confirm('Clear all saved items?')) {
        localStorage.removeItem('anjiana_wishlist');
        window.location.reload();
      }
    });
  }

  grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem;">Loading your saved pieces...</div>';

  try {
    const response = await fetch('ajax/wishlist.php?action=get_items&ids=' + wishlist.join(','));
    const data = await response.json();

    if (!data.products || data.products.length === 0) {
      grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;"><h3>No saved items found.</h3><a href="products.php" class="btn btn-primary" style="margin-top:1rem;">Browse Products</a></div>';
      return;
    }

    grid.innerHTML = '';
    data.products.forEach(p => {
      const price = parseFloat(p.price);
      const discount = parseFloat(p.discount || 0);
      const discPrice = discount > 0 ? (price * (1 - discount/100)) : price;

      const card = document.createElement('article');
      card.className = 'product-card';
      card.style.position = 'relative';

      card.innerHTML = `
        <button class="card-wishlist-btn active" data-id="${p.id}" aria-label="Remove from Wishlist" style="color: #E91E63;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#E91E63" stroke="#E91E63" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
        </button>
        <a href="product-details.php?id=${p.id}" class="product-img-wrap">
          ${discount > 0 ? `<span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2;">-${parseInt(discount)}%</span>` : ''}
          <img src="${p.image_url || 'images/placeholder.png'}" alt="${p.name}" class="product-img">
        </a>
        <div class="product-info">
          <p class="product-category">${p.category}</p>
          <h3 class="product-title"><a href="product-details.php?id=${p.id}">${p.name}</a></h3>
          <p class="product-price">
            ${discount > 0 ? `<span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.85rem; margin-right: 0.4rem;">$${price.toFixed(2)}</span><span style="font-weight: 600; color: var(--accent-color); font-size: 1.05rem;">$${discPrice.toFixed(2)}</span>` : `<span style="font-weight: 600; font-size: 1.05rem;">$${price.toFixed(2)}</span>`}
          </p>
          <div style="margin-top: 1rem;">
            <a href="product-details.php?id=${p.id}" class="btn btn-primary" style="width: 100%; text-align: center; padding: 8px 12px; font-size: 0.9rem;">View Product</a>
          </div>
        </div>
      `;
      grid.appendChild(card);
    });

  } catch (err) {
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: red;">Failed to load saved wishlist.</div>';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
