<?php
/**
 * Main Administrator & Staff Dashboard Console
 * Sage Anjiana Management System - PHP & MySQL Edition
 */
require_once __DIR__ . '/config.php';
requireAdminAuth('Staff');

$user = currentAdminUser();
$role = $user['role'] ?? 'Staff';
$site_name = getSetting('site_name', 'Sage Anjiana');
$currency = getSetting('currency_symbol', 'Rs.');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($site_name) ?> Admin Panel</title>
  <link rel="icon" type="image/png" href="images/logo.png">
  <link rel="stylesheet" href="css/styles.css">
  <!-- Chart.js CDN for Analytics & Dashboard Reports -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .pos-layout {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 2rem;
      height: calc(100vh - 190px);
    }

    .pos-products {
      background: var(--surface-color);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      border: 1px solid var(--border-color);
      overflow-y: auto;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 1rem;
    }

    .pos-product-card {
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      padding: 0.5rem;
      cursor: pointer;
      text-align: center;
      transition: all var(--transition-fast);
      background: var(--surface-color);
    }

    .pos-product-card:hover {
      border-color: var(--accent-color);
      transform: translateY(-2px);
    }

    .pos-product-img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: var(--radius-sm);
      margin-bottom: 0.5rem;
    }

    .pos-product-title {
      font-size: 0.85rem;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 0.25rem;
      color: var(--primary-color);
    }

    .pos-product-price {
      font-weight: 700;
      color: var(--primary-color);
    }

    .pos-register {
      background: var(--surface-color);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      border: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
    }

    .register-items {
      flex-grow: 1;
      overflow-y: auto;
      margin-bottom: 1rem;
    }

    .register-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px dashed var(--border-color);
    }

    .register-summary {
      border-top: 2px solid var(--border-color);
      padding-top: 1rem;
    }

    @media (max-width: 1023px) {
      .pos-layout {
        grid-template-columns: 1fr;
        height: auto;
        gap: 1.5rem;
      }

      .pos-products {
        height: 500px !important;
      }

      .register-items {
        max-height: 300px;
        overflow-y: auto;
      }
    }

    .pos-chip {
      padding: 6px 12px;
      border: 1px solid var(--border-color);
      background: var(--bg-color, #222);
      color: var(--primary-color, #fff);
      border-radius: 4px;
      font-weight: 500;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.2s ease;
    }

    .pos-chip:hover {
      border-color: var(--accent-color);
    }

    .pos-chip.active {
      background: var(--accent-color);
      color: #1a1a1a;
      border-color: var(--accent-color);
    }
    .payments-sub-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 10px;
    }
    .payments-sub-view {
      display: none;
    }
    .status-verifying {
      background: #FFF3E0;
      color: #E65100;
    }
    .status-failed {
      background: #FFEBEE;
      color: #C62828;
    }
    .status-verified {
      background: #E8F5E9;
      color: #2E7D32;
      border: 1px solid #A5D6A7;
    }
    .status-pending {
      background: #FFF8E1;
      color: #F57F17;
      border: 1px solid #FFE082;
    }
    .status-rejected {
      background: #FFEBEE;
      color: #C62828;
      border: 1px solid #FFCDD2;
    }
    .user-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      background: rgba(255,255,255,0.06);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      font-size: 0.85rem;
      color: var(--primary-color);
    }
  </style>
</head>

