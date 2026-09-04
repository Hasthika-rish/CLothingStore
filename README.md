# 🛍️ Anjiana Clothing Store - PHP & MySQL Web Application

A modern, dynamic e-commerce web application built with **PHP 8.x**, **MySQL**, **Vanilla CSS**, and **JavaScript**, specifically tailored for deployment on **Hostinger** (or any standard LAMP/LEMP cPanel/hPanel web hosting).

---

## 🌟 Key Features

### Storefront
- **Dynamic Catalog**: Products and categories loaded from MySQL.
- **Search & Filter**: Filter by category, gender, style tag, or search keywords; sort by price, newest, or alphabetical; with pagination.
- **Product Single Page**: Image gallery, live stock availability, size & color selectors, feature highlights, and related product recommendations.
- **Session-based Cart**: Item quantity adjuster, free shipping progress calculator (free shipping over $100), and order summary.
- **Multi-Payment Checkout**:
  - Cash on Delivery (COD)
  - Direct Bank Deposit with payment slip upload
- **Customer Order Tracking (`your-orders.php`)**: Track orders by Reference ID (`ANJ-XXXX-26`), Email, or Phone with live status progress bar (`Pending` ➔ `Processing` ➔ `Shipped` ➔ `Delivered`) and WhatsApp inquiry button.
- **Saved Wishlist**: Customer wishlist with local storage synchronization.
- **Dark / Light Mode**: Seamless theme switching with user preference persistence.

### Admin Control Panel (`/admin`)
- **Secure Authentication**: Bcrypt password hashing (`admin` / `admin123`).
- **Dashboard Overview**: Total revenue, total orders, active products count, low-stock warnings, and recent order feeds.
- **Product Management**: Add, edit, and delete products with native image uploads saved to `uploads/products/`.
- **Order Management**: Update order and payment statuses in real-time, view customer details, and inspect uploaded bank deposit slips.
- **Category Control**: Add, reorder, and remove store categories.
- **Point of Sale (POS)**: Fast register interface for processing in-store walk-in sales.

---

## 🚀 Deployment Instructions for Hostinger

Please follow the complete step-by-step guide in [**HOSTINGER_DEPLOYMENT_GUIDE.md**](HOSTINGER_DEPLOYMENT_GUIDE.md):

1. **Create MySQL Database** in Hostinger hPanel (**Databases** ➔ **Management**).
2. **Import [`database.sql`](database.sql)** via phpMyAdmin.
3. **Upload Files** into `public_html/`.
4. **Configure Database Credentials** in [`config/db.php`](config/db.php).
5. **Access Your Website**:
   - Customer Storefront: `https://yourdomain.com/`
   - Admin Panel: `https://yourdomain.com/admin/login.php` (Username: `admin` | Password: `admin123`)

---

## 🛠️ Tech Stack
- **Backend**: PHP 8.x with PDO (Prepared Statements & UTF-8)
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla CSS (CSS Variables, Responsive Grid/Flexbox), Vanilla JS
- **Server**: Apache / Nginx with `.htaccess` security rules
