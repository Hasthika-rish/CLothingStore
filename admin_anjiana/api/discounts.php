<?php
/**
 * Discounts, Coupons & Homepage Banners API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Coupons & Banners
if ($method === 'GET') {
    $couponsStmt = $db->query("SELECT * FROM coupons ORDER BY id DESC");
    $coupons = $couponsStmt->fetchAll();

    $bannersStmt = $db->query("SELECT * FROM banners ORDER BY id DESC");
    $banners = $bannersStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'coupons' => $coupons,
        'banners' => $banners
    ]);
}

// 2. CREATE Coupon or Banner
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $entity = $input['entity'] ?? 'coupon'; // 'coupon' or 'banner'

    if ($entity === 'coupon') {
        $code = strtoupper(trim($input['code'] ?? ''));
        $type = trim($input['type'] ?? 'Percentage');
        $value = (float)($input['value'] ?? 0);
        $minAmount = (float)($input['minAmount'] ?? 0);
        $expiry = trim($input['expiry'] ?? '');

        if (empty($code)) {
            jsonResponse(['error' => 'Coupon code is required.'], 400);
        }

        $ins = $db->prepare("INSERT INTO coupons (code, type, value, min_amount, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $ins->execute([$code, $type, $value, $minAmount, $expiry ?: null]);

        jsonResponse(['success' => true, 'message' => "Coupon {$code} created successfully."]);
    }

    if ($entity === 'banner') {
        $title = trim($input['title'] ?? '');
        $image = trim($input['image'] ?? '');
        $link = trim($input['link'] ?? '');
        $section = trim($input['section'] ?? 'Banner');

        if (empty($title) || empty($image)) {
            jsonResponse(['error' => 'Campaign title and banner image URL are required.'], 400);
        }

        $ins = $db->prepare("INSERT INTO banners (title, image_url, link_url, section_type, status) VALUES (?, ?, ?, ?, 'active')");
        $ins->execute([$title, $image, $link, $section]);

        jsonResponse(['success' => true, 'message' => 'Banner campaign uploaded successfully.']);
    }

    jsonResponse(['error' => 'Invalid entity specified.'], 400);
}

// 3. DELETE Coupon or Banner
if ($method === 'DELETE') {
    requireAdminAuth('Staff');

    $entity = $_GET['entity'] ?? 'coupon';
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['error' => 'Valid ID required.'], 400);
    }

    if ($entity === 'coupon') {
        $del = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $del->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Coupon removed.']);
    }

    if ($entity === 'banner') {
        $del = $db->prepare("DELETE FROM banners WHERE id = ?");
        $del->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Banner campaign removed.']);
    }

    jsonResponse(['error' => 'Invalid entity for deletion.'], 400);
}

jsonResponse(['error' => 'Invalid request method'], 405);
