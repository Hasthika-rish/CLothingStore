<?php
/**
 * Admin Point of Sale (POS) In-Store Checkout System
 * Anjiana Clothing Store
 */
$admin_page_title = 'In-Store Point of Sale';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle In-Store POS Order Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_pos_sale') {
    $rawItems = json_decode($_POST['pos_items'] ?? '[]', true);
    $customerName = trim($_POST['pos_customer_name'] ?? 'Walk-in Customer');
    $customerPhone = trim($_POST['pos_customer_phone'] ?? 'N/A');
    $paymentMethod = trim($_POST['pos_payment_method'] ?? 'CASH');
    $totalAmount = (float)($_POST['pos_total'] ?? 0);

    if (!empty($rawItems) && $totalAmount > 0) {
        try {
            $db->beginTransaction();

            $orderNumber = 'POS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)) . '-' . date('y');

            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    order_number, first_name, last_name, email, phone, address, city, district, postal_code,
                    subtotal, shipping, total_amount, payment_method, payment_status, order_status, notes
                ) VALUES (
                    ?, ?, 'Walk-in', 'pos@store.local', ?, 'In-Store Checkout', 'Store POS', 'Colombo', '00000',
                    ?, 0.00, ?, ?, 'Paid', 'Delivered', 'Processed via Admin POS Terminal'
                )
            ");

            $orderStmt->execute([
                $orderNumber, $customerName, $customerPhone,
                $totalAmount, $totalAmount, 'POS_' . $paymentMethod
            ]);

            $orderId = $db->lastInsertId();

            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, price, quantity, size, color, total, image_url
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $updateStockStmt = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");

            foreach ($rawItems as $it) {
                $pPrice = (float)$it['price'];
                $pQty = (int)$it['qty'];
                $pTotal = $pPrice * $pQty;

                $itemStmt->execute([
                    $orderId, $it['id'], $it['name'], $pPrice, $pQty, $it['size'] ?? 'M', 'N/A', $pTotal, $it['image'] ?? ''
                ]);

                $updateStockStmt->execute([$pQty, $it['id']]);
            }

            $db->commit();
            setFlash('success', "Sale complete! Order Ref: {$orderNumber}. Stock has been updated.");

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'POS Transaction Error: ' . $e->getMessage());
        }
    }
    header("Location: pos.php");
    exit;
}

// Fetch Active Products
$products = [];
try {
    $stmt = $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY name ASC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}
?>

