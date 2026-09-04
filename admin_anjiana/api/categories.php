<?php
/**
 * Categories CRUD API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Categories
if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $gender = trim($_GET['gender'] ?? '');
    $hierarchy = trim($_GET['hierarchy'] ?? ''); // 'toplevel' or 'subcategory'

    $sql = "SELECT * FROM categories WHERE 1=1";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND (name LIKE ?)";
        $params[] = "%{$q}%";
    }

    if (!empty($gender) && $gender !== 'All') {
        $sql .= " AND (gender = ? OR gender = 'Unisex')";
        $params[] = $gender;
    }

    if ($hierarchy === 'toplevel') {
        $sql .= " AND (parent_category IS NULL OR parent_category = '')";
    } elseif ($hierarchy === 'subcategory') {
        $sql .= " AND (parent_category IS NOT NULL AND parent_category != '')";
    }

    $sql .= " ORDER BY display_order ASC, id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();

    jsonResponse(['success' => true, 'categories' => $categories]);
}

// 2. CREATE or UPDATE Category
if ($method === 'POST') {
    requireAdminAuth('Staff');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id = (int)($input['id'] ?? $input['editCategoryId'] ?? 0);
    $name = trim($input['name'] ?? '');
    $gender = trim($input['gender'] ?? 'Women');
    $parent = trim($input['parent'] ?? '');
    $image = trim($input['image'] ?? '');

    if (empty($name)) {
        jsonResponse(['error' => 'Category name is required.'], 400);
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    if ($id > 0) {
        $updateStmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, gender = ?, parent_category = ?, image = ? WHERE id = ?");
        $updateStmt->execute([$name, $slug, $gender, $parent ?: null, $image ?: null, $id]);
        jsonResponse(['success' => true, 'message' => 'Category updated successfully.']);
    } else {
        $insertStmt = $db->prepare("INSERT INTO categories (name, slug, gender, parent_category, image) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$name, $slug, $gender, $parent ?: null, $image ?: null]);
        jsonResponse(['success' => true, 'message' => 'Category created successfully.', 'id' => $db->lastInsertId()]);
    }
}

// 3. DELETE Category
if ($method === 'DELETE') {
    requireAdminAuth('Staff');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
    }

    if ($id <= 0) {
        jsonResponse(['error' => 'Valid category ID required for deletion.'], 400);
    }

    $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $delStmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Category deleted successfully.']);
}

jsonResponse(['error' => 'Invalid request method'], 405);
