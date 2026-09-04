<?php
/**
 * Staff & Role Permissions Management API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Admin');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Staff List & Login Logs
if ($method === 'GET') {
    $staffStmt = $db->query("SELECT id, username, email, full_name, role, status, last_login, created_at FROM admins ORDER BY id ASC");
    $staffList = $staffStmt->fetchAll();

    $logsStmt = $db->query("SELECT * FROM staff_logs ORDER BY id DESC LIMIT 20");
    $loginLogs = $logsStmt->fetchAll();

    jsonResponse([
        'success'   => true,
        'staffList' => $staffList,
        'logs'      => $loginLogs
    ]);
}

// 2. POST (Add / Update Staff)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $role = trim($input['role'] ?? 'Staff');
    $status = trim($input['status'] ?? 'Approved');

    if (empty($name) || empty($email)) {
        jsonResponse(['error' => 'Name and email are required.'], 400);
    }

    if ($id > 0) {
        // Update existing staff
        $up = $db->prepare("UPDATE admins SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
        $up->execute([$name, $email, $role, $status, $id]);

        jsonResponse(['success' => true, 'message' => "Staff member {$name} updated successfully."]);
    } else {
        // Insert new staff member
        $username = explode('@', $email)[0];
        $defaultHash = '$2y$10$a8iW9loqRPtNZJpvvAFJr.pLRaOQfAx3NARC.VKmnFY70CzLfcWF6'; // 'admin123'

        $ins = $db->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $ins->execute([$username, $email, $defaultHash, $name, $role, $status]);
            jsonResponse(['success' => true, 'message' => "Staff member {$name} added successfully (Default Password: admin123)."]);
        } catch (Exception $e) {
            jsonResponse(['error' => 'A user with this email or username already exists.'], 409);
        }
    }
}

// 3. DELETE Staff Member
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['error' => 'Valid staff ID required.'], 400);
    }

    // Protect root superadmin from deletion
    $check = $db->prepare("SELECT email FROM admins WHERE id = ?");
    $check->execute([$id]);
    $u = $check->fetch();

    if ($u && (strpos($u['email'], 'admin@') !== false || $u['email'] === 'admin@anjiana.com' || $u['email'] === 'admin@sageanjiana.com')) {
        jsonResponse(['error' => 'Root Superadmin account cannot be deleted.'], 403);
    }

    $del = $db->prepare("DELETE FROM admins WHERE id = ?");
    $del->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Staff account removed.']);
}

jsonResponse(['error' => 'Invalid request method'], 405);
