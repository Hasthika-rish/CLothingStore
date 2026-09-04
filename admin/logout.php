<?php
/**
 * Admin Logout Handler
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/../config/db.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);

header("Location: login.php");
exit;
