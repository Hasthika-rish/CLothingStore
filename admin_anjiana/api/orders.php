<?php
/**
 * Orders Management & Detail API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Single Order or Filtered List
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // 1a. Single Order Detail with Items
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['error' => 'Order not found.'], 404);
        }

        $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$id]);
        $order['items'] = $itemsStmt->fetchAll();

        jsonResponse(['success' => true, 'order' => $order]);
    }

    // 1b. Orders List with Tabs & Search
    $filter = trim($_GET['filter'] ?? 'ALL'); // ALL, PENDING, VERIFIED, REJECTED, COD, FIXED_DEPOSIT
    $q = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND (order_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $term = "%{$q}%";
        $params = array_merge($params, [$term, $term, $term, $term, $term]);
    }

    switch ($filter) {
        case 'PENDING':
            $sql .= " AND (order_status = 'Pending' OR payment_status LIKE '%Pending%' OR payment_status LIKE '%Verifying%')";
            break;
        case 'VERIFIED':
            $sql .= " AND (order_status = 'Verified' OR payment_status = 'Verified' OR payment_status = 'Paid')";
            break;
        case 'REJECTED':
            $sql .= " AND (order_status = 'Rejected' OR payment_status = 'Rejected' OR order_status = 'Cancelled')";
            break;
        case 'COD':
            $sql .= " AND (payment_method LIKE '%COD%' OR payment_method LIKE '%Cash on Delivery%')";
            break;
        case 'FIXED_DEPOSIT':
            $sql .= " AND (payment_method LIKE '%BANK%' OR payment_method LIKE '%Deposit%' OR payment_method LIKE '%Transfer%')";
            break;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // KPI Counts
    $tot = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pen = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending' OR payment_status LIKE '%Verifying%' OR payment_status = 'Pending'")->fetchColumn();
    $ver = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Verified' OR payment_status = 'Verified' OR payment_status = 'Paid'")->fetchColumn();
    $rej = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Rejected' OR payment_status = 'Rejected' OR order_status = 'Cancelled'")->fetchColumn();

    jsonResponse([
        'success'          => true,
        'orders'           => $orders,
        'kpiTotalOrders'   => $tot,
        'kpiPendingOrders' => $pen,
        'kpiVerifiedOrders'=> $ver,
        'kpiRejectedOrders'=> $rej
    ]);
}

// 2. POST (Update Status, Dispatch Tracking, Delete)
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'update_status';
    $orderId = (int)($input['orderId'] ?? $input['id'] ?? 0);

    if ($orderId <= 0) {
        jsonResponse(['error' => 'Valid order ID required.'], 400);
    }

    // UPDATE STATUS
    if ($action === 'update_status') {
        $status = trim($input['status'] ?? 'Pending');
        $up = $db->prepare("UPDATE orders SET order_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $up->execute([$status, $orderId]);

        jsonResponse(['success' => true, 'message' => "Order #{$orderId} status updated to {$status}."]);
    }

    // UPDATE COURIER & TRACKING
    if ($action === 'update_tracking') {
        $courier = trim($input['courier'] ?? 'Local Logistics');
        $tracking = trim($input['trackingNumber'] ?? '');
        $up = $db->prepare("UPDATE orders SET courier = ?, tracking_number = ?, order_status = 'Shipped', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $up->execute([$courier, $tracking, $orderId]);

        jsonResponse(['success' => true, 'message' => "Courier dispatched with tracking #{$tracking}."]);
    }

    // DELETE ORDER
    if ($action === 'delete') {
        requireAdminAuth('Staff');
        $del = $db->prepare("DELETE FROM orders WHERE id = ?");
        $del->execute([$orderId]);
        jsonResponse(['success' => true, 'message' => 'Order record deleted.']);
    }

    jsonResponse(['error' => 'Unknown order action.'], 400);
}

jsonResponse(['error' => 'Invalid request method'], 405);
