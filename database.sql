-- ==========================================================
-- ANJIANA CLOTHING STORE - COMPLETE MYSQL DATABASE SCHEMA
-- Compatible with Hostinger MySQL / phpMyAdmin / MariaDB
-- ==========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: admins / staff
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT 'Store Administrator',
  `role` ENUM('Admin', 'Manager', 'Staff') DEFAULT 'Admin',
  `status` ENUM('Approved', 'Pending', 'Denied') DEFAULT 'Approved',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff_logs (Login Audit History)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_email` VARCHAR(100) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`staff_email`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `gender` VARCHAR(50) DEFAULT 'Unisex',
  `parent_category` VARCHAR(100) NULL,
  `image` VARCHAR(255) NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(100) DEFAULT 'Sage Anjiana',
  `category_id` INT NULL,
  `category` VARCHAR(100) NOT NULL,
  `gender` VARCHAR(50) DEFAULT 'Unisex',
  `material` VARCHAR(100) DEFAULT 'Cotton Blend',
  `tag` VARCHAR(50) DEFAULT 'everyday',
  `price` DECIMAL(10, 2) NOT NULL,
  `discount` DECIMAL(10, 2) DEFAULT 0.00,
  `sku` VARCHAR(100) NULL,
  `stock` INT DEFAULT 10,
  `sizes` VARCHAR(255) DEFAULT 'XS,S,M,L,XL',
  `colors` VARCHAR(255) DEFAULT 'White,Black,Navy',
  `size_color_stock` TEXT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `additional_images` TEXT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `views_count` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  INDEX (`status`),
  INDEX (`is_featured`),
  INDEX (`gender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: stock_history (Restock & Adjustment Audit Logs)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_size` VARCHAR(50) DEFAULT 'N/A',
  `variant_color` VARCHAR(50) DEFAULT 'N/A',
  `change_qty` INT NOT NULL,
  `new_stock` INT NOT NULL,
  `notes` VARCHAR(255) DEFAULT 'Manual restock',
  `created_by` VARCHAR(100) DEFAULT 'Admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`product_id`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `shipping` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'COD',
  `payment_status` VARCHAR(50) DEFAULT 'Pending',
  `payment_proof_url` VARCHAR(255) NULL,
  `order_status` VARCHAR(50) DEFAULT 'Pending',
  `courier` VARCHAR(100) DEFAULT 'Local Logistics',
  `tracking_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`email`),
  INDEX (`phone`),
  INDEX (`order_status`),
  INDEX (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `size` VARCHAR(50) DEFAULT 'M',
  `color` VARCHAR(50) DEFAULT 'N/A',
  `total` DECIMAL(10, 2) NOT NULL,
  `image_url` VARCHAR(255) NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: coupons
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('Percentage', 'Fixed', 'Buy2Get1') DEFAULT 'Percentage',
  `value` DECIMAL(10, 2) DEFAULT 0.00,
  `min_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `expiry_date` DATE NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: banners
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `link_url` VARCHAR(255) NULL,
  `section_type` ENUM('Banner', 'Seasonal', 'Featured') DEFAULT 'Banner',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reviews
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `admin_reply` TEXT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`product_id`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sms_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `gateway` VARCHAR(50) DEFAULT 'Anjiana Simulated Gateway',
  `status` VARCHAR(20) DEFAULT 'Delivered',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Default Administrators (Superadmin & Staff)
-- Passwords are 'admin123' hashed with bcrypt
INSERT INTO `admins` (`username`, `email`, `password_hash`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@anjiana.com', '$2y$10$a8iW9loqRPtNZJpvvAFJr.pLRaOQfAx3NARC.VKmnFY70CzLfcWF6', 'Anjiana Super Admin', 'Admin', 'Approved'),
('superadmin', 'admin@sageanjiana.com', '$2y$10$a8iW9loqRPtNZJpvvAFJr.pLRaOQfAx3NARC.VKmnFY70CzLfcWF6', 'Sage Anjiana Root', 'Admin', 'Approved'),
('sophia', 'sophia@anjiana.com', '$2y$10$a8iW9loqRPtNZJpvvAFJr.pLRaOQfAx3NARC.VKmnFY70CzLfcWF6', 'Sophia Carter', 'Manager', 'Approved')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Default Categories
INSERT INTO `categories` (`name`, `slug`, `gender`, `parent_category`, `image`, `display_order`) VALUES
('Women', 'women', 'Women', NULL, 'images/women_new.png', 1),
('Men', 'men', 'Men', NULL, 'images/men_new.png', 2),
('Kids Section', 'kids', 'Kids', NULL, 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600', 3),
('Dresses', 'dresses', 'Women', 'Women', 'images/product_dress1.png', 4),
('Jackets', 'jackets', 'Men', 'Men', 'images/product_jacket1.png', 5),
('Shirts', 'shirts', 'Men', 'Men', 'images/product_shirt1.png', 6),
('Other', 'other', 'Unisex', NULL, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600', 7)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Default Products
INSERT INTO `products` (`name`, `slug`, `brand`, `category_id`, `category`, `gender`, `material`, `tag`, `price`, `discount`, `sku`, `stock`, `sizes`, `colors`, `size_color_stock`, `description`, `image_url`, `is_featured`, `views_count`, `status`) VALUES
('Floral Summer Midi Dress', 'floral-summer-midi-dress', 'Sage Anjiana Signature', 1, 'Women', 'Women', '100% Cotton Linen', 'everyday', 48.00, 15.00, 'ANJ-DRS-01', 25, 'XS,S,M,L,XL', 'White,Black,Red', '{"XS_White":5,"S_White":5,"M_White":5,"L_White":5,"XL_White":5}', 'Embrace effortless elegance with this beautiful floral midi dress from Anjiana Store. Crafted from breathable cotton blend with subtle ruffle accents and a flattering silhouette.', 'images/product_dress1.png', 1, 142, 'active'),

('Urban Minimalist Jacket', 'urban-minimalist-jacket', 'Sage Anjiana Menswear', 2, 'Men', 'Men', 'Polyester Blend', 'nightout', 89.00, 10.00, 'ANJ-JKT-01', 18, 'S,M,L,XL,XXL', 'Black,Navy,Grey', '{"S_Black":4,"M_Black":6,"L_Black":4,"XL_Black":4}', 'Sharp, tailored, and weather-resistant modern outerwear engineered for urban comfort and clean aesthetic lines. Features premium zippers and soft lining.', 'images/product_jacket1.png', 1, 98, 'active'),

('Classic Oxford Cotton Shirt', 'classic-oxford-cotton-shirt', 'Sage Anjiana Essentials', 2, 'Men', 'Men', '100% Organic Cotton', 'essentials', 39.00, 0.00, 'ANJ-SHT-01', 30, 'S,M,L,XL', 'White,Navy,Beige', '{"S_White":8,"M_White":10,"L_White":8,"XL_White":4}', 'A versatile wardrobe essential crafted with 100% breathable organic cotton, tailored fit, button-down collar, and immaculate stitching.', 'images/product_shirt1.png', 1, 210, 'active'),

('Elegance Evening Satin Gown', 'elegance-evening-satin-gown', 'Sage Anjiana Luxury', 1, 'Women', 'Women', 'Premium Satin Silk', 'occasion', 95.00, 20.00, 'ANJ-GWN-01', 12, 'XS,S,M,L', 'Black,Red,Navy', '{"XS_Black":3,"S_Black":3,"M_Black":3,"L_Black":3}', 'Radiate luxury with our premium emerald and gold sheen satin evening gown, designed for gala dinners and memorable celebrations.', 'images/women_occasion.png', 1, 75, 'active'),

('Linen Casual Weekend Shirt', 'linen-casual-weekend-shirt', 'Sage Anjiana Casuals', 2, 'Men', 'Men', '100% Pure Linen', 'everyday', 44.00, 5.00, 'ANJ-LIN-01', 22, 'S,M,L,XL', 'Beige,White,Olive', '{"S_Beige":5,"M_Beige":7,"L_Beige":5,"XL_Beige":5}', 'Stay cool with our relaxed-fit lightweight pure linen shirt. Natural texture and breathable comfort for tropical afternoons and seaside getaways.', 'images/men_everyday.png', 1, 64, 'active'),

('Silk Touch Cocktail Dress', 'silk-touch-cocktail-dress', 'Sage Anjiana Luxury', 1, 'Women', 'Women', 'Silk Touch Blend', 'nightout', 68.00, 0.00, 'ANJ-COK-01', 15, 'S,M,L', 'Black,Red', '{"S_Black":5,"M_Black":5,"L_Black":5}', 'Step out in confidence with our sleek cocktail dress featuring delicate straps, premium stretch fabric, and contemporary minimalist tailoring.', 'images/women_nightout.png', 1, 115, 'active'),

('Everyday Comfy Essentials Tee', 'everyday-comfy-essentials-tee', 'Sage Anjiana Basics', 7, 'Other', 'Unisex', '100% Combed Cotton', 'essentials', 24.00, 0.00, 'ANJ-TEE-01', 50, 'XS,S,M,L,XL,XXL', 'White,Black,Grey', '{"M_White":20,"L_White":20,"XL_White":10}', 'Ultra-soft heavy jersey combed cotton t-shirt built for daily wear, enduring washes while maintaining crisp shape and softness.', 'images/women_essentials.png', 0, 320, 'active'),

('Kids Adventure Denim Dungarees', 'kids-adventure-denim-dungarees', 'Sage Anjiana Junior', 3, 'Kids Section', 'Kids', 'Durable Cotton Denim', 'everyday', 34.00, 10.00, 'ANJ-KID-01', 20, '2Y,4Y,6Y,8Y', 'Navy', '{"2Y_Navy":5,"4Y_Navy":5,"6Y_Navy":5,"8Y_Navy":5}', 'Durable, flexible, and comfortable denim dungarees for young explorers. Features adjustable metal buckles and reinforced knees.', 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=600', 0, 88, 'active')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Default Coupons
INSERT INTO `coupons` (`code`, `type`, `value`, `min_amount`, `expiry_date`, `status`) VALUES
('SUMMER25', 'Percentage', 25.00, 50.00, '2026-12-31', 'active'),
('WELCOME10', 'Fixed', 10.00, 30.00, '2026-12-31', 'active'),
('B2G1PROMO', 'Buy2Get1', 0.00, 0.00, '2026-12-31', 'active')
ON DUPLICATE KEY UPDATE `code`=`code`;

-- Default Banners
INSERT INTO `banners` (`title`, `image_url`, `link_url`, `section_type`, `status`) VALUES
('Autumn Warmth & Urban Luxury', 'images/banner1.png', 'products.php?category=women', 'Banner', 'active'),
('Timeless Classics for Gentlemen', 'images/banner2.png', 'products.php?category=men', 'Banner', 'active'),
('Curated Seasonal Collection', 'images/banner3.png', 'products.php', 'Seasonal', 'active')
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Default Reviews
INSERT INTO `reviews` (`product_id`, `customer_name`, `customer_email`, `rating`, `comment`, `admin_reply`, `status`) VALUES
(1, 'Samantha Perera', 'samantha@example.com', 5, 'The fabric quality of this floral dress is outstanding! Highly recommended.', 'Thank you so much for your kind words Samantha! We are thrilled you love it.', 'Approved'),
(2, 'Kasun Silva', 'kasun@example.com', 5, 'Tailoring and fit on this jacket are sleek and modern. Fast delivery too.', 'Thank you Kasun! Enjoy your new jacket.', 'Approved'),
(3, 'Dilshan Fernando', 'dilshan@example.com', 4, 'Very comfortable cotton shirt. Excellent quality for daily work wear.', NULL, 'Approved')
ON DUPLICATE KEY UPDATE `customer_email`=`customer_email`;

-- Default Site Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
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
('sms_template', 'Dear {name}, your purchase request for Order #{orderId} has been approved! Courier: {courier}, Tracking Number: {trackingNumber}. Thank you for choosing Anjiana!')
ON DUPLICATE KEY UPDATE `setting_value`=`setting_value`;

COMMIT;
