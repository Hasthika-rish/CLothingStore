<?php
/**
 * Customer Order Tracking & History
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Handle payment slip re-upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_slip') {
    $orderNumber = trim($_POST['order_number'] ?? '');
    if (!empty($orderNumber) && isset($_FILES['deposit_slip']) && $_FILES['deposit_slip']['error'] === UPLOAD_ERR_OK) {
        try {
            $slipUrl = handleImageUpload($_FILES['deposit_slip'], SLIP_UPLOAD_DIR, 'uploads/slips/');
            $stmt = $db->prepare("UPDATE orders SET payment_proof_url = ?, payment_status = 'Verifying Slip' WHERE order_number = ?");
            $stmt->execute([$slipUrl, $orderNumber]);
            setFlash('success', 'Deposit slip uploaded successfully! Our team will verify your payment.');
        } catch (Exception $e) {
            setFlash('error', 'Upload error: ' . $e->getMessage());
        }
    }
    header("Location: your-orders.php?order_number=" . urlencode($orderNumber));
    exit;
}

// Search Query
$searchQuery = trim($_GET['order_number'] ?? ($_GET['query'] ?? ($_GET['email'] ?? ($_SESSION['customer_email'] ?? ''))));
$orders = [];

if (!empty($searchQuery)) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM orders 
            WHERE order_number = ? OR email = ? OR phone = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$searchQuery, strtolower($searchQuery), $searchQuery]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        $orders = [];
    }
}

$page_title = 'Track Your Orders';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
  <section class="section">
    <div class="container">
      
      <div style="max-width: 650px; margin: 0 auto 3.5rem; text-align: center;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">Track Your Orders</h1>
        <p class="text-muted" style="margin-bottom: 2rem;">Enter your Order Reference Number (e.g. <code>ANJ-XXXX-26</code>), Email, or Phone Number to view your order progress.</p>
        
        <form action="your-orders.php" method="GET" style="display: flex; gap: 0.5rem; justify-content: center;">
          <input type="text" name="query" class="form-control" placeholder="Order ID, Email or Phone..." required value="<?= e($searchQuery) ?>" style="max-width: 400px; padding: 12px 18px;">
          <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Search Order</button>
        </form>
      </div>

      <!-- Orders Display -->
      <?php if (!empty($searchQuery)): ?>
        <?php if (empty($orders)): ?>
          <div style="text-align: center; padding: 4rem 1rem; background: var(--bg-alt, #f8f9fa); border-radius: 12px; border: 1px dashed var(--border-color); max-width: 700px; margin: 0 auto;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3>No Orders Found</h3>
            <p class="text-muted" style="margin-top: 0.5rem;">We couldn't find any orders matching "<strong><?= e($searchQuery) ?></strong>". Please verify your details or contact us on WhatsApp.</p>
          </div>
        <?php else: ?>
          
          <div class="orders-list" style="max-width: 850px; margin: 0 auto; display: flex; flex-direction: column; gap: 2.5rem;">
            <?php foreach ($orders as $order): 
              // Fetch line items for this order
              $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
              $itemStmt->execute([$order['id']]);
              $orderItems = $itemStmt->fetchAll();

              $status = $order['order_status'];
              $statusBg = '#FFF3E0';
              $statusColor = '#E65100';

              switch ($status) {
                  case 'Processing':
                      $statusBg = '#E3F2FD';
                      $statusColor = '#1565C0';
                      break;
                  case 'Shipped':
                      $statusBg = '#F3E5F5';
                      $statusColor = '#7B1FA2';
                      break;
                  case 'Delivered':
                      $statusBg = '#E8F5E9';
                      $statusColor = '#2E7D32';
                      break;
                  case 'Cancelled':
                      $statusBg = '#FFEBEE';
                      $statusColor = '#C62828';
                      break;
              }
            ?>
              <div class="order-card" style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: var(--shadow-sm);">
                
                <!-- Order Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                  <div>
                    <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Order Reference</span>
                    <h3 style="font-size: 1.35rem; font-weight: 800; font-family: monospace; color: var(--primary-color); margin: 2px 0 0;"><?= e($order['order_number']) ?></h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Placed on <?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?></span>
                  </div>

                  <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <span style="background: <?= $statusBg ?>; color: <?= $statusColor ?>; padding: 6px 14px; border-radius: 50px; font-weight: 700; font-size: 0.85rem;">
                      ● <?= e($order['order_status']) ?>
                    </span>
                  </div>
                </div>

                <!-- Progress Steps Indicator -->
                <div class="order-stepper" style="display: flex; justify-content: space-between; margin-bottom: 2rem; position: relative; padding: 0 1rem;">
                  <?php 
                  $steps = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                  $curIdx = array_search($order['order_status'], $steps);
                  if ($curIdx === false && $order['order_status'] === 'Cancelled') $curIdx = -1;
                  ?>
                  <?php foreach ($steps as $idx => $st): 
                    $isPassed = ($curIdx !== -1 && $idx <= $curIdx);
                  ?>
                    <div style="text-align: center; flex: 1; position: relative;">
                      <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $isPassed ? '#2E7D32' : 'var(--border-color)' ?>; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.4rem; font-size: 0.75rem; font-weight: 700;">
                        <?= $isPassed ? '✓' : ($idx + 1) ?>
                      </div>
                      <span style="font-size: 0.8rem; font-weight: <?= $isPassed ? '600' : '400' ?>; color: <?= $isPassed ? 'var(--primary-color)' : 'var(--text-muted)' ?>;"><?= $st ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Order Items Table -->
                <div style="margin-bottom: 1.5rem;">
                  <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Items in this order</h4>
                  <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($orderItems as $item): ?>
                      <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px dashed var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                          <img src="<?= e($item['image_url'] ?: 'images/placeholder.png') ?>" alt="<?= e($item['product_name']) ?>" style="width: 48px; height: 58px; object-fit: cover; border-radius: 4px;">
                          <div>
                            <strong style="font-size: 0.95rem;"><?= e($item['product_name']) ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.85rem;">
                              Size: <?= e($item['size']) ?> • Qty: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?>
                            </div>
                          </div>
                        </div>
                        <div style="font-weight: 600; font-size: 0.95rem;">
                          <?= formatPrice($item['total']) ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Delivery & Payment Summary Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; background: var(--bg-alt, #f8f9fa); padding: 1.25rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                  <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.4rem;">📍 Delivery Details</h5>
                    <div><strong><?= e($order['first_name'] . ' ' . $order['last_name']) ?></strong></div>
                    <div><?= e($order['address']) ?>, <?= e($order['city']) ?></div>
                    <div>District: <?= e($order['district']) ?> <?= e($order['postal_code']) ?></div>
                    <div>Phone: <?= e($order['phone']) ?></div>
                  </div>
                  <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.4rem;">💳 Payment & Total</h5>
                    <div>Payment Method: <strong><?= e($order['payment_method']) ?></strong></div>
                    <div>Payment Status: <strong><?= e($order['payment_status']) ?></strong></div>
                    <div>Subtotal: <?= formatPrice($order['subtotal']) ?></div>
                    <div>Shipping: <?= (float)$order['shipping'] === 0.0 ? 'Free' : formatPrice($order['shipping']) ?></div>
                    <div style="margin-top: 0.3rem; font-size: 1.05rem; font-weight: 800; color: var(--accent-color);">Total: <?= formatPrice($order['total_amount']) ?></div>
                  </div>
                </div>

                <!-- Deposit slip upload if Bank Transfer and no slip yet -->
                <?php if ($order['payment_method'] === 'BANK_TRANSFER'): ?>
                  <div style="border: 1px solid #FFE082; background: #FFFDE7; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: #F57F17;">🏦 Bank Deposit Slip:</h5>
                    <?php if (!empty($order['payment_proof_url'])): ?>
                      <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <span style="color: #2E7D32; font-weight: 600;">✓ Slip Attached</span>
                        <a href="<?= e($order['payment_proof_url']) ?>" target="_blank" class="btn btn-secondary" style="padding: 4px 12px; font-size: 0.85rem;">View Uploaded Slip</a>
                      </div>
                    <?php else: ?>
                      <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.75rem;">You chose Bank Transfer. Please upload your deposit slip image below so we can process your order promptly.</p>
                      <form action="your-orders.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="action" value="upload_slip">
                        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
                        <input type="file" name="deposit_slip" accept="image/*,.pdf" required class="form-control" style="width: auto; padding: 6px;">
                        <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">Upload Slip</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <!-- WhatsApp Quick Support for this Order -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center; flex-wrap: wrap;">
                  <a href="https://wa.me/<?= e(getSetting('whatsapp_number', '94704300342')) ?>?text=<?= urlencode("Hello Anjiana Store, I would like to inquire about my order: {$order['order_number']}") ?>" 
                     target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 8px 16px; font-size: 0.9rem;">
                    💬 Inquire on WhatsApp
                  </a>
                </div>

              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>
      <?php endif; ?>

    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
