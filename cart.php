<?php
/**
 * Shopping Cart Page & Controller
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF if present
    if (isset($_POST['csrf_token']) && !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid security token. Please try again.');
        header("Location: cart.php");
        exit;
    }

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $size = trim($_POST['size'] ?? 'M');
        $color = trim($_POST['color'] ?? 'Standard');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($productId > 0) {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if ($product) {
                addToCart($product['id'], $product['name'], $product['price'], $product['image_url'], $size, $color, $quantity, $product['discount']);
                setFlash('success', "{$quantity}x '{$product['name']}' ({$size}) added to your cart!");
            } else {
                setFlash('error', 'Product not found.');
            }
        }
        
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
        header("Location: {$redirectUrl}");
        exit;
    }

    if ($action === 'update') {
        $itemKey = $_POST['item_key'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);
        updateCartItem($itemKey, $quantity);
        setFlash('success', 'Cart updated successfully.');
        header("Location: cart.php");
        exit;
    }

    if ($action === 'remove') {
        $itemKey = $_POST['item_key'] ?? '';
        removeFromCart($itemKey);
        setFlash('success', 'Item removed from cart.');
        header("Location: cart.php");
        exit;
    }

    if ($action === 'clear') {
        clearCart();
        setFlash('success', 'Your cart has been cleared.');
        header("Location: cart.php");
        exit;
    }
}

$cart = getCart();
$subtotal = getCartSubtotal();
$shipping = getCartShipping($subtotal);
$total = getCartTotal();

$page_title = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section">
    <div class="container">
      <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 2rem;">Shopping Cart</h1>

      <?php if (empty($cart)): ?>
        <div class="empty-cart-state" style="text-align: center; padding: 5rem 1rem; background: var(--bg-alt, rgba(0,0,0,0.02)); border-radius: 12px; border: 1px dashed var(--border-color);">
          <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
          <h2 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem;">Your Cart is Empty</h2>
          <p class="text-muted" style="max-width: 400px; margin: 0 auto 2rem;">Looks like you haven't added any clothing pieces yet. Explore our latest arrivals to find something you love!</p>
          <a href="products.php" class="btn btn-primary" style="padding: 14px 32px;">Start Shopping</a>
        </div>
      <?php else: ?>
        
        <!-- Free shipping banner -->
        <?php if ($subtotal < FREE_SHIPPING_MIN): ?>
          <div style="background: rgba(159,93,68,0.08); border: 1px solid rgba(159,93,68,0.2); color: var(--accent-color); padding: 12px 20px; border-radius: 8px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; font-size: 0.95rem;">
            <span>Add <strong><?= formatPrice(FREE_SHIPPING_MIN - $subtotal) ?></strong> more to your cart to qualify for <strong>FREE DELIVERY!</strong> 🚚</span>
            <a href="products.php" style="color: var(--accent-color); font-weight: 600; text-decoration: underline;">Add More Items</a>
          </div>
        <?php else: ?>
          <div style="background: #E8F5E9; border: 1px solid #C8E6C9; color: #2E7D32; padding: 12px 20px; border-radius: 8px; margin-bottom: 2rem; font-size: 0.95rem; font-weight: 600;">
            🎉 Congratulations! You have unlocked <strong>FREE DELIVERY</strong> on this order!
          </div>
        <?php endif; ?>

        <div class="cart-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem; align-items: start;">
          
          <!-- Cart Items List -->
          <div class="cart-items-wrapper">
            <div class="cart-items-header" style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">
              <span>Product</span>
              <span>Total</span>
            </div>

            <div class="cart-items-list" id="cartItemsList">
              <?php foreach ($cart as $key => $item): 
                $itemTotal = (float)$item['price'] * (int)$item['quantity'];
              ?>
                <div class="cart-item" style="display: flex; gap: 1.5rem; padding: 1.5rem 0; border-bottom: 1px solid var(--border-color); align-items: center;">
                  <a href="product-details.php?id=<?= $item['id'] ?>" style="flex-shrink: 0;">
                    <img src="<?= e($item['imageUrl'] ?: 'images/placeholder.png') ?>" alt="<?= e($item['name']) ?>" style="width: 80px; height: 100px; object-fit: cover; border-radius: 6px;">
                  </a>

                  <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                      <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem;">
                        <a href="product-details.php?id=<?= $item['id'] ?>" style="color: inherit; text-decoration: none;"><?= e($item['name']) ?></a>
                      </h3>
                      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">
                        Size: <strong style="color: var(--primary-color);"><?= e($item['size']) ?></strong>
                        <?php if (!empty($item['color']) && $item['color'] !== 'Standard' && $item['color'] !== 'N/A'): ?>
                          • Color: <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?= e($item['color']) ?>; vertical-align:middle; border:1px solid #ccc;"></span>
                        <?php endif; ?>
                      </p>
                      <p style="font-size: 0.95rem; font-weight: 500;">
                        <?= formatPrice($item['price']) ?> <span style="color: var(--text-muted); font-size: 0.8rem;">each</span>
                      </p>
                    </div>

                    <!-- Quantity Controller & Remove Form -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                      <form action="cart.php" method="POST" style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="item_key" value="<?= e($key) ?>">
                        
                        <button type="submit" name="quantity" value="<?= max(0, $item['quantity'] - 1) ?>" style="padding: 4px 10px; background: none; border: none; cursor: pointer; font-size: 1rem;">-</button>
                        <span style="padding: 4px 10px; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); min-width: 32px; text-align: center; font-weight: 600; font-size: 0.9rem;"><?= (int)$item['quantity'] ?></span>
                        <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" style="padding: 4px 10px; background: none; border: none; cursor: pointer; font-size: 1rem;">+</button>
                      </form>

                      <form action="cart.php" method="POST" onsubmit="return confirm('Remove this item from your cart?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="item_key" value="<?= e($key) ?>">
                        <button type="submit" style="background: none; border: none; color: #C62828; text-decoration: underline; cursor: pointer; font-size: 0.85rem; font-weight: 500;">Remove</button>
                      </form>
                    </div>
                  </div>

                  <div style="font-size: 1.15rem; font-weight: 700; text-align: right; min-width: 80px;">
                    <?= formatPrice($itemTotal) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
              <a href="products.php" class="btn btn-secondary" style="padding: 10px 20px;">← Continue Shopping</a>
              <form action="cart.php" method="POST" onsubmit="return confirm('Are you sure you want to clear your entire cart?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-secondary" style="color: #C62828; border-color: #FFCDD2; background: #FFEBEE;">Clear Cart</button>
              </form>
            </div>
          </div>

          <!-- Order Summary Box -->
          <div class="order-summary-card" style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 1.5rem;">Order Summary</h2>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--text-color);">
              <span>Subtotal</span>
              <span style="font-weight: 600;"><?= formatPrice($subtotal) ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--text-color);">
              <span>Estimated Shipping</span>
              <span style="font-weight: 600;">
                <?= $shipping === 0.0 ? '<span style="color: #2E7D32;">FREE</span>' : formatPrice($shipping) ?>
              </span>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

            <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.25rem; font-weight: 800;">
              <span>Total</span>
              <span style="color: var(--accent-color);"><?= formatPrice($total) ?></span>
            </div>

            <a href="checkout.php" class="btn btn-primary" style="display: block; width: 100%; text-align: center; padding: 14px 20px; font-size: 1.05rem; font-weight: 700; border-radius: 6px; text-decoration: none;">
              Proceed to Checkout →
            </a>

            <div style="margin-top: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
              🔒 Guaranteed Safe & Secure Checkout<br>
              Cash on Delivery & Bank Transfer Supported
            </div>
          </div>

        </div>

      <?php endif; ?>
    </div>
  </section>
</main>

<style>
@media (max-width: 900px) {
  .cart-layout {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
