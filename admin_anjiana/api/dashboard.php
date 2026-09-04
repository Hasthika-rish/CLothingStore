<?php
/**
 * Dashboard KPIs & Chart Data API
 * Sage Anjiana Admin Panel
 */
require_once __DIR__ . '/../config.php';
requireAdminAuth('Staff');

$db = getDB();
$currency = getSetting('currency_symbol', 'Rs.');

// 1. Total Sales
$salesStmt = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'Cancelled' AND (payment_status = 'Paid' OR payment_status = 'Verified' OR order_status = 'Delivered')");
$totalSales = (float)$salesStmt->fetchColumn();

// 2. Orders Count
$ordersStmt = $db->query("SELECT COUNT(*) FROM orders");
$totalOrders = (int)$ordersStmt->fetchColumn();

// 3. Pending Orders
$pendingStmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending' OR payment_status LIKE '%Verifying%' OR payment_status = 'Pending'");
$pendingOrders = (int)$pendingStmt->fetchColumn();

// 4. Low Stock Count
$lowStockStmt = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'active'");
$lowStockCount = (int)$lowStockStmt->fetchColumn();

// 5. Total Customers (Unique Emails in orders)
$custStmt = $db->query("SELECT COUNT(DISTINCT email) FROM orders");
$totalCustomers = (int)$custStmt->fetchColumn();

// 6. Monthly Sales Chart (Last 6-12 Months)
$monthlySales = [];
$monthLabels = [];
$monthData = [];

// Initialize past 6 months
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $monthLabels[] = $monthName;
    $monthlySales[$monthKey] = 0.0;
}

try {
    $chartStmt = $db->query("
        SELECT strftime('%Y-%m', created_at) as month_key, SUM(total_amount) as total
        FROM orders 
        WHERE order_status != 'Cancelled'
        GROUP BY month_key
        ORDER BY month_key DESC
        LIMIT 12
    ");
    $rows = $chartStmt->fetchAll();
    foreach ($rows as $r) {
        if (isset($monthlySales[$r['month_key']])) {
            $monthlySales[$r['month_key']] = (float)$r['total'];
        }
    }
} catch (Exception $e) {
    // Fallback for MySQL date formatting
    try {
        $myChartStmt = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(total_amount) as total
            FROM orders 
            WHERE order_status != 'Cancelled'
            GROUP BY month_key
            ORDER BY month_key DESC
            LIMIT 12
        ");
        $rows = $myChartStmt->fetchAll();
        foreach ($rows as $r) {
            if (isset($monthlySales[$r['month_key']])) {
                $monthlySales[$r['month_key']] = (float)$r['total'];
            }
        }
    } catch (Exception $mye) {}
}

$monthData = array_values($monthlySales);

// 7. Best-Selling Products
$bestSellers = [];
try {
    $bsStmt = $db->query("
        SELECT oi.product_id, oi.product_name, oi.image_url, SUM(oi.quantity) as units_sold, SUM(oi.total) as total_revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status != 'Cancelled'
        GROUP BY oi.product_id, oi.product_name
        ORDER BY units_sold DESC
        LIMIT 5
    ");
    $bestSellers = $bsStmt->fetchAll();
} catch (Exception $e) {}

// Fallback if no order items exist yet, show top featured products as sample
if (empty($bestSellers)) {
    $featStmt = $db->query("SELECT id as product_id, name as product_name, image_url, 0 as units_sold, price as total_revenue FROM products WHERE status = 'active' LIMIT 4");
    $bestSellers = $featStmt->fetchAll();
}

// 8. Recent Orders
$recentOrdersStmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
$recentOrders = $recentOrdersStmt->fetchAll();

jsonResponse([
    'currency'        => $currency,
    'totalSales'      => $totalSales,
    'totalSalesFmt'   => $currency . ' ' . number_format($totalSales, 2),
    'totalOrders'     => $totalOrders,
    'pendingOrders'   => $pendingOrders,
    'totalCustomers'  => $totalCustomers,
    'lowStockCount'   => $lowStockCount,
    'chartLabels'     => $monthLabels,
    'chartData'       => $monthData,
    'bestSellers'     => $bestSellers,
    'recentOrders'    => $recentOrders
]);
