# 🚀 Hostinger Deployment Guide - Anjiana Clothing Store (PHP & MySQL)

This step-by-step guide explains how to host this PHP clothing store on **Hostinger** using **hPanel**, **MySQL Database**, and **File Manager / FTP**.

---

## 📋 Overview of Deployment Steps

1. **Create MySQL Database in Hostinger hPanel**
2. **Import `database.sql` via phpMyAdmin**
3. **Upload Project Files to `public_html`**
4. **Update `config/db.php` with Hostinger Database Credentials**
5. **Set Permissions for `uploads/` Folder**
6. **Access Storefront & Admin Portal**

---

## Step 1: Create a MySQL Database in Hostinger hPanel

1. Log in to your **Hostinger Control Panel (hPanel)**.
2. Under your hosting plan, navigate to **Databases** → **Management** (or **MySQL Databases**).
3. Under **Create a New MySQL Database and Database User**:
   - **MySQL Database Name**: e.g., `clothing_store` (Hostinger will prefix it, e.g., `u123456789_clothing_store`)
   - **MySQL Username**: e.g., `admin` (Hostinger will prefix it, e.g., `u123456789_admin`)
   - **Password**: Enter a strong password (e.g., `StorePass2026!#`)
4. Click **Create**.
5. **Copy down** the exact:
   - Database Name
   - Database Username
   - Database Password

---

## Step 2: Import `database.sql` into phpMyAdmin

1. In hPanel under **Databases**, find your new database and click **Enter phpMyAdmin**.
2. In phpMyAdmin, click on your database name on the left sidebar.
3. Click on the **Import** tab at the top.
4. Click **Choose File** and select the [`database.sql`](database.sql) file from this repository.
5. Click **Import** (or **Go**) at the bottom.
6. You will see a green success notification: *Import has been successfully finished.* All tables (`admins`, `categories`, `products`, `orders`, `order_items`, `settings`) and sample products will now be populated.

---

## Step 3: Upload Website Files to Hostinger

You can upload your files via **Hostinger File Manager** or **FTP (FileZilla)**:

### Option A: Using Hostinger File Manager (Fastest)
1. In hPanel, go to **Files** → **File Manager** → **Access files of [your domain]**.
2. Open the **`public_html`** directory.
3. Zip the contents of this project folder on your computer.
4. In File Manager, click **Upload** → **File** → select your `.zip` archive.
5. Right-click the uploaded `.zip` in `public_html` and choose **Extract**.
6. Ensure that `index.php`, `config/`, `includes/`, `admin/`, `css/`, `images/`, `uploads/`, and `.htaccess` are directly inside `public_html/`.

---

## Step 4: Configure Database Credentials in `config/db.php`

1. In Hostinger File Manager, navigate to `public_html/config/` and double-click to edit **`db.php`**.
2. Update the constants with your Hostinger database details:

```php
// Database Credentials (from Step 1)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_clothing_store'); // Your Hostinger DB Name
define('DB_USER', 'u123456789_admin');          // Your Hostinger DB User
define('DB_PASS', 'StorePass2026!#');           // Your DB Password
```

3. Click **Save & Close**.

---

## Step 5: Check `uploads/` Folder Permissions

1. Inside `public_html/`, ensure the `uploads/` folder exists along with subfolders:
   - `uploads/products/`
   - `uploads/slips/`
   - `uploads/categories/`
2. Right-click on `uploads/` → **Permissions** → set to `755` (or check Read/Write/Execute for Owner).

---

## Step 6: Test Your Website & Admin Portal

### 🛍️ Customer Storefront
- Visit: `https://yourdomain.com/` (or `https://yourdomain.lk/`)
- Try adding a product to cart and going through checkout (COD or Bank Transfer).
- Track the order on `https://yourdomain.com/your-orders.php`.

### ⚡ Admin Control Panel
- Visit: `https://yourdomain.com/admin/login.php` (or `https://yourdomain.com/admin/`)
- **Default Login Credentials**:
  - **Username**: `admin`
  - **Password**: `admin123`
- Inside Admin:
  - Add / edit / delete products with image uploads
  - Manage categories
  - View orders, check uploaded bank deposit slips, and change order delivery statuses
  - Use the In-Store POS register for quick walk-in billing

---

## 🔒 Security & Best Practices

1. **Change Default Admin Password**:
   - Once logged into the admin dashboard or via phpMyAdmin, change the default password.
2. **Enable SSL (HTTPS)**:
   - In Hostinger hPanel, go to **Security** → **SSL** and activate the free Let's Encrypt SSL certificate for your domain.
3. **Backups**:
   - Hostinger provides automatic daily/weekly backups in hPanel under **Files** → **Backups**.
