<?php
/**
 * Cart AJAX Handler
 * Anjiana Clothing Store
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$db = getDB();

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
            echo json_encode([
                'success' => true,
                'message' => "{$quantity}x {$product['name']} added to cart!",
                'cart_count' => getCartCount(),
                'subtotal' => getCartSubtotal(),
                'formatted_subtotal' => formatPrice(getCartSubtotal())
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($action === 'get_count') {
    echo json_encode([
        'success' => true,
        'cart_count' => getCartCount(),
        'subtotal' => getCartSubtotal()
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
