<?php
/**
 * File & Multi-Image Upload API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$folder = trim($_GET['folder'] ?? $_POST['folder'] ?? 'products');
$allowedFolders = ['products', 'slips', 'banners', 'categories'];

if (!in_array($folder, $allowedFolders)) {
    $folder = 'products';
}

$uploadedUrls = [];
$errors = [];

// Check if multiple files uploaded under "images[]" or single "image"
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $fileCount = count($_FILES['images']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $singleFile = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            try {
                $url = uploadAdminImage($singleFile, $folder);
                $uploadedUrls[] = $url;
            } catch (Exception $e) {
                $errors[] = $singleFile['name'] . ': ' . $e->getMessage();
            }
        }
    }
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    try {
        $url = uploadAdminImage($_FILES['image'], $folder);
        $uploadedUrls[] = $url;
    } catch (Exception $e) {
        $errors[] = $_FILES['image']['name'] . ': ' . $e->getMessage();
    }
}

if (empty($uploadedUrls)) {
    jsonResponse([
        'error' => !empty($errors) ? implode(', ', $errors) : 'No files were uploaded or format was invalid.'
    ], 400);
}

jsonResponse([
    'success' => true,
    'urls'    => $uploadedUrls,
    'primary' => $uploadedUrls[0],
    'errors'  => $errors
]);
