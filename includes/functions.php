<?php
/**
 * Helper Functions & Core Utilities
 * Anjiana Clothing Store
 */

require_once __DIR__ . '/../config/db.php';

/**
 * HTML Sanitization Helper
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format Currency Price
 */
function formatPrice($price) {
    return CURRENCY_SYMBOL . number_format((float)$price, 2);
}

/**
 * Calculate Discounted Price
 */
function getDiscountedPrice($price, $discount) {
    $price = (float)$price;
    $discount = (float)$discount;
    if ($discount > 0) {
        return $price * (1 - ($discount / 100));
    }
    return $price;
}

/**
 * Generate URL Slug
 */
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Generate Unique Order Number
 */
function generateOrderNumber() {
    return 'ANJ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)) . '-' . date('y');
}

/* ==========================================================
 * CART FUNCTIONS (SESSION-BASED)
 * ========================================================== */

/**
 * Initialize / Get Cart Array
 */
function getCart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Add Product to Cart
 */
function addToCart($productId, $name, $price, $imageUrl, $size = 'M', $color = 'N/A', $quantity = 1, $discount = 0) {
    getCart();
    $productId = (int)$productId;
    $quantity = max(1, (int)$quantity);
    $finalPrice = getDiscountedPrice($price, $discount);
    
    // Unique key for item variation
    $itemKey = $productId . '_' . md5($size . '_' . $color);

    if (isset($_SESSION['cart'][$itemKey])) {
        $_SESSION['cart'][$itemKey]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$itemKey] = [
            'id'        => $productId,
            'name'      => $name,
            'price'     => $finalPrice,
            'original_price' => (float)$price,
            'discount'  => (float)$discount,
            'imageUrl'  => $imageUrl,
            'size'      => $size,
            'color'     => $color,
            'quantity'  => $quantity
        ];
    }
    return true;
}

/**
 * Update Cart Item Quantity
 */
function updateCartItem($itemKey, $quantity) {
    getCart();
    $quantity = (int)$quantity;
    if (isset($_SESSION['cart'][$itemKey])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$itemKey]);
        } else {
            $_SESSION['cart'][$itemKey]['quantity'] = $quantity;
        }
        return true;
    }
    return false;
}

/**
 * Remove Single Item from Cart
 */
function removeFromCart($itemKey) {
    getCart();
    if (isset($_SESSION['cart'][$itemKey])) {
        unset($_SESSION['cart'][$itemKey]);
        return true;
    }
    return false;
}

/**
 * Clear All Items in Cart
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Total Quantity of Items in Cart
 */
function getCartCount() {
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Cart Subtotal Amount
 */
function getCartSubtotal() {
    $cart = getCart();
    $subtotal = 0.0;
    foreach ($cart as $item) {
        $subtotal += ((float)$item['price'] * (int)$item['quantity']);
    }
    return $subtotal;
}

/**
 * Calculate Shipping Fee
 */
function getCartShipping($subtotal) {
    if ($subtotal <= 0) return 0.0;
    return $subtotal >= FREE_SHIPPING_MIN ? 0.0 : DEFAULT_SHIPPING;
}

/**
 * Grand Total Amount
 */
function getCartTotal() {
    $subtotal = getCartSubtotal();
    if ($subtotal <= 0) return 0.0;
    return $subtotal + getCartShipping($subtotal);
}

/* ==========================================================
 * FLASH NOTIFICATIONS
 * ========================================================== */

function setFlash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function hasFlash($type) {
    return isset($_SESSION['flash'][$type]);
}

/* ==========================================================
 * CSRF PROTECTION
 * ========================================================== */

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* ==========================================================
 * ADMIN AUTHENTICATION GUARDS
 * ========================================================== */

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function currentAdmin() {
    if (!isAdminLoggedIn()) return null;
    return [
        'id'       => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'email'    => $_SESSION['admin_email'] ?? '',
        'name'     => $_SESSION['admin_name'] ?? 'Administrator'
    ];
}

/* ==========================================================
 * IMAGE UPLOAD HANDLER
 * ========================================================== */

/**
 * Safely upload image and return public relative path
 */
function handleImageUpload($fileInput, $targetDirectory, $relativePrefix = 'uploads/') {
    if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    $fileName = $fileInput['name'];
    $fileSize = $fileInput['size'];
    $fileTmp  = $fileInput['tmp_name'];

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception("Invalid image format. Allowed: JPG, JPEG, PNG, WEBP, GIF");
    }

    if ($fileSize > $maxFileSize) {
        throw new Exception("File too large. Maximum size is 5MB.");
    }

    // Verify it is a valid image
    $imageInfo = @getimagesize($fileTmp);
    if ($imageInfo === false) {
        throw new Exception("The uploaded file is not a valid image.");
    }

    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    $newFileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = rtrim($targetDirectory, '/') . '/' . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        return rtrim($relativePrefix, '/') . '/' . $newFileName;
    }

    throw new Exception("Failed to move uploaded file.");
}

/* ==========================================================
 * SETTINGS HELPER
 * ========================================================== */

function getSetting($key, $default = '') {
    global $pdo, $db_connected;
    if (!$db_connected || !$pdo) return $default;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
