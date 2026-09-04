<?php
/**
 * Database Configuration & Global Settings
 * Anjiana Clothing Store - Hostinger PHP/MySQL & Local Dev Auto-Fallback
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Update these with your Hostinger MySQL details)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'u123456789_clothing');     // Replace with your Hostinger DB name (e.g., u123456789_clothing)
if (!defined('DB_USER')) define('DB_USER', 'u123456789_admin');        // Replace with your Hostinger DB user
if (!defined('DB_PASS')) define('DB_PASS', 'YourStrongPassword123#');  // Replace with your Hostinger DB password
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Site Constants
if (!defined('SITE_NAME')) define('SITE_NAME', 'Sage Anjiana');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'Rs.');
if (!defined('DEFAULT_SHIPPING')) define('DEFAULT_SHIPPING', 10.00);
if (!defined('FREE_SHIPPING_MIN')) define('FREE_SHIPPING_MIN', 100.00);

// Base Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
define('PRODUCT_UPLOAD_DIR', ROOT_PATH . '/uploads/products/');
define('SLIP_UPLOAD_DIR', ROOT_PATH . '/uploads/slips/');

// Connect via PDO
$pdo = null;
$db_connected = false;

// 1. Try MySQL Connection First (for Hostinger or local MySQL)
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
    // 2. Local Development Fallback: Automatically connect to SQLite if MySQL is offline
    try {
        $sqliteFile = ROOT_PATH . '/config/local_store.db';
        $isNewDb = !file_exists($sqliteFile);
        $pdo = new PDO("sqlite:" . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db_connected = true;

        initSqliteDatabase($pdo);
    } catch (Exception $sqle) {
        $db_error = $e->getMessage();
    }
}

/**
 * Helper to auto-seed local SQLite database for instant local testing
 */
