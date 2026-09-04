<?php
/**
 * Admin Order Management & Status Controller
 * Anjiana Clothing Store
 */
$admin_page_title = 'Order Management';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStatus = trim($_POST['order_status'] ?? '');
    $paymentStatus = trim($_POST['payment_status'] ?? '');

    if ($orderId > 0 && !empty($orderStatus)) {
        try {
            $updateStmt = $db->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $updateStmt->execute([$orderStatus, $paymentStatus, $orderId]);
            setFlash('success', "Order #{$orderId} status updated to '{$orderStatus}' & '{$paymentStatus}'");
        } catch (Exception $e) {
            setFlash('error', 'Failed to update order: ' . $e->getMessage());
        }
    }
    header("Location: orders.php" . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Filter and Search
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$viewOrderId = (int)($_GET['order_id'] ?? 0);

$where = [];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(order_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ordersStmt = $db->prepare("SELECT * FROM orders {$whereSQL} ORDER BY id DESC");
    $ordersStmt->execute($params);
    $orders = $ordersStmt->fetchAll();

    // Order counts by status
    $countsStmt = $db->query("
        SELECT order_status, COUNT(*) as cnt 
        FROM orders 
        GROUP BY order_status
    ");
    $statusCounts = $countsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $orders = [];
    $statusCounts = [];
}
?>

<!-- Status Filter Tabs -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
  <a href="orders.php" class="btn btn-secondary <?= empty($statusFilter) ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    All Orders (<?= array_sum($statusCounts) ?>)
  </a>
  <a href="orders.php?status=Pending" class="btn btn-secondary <?= $statusFilter === 'Pending' ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    Pending (<?= $statusCounts['Pending'] ?? 0 ?>)
  </a>
  <a href="orders.php?status=Processing" class="btn btn-secondary <?= $statusFilter === 'Processing' ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    Processing (<?= $statusCounts['Processing'] ?? 0 ?>)
  </a>
  <a href="orders.php?status=Shipped" class="btn btn-secondary <?= $statusFilter === 'Shipped' ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    Shipped (<?= $statusCounts['Shipped'] ?? 0 ?>)
  </a>
  <a href="orders.php?status=Delivered" class="btn btn-secondary <?= $statusFilter === 'Delivered' ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    Delivered (<?= $statusCounts['Delivered'] ?? 0 ?>)
  </a>
  <a href="orders.php?status=Cancelled" class="btn btn-secondary <?= $statusFilter === 'Cancelled' ? 'btn-primary' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 50px;">
    Cancelled (<?= $statusCounts['Cancelled'] ?? 0 ?>)
  </a>
</div>

<!-- Search Bar -->
<div style="margin-bottom: 2rem;">
  <form action="orders.php" method="GET" style="display: flex; gap: 0.5rem; max-width: 450px;">
    <?php if (!empty($statusFilter)): ?>
      <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
    <?php endif; ?>
    <input type="text" name="search" class="form-control" placeholder="Search order ID, name, phone, email..." value="<?= e($search) ?>" style="padding: 8px 14px;">
    <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">Search</button>
    <?php if (!empty($search)): ?>
      <a href="orders.php" class="btn btn-secondary" style="padding: 8px 12px;">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Orders Table -->
<div class="admin-table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Order #</th>
        <th>Customer</th>
        <th>Delivery Address</th>
        <th>Amount</th>
        <th>Payment</th>
        <th>Status Update</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $ord): 
          $status = $ord['order_status'];
          $badgeColor = '#FF9800';
          if ($status === 'Processing') $badgeColor = '#2196F3';
          if ($status === 'Shipped') $badgeColor = '#9C27B0';
          if ($status === 'Delivered') $badgeColor = '#4CAF50';
          if ($status === 'Cancelled') $badgeColor = '#F44336';
        ?>
          <tr id="order-row-<?= $ord['id'] ?>" style="<?= ($viewOrderId === (int)$ord['id']) ? 'background: rgba(159,93,68,0.06);' : '' ?>">
            <td>
              <strong style="font-family: monospace; font-size: 0.95rem;"><?= e($ord['order_number']) ?></strong>
              <div style="font-size: 0.78rem; color: var(--text-muted);"><?= date('M d, Y - h:i A', strtotime($ord['created_at'])) ?></div>
            </td>
            <td>
              <div style="font-weight: 600;"><?= e($ord['first_name'] . ' ' . $ord['last_name']) ?></div>
              <div style="font-size: 0.8rem; color: var(--text-muted);">📞 <?= e($ord['phone']) ?></div>
              <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($ord['email']) ?></div>
            </td>
            <td style="font-size: 0.85rem; max-width: 220px;">
              <div><?= e($ord['address']) ?></div>
              <div style="color: var(--text-muted);"><?= e($ord['city']) ?>, <?= e($ord['district']) ?></div>
            </td>
            <td>
              <strong style="color: var(--accent-color); font-size: 1rem;"><?= formatPrice($ord['total_amount']) ?></strong>
            </td>
            <td>
              <div style="font-size: 0.85rem; font-weight: 600;"><?= e($ord['payment_method']) ?></div>
              <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($ord['payment_status']) ?></div>
              <?php if (!empty($ord['payment_proof_url'])): ?>
                <a href="../<?= e($ord['payment_proof_url']) ?>" target="_blank" style="display: inline-block; margin-top: 4px; font-size: 0.78rem; color: #1976D2; text-decoration: underline; font-weight: 600;">
                  📄 View Slip
                </a>
              <?php endif; ?>
            </td>
            <td>
              <form action="orders.php" method="POST" style="display: flex; flex-direction: column; gap: 4px;">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                
                <select name="order_status" class="form-control" style="padding: 4px 8px; font-size: 0.82rem; font-weight: 600; color: <?= $badgeColor ?>; width: auto;" onchange="this.form.submit()">
                  <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Processing" <?= $status === 'Processing' ? 'selected' : '' ?>>Processing</option>
                  <option value="Shipped" <?= $status === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                  <option value="Delivered" <?= $status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                  <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <select name="payment_status" class="form-control" style="padding: 2px 6px; font-size: 0.75rem; width: auto;" onchange="this.form.submit()">
                  <option value="Pending" <?= $ord['payment_status'] === 'Pending' ? 'selected' : '' ?>>Payment: Pending</option>
                  <option value="Paid" <?= $ord['payment_status'] === 'Paid' ? 'selected' : '' ?>>Payment: Paid</option>
                  <option value="Verifying Slip" <?= $ord['payment_status'] === 'Verifying Slip' ? 'selected' : '' ?>>Payment: Verifying Slip</option>
                  <option value="Refunded" <?= $ord['payment_status'] === 'Refunded' ? 'selected' : '' ?>>Payment: Refunded</option>
                </select>
              </form>
            </td>
            <td>
              <button type="button" class="btn btn-secondary view-order-btn" data-id="<?= $ord['id'] ?>" style="padding: 6px 12px; font-size: 0.8rem;">
                View Items
              </button>
            </td>
          </tr>

          <!-- Collapsible Order Details Sub-row -->
          <tr id="details-subrow-<?= $ord['id'] ?>" style="display: <?= ($viewOrderId === (int)$ord['id']) ? 'table-row' : 'none' ?>; background: var(--bg-alt, #fafafa);">
            <td colspan="7" style="padding: 1.5rem 2rem;">
              <div style="background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 8px; padding: 1.25rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Order Line Items (<?= e($ord['order_number']) ?>)</h4>
                
                <?php
                $itemQuery = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itemQuery->execute([$ord['id']]);
                $items = $itemQuery->fetchAll();
                ?>
                <table style="width: 100%; font-size: 0.88rem; border-collapse: collapse;">
                  <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                      <th style="padding: 6px 0; text-align: left;">Item</th>
                      <th style="padding: 6px 0; text-align: center;">Size</th>
                      <th style="padding: 6px 0; text-align: center;">Qty</th>
                      <th style="padding: 6px 0; text-align: right;">Unit Price</th>
                      <th style="padding: 6px 0; text-align: right;">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $it): ?>
                      <tr style="border-bottom: 1px dashed var(--border-color);">
                        <td style="padding: 8px 0; display: flex; align-items: center; gap: 0.75rem;">
                          <img src="../<?= e($it['image_url'] ?: 'images/placeholder.png') ?>" alt="" style="width: 32px; height: 40px; object-fit: cover; border-radius: 3px;">
                          <strong><?= e($it['product_name']) ?></strong>
                        </td>
                        <td style="padding: 8px 0; text-align: center;"><?= e($it['size']) ?></td>
                        <td style="padding: 8px 0; text-align: center;"><?= (int)$it['quantity'] ?></td>
                        <td style="padding: 8px 0; text-align: right;"><?= formatPrice($it['price']) ?></td>
                        <td style="padding: 8px 0; text-align: right; font-weight: 600;"><?= formatPrice($it['total']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <?php if (!empty($ord['notes'])): ?>
                  <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted); background: var(--bg-alt, #f9f9f9); padding: 8px 12px; border-radius: 4px;">
                    <strong>Customer Notes:</strong> <?= e($ord['notes']) ?>
                  </div>
                <?php endif; ?>

                <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 1rem; font-size: 0.88rem;">
                  <div>Subtotal: <strong><?= formatPrice($ord['subtotal']) ?></strong></div>
                  <div>Shipping: <strong><?= (float)$ord['shipping'] === 0.0 ? 'Free' : formatPrice($ord['shipping']) ?></strong></div>
                  <div>Grand Total: <strong style="color: var(--accent-color);"><?= formatPrice($ord['total_amount']) ?></strong></div>
                </div>
              </div>
            </td>
          </tr>

        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 4rem;">
            No orders found matching this filter.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.view-order-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const subrow = document.getElementById('details-subrow-' + id);
      if (subrow) {
        if (subrow.style.display === 'none') {
          subrow.style.display = 'table-row';
          btn.textContent = 'Hide Items';
        } else {
          subrow.style.display = 'none';
          btn.textContent = 'View Items';
        }
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
