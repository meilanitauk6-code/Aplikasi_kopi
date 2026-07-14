-- =========================================
-- DATABASE: aplikasi_kopi
-- Aplikasi Penjualan Kopi Bubuk & Kopi Biji
-- =========================================

CREATE DATABASE IF NOT EXISTS aplikasi_kopi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aplikasi_kopi;

-- ==================
-- TABLE: users
-- ==================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','seller','buyer') NOT NULL DEFAULT 'buyer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    phone VARCHAR(20),
    avatar VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==================
-- TABLE: categories
-- ==================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================
-- TABLE: stores
-- ==================
CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(20),
    logo VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==================
-- TABLE: products
-- ==================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    type ENUM('bubuk','biji') NOT NULL DEFAULT 'bubuk',
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    weight INT DEFAULT 0 COMMENT 'gram',
    image VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_sold INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ==================
-- TABLE: promos
-- ==================
CREATE TABLE IF NOT EXISTS promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(12,2) NOT NULL,
    min_order DECIMAL(12,2) DEFAULT 0,
    max_use INT DEFAULT 0 COMMENT '0 = unlimited',
    used INT DEFAULT 0,
    expired_at DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================
-- TABLE: carts
-- ==================
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (buyer_id, product_id),
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ==================
-- TABLE: orders
-- ==================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    seller_id INT,
    promo_id INT,
    invoice VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    shipping_cost DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    shipping_name VARCHAR(150),
    shipping_phone VARCHAR(20),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_province VARCHAR(100),
    shipping_postal VARCHAR(10),
    payment_method ENUM('transfer_bank','ewallet','virtual_account','kartu_kredit') DEFAULT 'transfer_bank',
    notes TEXT,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (promo_id) REFERENCES promos(id) ON DELETE SET NULL
);

-- ==================
-- TABLE: order_items
-- ==================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(255),
    price DECIMAL(12,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ==================
-- TABLE: payments
-- ==================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    method ENUM('transfer_bank','ewallet','virtual_account','kartu_kredit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    proof VARCHAR(255),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    verified_at TIMESTAMP NULL,
    verified_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ==================
-- TABLE: shipping
-- ==================
CREATE TABLE IF NOT EXISTS shipping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    courier VARCHAR(100),
    tracking_number VARCHAR(100),
    status ENUM('pending','processing','shipped','in_transit','delivered') DEFAULT 'pending',
    estimated_date DATE,
    delivered_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ==================
-- TABLE: reviews
-- ==================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    buyer_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id)
);

-- ==================
-- TABLE: contacts
-- ==================
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    reply TEXT,
    status ENUM('unread','read','replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==================
-- TABLE: settings
-- ==================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================
-- TABLE: notifications
-- =====================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==================
-- SEED DATA
-- ==================

-- Demo accounts — login password for all three is: password
INSERT INTO users (name, email, password, role, status) VALUES
('Administrator', 'meilanitauk@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('Budi Santoso', 'penjual@kopiku.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'active'),
('Ani Rahayu', 'pembeli@kopiku.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'active');

-- Categories
INSERT INTO categories (id, name, slug, description) VALUES
(1, 'Kopi Arabika', 'kopi-arabika', 'Kopi Arabika asli pilihan dengan rasa halus dan aroma sedap'),
(2, 'Kopi Robusta', 'kopi-robusta', 'Kopi Robusta rasa kuat dan body tebal'),
(3, 'Kopi Wuetan', 'kopi-wuetan', 'Kopi Bubuk Wuetan racikan khas tradisional'),
(4, 'Kopi NTC', 'kopi-ntc', 'Kopi Bubuk NTC berkualitas premium');

INSERT INTO stores (seller_id, name, description, address, phone) VALUES
(2, 'Toko Kopi Nusantara', 'Menyediakan berbagai jenis kopi pilihan dari seluruh Nusantara', 'Jl. Soka No. 1 Gang IV.6, Denpasar Timur, Kesiman Kertalanggu', '081236547850');

-- Products
INSERT INTO products (seller_id, category_id, name, slug, type, description, price, stock, weight, rating, image) VALUES
(2, 2, 'Kopi robusta', 'kopi-robusta', 'bubuk', 'Kopi Robusta pilihan dengan cita rasa pekat, aroma yang kuat, dan tekstur yang kaya. Sangat cocok bagi pecinta kopi dengan karakter bold.', 70000.00, 100, 250, 4.60, 'kopi bubuk robusta.webp'),
(2, 1, 'Kopi Arabika', 'kopi-arabika', 'bubuk', 'Kopi Arabika asli dengan aroma floral, rasa lembut, dan aftertaste yang bersih sehingga cocok dinikmati setiap hari.', 80000.00, 100, 250, 4.80, 'kopi arabika bubuk.jpeg'),
(2, 4, 'Kopi ntc', 'kopi-ntc', 'bubuk', 'Kopi Bubuk NTC diolah dari biji kopi pilihan berkualitas tinggi. Memiliki aroma khas yang harum and rasa yang lembut.', 82000.00, 100, 250, 4.80, 'kopi bubuk Ntc.jpeg'),
(2, 3, 'Kopi wuetan', 'kopi-wuetan', 'bubuk', 'Kopi Bubuk Wuetan merupakan racikan kopi premium khas dengan aroma menggoda dan rasa seimbang di setiap seduhan.', 78000.00, 100, 250, 4.70, 'kopi bubuk wuetan.jpeg'),
(2, 2, 'Biji kopi robusta', 'biji-kopi-robusta', 'biji', 'Biji Kopi Robusta pilihan dengan karakter rasa yang kuat, body tebal, aroma khas, serta menghasilkan cita rasa kopi yang nikmat.', 75000.00, 100, 250, 4.70, 'kopi biji robusta.jpeg'),
(2, 1, 'Biji kopi Arabika', 'biji-kopi-arabika', 'biji', 'Biji Kopi Arabika dipetik dari perkebunan dataran tinggi kualitas premium. Memiliki aroma harum, rasa manis alami, dan tingkat keasaman seimbang.', 85000.00, 100, 250, 4.80, 'arabika_biji.png');

-- Promos
INSERT INTO promos (code, name, type, value, min_order, max_use, expired_at, status) VALUES
('KOPI10', 'Diskon 10% Semua Produk', 'percent', 10, 50000, 100, '2026-12-31', 'active'),
('NEWMEMBER', 'Diskon Member Baru Rp 20.000', 'fixed', 20000, 100000, 50, '2026-12-31', 'active'),
('KOPIHARI', 'Hari Kopi Nasional 25%', 'percent', 25, 75000, 30, '2026-07-31', 'active');

-- Settings
INSERT INTO settings (`key`, `value`) VALUES
('site_name', 'Mey Coffee - Toko Kopi Online'),
('site_tagline', 'Kopi Pilihan dari Seluruh Nusantara'),
('site_email', 'meilanitauk@gmail.com'),
('site_phone', '081236547850'),
('site_address', 'Jl. Soka No. 1 Gang IV.6, Denpasar Timur, Kesiman Kertalanggu'),
('bank_name', 'Bank BCA'),
('bank_account', '1234567890'),
('bank_holder', 'PT Kopi Nusantara'),
('shipping_cost', '15000'),
('currency', 'IDR');
