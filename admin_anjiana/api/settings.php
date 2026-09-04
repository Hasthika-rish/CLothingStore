<?php
/**
 * Store Configuration & Settings API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET Settings & SMS Logs
if ($method === 'GET') {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $raw = $stmt->fetchAll();
    $settings = [];
    foreach ($raw as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }

    $smsStmt = $db->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 20");
    $smsLogs = $smsStmt->fetchAll();

    jsonResponse([
        'success'  => true,
        'settings' => $settings,
        'smsLogs'  => $smsLogs
    ]);
}

// 2. POST (Save Settings)
if ($method === 'POST') {
    requireAdminAuth('Admin');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $type = $input['type'] ?? 'store'; // 'store', 'shipping', 'sms'

    if ($type === 'store') {
        if (isset($input['store_name'])) saveSetting('site_name', trim($input['store_name']));
        if (isset($input['store_email'])) saveSetting('contact_email', trim($input['store_email']));
        if (isset($input['store_phone'])) saveSetting('contact_phone', trim($input['store_phone']));
        if (isset($input['store_address'])) saveSetting('store_address', trim($input['store_address']));
        if (isset($input['currency'])) saveSetting('currency_symbol', trim($input['currency']));
        if (isset($input['tax_rate'])) saveSetting('tax_rate', trim($input['tax_rate']));
        if (isset($input['fb'])) saveSetting('facebook_url', trim($input['fb']));
        if (isset($input['ig'])) saveSetting('instagram_url', trim($input['ig']));
        if (isset($input['tw'])) saveSetting('twitter_url', trim($input['tw']));
        if (isset($input['notif_new_order'])) saveSetting('notif_new_order', $input['notif_new_order'] ? '1' : '0');
        if (isset($input['notif_low_stock'])) saveSetting('notif_low_stock', $input['notif_low_stock'] ? '1' : '0');
        if (isset($input['notif_returns'])) saveSetting('notif_returns', $input['notif_returns'] ? '1' : '0');

        // Also update SMS configurations if passed in store form
        if (isset($input['sms_on_approval'])) saveSetting('sms_on_approval', $input['sms_on_approval'] ? '1' : '0');
        if (isset($input['sms_gateway'])) saveSetting('sms_gateway', trim($input['sms_gateway']));
        if (isset($input['sms_template'])) saveSetting('sms_template', trim($input['sms_template']));

        jsonResponse(['success' => true, 'message' => 'Store information & parameters saved successfully.']);
    }

    if ($type === 'shipping') {
        if (isset($input['standard_fee'])) saveSetting('shipping_fee', trim($input['standard_fee']));
        if (isset($input['express_fee'])) saveSetting('express_shipping_fee', trim($input['express_fee']));
        if (isset($input['free_threshold'])) saveSetting('free_shipping_threshold', trim($input['free_threshold']));
        if (isset($input['couriers'])) saveSetting('approved_couriers', trim($input['couriers']));

        jsonResponse(['success' => true, 'message' => 'Shipping & Courier rules saved successfully.']);
    }

    jsonResponse(['error' => 'Invalid settings form type.'], 400);
}

jsonResponse(['error' => 'Invalid request method'], 405);
