<?php
/**
 * Payments Ledger, Bank Deposit Verification & COD API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Payments Data
if ($method === 'GET') {
    // 1a. Summary KPIs
    $grossStmt = $db->query("SELECT SUM(total_amount) FROM orders WHERE payment_status IN ('Paid', 'Verified') AND order_status != 'Cancelled'");
    $grossPaid = (float)$grossStmt->fetchColumn();

    $refundStmt = $db->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'Refunded' OR order_status = 'Cancelled'");
    $refundsProcessed = (float)$refundStmt->fetchColumn();

    $netRevenue = max(0, $grossPaid - $refundsProcessed);

    // 1b. Transaction Ledger (All Orders)
    $ledgerStmt = $db->query("
        SELECT id, order_number, first_name, last_name, email, payment_method, total_amount, payment_status, order_status, payment_proof_url, created_at
        FROM orders
        ORDER BY id DESC
    ");
    $transactions = $ledgerStmt->fetchAll();

    // 1c. Bank Deposits to Verify (Fixed Deposit / Bank Transfer orders)
    $bankStmt = $db->query("
        SELECT id, order_number, first_name, last_name, email, phone, total_amount, payment_method, payment_status, payment_proof_url, created_at
        FROM orders
        WHERE (payment_method IN ('BANK_TRANSFER', 'FIXED_DEPOSIT', 'Bank Transfer', 'Fixed Deposit') OR payment_proof_url IS NOT NULL)
        ORDER BY id DESC
    ");
    $bankDeposits = $bankStmt->fetchAll();

    // 1d. COD Queue
    $codStmt = $db->query("
        SELECT id, order_number, first_name, last_name, phone, address, city, courier, total_amount, payment_status, order_status, created_at
        FROM orders
        WHERE payment_method LIKE '%COD%' OR payment_method LIKE '%Cash on Delivery%'
        ORDER BY id DESC
    ");
    $codList = $codStmt->fetchAll();

    jsonResponse([
        'success'           => true,
        'grossPaid'         => $grossPaid,
        'refundsProcessed'  => $refundsProcessed,
        'netRevenue'        => $netRevenue,
        'transactions'      => $transactions,
        'bankDeposits'      => $bankDeposits,
        'codList'           => $codList,
        'pendingDeposits'   => count(array_filter($bankDeposits, fn($d) => in_array($d['payment_status'], ['Pending', 'Verifying Slip', 'Awaiting Slip'])))
    ]);
}

// 2. POST Actions (Approve / Reject Bank Deposit, Refund, Mark COD Paid)
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? '';
    $orderId = (int)($input['orderId'] ?? $input['id'] ?? 0);

    if ($orderId <= 0) {
        jsonResponse(['error' => 'Valid order ID required.'], 400);
    }

    $ordStmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $ordStmt->execute([$orderId]);
    $order = $ordStmt->fetch();

    if (!$order) {
        jsonResponse(['error' => 'Order not found.'], 404);
    }

    // ACTION: APPROVE BANK DEPOSIT
    if ($action === 'approve_bank_deposit') {
        $update = $db->prepare("UPDATE orders SET payment_status = 'Verified', order_status = 'Verified', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$orderId]);

        // Trigger Automated SMS simulation
        $courier = $order['courier'] ?: 'FedEx Express';
        $trackingNum = $order['tracking_number'] ?: ('ANJ-' . strtoupper(substr(md5($orderId . time()), 0, 8)));
        
        $msgTemplate = getSetting('sms_template', "Dear {name}, your payment for Order #{orderId} has been approved! Courier: {courier}, Tracking: {trackingNumber}.");
        $smsBody = str_replace(
            ['{name}', '{orderId}', '{courier}', '{trackingNumber}', '{total}'],
            [$order['first_name'], $order['order_number'], $courier, $trackingNum, formatPrice($order['total_amount'])],
            $msgTemplate
        );

        $smsLog = $db->prepare("INSERT INTO sms_logs (order_id, customer_name, phone, message, gateway, status) VALUES (?, ?, ?, ?, ?, 'Delivered')");
        $smsLog->execute([
            $order['order_number'],
            $order['first_name'] . ' ' . $order['last_name'],
            $order['phone'],
            $smsBody,
            getSetting('sms_gateway', 'Anjiana Simulated Gateway')
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Bank deposit verified & approved successfully. Automated SMS dispatched.',
            'smsBody' => $smsBody
        ]);
    }

    // ACTION: REJECT BANK DEPOSIT
    if ($action === 'reject_bank_deposit') {
        $update = $db->prepare("UPDATE orders SET payment_status = 'Rejected', order_status = 'Rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$orderId]);

        jsonResponse(['success' => true, 'message' => 'Bank deposit rejected. Customer will be notified to re-upload proof.']);
    }

    // ACTION: REFUND TRANSACTION
    if ($action === 'refund') {
        $update = $db->prepare("UPDATE orders SET payment_status = 'Refunded', order_status = 'Cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$orderId]);

        jsonResponse(['success' => true, 'message' => 'Transaction marked as Refunded and order cancelled.']);
    }

    // ACTION: MARK COD AS PAID
    if ($action === 'mark_cod_paid') {
        $update = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Delivered', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$orderId]);

        jsonResponse(['success' => true, 'message' => 'COD order marked as Paid & Delivered.']);
    }

    jsonResponse(['error' => 'Unknown payment action specified.'], 400);
}

jsonResponse(['error' => 'Invalid request method'], 405);
