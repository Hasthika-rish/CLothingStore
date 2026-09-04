# Hostinger Subdomain Deployment Guide: Sage Anjiana Admin Panel

This guide walks you through deploying the **Sage Anjiana PHP Admin Panel** (`admin_anjiana`) under a dedicated subdomain on **Hostinger** (for example: `admin.yourstore.com` or `panel.yourstore.com`), fully connected to your Clothing Store storefront database.

---

## 📋 System Architecture

```
                               ┌──────────────────────────────────────────────┐
                               │            Hostinger MySQL Database          │
                               │  (u123456789_clothing / database.sql)        │
                               └──────────────────────┬───────────────────────┘
                                                      │
                       ┌──────────────────────────────┴──────────────────────────────┐
                       │                                                             │
                       ▼                                                             ▼
        ┌──────────────────────────────┐                              ┌──────────────────────────────┐
        │       Storefront Domain      │                              │       Admin Subdomain        │
        │    https://yourstore.com     │                              │   https://admin.yourstore.com│
        │                              │                              │                              │
        │ • index.php (Home/Banners)   │                              │ • index.php (Admin Login)    │
        │ • products.php (Catalog)     │                              │ • dashboard.php (Full Panel) │
        │ • product-details.php        │                              │ • api/*.php (REST Endpoints) │
        │ • checkout.php (Orders)      │                              │ • js/admin-app.js            │
        │ • uploads/ (Product images)  │                              │ • Shared /uploads directory  │
        └──────────────────────────────┘                              └──────────────────────────────┘
```

---

## 🚀 Step-by-Step Deployment Instructions

### Step 1: Create the Subdomain in Hostinger hPanel

1. Log in to your [Hostinger hPanel](https://hpanel.hostinger.com/).
2. Go to **Websites** &rarr; Click **Manage** on your domain.
3. In the left sidebar, navigate to **Domains** &rarr; **Subdomains**.
4. Fill in the Subdomain details:
   - **Subdomain Name:** `admin` (or `panel`)
   - **Custom folder for subdomain (Document Root):**
     - Check the box **Custom folder for subdomain**.
     - Set the path to: `public_html/admin_anjiana` (or `public_html/admin`)
5. Click **Create**.

---

### Step 2: Upload Files via File Manager or Git

#### Option A: Direct Repository Upload (Recommended)
Upload the entire repository folder into your `public_html/` directory on Hostinger so that:
- `public_html/` contains the Storefront (`index.php`, `products.php`, `config/`, `uploads/`, etc.).
- `public_html/admin_anjiana/` contains the Admin Panel (`index.php`, `dashboard.php`, `api/`, `config.php`, `css/`, `js/`).

#### Option B: Separate Subdomain Folder
If your Hostinger account creates subdomains in separate folders (e.g. `domains/admin.yourstore.com/public_html/`), copy the contents of `admin_anjiana/` into that folder.

---

### Step 3: Create & Import MySQL Database on Hostinger

1. In hPanel, go to **Databases** &rarr; **MySQL Databases**.
2. Create a new database:
   - **Database Name:** e.g., `u123456789_clothing`
   - **MySQL Username:** e.g., `u123456789_admin`
   - **Password:** A strong password (e.g., `YourSecurePass123#`)
3. Click **Create**.
4. Open **phpMyAdmin** next to the newly created database.
5. Click the **Import** tab at the top.
6. Choose the file **`database.sql`** from your repository root and click **Import** (or **Go**).
   - This automatically creates all tables (`products`, `categories`, `orders`, `order_items`, `admins`, `staff_logs`, `stock_history`, `coupons`, `banners`, `reviews`, `sms_logs`, `settings`).

---

### Step 4: Update Database Credentials in Configuration

Open `config/db.php` (and `admin_anjiana/config.php` if running isolated):

```php
// Database Credentials (Update with your Hostinger MySQL details)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_clothing');     // Your Hostinger DB name
define('DB_USER', 'u123456789_admin');        // Your Hostinger DB user
define('DB_PASS', 'YourSecurePass123#');      // Your Hostinger DB password
define('DB_CHARSET', 'utf8mb4');
```

---

### Step 5: Set Permissions for Uploads Folder

In **hPanel File Manager**:
1. Ensure the `uploads/` folder has **`755` permissions**.
2. Sub-directories (`uploads/products/`, `uploads/slips/`, `uploads/banners/`) will automatically store images uploaded by the storefront and admin panel.

---

### Step 6: Enable Free SSL for the Subdomain

1. In hPanel, go to **Security** &rarr; **SSL**.
2. Select your new subdomain `admin.yourstore.com`.
3. Click **Install SSL** (Let's Encrypt Free SSL).
4. Enable **Force HTTPS**.

---

## 🔑 Default Administrator Login Credentials

| Role | Email / Username | Default Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@anjiana.com` | `admin123` | Full Root Access |
| **Root Admin** | `admin@sageanjiana.com` | `admin123` | Full Root Access |
| **Store Manager** | `sophia@anjiana.com` | `admin123` | Catalog, Orders & Refunds |

> [!TIP]
> After logging in, you can add new team members or update passwords at any time under **Staff Management** or change master parameters under **Store Settings**!

---

## 🧪 Verification & Feature Testing

1. **Admin Login:** Visit `https://admin.yourstore.com/` &rarr; Log in with `admin@anjiana.com` / `admin123`.
2. **Dashboard Overview:** Verify that Total Sales, Chart.js trends, and low stock alerts load in real-time.
3. **Products Catalog:** Add or edit products; verify that changes immediately show on your storefront (`https://yourstore.com/products.php`).
4. **Order & Payment Verification:**
   - When a customer orders on the storefront with Bank Transfer / Fixed Deposit, their slip appears in the Admin **Payments &rarr; Bank Deposit Verification** tab.
   - Clicking **Approve Payment** updates the order status to `Verified` and triggers simulated customer SMS notification!
5. **Coupons & Banners:** Create promotional codes or homepage sliders; they instantly sync with the storefront.
