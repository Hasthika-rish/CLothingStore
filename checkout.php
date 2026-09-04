<?php
/**
 * Checkout & Order Processing Page
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$cart = getCart();

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$subtotal = getCartSubtotal();
$shipping = getCartShipping($subtotal);
$total = getCartTotal();

// Bank Account Info from Settings
$bankName = getSetting('bank_name', 'Commercial Bank of Ceylon');
$bankAccountName = getSetting('bank_account_name', 'Anjiana Store Holdings');
$bankAccountNumber = getSetting('bank_account_number', '8001234567');
$bankBranch = getSetting('bank_branch', 'Colombo Main Branch');

$errors = [];

// Handle Checkout Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page and try again.';
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $district  = trim($_POST['district'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'COD');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'Please provide both first and last name.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($phone)) {
        $errors[] = 'Please provide a contact phone number.';
    }
    if (empty($address) || empty($city) || empty($district)) {
        $errors[] = 'Please provide complete delivery address details.';
    }

    $slipUrl = null;
    if ($paymentMethod === 'BANK_TRANSFER') {
        if (isset($_FILES['deposit_slip']) && $_FILES['deposit_slip']['error'] === UPLOAD_ERR_OK) {
            try {
                $slipUrl = handleImageUpload($_FILES['deposit_slip'], SLIP_UPLOAD_DIR, 'uploads/slips/');
            } catch (Exception $e) {
                $errors[] = 'Deposit Slip Upload: ' . $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $orderNumber = generateOrderNumber();
            $paymentStatus = ($paymentMethod === 'BANK_TRANSFER') ? ($slipUrl ? 'Verifying Slip' : 'Awaiting Slip') : 'Pending (COD)';
            $orderStatus = 'Pending';

            // Insert Order
            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    order_number, first_name, last_name, email, phone, address, city, district, postal_code,
                    subtotal, shipping, total_amount, payment_method, payment_status, payment_proof_url, order_status, notes
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $orderStmt->execute([
                $orderNumber, $firstName, $lastName, strtolower($email), $phone, $address, $city, $district, $postalCode,
                $subtotal, $shipping, $total, $paymentMethod, $paymentStatus, $slipUrl, $orderStatus, $notes
            ]);

            $orderId = $db->lastInsertId();

            // Insert Order Items
            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, price, quantity, size, color, total, image_url
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $updateStockStmt = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");

            foreach ($cart as $item) {
                $itemPrice = (float)$item['price'];
                $itemQty = (int)$item['quantity'];
                $itemTotal = $itemPrice * $itemQty;

                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $itemPrice,
                    $itemQty,
                    $item['size'] ?? 'M',
                    $item['color'] ?? 'N/A',
                    $itemTotal,
                    $item['imageUrl'] ?? ''
                ]);

                // Reduce stock
                $updateStockStmt->execute([$itemQty, $item['id']]);
            }

            $db->commit();

            // Clear Cart Session
            clearCart();
            $_SESSION['customer_email'] = strtolower($email);
            $_SESSION['customer_phone'] = $phone;

            setFlash('success', "Order placed successfully! Reference ID: {$orderNumber}");
            header("Location: your-orders.php?order_number=" . urlencode($orderNumber));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to process order: ' . $e->getMessage();
        }
    }
}

$page_title = 'Secure Checkout';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section">
    <div class="container">
      <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 2rem;">Secure Checkout</h1>

      <?php if (!empty($errors)): ?>
        <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 1.25rem; border-radius: 8px; margin-bottom: 2rem;">
          <h4 style="margin-bottom: 0.5rem; font-weight: 700;">Please fix the following:</h4>
          <ul style="margin-left: 1.5rem;">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="checkout.php" method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <div class="checkout-grid" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 3.5rem; align-items: start;">
          
          <!-- Left Column: Customer & Delivery Details -->
          <div class="checkout-form-column">
            
            <!-- Delivery Info Section -->
            <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
              <h2 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>1.</span> Delivery Information
              </h2>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                  <label for="first_name" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">First Name *</label>
                  <input type="text" name="first_name" id="first_name" class="form-control" required value="<?= e($_POST['first_name'] ?? '') ?>" placeholder="John">
                </div>
                <div>
                  <label for="last_name" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Last Name *</label>
                  <input type="text" name="last_name" id="last_name" class="form-control" required value="<?= e($_POST['last_name'] ?? '') ?>" placeholder="Doe">
                </div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                  <label for="email" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Email Address *</label>
                  <input type="email" name="email" id="email" class="form-control" required value="<?= e($_POST['email'] ?? ($_SESSION['customer_email'] ?? '')) ?>" placeholder="john@example.com">
                </div>
                <div>
                  <label for="phone" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Phone Number *</label>
                  <input type="tel" name="phone" id="phone" class="form-control" required value="<?= e($_POST['phone'] ?? ($_SESSION['customer_phone'] ?? '')) ?>" placeholder="07XXXXXXXX">
                </div>
              </div>

              <div style="margin-bottom: 1rem;">
                <label for="address" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Street Address *</label>
                <input type="text" name="address" id="address" class="form-control" required value="<?= e($_POST['address'] ?? '') ?>" placeholder="No 123, Galle Road">
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                  <label for="city" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">City *</label>
                  <input type="text" name="city" id="city" class="form-control" required value="<?= e($_POST['city'] ?? '') ?>" placeholder="Colombo">
                </div>
                <div>
                  <label for="district" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">District *</label>
                  <select name="district" id="district" class="form-control" required>
                    <option value="">Select District</option>
                    <?php
                    $districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee', 'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle'];
                    $selectedDist = $_POST['district'] ?? 'Colombo';
                    foreach ($districts as $d):
                    ?>
                      <option value="<?= $d ?>" <?= $selectedDist === $d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="postal_code" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Postal Code</label>
                  <input type="text" name="postal_code" id="postal_code" class="form-control" value="<?= e($_POST['postal_code'] ?? '') ?>" placeholder="00300">
                </div>
              </div>

              <div>
                <label for="notes" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">Order Notes (Optional)</label>
                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Special delivery instructions or landmark..."><?= e($_POST['notes'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Payment Method Section -->
            <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: var(--shadow-sm);">
              <h2 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>2.</span> Payment Method
              </h2>

              <!-- Option 1: Cash on Delivery -->
              <label class="payment-method-card" style="display: block; border: 1.5px solid var(--border-color); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <input type="radio" name="payment_method" value="COD" checked id="payCod">
                  <div>
                    <strong style="font-size: 1.05rem;">Cash on Delivery (COD)</strong>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.2rem;">Pay with cash when your parcel is delivered to your doorstep.</p>
                  </div>
                </div>
              </label>

              <!-- Option 2: Bank Deposit / Transfer -->
              <label class="payment-method-card" style="display: block; border: 1.5px solid var(--border-color); border-radius: 8px; padding: 1.25rem; cursor: pointer; transition: all 0.2s;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <input type="radio" name="payment_method" value="BANK_TRANSFER" id="payBank" <?= (($_POST['payment_method'] ?? '') === 'BANK_TRANSFER') ? 'checked' : '' ?>>
                  <div>
                    <strong style="font-size: 1.05rem;">Direct Bank Deposit / Transfer</strong>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.2rem;">Transfer funds directly to our bank account and upload your deposit slip.</p>
                  </div>
                </div>
              </label>

              <!-- Bank Account Info Panel (Hidden unless Bank Transfer is selected) -->
              <div id="bankInfoPanel" style="display: none; margin-top: 1.25rem; background: var(--bg-alt, #f8f9fa); border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem;">
                <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--primary-color);">🏦 Bank Transfer Details:</h4>
                <div style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                  <div><strong>Bank:</strong> <?= e($bankName) ?></div>
                  <div><strong>Account Name:</strong> <?= e($bankAccountName) ?></div>
                  <div><strong>Account Number:</strong> <code style="font-size: 1rem; font-weight: 700; background: rgba(0,0,0,0.06); padding: 2px 6px; border-radius: 4px;"><?= e($bankAccountNumber) ?></code></div>
                  <div><strong>Branch:</strong> <?= e($bankBranch) ?></div>
                </div>

                <label for="deposit_slip" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">
                  Upload Payment Receipt / Slip (JPG, PNG, PDF):
                </label>
                <input type="file" name="deposit_slip" id="deposit_slip" class="form-control" accept="image/*,.pdf" style="padding: 6px;">
                <small class="text-muted" style="display: block; margin-top: 0.3rem;">You can also upload or send your slip later via WhatsApp.</small>
              </div>

            </div>

          </div>

          <!-- Right Column: Order Summary Review -->
          <div class="order-summary-column" style="position: sticky; top: 100px;">
            <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: var(--shadow-sm);">
              <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 1.5rem;">Order Summary</h2>

              <!-- Items mini list -->
              <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 0.5rem;">
                <?php foreach ($cart as $item): 
                  $itemLineTotal = (float)$item['price'] * (int)$item['quantity'];
                ?>
                  <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                    <img src="<?= e($item['imageUrl'] ?: 'images/placeholder.png') ?>" alt="<?= e($item['name']) ?>" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
                    <div style="flex: 1;">
                      <h4 style="font-size: 0.92rem; font-weight: 600; margin: 0;"><?= e($item['name']) ?></h4>
                      <p style="color: var(--text-muted); font-size: 0.8rem; margin: 2px 0 0;">
                        Size: <?= e($item['size']) ?> • Qty: <?= (int)$item['quantity'] ?>
                      </p>
                    </div>
                    <div style="font-weight: 600; font-size: 0.95rem;">
                      <?= formatPrice($itemLineTotal) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

              <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: var(--text-color);">
                <span>Subtotal</span>
                <span style="font-weight: 600;"><?= formatPrice($subtotal) ?></span>
              </div>

              <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: var(--text-color);">
                <span>Islandwide Delivery</span>
                <span style="font-weight: 600;">
                  <?= $shipping === 0.0 ? '<span style="color: #2E7D32;">FREE</span>' : formatPrice($shipping) ?>
                </span>
              </div>

              <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

              <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.35rem; font-weight: 800;">
                <span>Total Due</span>
                <span style="color: var(--accent-color);"><?= formatPrice($total) ?></span>
              </div>

              <button type="submit" class="btn btn-primary" style="display: block; width: 100%; text-align: center; padding: 16px 20px; font-size: 1.1rem; font-weight: 700; border-radius: 6px;">
                Place Order Now 🛍️
              </button>

              <div style="margin-top: 1.25rem; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                By placing this order, you agree to our store policies & terms.
              </div>
            </div>
          </div>

        </div>
      </form>
    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const payCod = document.getElementById('payCod');
  const payBank = document.getElementById('payBank');
  const bankPanel = document.getElementById('bankInfoPanel');

  function toggleBankPanel() {
    if (payBank && payBank.checked) {
      bankPanel.style.display = 'block';
    } else {
      bankPanel.style.display = 'none';
    }
  }

  if (payCod && payBank) {
    payCod.addEventListener('change', toggleBankPanel);
    payBank.addEventListener('change', toggleBankPanel);
    toggleBankPanel();
  }
});
</script>

<style>
@media (max-width: 900px) {
  .checkout-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
