<?php
/**
 * Setup Script - Run once to initialize database
 * Access: http://localhost/Aplikasi_kopi/setup.php
 */

// Direct DB connection without session
$conn = new mysqli('127.0.0.1', 'root', '', '', 3307);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee;color:#c00;">
        <h2>❌ Koneksi MySQL Gagal</h2>
        <p>Error: ' . $conn->connect_error . '</p>
        <p>Pastikan MySQL XAMPP sudah berjalan!</p>
    </div>');
}

// Refuse to run once the app is already installed (prevents public abuse on a live site).
$installed = @$conn->query("SELECT COUNT(*) AS c FROM aplikasi_kopi.users");
if ($installed && (int) $installed->fetch_assoc()['c'] > 0) {
    die('<div style="font-family:sans-serif;padding:24px;max-width:560px;margin:40px auto;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;color:#166534;">
        <h2>✅ Aplikasi sudah terpasang</h2>
        <p>Database sudah berisi data. Untuk keamanan, <strong>hapus file <code>setup.php</code></strong> dari server.</p>
        <p><a href="/Aplikasi_kopi/">← Ke Beranda</a></p>
    </div>');
}

$log = [];
$errors = [];

function runSQL($conn, $sql, $desc, &$log, &$errors)
{
    if ($conn->query($sql)) {
        $log[] = "✅ $desc";
    } else {
        $errors[] = "❌ $desc: " . $conn->error;
    }
}

// Create database
runSQL($conn, "CREATE DATABASE IF NOT EXISTS aplikasi_kopi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", "Membuat database", $log, $errors);
runSQL($conn, "USE aplikasi_kopi", "Menggunakan database", $log, $errors);
$conn->select_db('aplikasi_kopi');

// Create tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
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
    )",
    "categories" => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "stores" => "CREATE TABLE IF NOT EXISTS stores (
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
    )",
    "products" => "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        category_id INT,
        name VARCHAR(200) NOT NULL,
        slug VARCHAR(200) NOT NULL UNIQUE,
        type ENUM('bubuk','biji') NOT NULL DEFAULT 'bubuk',
        description TEXT,
        price DECIMAL(12,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        weight INT DEFAULT 0,
        image VARCHAR(255),
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_sold INT DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "promos" => "CREATE TABLE IF NOT EXISTS promos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        value DECIMAL(12,2) NOT NULL,
        min_order DECIMAL(12,2) DEFAULT 0,
        max_use INT DEFAULT 0,
        used INT DEFAULT 0,
        expired_at DATE,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "carts" => "CREATE TABLE IF NOT EXISTS carts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cart (buyer_id, product_id),
        FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )",
    "orders" => "CREATE TABLE IF NOT EXISTS orders (
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
        FOREIGN KEY (seller_id) REFERENCES users(id)
    )",
    "order_items" => "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        product_name VARCHAR(200) NOT NULL,
        product_image VARCHAR(255),
        price DECIMAL(12,2) NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )",
    "payments" => "CREATE TABLE IF NOT EXISTS payments (
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
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )",
    "shipping" => "CREATE TABLE IF NOT EXISTS shipping (
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
    )",
    "reviews" => "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        buyer_id INT NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_review (order_id, product_id)
    )",
    "contacts" => "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        reply TEXT,
        status ENUM('unread','read','replied') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "settings" => "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(100) NOT NULL UNIQUE,
        `value` TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT,
        type VARCHAR(50) DEFAULT 'info',
        is_read TINYINT DEFAULT 0,
        url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
];

foreach ($tables as $name => $sql) {
    runSQL($conn, $sql, "Membuat tabel: $name", $log, $errors);
}

// Generate proper hashed passwords
$password = password_hash('password', PASSWORD_DEFAULT);
$conn->query("DELETE FROM users");

// Insert users
$users = [
    ['Administrator', 'meilanitauk@gmail.com', 'admin'],
    ['Budi Santoso', 'penjual@kopiku.com', 'seller'],
    ['Ani Rahayu', 'pembeli@kopiku.com', 'buyer'],
];

foreach ($users as [$name, $email, $role]) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    $stmt->execute();
    $log[] = "✅ User: $name ($role)";
}

// Categories
$conn->query("DELETE FROM categories");
$categories = [
    ['Kopi Arabika', 'kopi-arabika', 'Kopi Arabika asli pilihan dengan rasa halus dan aroma sedap'],
    ['Kopi Robusta', 'kopi-robusta', 'Kopi Robusta rasa kuat dan body tebal'],
    ['Kopi Wuetan', 'kopi-wuetan', 'Kopi Bubuk Wuetan racikan khas tradisional'],
    ['Kopi NTC', 'kopi-ntc', 'Kopi Bubuk NTC berkualitas premium'],
];

