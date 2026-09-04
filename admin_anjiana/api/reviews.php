<?php
/**
 * Customer Reviews Moderation API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Reviews
if ($method === 'GET') {
    $stmt = $db->query("
        SELECT r.*, p.name as product_name, p.image_url as product_image
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.id
        ORDER BY r.id DESC
    ");
    $reviews = $stmt->fetchAll();

    jsonResponse(['success' => true, 'reviews' => $reviews]);
}

// 2. POST (Approve, Reject, Admin Reply)
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? '';
    $id = (int)($input['id'] ?? $input['reviewId'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['error' => 'Valid review ID required.'], 400);
    }

    if ($action === 'approve') {
        $up = $db->prepare("UPDATE reviews SET status = 'Approved' WHERE id = ?");
        $up->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Review approved and published to storefront.']);
    }

    if ($action === 'reject') {
        $up = $db->prepare("UPDATE reviews SET status = 'Rejected' WHERE id = ?");
        $up->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Review rejected and hidden from storefront.']);
    }

    if ($action === 'reply') {
        $replyText = trim($input['reply'] ?? $input['replyText'] ?? '');
        if (empty($replyText)) {
            jsonResponse(['error' => 'Reply text cannot be empty.'], 400);
        }

        $up = $db->prepare("UPDATE reviews SET admin_reply = ?, status = 'Approved' WHERE id = ?");
        $up->execute([$replyText, $id]);

        jsonResponse(['success' => true, 'message' => 'Admin response published successfully.']);
    }

    jsonResponse(['error' => 'Unknown review action.'], 400);
}

jsonResponse(['error' => 'Invalid request method'], 405);