<div class="pos-container" style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 2rem; align-items: start;">
  
  <!-- Left Column: Product Selection Grid -->
  <div>
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center;">
      <input type="text" id="posSearch" class="form-control" placeholder="Search catalog by product name..." style="padding: 10px 16px;">
    </div>

    <div class="pos-product-grid" id="posProductGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 1.25rem; max-height: calc(100vh - 220px); overflow-y: auto; padding-right: 0.5rem;">
      <?php foreach ($products as $p): 
        $price = (float)$p['price'];
        $discount = (float)$p['discount'];
        $actualPrice = getDiscountedPrice($price, $discount);
        $sizes = !empty($p['sizes']) ? explode(',', $p['sizes']) : ['M'];
      ?>
        <div class="pos-product-card" data-name="<?= strtolower(e($p['name'])) ?>" style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; text-align: center; box-shadow: var(--shadow-sm);">
          <img src="../<?= e($p['image_url'] ?: 'images/placeholder.png') ?>" alt="" style="width: 100%; height: 130px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
          <h4 style="font-size: 0.88rem; font-weight: 600; margin: 0 0 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($p['name']) ?></h4>
          <div style="font-weight: 700; color: var(--accent-color); font-size: 0.95rem; margin-bottom: 0.5rem;"><?= formatPrice($actualPrice) ?></div>
          
          <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap;">
            <?php foreach ($sizes as $sz): 
              $sz = trim($sz);
              if (empty($sz)) continue;
            ?>
              <button type="button" class="btn btn-secondary pos-add-btn" 
                      data-id="<?= $p['id'] ?>"
                      data-name="<?= e($p['name']) ?>"
                      data-price="<?= $actualPrice ?>"
                      data-size="<?= e($sz) ?>"
                      data-image="<?= e($p['image_url']) ?>"
                      style="padding: 3px 8px; font-size: 0.75rem;">
                + <?= e($sz) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Column: Current Register Ticket -->
  <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.75rem; box-shadow: var(--shadow-sm); position: sticky; top: 20px;">
    <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">Current Register Ticket</h3>

    <div id="posCartItems" style="min-height: 180px; max-height: 280px; overflow-y: auto; margin-bottom: 1.25rem; border-bottom: 1px dashed var(--border-color); padding-bottom: 1rem;">
      <p style="color: var(--text-muted); text-align: center; padding-top: 3rem;">No items in ticket. Click product sizes on the left to add.</p>
    </div>

    <!-- Customer details -->
    <div style="margin-bottom: 1.25rem;">
      <input type="text" id="posCustomerName" class="form-control" placeholder="Customer Name (Optional)" style="margin-bottom: 0.5rem; font-size: 0.88rem;">
      <input type="tel" id="posCustomerPhone" class="form-control" placeholder="Phone Number (Optional)" style="font-size: 0.88rem;">
    </div>

    <!-- Payment mode -->
    <div style="margin-bottom: 1.5rem;">
      <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">Payment Method</label>
      <select id="posPaymentMethod" class="form-control" style="font-size: 0.9rem;">
        <option value="CASH">💵 Cash</option>
        <option value="CARD">💳 Credit / Debit Card</option>
        <option value="BANK_TRANSFER">🏦 Bank Deposit / Transfer</option>
      </select>
    </div>

    <!-- Total -->
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.5rem; font-size: 1.4rem; font-weight: 800;">
      <span>Total Amount:</span>
      <span style="color: var(--accent-color);" id="posTotalDisplay">$0.00</span>
    </div>

    <form action="pos.php" method="POST" id="posForm">
      <input type="hidden" name="action" value="complete_pos_sale">
      <input type="hidden" name="pos_items" id="posItemsInput" value="[]">
      <input type="hidden" name="pos_customer_name" id="posCustNameInput" value="">
      <input type="hidden" name="pos_customer_phone" id="posCustPhoneInput" value="">
      <input type="hidden" name="pos_payment_method" id="posPayMethodInput" value="CASH">
      <input type="hidden" name="pos_total" id="posTotalInput" value="0">

      <button type="submit" id="posCompleteBtn" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 700; font-size: 1.05rem;" disabled>
        Complete In-Store Sale 🧾
      </button>
    </form>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let ticketItems = [];

  const cartContainer = document.getElementById('posCartItems');
  const totalDisplay = document.getElementById('posTotalDisplay');
  const completeBtn = document.getElementById('posCompleteBtn');
  const itemsInput = document.getElementById('posItemsInput');
  const totalInput = document.getElementById('posTotalInput');

  // Search filter on products
  const searchInput = document.getElementById('posSearch');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.pos-product-card').forEach(card => {
        const name = card.getAttribute('data-name');
        card.style.display = name.includes(q) ? 'block' : 'none';
      });
    });
  }

  // Add Item to ticket
  document.querySelectorAll('.pos-add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = parseInt(btn.getAttribute('data-id'));
      const name = btn.getAttribute('data-name');
      const price = parseFloat(btn.getAttribute('data-price'));
      const size = btn.getAttribute('data-size');
      const image = btn.getAttribute('data-image');

      const existingIndex = ticketItems.findIndex(i => i.id === id && i.size === size);
      if (existingIndex > -1) {
        ticketItems[existingIndex].qty += 1;
      } else {
        ticketItems.push({ id, name, price, size, image, qty: 1 });
      }
      renderTicket();
    });
  });

  function renderTicket() {
    if (ticketItems.length === 0) {
      cartContainer.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding-top: 3rem;">No items in ticket. Click product sizes on the left to add.</p>';
      totalDisplay.innerText = '$0.00';
      completeBtn.disabled = true;
      itemsInput.value = '[]';
      totalInput.value = '0';
      return;
    }

    let subtotal = 0;
    cartContainer.innerHTML = '';

    ticketItems.forEach((it, idx) => {
      const lineTotal = it.price * it.qty;
      subtotal += lineTotal;

      const row = document.createElement('div');
      row.style = "display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; font-size:0.88rem;";
      row.innerHTML = `
        <div style="flex:1;">
          <strong>${it.name}</strong> (${it.size})
          <div style="color:var(--text-muted); font-size:0.78rem;">$${it.price.toFixed(2)} each</div>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <button type="button" class="btn btn-secondary" style="padding:2px 6px; font-size:0.75rem;" onclick="updatePosQty(${idx}, -1)">-</button>
          <span style="font-weight:600;">${it.qty}</span>
          <button type="button" class="btn btn-secondary" style="padding:2px 6px; font-size:0.75rem;" onclick="updatePosQty(${idx}, 1)">+</button>
          <span style="font-weight:700; min-width:55px; text-align:right;">$${lineTotal.toFixed(2)}</span>
        </div>
      `;
      cartContainer.appendChild(row);
    });

    totalDisplay.innerText = '$' + subtotal.toFixed(2);
    itemsInput.value = JSON.stringify(ticketItems);
    totalInput.value = subtotal.toFixed(2);
    completeBtn.disabled = false;
  }

  window.updatePosQty = (idx, delta) => {
    if (ticketItems[idx]) {
      ticketItems[idx].qty += delta;
      if (ticketItems[idx].qty <= 0) {
        ticketItems.splice(idx, 1);
      }
      renderTicket();
    }
  };

  // Form submit sync
  const posForm = document.getElementById('posForm');
  if (posForm) {
    posForm.addEventListener('submit', () => {
      document.getElementById('posCustNameInput').value = document.getElementById('posCustomerName').value;
      document.getElementById('posCustPhoneInput').value = document.getElementById('posCustomerPhone').value;
      document.getElementById('posPayMethodInput').value = document.getElementById('posPaymentMethod').value;
    });
  }
});
</script>

<style>
@media (max-width: 900px) {
  .pos-container {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