function initSqliteDatabase($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT DEFAULT 'Store Administrator',
            role TEXT DEFAULT 'Admin',
            status TEXT DEFAULT 'Approved',
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS staff_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            staff_email TEXT NOT NULL,
            user_agent TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            gender TEXT DEFAULT 'Unisex',
            parent_category TEXT,
            image TEXT,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL,
            brand TEXT DEFAULT 'Sage Anjiana',
            category_id INTEGER,
            category TEXT NOT NULL,
            gender TEXT DEFAULT 'Unisex',
            material TEXT DEFAULT 'Cotton Blend',
            tag TEXT DEFAULT 'everyday',
            price REAL NOT NULL,
            discount REAL DEFAULT 0.00,
            sku TEXT,
            stock INTEGER DEFAULT 10,
            sizes TEXT DEFAULT 'XS,S,M,L,XL',
            colors TEXT DEFAULT 'White,Black,Navy',
            size_color_stock TEXT,
            description TEXT,
            image_url TEXT NOT NULL,
            additional_images TEXT,
            is_featured INTEGER DEFAULT 0,
            views_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS stock_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            variant_size TEXT DEFAULT 'N/A',
            variant_color TEXT DEFAULT 'N/A',
            change_qty INTEGER NOT NULL,
            new_stock INTEGER NOT NULL,
            notes TEXT DEFAULT 'Manual restock',
            created_by TEXT DEFAULT 'Admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT NOT NULL UNIQUE,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            address TEXT NOT NULL,
            city TEXT NOT NULL,
            district TEXT NOT NULL,
            postal_code TEXT NOT NULL,
            subtotal REAL NOT NULL,
            shipping REAL DEFAULT 0.00,
            total_amount REAL NOT NULL,
            payment_method TEXT DEFAULT 'COD',
            payment_status TEXT DEFAULT 'Pending',
            payment_proof_url TEXT,
            order_status TEXT DEFAULT 'Pending',
            courier TEXT DEFAULT 'Local Logistics',
            tracking_number TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER,
            product_name TEXT NOT NULL,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            size TEXT DEFAULT 'M',
            color TEXT DEFAULT 'N/A',
            total REAL NOT NULL,
            image_url TEXT
        );

        CREATE TABLE IF NOT EXISTS coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            type TEXT DEFAULT 'Percentage',
            value REAL DEFAULT 0.00,
            min_amount REAL DEFAULT 0.00,
            expiry_date DATE,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS banners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            image_url TEXT NOT NULL,
            link_url TEXT,
            section_type TEXT DEFAULT 'Banner',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            rating INTEGER NOT NULL DEFAULT 5,
            comment TEXT NOT NULL,
            admin_reply TEXT,
            status TEXT DEFAULT 'Pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS sms_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id TEXT NOT NULL,
            customer_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            message TEXT NOT NULL,
            gateway TEXT DEFAULT 'Anjiana Simulated Gateway',
            status TEXT DEFAULT 'Delivered',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT
        );
    ");

    // Column auto-migrations for existing SQLite tables
    $columnsToEnsure = [
        "admins"     => ["status TEXT DEFAULT 'Approved'", "last_login DATETIME", "role TEXT DEFAULT 'Admin'"],
        "categories" => ["gender TEXT DEFAULT 'Unisex'", "parent_category TEXT"],
        "products"   => ["brand TEXT DEFAULT 'Sage Anjiana'", "gender TEXT DEFAULT 'Unisex'", "material TEXT DEFAULT 'Cotton Blend'", "sku TEXT", "size_color_stock TEXT", "views_count INTEGER DEFAULT 0"],
        "orders"     => ["courier TEXT DEFAULT 'Local Logistics'", "tracking_number TEXT"]
    ];

    foreach ($columnsToEnsure as $tbl => $cols) {
        foreach ($cols as $colDef) {
            try {
                $db->exec("ALTER TABLE {$tbl} ADD COLUMN {$colDef}");
            } catch (Exception $e) {
                // Column already exists
            }
        }
    }

    // Insert default admins (superadmin, admin, staff)
    $adminHash = '$2y$10$a8iW9loqRPtNZJpvvAFJr.pLRaOQfAx3NARC.VKmnFY70CzLfcWF6';
    $db->exec("INSERT OR IGNORE INTO admins (username, email, password_hash, full_name, role, status) VALUES 
        ('admin', 'admin@anjiana.com', '{$adminHash}', 'Anjiana Super Admin', 'Admin', 'Approved'),
        ('superadmin', 'admin@sageanjiana.com', '{$adminHash}', 'Sage Anjiana Root', 'Admin', 'Approved'),
        ('sophia', 'sophia@anjiana.com', '{$adminHash}', 'Sophia Carter', 'Manager', 'Approved');
    ");
    $db->exec("UPDATE admins SET role = 'Admin', status = 'Approved' WHERE role IS NULL OR role = '' OR role = 'Staff';");


    // Insert categories
    $db->exec("
        INSERT OR IGNORE INTO categories (id, name, slug, gender, parent_category, image, display_order) VALUES
        (1, 'Women', 'women', 'Women', NULL, 'images/women_new.png', 1),
        (2, 'Men', 'men', 'Men', NULL, 'images/men_new.png', 2),
        (3, 'Kids Section', 'kids', 'Kids', NULL, 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600', 3),
        (4, 'Dresses', 'dresses', 'Women', 'Women', 'images/product_dress1.png', 4),
        (5, 'Jackets', 'jackets', 'Men', 'Men', 'images/product_jacket1.png', 5),
        (6, 'Shirts', 'shirts', 'Men', 'Men', 'images/product_shirt1.png', 6),
        (7, 'Other', 'other', 'Unisex', NULL, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600', 7);
    ");

    // Insert products
    $db->exec("
        INSERT OR IGNORE INTO products (id, name, slug, brand, category_id, category, gender, material, tag, price, discount, sku, stock, sizes, colors, size_color_stock, description, image_url, is_featured, views_count, status) VALUES
        (1, 'Floral Summer Midi Dress', 'floral-summer-midi-dress', 'Sage Anjiana Signature', 1, 'Women', 'Women', '100% Cotton Linen', 'everyday', 48.00, 15.00, 'ANJ-DRS-01', 25, 'XS,S,M,L,XL', 'White,Black,Red', '{\"XS_White\":5,\"S_White\":5,\"M_White\":5,\"L_White\":5,\"XL_White\":5}', 'Embrace effortless elegance with this beautiful floral midi dress from Anjiana Store. Crafted from breathable cotton blend with subtle ruffle accents and a flattering silhouette.', 'images/product_dress1.png', 1, 142, 'active'),
        (2, 'Urban Minimalist Jacket', 'urban-minimalist-jacket', 'Sage Anjiana Menswear', 2, 'Men', 'Men', 'Polyester Blend', 'nightout', 89.00, 10.00, 'ANJ-JKT-01', 18, 'S,M,L,XL,XXL', 'Black,Navy,Grey', '{\"S_Black\":4,\"M_Black\":6,\"L_Black\":4,\"XL_Black\":4}', 'Sharp, tailored, and weather-resistant modern outerwear engineered for urban comfort and clean aesthetic lines. Features premium zippers and soft lining.', 'images/product_jacket1.png', 1, 98, 'active'),
        (3, 'Classic Oxford Cotton Shirt', 'classic-oxford-cotton-shirt', 'Sage Anjiana Essentials', 2, 'Men', 'Men', '100% Organic Cotton', 'essentials', 39.00, 0.00, 'ANJ-SHT-01', 30, 'S,M,L,XL', 'White,Navy,Beige', '{\"S_White\":8,\"M_White\":10,\"L_White\":8,\"XL_White\":4}', 'A versatile wardrobe essential crafted with 100% breathable organic cotton, tailored fit, button-down collar, and immaculate stitching.', 'images/product_shirt1.png', 1, 210, 'active'),
        (4, 'Elegance Evening Satin Gown', 'elegance-evening-satin-gown', 'Sage Anjiana Luxury', 1, 'Women', 'Women', 'Premium Satin Silk', 'occasion', 95.00, 20.00, 'ANJ-GWN-01', 12, 'XS,S,M,L', 'Black,Red,Navy', '{\"XS_Black\":3,\"S_Black\":3,\"M_Black\":3,\"L_Black\":3}', 'Radiate luxury with our premium emerald and gold sheen satin evening gown, designed for gala dinners and memorable celebrations.', 'images/women_occasion.png', 1, 75, 'active'),
        (5, 'Linen Casual Weekend Shirt', 'linen-casual-weekend-shirt', 'Sage Anjiana Casuals', 2, 'Men', 'Men', '100% Pure Linen', 'everyday', 44.00, 5.00, 'ANJ-LIN-01', 22, 'S,M,L,XL', 'Beige,White,Olive', '{\"S_Beige\":5,\"M_Beige\":7,\"L_Beige\":5,\"XL_Beige\":5}', 'Stay cool with our relaxed-fit lightweight pure linen shirt. Natural texture and breathable comfort for tropical afternoons and seaside getaways.', 'images/men_everyday.png', 1, 64, 'active'),
        (6, 'Silk Touch Cocktail Dress', 'silk-touch-cocktail-dress', 'Sage Anjiana Luxury', 1, 'Women', 'Women', 'Silk Touch Blend', 'nightout', 68.00, 0.00, 'ANJ-COK-01', 15, 'S,M,L', 'Black,Red', '{\"S_Black\":5,\"M_Black\":5,\"L_Black\":5}', 'Step out in confidence with our sleek cocktail dress featuring delicate straps, premium stretch fabric, and contemporary minimalist tailoring.', 'images/women_nightout.png', 1, 115, 'active'),
        (7, 'Everyday Comfy Essentials Tee', 'everyday-comfy-essentials-tee', 'Sage Anjiana Basics', 7, 'Other', 'Unisex', '100% Combed Cotton', 'essentials', 24.00, 0.00, 'ANJ-TEE-01', 50, 'XS,S,M,L,XL,XXL', 'White,Black,Grey', '{\"M_White\":20,\"L_White\":20,\"XL_White\":10}', 'Ultra-soft heavy jersey combed cotton t-shirt built for daily wear, enduring washes while maintaining crisp shape and softness.', 'images/women_essentials.png', 0, 320, 'active'),
        (8, 'Kids Adventure Denim Dungarees', 'kids-adventure-denim-dungarees', 'Sage Anjiana Junior', 3, 'Kids Section', 'Kids', 'Durable Cotton Denim', 'everyday', 34.00, 10.00, 'ANJ-KID-01', 20, '2Y,4Y,6Y,8Y', 'Navy', '{\"2Y_Navy\":5,\"4Y_Navy\":5,\"6Y_Navy\":5,\"8Y_Navy\":5}', 'Durable, flexible, and comfortable denim dungarees for young explorers. Features adjustable metal buckles and reinforced knees.', 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600', 0, 88, 'active');
    ");

    // Ensure distinct brands are populated on existing records
    $db->exec("
        UPDATE products SET brand = 'Sage Anjiana Signature' WHERE id = 1 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Menswear' WHERE id = 2 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Essentials' WHERE id = 3 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Luxury' WHERE id = 4 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Casuals' WHERE id = 5 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Luxury' WHERE id = 6 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Basics' WHERE id = 7 AND (brand = 'Sage Anjiana' OR brand IS NULL);
        UPDATE products SET brand = 'Sage Anjiana Junior' WHERE id = 8 AND (brand = 'Sage Anjiana' OR brand IS NULL);
    ");

    // Insert coupons
    $db->exec("
        INSERT OR IGNORE INTO coupons (id, code, type, value, min_amount, expiry_date, status) VALUES
        (1, 'SUMMER25', 'Percentage', 25.00, 50.00, '2026-12-31', 'active'),
        (2, 'WELCOME10', 'Fixed', 10.00, 30.00, '2026-12-31', 'active'),
        (3, 'B2G1PROMO', 'Buy2Get1', 0.00, 0.00, '2026-12-31', 'active');
    ");

    // Insert banners
    $db->exec("
        INSERT OR IGNORE INTO banners (id, title, image_url, link_url, section_type, status) VALUES
        (1, 'Autumn Warmth & Urban Luxury', 'images/banner1.png', 'products.php?category=women', 'Banner', 'active'),
        (2, 'Timeless Classics for Gentlemen', 'images/banner2.png', 'products.php?category=men', 'Banner', 'active'),
        (3, 'Curated Seasonal Collection', 'images/banner3.png', 'products.php', 'Seasonal', 'active');
    ");

    // Insert reviews
    $db->exec("
        INSERT OR IGNORE INTO reviews (id, product_id, customer_name, customer_email, rating, comment, admin_reply, status) VALUES
        (1, 1, 'Samantha Perera', 'samantha@example.com', 5, 'The fabric quality of this floral dress is outstanding! Highly recommended.', 'Thank you so much for your kind words Samantha! We are thrilled you love it.', 'Approved'),
        (2, 2, 'Kasun Silva', 'kasun@example.com', 5, 'Tailoring and fit on this jacket are sleek and modern. Fast delivery too.', 'Thank you Kasun! Enjoy your new jacket.', 'Approved'),
        (3, 3, 'Dilshan Fernando', 'dilshan@example.com', 4, 'Very comfortable cotton shirt. Excellent quality for daily work wear.', NULL, 'Approved');
    ");

    // Insert settings
    $db->exec("
        INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
        ('site_name', 'Sage Anjiana'),
        ('site_tagline', 'Modern & Timeless Apparel'),
        ('contact_email', 'info@anjiana.com'),
        ('contact_phone', '+1 (555) 123-4567'),
        ('whatsapp_number', '94704300342'),
        ('currency_symbol', 'Rs.'),
        ('tax_rate', '10.00'),
        ('shipping_fee', '10.00'),
        ('express_shipping_fee', '25.00'),
        ('free_shipping_threshold', '100.00'),
        ('approved_couriers', 'FedEx, DHL Express, USPS, UPS, Local Logistics'),
        ('bank_name', 'Commercial Bank of Ceylon'),
        ('bank_account_name', 'Anjiana Store Holdings'),
        ('bank_account_number', '8001234567'),
        ('bank_branch', 'Colombo Main Branch'),
        ('facebook_url', 'https://facebook.com/anjiana'),
        ('instagram_url', 'https://instagram.com/anjiana'),
        ('twitter_url', 'https://twitter.com/anjiana'),
        ('notif_new_order', '1'),
        ('notif_low_stock', '1'),
        ('notif_returns', '1'),
        ('sms_on_approval', '1'),
        ('sms_gateway', 'mock'),
        ('sms_template', 'Dear {name}, your purchase request for Order #{orderId} has been approved! Courier: {courier}, Tracking Number: {trackingNumber}. Thank you for choosing Anjiana!');
    ");
}

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDB() {
    global $pdo, $db_connected, $db_error;
    if (!$db_connected || !$pdo) {
        die("<div style='font-family:sans-serif; padding:2rem; max-width:650px; margin:3rem auto; background:#FFF0F0; border:1px solid #FFCACA; border-radius:8px; color:#900;'>
            <h2 style='margin-top:0;'>Database Connection Required</h2>
            <p>Could not connect to the database.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($db_error ?? 'Database not initialized') . "</p>
            <hr style='border:0; border-top:1px solid #FFCACA; margin:1.5rem 0;'>
            <p style='font-size:0.9rem; color:#600;'>Refer to <strong>HOSTINGER_DEPLOYMENT_GUIDE.md</strong> for step-by-step instructions on creating a database in Hostinger hPanel and importing <code>database.sql</code>.</p>
        </div>");
    }
    return $pdo;
}