<body class="admin-layout" data-role="<?= htmlspecialchars($role) ?>" data-user="<?= htmlspecialchars($user['full_name'] ?? 'Admin') ?>">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-logo" style="display: flex; align-items: center; gap: 12px; margin-bottom: 3rem; text-transform: none;">
      <img src="images/logo.png" alt="<?= htmlspecialchars($site_name) ?> Logo" style="width: 40px; height: 40px; border-radius: 50%; background: #fff; padding: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
      <span style="font-weight: 800; font-size: 1.25rem; color: #fff; letter-spacing: 0.5px;"><?= htmlspecialchars($site_name) ?></span>
    </div>
    <nav class="admin-nav" id="sidebarNav">
      <a href="#dashboard" class="nav-item active" data-tab="dashboard">
        <svg viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="9" rx="1"></rect>
          <rect x="14" y="3" width="7" height="5" rx="1"></rect>
          <rect x="14" y="12" width="7" height="9" rx="1"></rect>
          <rect x="3" y="16" width="7" height="5" rx="1"></rect>
        </svg>
        Dashboard
      </a>
      <a href="#products" class="nav-item" data-tab="products">
        <svg viewBox="0 0 24 24">
          <path
            d="M20.37 8.91l-8-1.72a2 2 0 00-1.07 0l-8 1.72a2 2 0 00-1.54 2l1 10a2 2 0 002 1.8h9.3a2 2 0 002-1.8l1-10a2 2 0 00-1.54-2z">
          </path>
          <path d="M12 2v5M8 5h8"></path>
        </svg>
        Products
      </a>
      <a href="#add-product" class="nav-item" data-tab="add-product">
        <svg viewBox="0 0 24 24">
          <path d="M12 5v14M5 12h14"></path>
        </svg>
        Add Product
      </a>
      <a href="#categories" class="nav-item <?= $role === 'Staff' ? 'role-restricted' : '' ?>" data-tab="categories" style="<?= $role === 'Staff' ? 'display: none;' : '' ?>">
        <svg viewBox="0 0 24 24">
          <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01"></path>
        </svg>
        Categories
      </a>
      <a href="#inventory" class="nav-item" data-tab="inventory">
        <svg viewBox="0 0 24 24">
          <path d="M22 12h-6l-3 9L9 3l-3 9H2"></path>
        </svg>
        Inventory
      </a>
      <a href="#payments" class="nav-item <?= $role === 'Staff' ? 'role-restricted' : '' ?>" data-tab="payments" style="<?= $role === 'Staff' ? 'display: none;' : '' ?>">
        <svg viewBox="0 0 24 24">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
          <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        Payments
      </a>
      <a href="#orders" class="nav-item" data-tab="orders">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        Orders
      </a>
      <a href="#discounts" class="nav-item <?= $role === 'Staff' ? 'role-restricted' : '' ?>" data-tab="discounts" style="<?= $role === 'Staff' ? 'display: none;' : '' ?>">
        <svg viewBox="0 0 24 24">
          <path d="M20 12V4H4v8M2 12h20M12 2v20M12 12l4-4M12 12l-4-4"></path>
        </svg>
        Discounts & Banners
      </a>
      <a href="#reviews" class="nav-item" data-tab="reviews">
        <svg viewBox="0 0 24 24">
          <polygon
            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
          </polygon>
        </svg>
        Reviews
      </a>
      <a href="#reports" class="nav-item" data-tab="reports">
        <svg viewBox="0 0 24 24">
          <line x1="18" y1="20" x2="18" y2="10"></line>
          <line x1="12" y1="20" x2="12" y2="4"></line>
          <line x1="6" y1="20" x2="6" y2="14"></line>
        </svg>
        Reports & Analytics
      </a>
      <a href="#staff" class="nav-item <?= $role !== 'Admin' ? 'role-restricted' : '' ?>" data-tab="staff" style="<?= $role !== 'Admin' ? 'display: none;' : '' ?>">
        <svg viewBox="0 0 24 24">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
        </svg>
        Staff Management
      </a>
      <a href="#settings" class="nav-item <?= $role !== 'Admin' ? 'role-restricted' : '' ?>" data-tab="settings" style="<?= $role !== 'Admin' ? 'display: none;' : '' ?>">
        <svg viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="3"></circle>
          <path
            d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z">
          </path>
        </svg>
        Store Settings
      </a>
    </nav>
    <div style="margin-top: auto; display: flex; flex-direction: column; gap: 1rem; padding: 1rem 0 0 0;">
      <div style="font-size: 0.8rem; color: rgba(255,255,255,0.4);">
        Logged in as: <strong style="color: #fff;"><?= htmlspecialchars($user['full_name'] ?? 'Admin') ?></strong> (<?= htmlspecialchars($role) ?>)
      </div>
      <a href="logout.php" id="logoutBtn"
        style="color: rgba(255,100,100,0.8); font-size: 0.9rem; text-decoration: none; display: flex; align-items: center; gap: 6px;">
        <span>🚪</span> Logout
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="admin-content">

    <!-- Top Header -->
    <header class="admin-header">
      <div>
        <h1 id="viewTitle" style="font-size: 1.8rem; margin-bottom: 0.25rem;">Dashboard</h1>
        <p id="viewDesc" class="text-muted">Overview of your store's performance metrics.</p>
      </div>
      <div style="display: flex; gap: 1rem; align-items: center;">

        <!-- Live Storefront Quick Link -->
        <a href="../index.php" target="_blank" class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
          <span>🌐</span> View Storefront
        </a>

        <!-- Live Clock -->
        <div id="liveClock" class="premium-clock"></div>

        <!-- Notification Bell -->
        <div class="bell-container" id="notifBell">
          <button class="btn btn-secondary"
            style="padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;">
            <svg class="header-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </button>
          <span class="nav-badge" id="notifBadge" style="display: none;">0</span>

          <!-- Notifications Dropdown -->
          <div class="bell-dropdown" id="notifDropdown">
            <div class="bell-dropdown-header">
              <span>Notifications</span>
              <button id="clearNotifBtn"
                style="background: none; border: none; color: var(--accent-color); cursor: pointer; font-size: 0.8rem;">Clear
                All</button>
            </div>
            <div id="notifListContainer">
              <div class="bell-dropdown-empty">No new notifications</div>
            </div>
          </div>
        </div>

        <button class="theme-toggle btn btn-secondary"
          style="padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;">
          <span class="icon-sun" style="display: flex; align-items: center;">
            <svg class="header-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
          </span>
          <span class="icon-moon" style="display: none; align-items: center;">
            <svg class="header-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
          </span>
        </button>

      </div>
    </header>

    <!-- Sub-views/Tabs container -->
    <div id="tabContainer">

      <!-- 1. DASHBOARD VIEW -->
      <section id="dashboard" class="tab-pane active">

        <!-- Time-Based Greeting Banner -->
        <div id="greetingBanner" style="
          display: flex;
          align-items: center;
          gap: 1rem;
          background: linear-gradient(135deg, rgba(212,175,55,0.12) 0%, rgba(212,175,55,0.05) 100%);
          border: 1px solid rgba(212,175,55,0.25);
          border-radius: 14px;
          padding: 1rem 1.5rem;
          margin-bottom: 1.5rem;
          animation: greetFadeIn 0.6s ease;
        ">
          <span id="greetingEmoji" style="font-size: 2rem; line-height: 1;">👋</span>
          <div>
            <div id="greetingText" style="font-size: 1.1rem; font-weight: 700; color: var(--primary-color); letter-spacing: 0.01em;">Welcome, <?= htmlspecialchars($user['full_name'] ?? 'Admin') ?>!</div>
            <div id="greetingSubtext" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Here is the real-time summary of your clothing storefront.</div>
          </div>
        </div>
        <style>
          @keyframes greetFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
          }
        </style>

        <div class="metrics-grid">
          <div class="metric-card primary">
            <div class="metric-header">
              <span>Total Sales</span>
              <span>💰</span>
            </div>
            <div class="metric-value" id="kpiSales"><?= htmlspecialchars($currency) ?> 0.00</div>
            <div class="metric-footer">
              <span class="trend-up">↑ Live</span> completed sales
            </div>
          </div>
          <div class="metric-card success">
            <div class="metric-header">
              <span>Orders Placed</span>
              <span>📦</span>
            </div>
            <div class="metric-value" id="kpiOrders">0</div>
            <div class="metric-footer" id="kpiPendingText">
              0 pending fulfillment
            </div>
          </div>
          <div class="metric-card info">
            <div class="metric-header">
              <span>Customers</span>
              <span>👥</span>
            </div>
            <div class="metric-value" id="kpiCustomers">0</div>
            <div class="metric-footer">
              <span class="trend-up">Active</span> storefront shoppers
            </div>
          </div>
          <div class="metric-card warning">
            <div class="metric-header">
              <span>Low Stock Alerts</span>
              <span>⚠️</span>
            </div>
            <div class="metric-value" id="kpiLowStock">0</div>
            <div class="metric-footer" style="color: var(--error-color);" id="kpiLowStockFooter">
              No immediate critical items
            </div>
          </div>
        </div>

        <div class="dashboard-grid-2">
          <!-- Monthly Chart -->
          <div class="admin-panel-card" style="min-height: 380px;">
            <h3 style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
              <span>Monthly Sales Performance</span>
              <span class="text-muted" style="font-size: 0.85rem; font-weight: normal;">Live database updates</span>
            </h3>
            <div style="position: relative; height: 280px; width: 100%;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>

          <!-- Top Products -->
          <div class="admin-panel-card" style="overflow-y: auto;">
            <h3 style="margin-bottom: 1.25rem;">Best-Selling Products</h3>
            <div id="bestSellersContainer">
              <p class="text-muted" style="text-align: center; padding: 2rem 0;">Loading sales data...</p>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="admin-panel-card" style="margin-top: 1.5rem;">
          <h3 style="margin-bottom: 1rem;">Recent Orders</h3>
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Payment</th>
                  <th>Shipping</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="recentOrdersTableBody">
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading recent orders...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 2. PRODUCTS VIEW -->
      <section id="products" class="tab-pane">
        <div class="admin-panel-card">
          <div
            style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 0.5rem; flex-grow: 1; max-width: 500px;">
              <input type="text" id="productSearchInput" class="form-control"
                placeholder="Search product name, SKU or brand..." style="margin-bottom: 0;">
              <select id="productFilterGender" class="form-control" style="width: 140px; margin-bottom: 0;">
                <option value="">All Genders</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
                <option value="Kids">Kids</option>
                <option value="Unisex">Unisex</option>
              </select>
            </div>
            <a href="#add-product" class="btn btn-accent" id="addNewProductBtn">+ Add Clothing Item</a>
          </div>

          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Brand</th>
                  <th>Category</th>
                  <th>Base Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="productsTableBody">
                <tr>
                  <td colspan="8" style="text-align: center; color: var(--text-muted);">Loading product database...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 3. ADD/EDIT PRODUCT FORM VIEW -->
      <section id="add-product" class="tab-pane">
        <div class="admin-panel-card" style="max-width: 900px; margin: 0 auto;">
          <h2 id="addProductTitle" style="font-size: 1.5rem; margin-bottom: 1.5rem;">Add New Clothing Item</h2>

          <form id="productForm">
            <input type="hidden" id="editProductId" value="">

            <div class="form-row-2">
              <div class="form-group">
                <label for="prodName">Product Name *</label>
                <input type="text" id="prodName" class="form-control" required placeholder="e.g. Classic Linen Shirt">
              </div>
              <div class="form-group">
                <label for="prodBrand">Brand *</label>
                 <input type="text" id="prodBrand" class="form-control" required placeholder="e.g. Sage Anjiana Signature">
              </div>
            </div>

            <div class="form-row-3">
              <div class="form-group">
                <label for="prodCategory">Category *</label>
                <select id="prodCategory" class="form-control">
                  <!-- Categories populated dynamically -->
                </select>
              </div>
              <div class="form-group">
                <label for="prodGender">Gender Target *</label>
                <select id="prodGender" class="form-control">
                  <option value="Women">Women</option>
                  <option value="Men">Men</option>
                  <option value="Kids">Kids</option>
                  <option value="Unisex">Unisex</option>
                </select>
              </div>
              <div class="form-group">
                <label for="prodMaterial">Material / Fabric</label>
                <input type="text" id="prodMaterial" class="form-control" placeholder="e.g. 100% Cotton">
              </div>
            </div>

            <div class="form-row-3">
              <div class="form-group">
                <label for="prodPrice">Base Price (<?= htmlspecialchars($currency) ?>) *</label>
                <input type="number" id="prodPrice" class="form-control" required placeholder="0.00" step="0.01">
              </div>
              <div class="form-group">
                <label for="prodDiscount">Discount % (Optional)</label>
                <input type="number" id="prodDiscount" class="form-control" placeholder="0" step="1" min="0" max="100">
              </div>
              <div class="form-group">
                <label for="prodSku">SKU / Barcode prefix</label>
                <input type="text" id="prodSku" class="form-control" placeholder="e.g. ANJ-SH-01">
              </div>
            </div>

            <div class="form-group">
              <label style="font-weight: 500;">Product Status</label>
              <div class="status-radio-group">
                <label class="status-radio-label">
                  <input type="radio" name="prodStatus" value="Available" checked>
                  <span class="status-radio-box">Available</span>
                </label>
                <label class="status-radio-label">
                  <input type="radio" name="prodStatus" value="Out of Stock">
                  <span class="status-radio-box">Out of Stock</span>
                </label>
              </div>
            </div>

            <!-- Variants and stock levels management -->
            <div class="form-group"
              style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; background: rgba(0,0,0,0.02); margin: 2rem 0;">
              <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Product Variants & Stock Levels</h3>
              <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">Select sizes and colors to build a variant matrix, or specify single stock.</p>

              <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                  <label style="font-weight: 500; font-size: 0.9rem;">Sizes</label>
                  <div id="sizeSelectGroup">
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="XS">
                      <span class="size-chip-box">XS</span>
                    </label>
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="S">
                      <span class="size-chip-box">S</span>
                    </label>
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="M">
                      <span class="size-chip-box">M</span>
                    </label>
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="L">
                      <span class="size-chip-box">L</span>
                    </label>
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="XL">
                      <span class="size-chip-box">XL</span>
                    </label>
                    <label class="size-chip-label">
                      <input type="checkbox" name="vSizes" value="XXL">
                      <span class="size-chip-box">XXL</span>
                    </label>
                  </div>
                </div>
                <div>
                  <label style="font-weight: 500; font-size: 0.9rem;">Colors</label>
                  <div id="colorSelectGroup"
                    style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 0.5rem; margin-bottom: 0.75rem;">
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="White">
                      <span class="color-chip-box"><span class="color-dot"
                          style="background: #fff; border: 1px solid #ccc;"></span> White</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Black">
                      <span class="color-chip-box"><span class="color-dot" style="background: #000;"></span>
                        Black</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Navy">
                      <span class="color-chip-box"><span class="color-dot" style="background: #000080;"></span>
                        Navy</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Grey">
                      <span class="color-chip-box"><span class="color-dot" style="background: #808080;"></span>
                        Grey</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Beige">
                      <span class="color-chip-box"><span class="color-dot" style="background: #F5F5DC;"></span>
                        Beige</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Red">
                      <span class="color-chip-box"><span class="color-dot" style="background: #FF0000;"></span>
                        Red</span>
                    </label>
                    <label class="color-chip-label">
                      <input type="checkbox" name="vColors" value="Olive">
                      <span class="color-chip-box"><span class="color-dot" style="background: #808000;"></span>
                        Olive</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Variants Table Matrix -->
              <div id="variantMatrixContainer"
                style="display: none; border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1rem;">
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                  <label style="font-size: 0.85rem; font-weight: 500;">Set Same Quantity for All:</label>
                  <input type="number" id="bulkStockInput" class="form-control"
                    style="width: 100px; padding: 6px 10px; margin-bottom: 0;" placeholder="0">
                  <button type="button" id="bulkStockApplyBtn" class="btn btn-secondary"
                    style="padding: 6px 12px; font-size: 0.85rem; margin: 0;">Apply to All</button>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                  <table style="width: 100%;">
                    <thead>
                      <tr>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Stock Qty</th>
                        <th>Custom SKU / Barcode</th>
                      </tr>
                    </thead>
                    <tbody id="variantMatrixBody">
                      <!-- populated on sizes/colors changes -->
                    </tbody>
                  </table>
                </div>
              </div>

              <div id="singleStockContainer" style="display: block;">
                <div class="form-group" style="max-width: 250px; margin-bottom: 0;">
                  <label for="prodSingleStock">Single Stock Quantity</label>
                  <input type="number" id="prodSingleStock" class="form-control" value="10" min="0">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="prodDesc">Product Description *</label>
              <textarea id="prodDesc" class="form-control" rows="4" required
                placeholder="Enter fabrics care, detailing, styling instructions..."></textarea>
            </div>

            <!-- Multiple Product Images Upload -->
            <div class="form-group">
              <label>Product Images <span class="text-muted">(First image is primary display)</span></label>
              <div class="custom-file-upload" id="multiImageDropzone">
                <input type="file" id="prodImagesInput" accept="image/*" multiple style="display: none;">
                <div class="upload-placeholder">
                  <span class="upload-icon">📷</span>
                  <span class="upload-text">Click or drag images to upload</span>
                  <span class="upload-info">Supports JPEG, PNG, WEBP. Select multiple files.</span>
                </div>
              </div>
              <div class="image-previews-container" id="multiImagePreviews">
                <!-- thumbnails loaded here -->
              </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
              <button type="submit" id="saveProductBtn" class="btn btn-primary">Save Product Listing</button>
              <a href="#products" class="btn btn-secondary">Cancel</a>
            </div>

          </form>
        </div>
      </section>

      <!-- 4. CATEGORIES VIEW -->
      <section id="categories" class="tab-pane">
        <div class="dashboard-grid-2">
          <!-- Left: Categories list -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Product Categories</h3>

            <!-- Category Filters -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
              <input type="text" id="catSearchInput" class="form-control" placeholder="Search category name..." style="flex: 2; min-width: 150px; margin-bottom: 0;">
              
              <select id="catHierarchyFilter" class="form-control" style="flex: 1; min-width: 130px; margin-bottom: 0;">
                <option value="">All Types</option>
                <option value="toplevel">Top-Level</option>
                <option value="subcategory">Subcategories</option>
              </select>

              <select id="catGenderFilter" class="form-control" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                <option value="">All Genders</option>
                <option value="Women">Women</option>
                <option value="Men">Men</option>
                <option value="Kids">Kids</option>
                <option value="Unisex">Unisex</option>
              </select>
            </div>

            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Category Name</th>
                    <th>Gender Target</th>
                    <th>Parent / Subcategory</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="categoriesTableBody">
                  <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted);">Loading categories...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Right: Add Category Form -->
          <div class="admin-panel-card">
            <h3 id="categoryFormTitle" style="margin-bottom: 1.5rem;">Add Category</h3>
            <form id="categoryForm">
              <input type="hidden" id="editCategoryId" value="">

              <div class="form-group">
                <label for="catName">Category Name *</label>
                <input type="text" id="catName" class="form-control" required placeholder="e.g. Jackets">
              </div>

              <div class="form-group">
                <label for="catGender">Gender Association *</label>
                <select id="catGender" class="form-control">
                  <option value="Women">Women</option>
                  <option value="Men">Men</option>
                  <option value="Kids">Kids</option>
                  <option value="Unisex">Unisex</option>
                </select>
              </div>

              <div class="form-group">
                <label for="catParent">Parent Category <span class="text-muted">(Optional for
                    Subcategories)</span></label>
                <select id="catParent" class="form-control">
                  <option value="">None (Top-Level Category)</option>
                  <option value="Men">Men</option>
                  <option value="Women">Women</option>
                  <option value="Kids">Kids</option>
                </select>
              </div>

              <div class="form-group">
                <label for="catImage">Category Thumbnail Image URL</label>
                <input type="text" id="catImage" class="form-control"
                  placeholder="images/women_new.png or https://...">
              </div>

              <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" id="saveCategoryBtn" class="btn btn-primary" style="width: 100%;">Save
                  Category</button>
                <button type="button" id="resetCategoryFormBtn" class="btn btn-secondary"
                  style="display: none;">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- 5. INVENTORY VIEW -->
      <section id="inventory" class="tab-pane">
        <div
          style="display: flex; gap: 1rem; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <div style="display: flex; gap: 0.5rem; align-items: center; flex-grow: 1; max-width: 600px;">
            <input type="text" id="inventorySearch" class="form-control" placeholder="Search variant stock levels..."
              style="margin-bottom: 0;">
            <select id="inventoryStockFilter" class="form-control" style="width: 180px; margin-bottom: 0;">
              <option value="all">All Inventory</option>
              <option value="low">Low Stock Alerts (≤ 5)</option>
              <option value="out">Out of Stock Only</option>
            </select>
          </div>
        </div>

        <div class="dashboard-grid-2">
          <!-- Stock by Variant Matrix -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Stock Levels by Variant</h3>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>SKU / Barcode</th>
                    <th>Available Stock</th>
                    <th>Restock</th>
                  </tr>
                </thead>
                <tbody id="inventoryTableBody">
                  <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading stock catalog...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Stock adjustments History Logs -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Stock Adjustment History</h3>
            <div id="stockHistoryLogs" style="max-height: 500px; overflow-y: auto;">
              <div class="log-timeline" id="stockLogsTimeline">
                <!-- populated dynamically -->
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 6. PAYMENTS VIEW -->
      <section id="payments" class="tab-pane">
        <div class="metrics-grid">
          <div class="metric-card success">
            <div class="metric-header">
              <span>Gross Sales (Paid)</span>
              <span>💵</span>
            </div>
            <div class="metric-value" id="paymentsPaidSum"><?= htmlspecialchars($currency) ?> 0.00</div>
          </div>
          <div class="metric-card warning">
            <div class="metric-header">
              <span>Refunds Processed</span>
              <span>↩️</span>
            </div>
            <div class="metric-value" id="paymentsRefundedSum"><?= htmlspecialchars($currency) ?> 0.00</div>
          </div>
          <div class="metric-card info">
            <div class="metric-header">
              <span>Net Revenue</span>
              <span>📈</span>
            </div>
            <div class="metric-value" id="paymentsNetSum"><?= htmlspecialchars($currency) ?> 0.00</div>
          </div>
        </div>

        <!-- Payments Navigation Sub-tabs -->
        <div class="payments-sub-tabs" style="display: flex; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
          <button type="button" class="pos-chip active" id="btnPaymentsLedger">Transaction History Ledger</button>
          <button type="button" class="pos-chip" id="btnBankDepositVerification">
            Bank Deposit Verification
            <span class="nav-badge" id="bankDepositBadge" style="display: none; margin-left: 6px; position: relative; top: -1px; background: var(--error-color); color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem;">0</span>
          </button>
          <button type="button" class="pos-chip" id="btnCODManagement">
            COD Management
            <span class="nav-badge" id="codBadge" style="display: none; margin-left: 6px; position: relative; top: -1px; background: var(--accent-color); color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem;">0</span>
          </button>
        </div>

        <!-- 6a. TRANSACTION LEDGER SUB-VIEW -->
        <div id="subViewLedger" class="payments-sub-view" style="display: block;">
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Transaction History Ledger</h3>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Order Reference</th>
                    <th>Customer Name</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="paymentsTableBody">
                  <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading ledger...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 6b. BANK DEPOSIT VERIFICATION SUB-VIEW -->
        <div id="subViewBankDeposit" class="payments-sub-view" style="display: none;">
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Bank Deposit Verification</h3>
            <p class="text-muted" style="margin-bottom: 1.5rem; font-size: 0.9rem;">Review uploaded bank deposit slips and approve or reject payments.</p>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Order Reference</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Total Amount</th>
                    <th>Date</th>
                    <th>Uploaded Slip</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="bankDepositTableBody">
                  <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading bank deposits...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 6c. COD MANAGEMENT SUB-VIEW -->
        <div id="subViewCOD" class="payments-sub-view" style="display: none;">
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">COD Management Flow</h3>
            <p class="text-muted" style="margin-bottom: 1.5rem; font-size: 0.9rem;">Track Cash on Delivery orders. Mark orders as paid once courier services remit collected cash.</p>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Order Reference</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Courier</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Quick Action</th>
                  </tr>
                </thead>
                <tbody id="codTableBody">
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted);">Loading COD list...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- 7. ORDERS VIEW -->
      <section id="orders" class="tab-pane">
        <!-- KPI Metrics Grid -->
        <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
          <div class="admin-panel-card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Total Storefront Orders</div>
            <div id="kpiTotalOrders" style="font-size: 1.75rem; font-weight: 700; color: var(--primary-color);">0</div>
          </div>
          <div class="admin-panel-card" style="padding: 1.25rem; border-left: 4px solid #F57F17;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Pending Approval</div>
            <div id="kpiPendingOrders" style="font-size: 1.75rem; font-weight: 700; color: #F57F17;">0</div>
          </div>
          <div class="admin-panel-card" style="padding: 1.25rem; border-left: 4px solid #2E7D32;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Verified Orders</div>
            <div id="kpiVerifiedOrders" style="font-size: 1.75rem; font-weight: 700; color: #2E7D32;">0</div>
          </div>
          <div class="admin-panel-card" style="padding: 1.25rem; border-left: 4px solid #C62828;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Rejected Orders</div>
            <div id="kpiRejectedOrders" style="font-size: 1.75rem; font-weight: 700; color: #C62828;">0</div>
          </div>
        </div>

        <!-- Orders Table Card -->
        <div class="admin-panel-card">
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
              <h3 style="margin-bottom: 0.25rem;">Website Customer Orders</h3>
              <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Review, verify, approve, or reject user orders placed via Cash on Delivery or Fixed Deposit.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <input type="text" id="ordersSearchInput" class="form-control" placeholder="Search Order ID, name, email or phone..." style="width: 280px; font-size: 0.85rem;">
            </div>
          </div>

          <!-- Quick Filter Tabs -->
          <div class="payments-sub-tabs" id="ordersFilterTabs" style="margin-bottom: 1.5rem;">
            <button class="pos-chip active" data-filter="ALL">All Orders</button>
            <button class="pos-chip" data-filter="PENDING">Pending Verification</button>
            <button class="pos-chip" data-filter="VERIFIED">Verified</button>
            <button class="pos-chip" data-filter="REJECTED">Rejected</button>
            <button class="pos-chip" data-filter="COD">Cash on Delivery</button>
            <button class="pos-chip" data-filter="FIXED_DEPOSIT">Fixed / Bank Deposit</button>
          </div>

          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Order Reference</th>
                  <th>Date & Time</th>
                  <th>Customer Info</th>
                  <th>Payment Method</th>
                  <th>Total Amount</th>
                  <th>Deposit Slip</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="ordersTableBody">
                <tr>
                  <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Loading website orders...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Collapsible Shipping Fees Configuration Setup -->
        <div class="admin-panel-card" style="margin-top: 1.5rem;">
          <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">Shipping Fees & Courier Configuration</h4>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Set standard shipping fees, express shipping fees, and free delivery thresholds for storefront checkout.</p>
          <form id="shippingSettingsForm">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
              <div class="form-group">
                <label>Standard Shipping Fee (<?= htmlspecialchars($currency) ?>)</label>
                <input type="number" id="shipStandardFee" class="form-control" value="10.00" step="0.01">
              </div>
              <div class="form-group">
                <label>Express Shipping Fee (<?= htmlspecialchars($currency) ?>)</label>
                <input type="number" id="shipExpressFee" class="form-control" value="25.00" step="0.01">
              </div>
              <div class="form-group">
                <label>Free Shipping Threshold (<?= htmlspecialchars($currency) ?>)</label>
                <input type="number" id="shipFreeThreshold" class="form-control" value="100.00" step="0.01">
              </div>
              <div class="form-group">
                <label>Approved Couriers</label>
                <input type="text" id="shipCouriers" class="form-control" value="FedEx, DHL Express, USPS, UPS, Local Logistics">
              </div>
            </div>
            <button type="submit" class="btn btn-secondary" style="margin-top: 1rem;">Save Shipping Rules</button>
          </form>
        </div>
      </section>

      <!-- 8. DISCOUNTS & BANNER VIEW -->
      <section id="discounts" class="tab-pane">
        <div class="dashboard-grid-2">

          <!-- Coupons Panel -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1.25rem;">Coupon Codes Creator</h3>
            <form id="couponForm"
              style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
              <div class="form-row-2">
                <div class="form-group">
                  <label for="cCode">Coupon Code *</label>
                  <input type="text" id="cCode" class="form-control" required placeholder="e.g. SUMMER25">
                </div>
                <div class="form-group">
                  <label for="cType">Discount Type *</label>
                  <select id="cType" class="form-control">
                    <option value="Percentage">Percentage Discount (%)</option>
                    <option value="Fixed">Fixed Amount (<?= htmlspecialchars($currency) ?>)</option>
                    <option value="Buy2Get1">Buy 2 Get 1 Free (B2G1)</option>
                  </select>
                </div>
              </div>

              <div class="form-row-3">
                <div class="form-group">
                  <label for="cValue">Value (<?= htmlspecialchars($currency) ?> or %)</label>
                  <input type="number" id="cValue" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                  <label for="cMinAmount">Min Purchase Amount</label>
                  <input type="number" id="cMinAmount" class="form-control" value="0">
                </div>
                <div class="form-group">
                  <label for="cExpiry">Expiry Date</label>
                  <input type="date" id="cExpiry" class="form-control">
                </div>
              </div>

              <button type="submit" class="btn btn-accent" style="width: 100%;">Create Coupon</button>
            </form>

            <h4>Active Coupons</h4>
            <div style="overflow-x: auto; margin-top: 0.75rem;">
              <table style="font-size: 0.85rem;">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="couponsTableBody">
                  <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted);">Loading coupons...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Banners & Featured Collections Panel -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Homepage Banners & Seasonal Sales</h3>
            <form id="bannerForm"
              style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
              <div class="form-group">
                <label for="bannerTitle">Campaign Title *</label>
                <input type="text" id="bannerTitle" class="form-control" required
                  placeholder="e.g. Autumn Warmth Collection">
              </div>
              <div class="form-group">
                <label for="bannerImage">Banner Image URL *</label>
                <input type="text" id="bannerImage" class="form-control" required
                  placeholder="images/banner1.png or https://...">
              </div>
              <div class="form-row-2">
                <div class="form-group">
                  <label for="bannerLink">Call to Action Link</label>
                  <input type="text" id="bannerLink" class="form-control"
                    placeholder="products.php?category=women">
                </div>
                <div class="form-group">
                  <label for="bannerType">Banner Section Area</label>
                  <select id="bannerType" class="form-control">
                    <option value="Banner">Top Hero Banner</option>
                    <option value="Seasonal">Seasonal Collection Grid</option>
                    <option value="Featured">Featured Collection Spotlight</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" style="width: 100%;">Upload / Add Banner Campaign</button>
            </form>

            <h4>Active Banner Slots</h4>
            <div id="bannersContainer" style="max-height: 250px; overflow-y: auto; margin-top: 1rem;">
              <!-- populated lists -->
            </div>
          </div>

        </div>
      </section>

      <!-- 9. REVIEWS VIEW -->
      <section id="reviews" class="tab-pane">
        <div class="admin-panel-card">
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Customer</th>
                  <th>Rating</th>
                  <th>Comment Review</th>
                  <th>Status</th>
                  <th>Admin Reply</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="reviewsTableBody">
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--text-muted);">Loading customer reviews...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 10. REPORTS & ANALYTICS VIEW -->
      <section id="reports" class="tab-pane">
        <div class="admin-panel-card" style="margin-bottom: 2rem;">
          <h3 style="margin-bottom: 1.5rem;">Visual Analytics Suite</h3>
          <div class="form-row-3" style="max-width: 600px; margin-bottom: 2rem;">
            <div class="form-group">
              <label>Select Report Scope</label>
              <select id="reportScope" class="form-control" style="margin-bottom: 0;">
                <option value="Monthly">Monthly Analytics Report</option>
                <option value="Weekly">Weekly Analytics Report</option>
                <option value="Daily">Daily Analytics Report</option>
              </select>
            </div>
          </div>

          <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="reportsDetailedChart"></canvas>
          </div>
        </div>

        <div class="dashboard-grid-2">
          <!-- Top Selling and Most Viewed -->
          <div class="admin-panel-card">
            <h3>Inventory Valuation and Returns</h3>
            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
              <div
                style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <span class="text-muted">Total Stock Valuation (Cost basis)</span>
                <strong id="analyticsStockValue"><?= htmlspecialchars($currency) ?> 0.00</strong>
              </div>
              <div
                style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <span class="text-muted">Return Rate (Orders returned %)</span>
                <strong id="analyticsReturnRate">0%</strong>
              </div>
              <div
                style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <span class="text-muted">Exchange Rate (Exchange requested %)</span>
                <strong id="analyticsExchangeRate">0%</strong>
              </div>
              <div
                style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <span class="text-muted">Customer Growth (Past 30 days)</span>
                <strong id="analyticsCustomerGrowth">+0</strong>
              </div>
            </div>
          </div>

          <!-- Product views analytics -->
          <div class="admin-panel-card">
            <h3>Product View Counter (Top Viewed)</h3>
            <div id="topViewedContainer" style="margin-top: 1rem;">
              <!-- lists of top viewed products -->
            </div>
          </div>
        </div>
      </section>

      <!-- 11. STAFF VIEW -->
      <section id="staff" class="tab-pane">
        <div class="dashboard-grid-2">
          <!-- Staff profiles -->
          <div class="admin-panel-card">
            <h3 style="margin-bottom: 1rem;">Active Staff Roster</h3>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Staff Name</th>
                    <th>Email</th>
                    <th>Role Designation</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="staffTableBody">
                  <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted);">Loading staff...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Add Staff Form -->
          <div class="admin-panel-card">
            <h3 id="staffFormTitle" style="margin-bottom: 1.5rem;">Add Authorized Staff</h3>
            <form id="staffForm">
              <input type="hidden" id="editStaffId" value="">
              <div class="form-group">
                <label for="staffNameInput">Display Name *</label>
                <input type="text" id="staffNameInput" class="form-control" required placeholder="e.g. Sophia Carter">
              </div>
              <div class="form-group">
                <label for="staffEmailInput">Email Address *</label>
                <input type="email" id="staffEmailInput" class="form-control" required
                  placeholder="e.g. sophia@anjiana.com">
              </div>
              <div class="form-group">
                <label for="staffRoleInput">System Permissions / Role *</label>
                <select id="staffRoleInput" class="form-control">
                  <option value="Staff">Staff (Process orders, view credentials only)</option>
                  <option value="Manager">Manager (Edit catalog, process refunds/returns)</option>
                  <option value="Admin">Administrator (Full root access to configs)</option>
                </select>
              </div>
              <div class="form-group">
                <label for="staffStatusInput">Approval Status *</label>
                <select id="staffStatusInput" class="form-control">
                  <option value="Approved">Approved (Access Granted)</option>
                  <option value="Pending">Pending Approval (Access Blocked)</option>
                  <option value="Denied">Denied (Access Blocked)</option>
                </select>
              </div>
              <button type="submit" id="staffFormSubmitBtn" class="btn btn-primary"
                style="width: 100%; margin-top: 1rem;">Add Team Member</button>
              <button type="button" id="cancelStaffEditBtn" class="btn btn-secondary"
                style="width: 100%; margin-top: 0.5rem; display: none;">Cancel Edit</button>
            </form>
          </div>
        </div>

        <div class="admin-panel-card" style="margin-top: 2rem;">
          <h3>Staff Login History Tracker</h3>
          <div style="overflow-x: auto; margin-top: 1rem;">
            <table>
              <thead>
                <tr>
                  <th>Email</th>
                  <th>Browser / Source Info</th>
                  <th>Access Timestamp</th>
                </tr>
              </thead>
              <tbody id="staffHistoryTableBody">
                <!-- populated logs -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 12. SETTINGS VIEW -->
      <section id="settings" class="tab-pane">
        <div class="admin-panel-card" style="max-width: 800px; margin: 0 auto;">
          <h3 style="margin-bottom: 1.5rem;">Store Information & Parameters</h3>
          <form id="storeSettingsForm">
            <div class="form-row-2">
              <div class="form-group">
                <label for="setStoreName">Store Name *</label>
                 <input type="text" id="setStoreName" class="form-control" required value="<?= htmlspecialchars($site_name) ?>">
              </div>
              <div class="form-group">
                <label for="setStoreEmail">Store Admin Email *</label>
                <input type="email" id="setStoreEmail" class="form-control" required value="<?= htmlspecialchars(getSetting('contact_email', 'info@anjiana.com')) ?>">
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label for="setStorePhone">Store Phone Contact</label>
                <input type="text" id="setStorePhone" class="form-control" value="<?= htmlspecialchars(getSetting('contact_phone', '+1 (555) 123-4567')) ?>">
              </div>
              <div class="form-group">
                <label for="setStoreAddress">Store Address</label>
                <input type="text" id="setStoreAddress" class="form-control" value="<?= htmlspecialchars(getSetting('store_address', '742 Luxury Blvd, Manhattan, NY')) ?>">
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label for="setCurrency">Store Currency Symbol *</label>
                <select id="setCurrency" class="form-control">
                  <option value="Rs." <?= $currency === 'Rs.' ? 'selected' : '' ?>>LKR (Rs.)</option>
                  <option value="$" <?= $currency === '$' ? 'selected' : '' ?>>USD ($)</option>
                  <option value="€" <?= $currency === '€' ? 'selected' : '' ?>>EUR (€)</option>
                  <option value="£" <?= $currency === '£' ? 'selected' : '' ?>>GBP (£)</option>
                  <option value="¥" <?= $currency === '¥' ? 'selected' : '' ?>>JPY (¥)</option>
                  <option value="Rs" <?= $currency === 'Rs' ? 'selected' : '' ?>>INR (Rs)</option>
                </select>
              </div>
              <div class="form-group">
                <label for="setTaxRate">Default Tax Rate (%) *</label>
                <input type="number" id="setTaxRate" class="form-control" required value="<?= htmlspecialchars(getSetting('tax_rate', '10.00')) ?>" step="0.1">
              </div>
            </div>

            <h4 style="margin: 1.5rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
              Social Media Connections</h4>
            <div class="form-row-3">
              <div class="form-group">
                <label>Facebook URL</label>
                <input type="text" id="setFb" class="form-control" placeholder="https://facebook.com/anjiana" value="<?= htmlspecialchars(getSetting('facebook_url', '')) ?>">
              </div>
              <div class="form-group">
                <label>Instagram URL</label>
                <input type="text" id="setIg" class="form-control" placeholder="https://instagram.com/anjiana" value="<?= htmlspecialchars(getSetting('instagram_url', '')) ?>">
              </div>
              <div class="form-group">
                <label>Twitter URL</label>
                <input type="text" id="setTw" class="form-control" placeholder="https://twitter.com/anjiana" value="<?= htmlspecialchars(getSetting('twitter_url', '')) ?>">
              </div>
            </div>

            <h4 style="margin: 1.5rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
              Notifications Configuration</h4>
            <div class="form-group">
              <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                <input type="checkbox" id="setNotifNewOrder" <?= getSetting('notif_new_order', '1') === '1' ? 'checked' : '' ?>> Receive alert notifications on New Orders
              </label>
              <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                <input type="checkbox" id="setNotifLowStock" <?= getSetting('notif_low_stock', '1') === '1' ? 'checked' : '' ?>> Receive alert notifications on Low Stock Alerts (&le; 5 items)
              </label>
              <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                <input type="checkbox" id="setNotifReturns" <?= getSetting('notif_returns', '1') === '1' ? 'checked' : '' ?>> Receive alert notifications on Customer Return Requests
              </label>
            </div>

            <h4 style="margin: 1.5rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
              SMS Notification Configuration</h4>
            <div class="form-group">
              <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                <input type="checkbox" id="setSmsOnApproval" <?= getSetting('sms_on_approval', '1') === '1' ? 'checked' : '' ?>> Send automated SMS notification to customers when order/payment is approved
              </label>
              <div class="form-group" style="margin-top: 0.5rem;">
                <label for="setSmsGateway">SMS Gateway Provider (Simulated for Hostinger Demo)</label>
                <select id="setSmsGateway" class="form-control" style="max-width: 300px;">
                  <option value="mock">Anjiana Simulated Gateway</option>
                  <option value="twilio">Twilio SMS API</option>
                  <option value="dialog">Dialog SMS Sri Lanka</option>
                  <option value="mobitel">Mobitel mSpace</option>
                </select>
              </div>
              <div class="form-group" style="margin-top: 0.5rem;">
                <label for="setSmsTemplate">SMS Approval Message Template</label>
                <textarea id="setSmsTemplate" class="form-control" rows="2" style="resize: vertical;"><?= htmlspecialchars(getSetting('sms_template', 'Dear {name}, your purchase request for Order #{orderId} has been approved! Courier: {courier}, Tracking Number: {trackingNumber}. Thank you for purchasing from Anjiana!')) ?></textarea>
                <small class="text-muted">Available placeholders: {name}, {orderId}, {courier}, {trackingNumber}, {total}</small>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">Save Settings</button>
          </form>
        </div>

        <div class="admin-panel-card" style="max-width: 800px; margin: 1.5rem auto 0 auto;">
          <h3 style="margin-bottom: 1rem;">Sent SMS Notifications Log</h3>
          <p class="text-muted" style="margin-bottom: 1.5rem; font-size: 0.9rem;">Review simulated SMS messages dispatched to customers upon purchase approvals.</p>
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer Name</th>
                  <th>Phone Number</th>
                  <th>Message Content</th>
                  <th>Gateway</th>
                  <th>Status</th>
                  <th>Timestamp</th>
                </tr>
              </thead>
              <tbody id="smsLogsTableBody">
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">No SMS messages logged yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- Modals -->

  <!-- Restock Modal -->
  <div class="admin-modal-overlay" id="restockModal">
    <div class="admin-modal-card" style="max-width: 400px;">
      <div class="modal-header">
        <h3>Restock Product Variant</h3>
        <button class="modal-close-btn" id="closeRestockModal">&times;</button>
      </div>
      <form id="restockForm">
        <input type="hidden" id="restockProductId">
        <input type="hidden" id="restockSize">
        <input type="hidden" id="restockColor">
        <div class="form-group">
          <p id="restockDetailsText" style="margin-bottom: 1rem;"></p>
          <label for="restockQuantityInput">Restock Quantity *</label>
          <input type="number" id="restockQuantityInput" class="form-control" value="10" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Complete Restock</button>
      </form>
    </div>
  </div>

  <!-- Review Reply Modal -->
  <div class="admin-modal-overlay" id="replyModal">
    <div class="admin-modal-card" style="max-width: 500px;">
      <div class="modal-header">
        <h3>Reply to Review</h3>
        <button class="modal-close-btn" id="closeReplyModal">&times;</button>
      </div>
      <form id="replyForm">
        <input type="hidden" id="replyReviewId">
        <div class="form-group">
          <p id="reviewTextPreview"
            style="font-style: italic; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
          </p>
          <label for="replyTextInput">Admin Response Text *</label>
          <textarea id="replyTextInput" class="form-control" rows="4" required
            placeholder="Thank you for your feedback! We will..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Send Reply</button>
      </form>
    </div>
  </div>

  <!-- Bank Deposit Verification Modal -->
  <div class="admin-modal-overlay" id="bankDepositVerifyModal">
    <div class="admin-modal-card" style="max-width: 600px;">
      <div class="modal-header">
        <h3>Verify Bank Deposit Slip</h3>
        <button class="modal-close-btn" id="closeBankDepositModal">&times;</button>
      </div>
      <div style="padding: 1rem 0;">
        <div id="bankDepositDetailsText" style="line-height: 1.6; margin-bottom: 1.5rem; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: var(--radius-md); font-size: 0.9rem; border: 1px solid var(--border-color);">
          <!-- Populated dynamically -->
        </div>
        <div class="form-group">
          <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Uploaded Slip Image</label>
          <div style="background: rgba(0,0,0,0.04); text-align: center; border-radius: var(--radius-sm); border: 1px dashed var(--border-color); overflow: hidden; min-height: 250px; display: flex; align-items: center; justify-content: center;">
            <img id="bankDepositSlipImg" src="" alt="Payment Proof" style="max-width: 100%; max-height: 400px; object-fit: contain; cursor: pointer;" onclick="window.open(this.src, '_blank')">
            <div id="noSlipWarning" style="display: none; padding: 2rem; color: var(--error-color);">No payment proof slip uploaded.</div>
          </div>
          <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem; display: block; text-align: center;">Click slip image to open full resolution in new tab</span>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" id="bankDepositApproveBtn" class="btn btn-primary" style="flex: 1; background: var(--success-color); border: none;">Approve Payment</button>
          <button type="button" id="bankDepositRejectBtn" class="btn btn-secondary" style="flex: 1; background: var(--error-color); color: white; border: none;">Reject Payment</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Order Detail Modal -->
  <div class="admin-modal-overlay" id="orderDetailModal">
    <div class="admin-modal-card" style="max-width: 800px;">
      <div class="modal-header">
        <h3 id="modalOrderRef">Order Detail</h3>
        <button class="modal-close-btn" onclick="document.getElementById('orderDetailModal').classList.remove('active')">&times;</button>
      </div>
      <div id="modalOrderContent">
        <!-- Dynamically populated -->
      </div>
    </div>
  </div>

  <!-- INVOICE PRINT CONTEXT (HIDDEN EXCEPT ON window.print()) -->
  <div id="invoicePrintContainer" style="display: none;">
    <!-- populated just before print trigger -->
  </div>

  <!-- Toast Notification -->
  <div id="toastNotification" class="toast-notification">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div id="toastIcon" class="toast-icon">✓</div>
      <div class="toast-message-body">
        <h4 id="toastTitle" class="toast-title">Success</h4>
        <p id="toastMessage" class="toast-desc">Item saved successfully!</p>
      </div>
    </div>
  </div>

  <!-- Floating Smartphone SMS Simulator -->
  <div id="smsSimulatorWidget" class="sms-simulator-widget">
    <div class="phone-frame">
      <div class="phone-screen">
        <div class="phone-notch"></div>
        <div class="phone-status-bar">
          <span>9:41 AM</span>
          <span style="display: flex; gap: 4px; align-items: center;">📶 🔋 100%</span>
        </div>
        <div class="phone-notification-bubble">
          <div class="notification-header">
            <span class="app-icon">💬</span>
            <span class="app-name">MESSAGES</span>
            <span class="notification-time">now</span>
          </div>
          <div class="notification-content">
            <strong class="notification-title"><?= htmlspecialchars($site_name) ?></strong>
            <p class="notification-body" id="smsSimulatorBody">Dear Customer, your order has been approved!</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript Scripts -->
  <script src="js/app.js"></script>
  <script src="js/admin-app.js"></script>
</body>

</html>
