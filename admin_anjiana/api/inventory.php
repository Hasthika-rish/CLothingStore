<?php
/**
 * Inventory & Stock Audit API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Inventory Items & History
if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $filter = trim($_GET['filter'] ?? 'all'); // 'all', 'low', 'out'

    // Fetch all active products
    $prodStmt = $db->query("SELECT id, name, sku, stock, sizes, colors, size_color_stock, image_url FROM products WHERE status = 'active' ORDER BY id DESC");
    $allProducts = $prodStmt->fetchAll();

    $inventoryRows = [];

    foreach ($allProducts as $p) {
        $hasVariants = false;
        if (!empty($p['size_color_stock'])) {
            $matrix = json_decode($p['size_color_stock'], true);
            if (is_array($matrix) && !empty($matrix)) {
                $hasVariants = true;
                foreach ($matrix as $v) {
                    $vSize = $v['size'] ?? 'M';
                    $vColor = $v['color'] ?? 'N/A';
                    $vQty = (int)($v['qty'] ?? 0);
                    $vSku = $v['sku'] ?? ($p['sku'] ? $p['sku'] . '-' . $vSize . '-' . substr($vColor, 0, 3) : 'ANJ-' . $p['id']);

                    // Filter condition
                    if ($filter === 'low' && ($vQty > 5 || $vQty === 0)) continue;
                    if ($filter === 'out' && $vQty > 0) continue;

                    if (!empty($q)) {
                        $searchable = strtolower($p['name'] . ' ' . $vSize . ' ' . $vColor . ' ' . $vSku);
                        if (strpos($searchable, strtolower($q)) === false) continue;
                    }

                    $inventoryRows[] = [
                        'productId' => $p['id'],
                        'name'      => $p['name'],
                        'imageUrl'  => $p['image_url'],
                        'size'      => $vSize,
                        'color'     => $vColor,
                        'sku'       => $vSku,
                        'stock'     => $vQty,
                        'isVariant' => true
                    ];
                }
            }
        }

        if (!$hasVariants) {
            $stock = (int)$p['stock'];
            if ($filter === 'low' && ($stock > 5 || $stock === 0)) continue;
            if ($filter === 'out' && $stock > 0) continue;

            if (!empty($q)) {
                $searchable = strtolower($p['name'] . ' ' . ($p['sku'] ?? ''));
                if (strpos($searchable, strtolower($q)) === false) continue;
            }

            $inventoryRows[] = [
                'productId' => $p['id'],
                'name'      => $p['name'],
                'imageUrl'  => $p['image_url'],
                'size'      => 'All Sizes',
                'color'     => 'Standard',
                'sku'       => $p['sku'] ?: 'ANJ-' . $p['id'],
                'stock'     => $stock,
                'isVariant' => false
            ];
        }
    }

    // Fetch Stock Adjustment History (Latest 15)
    $historyStmt = $db->query("SELECT * FROM stock_history ORDER BY id DESC LIMIT 15");
    $stockHistory = $historyStmt->fetchAll();

    jsonResponse([
        'success'   => true,
        'inventory' => $inventoryRows,
        'history'   => $stockHistory
    ]);
}

// 2. RESTOCK Variant or Product
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $productId = (int)($input['productId'] ?? 0);
    $size = trim($input['size'] ?? '');
    $color = trim($input['color'] ?? '');
    $quantity = (int)($input['quantity'] ?? 0);

    if ($productId <= 0 || $quantity <= 0) {
        jsonResponse(['error' => 'Please provide a valid product ID and restock quantity greater than 0.'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if (!$prod) {
        jsonResponse(['error' => 'Product not found.'], 404);
    }

    $newStock = (int)$prod['stock'] + $quantity;
    $updatedMatrixJson = $prod['size_color_stock'];

    if (!empty($prod['size_color_stock'])) {
        $matrix = json_decode($prod['size_color_stock'], true);
        if (is_array($matrix)) {
            $total = 0;
            $found = false;
            foreach ($matrix as &$v) {
                if (($v['size'] ?? '') === $size && ($v['color'] ?? '') === $color) {
                    $v['qty'] = (int)($v['qty'] ?? 0) + $quantity;
                    $found = true;
                }
                $total += (int)($v['qty'] ?? 0);
            }
            if ($found) {
                $newStock = $total;
                $updatedMatrixJson = json_encode($matrix);
            }
        }
    }

    // Update product stock
    $upStmt = $db->prepare("UPDATE products SET stock = ?, size_color_stock = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $upStmt->execute([$newStock, $updatedMatrixJson, $productId]);

    // Insert Stock History Log
    $user = currentAdminUser();
    $adminName = $user['full_name'] ?? 'Admin';
    $logStmt = $db->prepare("
        INSERT INTO stock_history (product_id, product_name, variant_size, variant_color, change_qty, new_stock, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 'Restocked inventory', ?)
    ");
    $logStmt->execute([
        $productId,
        $prod['name'],
        $size ?: 'All',
        $color ?: 'Standard',
        $quantity,
        $newStock,
        $adminName
    ]);

    jsonResponse([
        'success'  => true,
        'message'  => "Added {$quantity} units to {$prod['name']} successfully.",
        'newStock' => $newStock
    ]);
}

jsonResponse(['error' => 'Invalid request method'], 405);
