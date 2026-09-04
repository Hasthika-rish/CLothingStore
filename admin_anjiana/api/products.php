<?php
/**
 * Products CRUD API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Product or Products List
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();

        if (!$prod) {
            jsonResponse(['error' => 'Product not found'], 404);
        }

        // Parse JSON variant matrix if exists
        $prod['variantMatrix'] = [];
        if (!empty($prod['size_color_stock'])) {
            $parsed = json_decode($prod['size_color_stock'], true);
            if (is_array($parsed)) {
                $prod['variantMatrix'] = $parsed;
            }
        }

        jsonResponse(['success' => true, 'product' => $prod]);
    }

    // List with Filters & Search
    $q = trim($_GET['q'] ?? '');
    $gender = trim($_GET['gender'] ?? '');
    $category = trim($_GET['category'] ?? '');

    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND (name LIKE ? OR sku LIKE ? OR brand LIKE ? OR description LIKE ?)";
        $term = "%{$q}%";
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    if (!empty($gender) && $gender !== 'All') {
        $sql .= " AND (gender = ? OR gender = 'Unisex')";
        $params[] = $gender;
    }

    if (!empty($category)) {
        $sql .= " AND (category LIKE ?)";
        $params[] = "%{$category}%";
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    jsonResponse(['success' => true, 'products' => $products]);
}

// 2. CREATE or UPDATE Product
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id = (int)($input['id'] ?? $input['editProductId'] ?? 0);
    $name = trim($input['name'] ?? '');
    $brand = trim($input['brand'] ?? 'Sage Anjiana');
    $category = trim($input['category'] ?? 'Women');
    $gender = trim($input['gender'] ?? 'Women');
    $material = trim($input['material'] ?? '');
    $price = (float)($input['price'] ?? 0);
    $discount = (float)($input['discount'] ?? 0);
    $sku = trim($input['sku'] ?? '');
    $status = trim($input['status'] ?? 'Available');
    if ($status === 'Available') $status = 'active';
    if ($status === 'Out of Stock') $status = 'inactive';

    $description = trim($input['description'] ?? '');
    $sizes = is_array($input['sizes'] ?? null) ? implode(',', $input['sizes']) : trim($input['sizes'] ?? 'S,M,L');
    $colors = is_array($input['colors'] ?? null) ? implode(',', $input['colors']) : trim($input['colors'] ?? 'White,Black');
    
    // Variant stock matrix JSON
    $variantMatrix = $input['variantMatrix'] ?? null;
    $variantMatrixJson = null;
    $totalStock = (int)($input['singleStock'] ?? $input['stock'] ?? 0);

    if (is_array($variantMatrix) && !empty($variantMatrix)) {
        $variantMatrixJson = json_encode($variantMatrix);
        $totalStock = 0;
        foreach ($variantMatrix as $v) {
            $totalStock += (int)($v['qty'] ?? 0);
        }
    }

    // Images
    $imageUrl = trim($input['imageUrl'] ?? $input['image_url'] ?? '');
    $additionalImages = is_array($input['additionalImages'] ?? null) ? json_encode($input['additionalImages']) : trim($input['additionalImages'] ?? '');

    if (empty($imageUrl)) {
        $imageUrl = 'images/placeholder.png';
    }

    if (empty($name)) {
        jsonResponse(['error' => 'Product name is required.'], 400);
    }
    if ($price <= 0) {
        jsonResponse(['error' => 'Base price must be greater than 0.'], 400);
    }

    // Generate Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    // Find category ID
    $catStmt = $db->prepare("SELECT id FROM categories WHERE name LIKE ? LIMIT 1");
    $catStmt->execute(["%{$category}%"]);
    $catRow = $catStmt->fetch();
    $categoryId = $catRow ? (int)$catRow['id'] : null;

    if ($id > 0) {
        // UPDATE
        $updateStmt = $db->prepare("
            UPDATE products SET
                name = ?, slug = ?, brand = ?, category_id = ?, category = ?, gender = ?,
                material = ?, price = ?, discount = ?, sku = ?, stock = ?, sizes = ?,
                colors = ?, size_color_stock = ?, description = ?, image_url = ?,
                additional_images = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([
            $name, $slug, $brand, $categoryId, $category, $gender,
            $material, $price, $discount, $sku, $totalStock, $sizes,
            $colors, $variantMatrixJson, $description, $imageUrl,
            $additionalImages, $status, $id
        ]);

        jsonResponse(['success' => true, 'message' => 'Product updated successfully.', 'id' => $id]);
    } else {
        // INSERT
        $insertStmt = $db->prepare("
            INSERT INTO products (
                name, slug, brand, category_id, category, gender, material, tag,
                price, discount, sku, stock, sizes, colors, size_color_stock,
                description, image_url, additional_images, is_featured, views_count, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'everyday',
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, 0, 0, ?
            )
        ");
        $insertStmt->execute([
            $name, $slug, $brand, $categoryId, $category, $gender, $material,
            $price, $discount, $sku, $totalStock, $sizes, $colors, $variantMatrixJson,
            $description, $imageUrl, $additionalImages, $status
        ]);
        $newId = $db->lastInsertId();

        jsonResponse(['success' => true, 'message' => 'Product created successfully.', 'id' => $newId]);
    }
}

// 3. DELETE Product
if ($method === 'DELETE') {
    requireAdminAuth('Staff');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
    }

    if ($id <= 0) {
        jsonResponse(['error' => 'Valid product ID required for deletion.'], 400);
    }

    $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $delStmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Product deleted successfully.']);
}

jsonResponse(['error' => 'Invalid request method'], 405);
