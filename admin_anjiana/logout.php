<?php
/**
 * Admin Logout Handler
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['admin_user']);
    session_destroy();
}

header("Location: index.php");
exit;