foreach ($categories as [$name, $slug, $desc]) {
    $conn->query("INSERT IGNORE INTO categories (name, slug, description) VALUES ('$name', '$slug', '$desc')");
}
$log[] = "✅ Kategori: " . count($categories) . " kategori ditambahkan";

// Seller ID
$sellerRow = $conn->query("SELECT id FROM users WHERE role='seller' LIMIT 1")->fetch_assoc();
$sellerId = $sellerRow['id'] ?? 2;

// Store
$conn->query("INSERT IGNORE INTO stores (seller_id, name, description, address, phone) VALUES ($sellerId, 'Toko Kopi Nusantara', 'Menyediakan berbagai jenis kopi pilihan dari seluruh Nusantara', 'Jl. Soka No. 1 Gang IV.6, Denpasar Timur, Kesiman Kertalanggu', '081236547850')");
$log[] = "✅ Toko penjual dibuat";

// Products
$conn->query("DELETE FROM products");
$catIds = [];
$r = $conn->query("SELECT id, slug FROM categories");
while ($c = $r->fetch_assoc())
    $catIds[$c['slug']] = $c['id'];

$products = [
    [
        $sellerId,
        $catIds['kopi-robusta'] ?? 2,
        'Kopi robusta',
        'kopi-robusta',
        'bubuk',
        'Kopi Robusta pilihan dengan cita rasa pekat, aroma yang kuat, dan tekstur yang kaya. Sangat cocok bagi pecinta kopi dengan karakter bold.',
        70000,
        100,
        250,
        4.6,
        'kopi bubuk robusta.webp'
    ],
    [
        $sellerId,
        $catIds['kopi-arabika'] ?? 1,
        'Kopi Arabika',
        'kopi-arabika',
        'bubuk',
        'Kopi Arabika asli dengan aroma floral, rasa lembut, dan aftertaste yang bersih sehingga cocok dinikmati setiap hari.',
        80000,
        100,
        250,
        4.8,
        'kopi arabika bubuk.jpeg'
    ],
    [
        $sellerId,
        $catIds['kopi-ntc'] ?? 4,
        'Kopi ntc',
        'kopi-ntc',
        'bubuk',
        'Kopi Bubuk NTC diolah dari biji kopi pilihan berkualitas tinggi. Memiliki aroma khas yang harum dan rasa yang lembut.',
        82000,
        100,
        250,
        4.8,
        'kopi bubuk Ntc.jpeg'
    ],
    [
        $sellerId,
        $catIds['kopi-wuetan'] ?? 3,
        'Kopi wuetan',
        'kopi-wuetan',
        'bubuk',
        'Kopi Bubuk Wuetan merupakan racikan kopi premium khas dengan aroma menggoda dan rasa seimbang di setiap seduhan.',
        78000,
        100,
        250,
        4.7,
        'kopi bubuk wuetan.jpeg'
    ],
    [
        $sellerId,
        $catIds['kopi-robusta'] ?? 2,
        'Biji kopi robusta',
        'biji-kopi-robusta',
        'biji',
        'Biji Kopi Robusta pilihan dengan karakter rasa yang kuat, body tebal, aroma khas, serta menghasilkan cita rasa kopi yang nikmat.',
        75000,
        100,
        250,
        4.7,
        'kopi biji robusta.jpeg'
    ],
    [
        $sellerId,
        $catIds['kopi-arabika'] ?? 1,
        'Biji kopi Arabika',
        'biji-kopi-arabika',
        'biji',
        'Biji Kopi Arabika dipetik dari perkebunan dataran tinggi kualitas premium. Memiliki aroma harum, rasa manis alami, dan tingkat keasaman seimbang.',
        85000,
        100,
        250,
        4.8,
        'arabika_biji.png'
    ],
];
foreach ($products as $p) {
    $stmt = $conn->prepare("INSERT IGNORE INTO products (seller_id, category_id, name, slug, type, description, price, stock, weight, rating, image) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iiisssdiids", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10]);
    $stmt->execute();
}
$log[] = "✅ Produk: " . count($products) . " produk ditambahkan";

