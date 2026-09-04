<?php
/**
 * Product Deletion Controller
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();
$productId = (int)($_GET['id'] ?? 0);

if ($productId > 0) {
    try {
        $stmt = $db->prepare("SELECT image_url, name FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            // Delete product
            $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $delStmt->execute([$productId]);

            // If image is in uploads/products, remove file
            if (!empty($product['image_url']) && str_starts_with($product['image_url'], 'uploads/')) {
                $filePath = ROOT_PATH . '/' . $product['image_url'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            setFlash('success', "Product '{$product['name']}' deleted successfully.");
        }
    } catch (Exception $e) {
        setFlash('error', 'Error deleting product: ' . $e->getMessage());
    }
}

header("Location: products.php");
exit;
