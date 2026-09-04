<?php
/**
 * Wishlist AJAX Handler
 * Anjiana Clothing Store
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = getDB();

if ($action === 'get_items') {
    $rawIds = $_GET['ids'] ?? '';
    if (empty($rawIds)) {
        echo json_encode(['success' => true, 'products' => []]);
        exit;
    }

    $idArray = array_filter(array_map('intval', explode(',', $rawIds)));
    if (empty($idArray)) {
        echo json_encode(['success' => true, 'products' => []]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($idArray), '?'));
    $stmt = $db->prepare("SELECT id, name, category, price, discount, image_url, stock FROM products WHERE id IN ($placeholders) AND status = 'active'");
    $stmt->execute($idArray);
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'products' => $products]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