// Promos
$conn->query("DELETE FROM promos");
$conn->query("INSERT INTO promos (code, name, type, value, min_order, max_use, expired_at, status) VALUES
    ('KOPI10', 'Diskon 10% Semua Produk', 'percent', 10, 50000, 100, '2026-12-31', 'active'),
    ('NEWMEMBER', 'Diskon Member Baru Rp 20.000', 'fixed', 20000, 100000, 50, '2026-12-31', 'active'),
    ('KOPIHARI', 'Hari Kopi Nasional 25%', 'percent', 25, 75000, 30, '2026-12-31', 'active')");
$log[] = "✅ Promo: 3 kode promo ditambahkan";

// Settings
$conn->query("DELETE FROM settings");
$settings = [
    'site_name' => 'Mey Coffee - Toko Kopi Online',
    'site_tagline' => 'Kopi Pilihan dari Seluruh Nusantara',
    'site_email' => 'meilanitauk@gmail.com',
    'site_phone' => '081236547850',
    'site_address' => 'Jl. soka gang IV 6 no 1 Bali, Denpasar Timur, Kesiman Kertalanggu',
    'bank_name' => 'Bank BCA',
    'bank_account' => '1234567890',
    'bank_holder' => 'PT Kopi Nusantara',
    'shipping_cost' => '15000',
    'currency' => 'IDR',
];
foreach ($settings as $key => $value) {
    $conn->query("INSERT INTO settings (`key`, `value`) VALUES ('$key', '$value')");
}
$log[] = "✅ Pengaturan sistem ditambahkan";

// Create upload directories
$projectRoot = dirname(__FILE__);
$dirs = [
    $projectRoot . '/uploads/',
    $projectRoot . '/uploads/products/',
    $projectRoot . '/uploads/payments/',
    $projectRoot . '/uploads/stores/',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $log[] = "✅ Folder dibuat: " . basename(dirname($dir)) . '/' . basename($dir);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Setup - Aplikasi Kopi</title>
    <style>
        body {
            font-family: sans-serif;
            background: #0f0a07;
            color: #f5ede4;
            padding: 40px;
            max-width: 680px;
            margin: 0 auto;
        }

        h1 {
            color: #c8703a;
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .log {
            background: #1a1008;
            border: 1px solid rgba(200, 112, 58, .2);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .log p {
            margin: 4px 0;
            font-size: 0.9rem;
        }

        .error {
            color: #e74c3c;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #c8703a, #a0522d);
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 16px;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <h1>☕ Setup Aplikasi Kopi</h1>
    <p>Inisialisasi database dan data awal</p>

    <div class="log">
        <?php
        foreach ($log as $l)
            echo "<p>$l</p>";
        foreach ($errors as $e)
            echo "<p class='error'>$e</p>";
        ?>
    </div>

    <?php if (empty($errors)): ?>
        <div
            style="background:rgba(46,204,113,.12);border:1px solid rgba(46,204,113,.3);border-radius:12px;padding:20px;margin-bottom:20px;">
            <h3 style="color:#2ecc71;margin-bottom:12px;">✅ Setup Berhasil!</h3>
            <table style="width:100%;font-size:0.9rem;border-collapse:collapse;">
                <tr>
                    <th style="text-align:left;padding:6px 10px;color:#b5977e;">Role</th>
                    <th style="text-align:left;padding:6px 10px;color:#b5977e;">Email</th>
                    <th style="text-align:left;padding:6px 10px;color:#b5977e;">Password</th>
                </tr>
                <tr>
                    <td style="padding:6px 10px;">Admin</td>
                    <td style="padding:6px 10px;">admin@kopiku.com</td>
                    <td style="padding:6px 10px;">password</td>
                </tr>
                <tr>
                    <td style="padding:6px 10px;">Penjual</td>
                    <td style="padding:6px 10px;">penjual@kopiku.com</td>
                    <td style="padding:6px 10px;">password</td>
                </tr>
                <tr>
                    <td style="padding:6px 10px;">Pembeli</td>
                    <td style="padding:6px 10px;">pembeli@kopiku.com</td>
                    <td style="padding:6px 10px;">password</td>
                </tr>
            </table>
        </div>
        <a href="/Aplikasi_kopi/" class="btn">🏠 Buka Aplikasi</a>
        <a href="/Aplikasi_kopi/auth/login.php" class="btn" style="background:linear-gradient(135deg,#2ecc71,#27ae60);">🔐
            Login Sekarang</a>
    <?php else: ?>
        <div style="background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);border-radius:12px;padding:16px;">
            <h3 style="color:#e74c3c;">❌ Ada Error</h3>
            <p>Pastikan MySQL XAMPP sudah berjalan dan coba lagi.</p>
        </div>
    <?php endif; ?>
</body>

</html>