<?php
/**
 * Configuration & Environment Resolver
 * Sage Anjiana Admin Panel - Subdomain & Standalone Support
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session security for subdomain compatibility
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Global Definitions
define('ADMIN_ROOT', __DIR__);

// Auto-detect Parent Repository Directory
$parentDir = dirname(__DIR__);
$parentConfigFile = $parentDir . '/config/db.php';

// Database Credentials (Update with Hostinger MySQL details when deploying)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'u123456789_clothing');     // Replace with Hostinger MySQL DB Name
if (!defined('DB_USER')) define('DB_USER', 'u123456789_admin');        // Replace with Hostinger MySQL DB User
if (!defined('DB_PASS')) define('DB_PASS', 'YourStrongPassword123#');  // Replace with Hostinger MySQL DB Pass
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Establish PDO Database Connection
$pdo = null;
$db_connected = false;
$db_error = null;

// 1. If parent config exists and is accessible, load it
if (file_exists($parentConfigFile)) {
    require_once $parentConfigFile;
} else {
    // 2. Direct MySQL Connection (Hostinger Subdomain isolated folder)
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $db_connected = true;
    } catch (PDOException $e) {
        // 3. Fallback SQLite for local dev
        try {
            $sqlitePath = ADMIN_ROOT . '/local_store.db';
            if (file_exists($parentDir . '/config/local_store.db')) {
                $sqlitePath = $parentDir . '/config/local_store.db';
            }
            $isNew = !file_exists($sqlitePath);
            $pdo = new PDO("sqlite:" . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db_connected = true;
        } catch (Exception $sqle) {
            $db_error = $e->getMessage();
        }
    }
}

// Upload Directories
$uploadDir = file_exists($parentDir . '/uploads') ? ($parentDir . '/uploads') : (ADMIN_ROOT . '/uploads');
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
    @mkdir($uploadDir . '/products', 0755, true);
    @mkdir($uploadDir . '/slips', 0755, true);
    @mkdir($uploadDir . '/banners', 0755, true);
}

define('ADMIN_UPLOAD_DIR', $uploadDir);
define('ADMIN_PRODUCT_UPLOAD_DIR', $uploadDir . '/products');
define('ADMIN_SLIP_UPLOAD_DIR', $uploadDir . '/slips');
define('ADMIN_BANNER_UPLOAD_DIR', $uploadDir . '/banners');

/**
 * Get PDO Database Connection
 * @return PDO
 */
if (!function_exists('getDB')) {
    function getDB() {
        global $pdo, $db_connected, $db_error;
        if (!$db_connected || !$pdo) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . ($db_error ?? 'Not connected')]));
        }
        return $pdo;
    }
}

/**
 * Escape HTML output
 */
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Ensure request method is initialized
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

/**
 * Output JSON Response
 */
function jsonResponse($data, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

/**
 * Admin / Staff Authentication Check
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
}

function requireAdminAuth($minRole = 'Staff') {
    if (!isAdminLoggedIn()) {
        if (isAjaxRequest()) {
            jsonResponse(['error' => 'Unauthorized. Please login again.', 'redirect' => 'index.php'], 401);
        } else {
            header("Location: index.php");
            exit;
        }
    }

    $user = currentAdminUser();
    $role = $user['role'] ?? 'Staff';

    // Role Hierarchy: Admin > Manager > Staff
    $rolesOrder = ['Staff' => 1, 'Manager' => 2, 'Admin' => 3];
    $userLevel = $rolesOrder[$role] ?? 1;
    $requiredLevel = $rolesOrder[$minRole] ?? 1;

    if ($userLevel < $requiredLevel) {
        if (isAjaxRequest()) {
            jsonResponse(['error' => 'Access Denied: You do not have permissions for this action.'], 403);
        } else {
            die("Access Denied: Insufficient permissions.");
        }
    }
}

function currentAdminUser() {
    if (!isAdminLoggedIn()) return null;
    return $_SESSION['admin_user'];
}

function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
           (strpos($_SERVER['REQUEST_URI'], '/api/') !== false);
}

/**
 * Settings Helper
 */
if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            return $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

function saveSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value
    ");
    try {
        $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        // Fallback for MySQL ON DUPLICATE KEY UPDATE
        $myStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $myStmt->execute([$key, $value]);
    }
}

/**
 * Price Formatter
 */
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        $currency = getSetting('currency_symbol', 'Rs.');
        return $currency . ' ' . number_format((float)$price, 2);
    }
}

/**
 * Log Staff Login History
 */
function logStaffLogin($email) {
    $db = getDB();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    try {
        $stmt = $db->prepare("INSERT INTO staff_logs (staff_email, user_agent, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$email, substr($userAgent, 0, 250), $ip]);

        // Update last_login in admins table
        $updateStmt = $db->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE email = ?");
        $updateStmt->execute([$email]);
    } catch (Exception $e) {
        // Ignore log errors
    }
}

/**
 * Multi-Image File Upload Handler
 */
function uploadAdminImage($file, $targetSubfolder = 'products') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed or was empty.");
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp  = $file['tmp_name'];

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception("Invalid image format. Allowed: JPG, PNG, WEBP, GIF");
    }

    if ($fileSize > $maxFileSize) {
        throw new Exception("File exceeds maximum allowed size of 10MB.");
    }

    $destinationDir = ADMIN_UPLOAD_DIR . '/' . trim($targetSubfolder, '/');
    if (!is_dir($destinationDir)) {
        @mkdir($destinationDir, 0755, true);
    }

    $newFileName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $targetPath = $destinationDir . '/' . $newFileName;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        return 'uploads/' . trim($targetSubfolder, '/') . '/' . $newFileName;
    }

    throw new Exception("Could not move uploaded image to storage destination.");
}
