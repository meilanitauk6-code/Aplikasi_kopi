<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

// Direct database modification script.
echo "<h2>Mengupdate Produk & Alamat Toko Kopi</h2>";

// 1. Update Settings Address
$address = 'Jl. Soka No. 1 Gang IV.6, Denpasar Timur, Kesiman Kertalanggu';
$conn->query("UPDATE settings SET `value` = '$address' WHERE `key` = 'site_address'");
echo "✅ Setting alamat situs berhasil diperbarui.<br>";

// 2. Update Seller Store Address
$sellerRow = $conn->query("SELECT id FROM users WHERE role='seller' LIMIT 1")->fetch_assoc();
$sellerId = $sellerRow['id'] ?? 2;

$conn->query("UPDATE stores SET address = '$address', phone = '081236547850' WHERE seller_id = $sellerId");
echo "✅ Alamat Toko penjual berhasil diperbarui.<br>";

// 3. Update Seller User Address
$conn->query("UPDATE users SET address = '$address', phone = '081236547850' WHERE id = $sellerId");
echo "✅ Alamat User penjual berhasil diperbarui.<br>";

// 4. Update Categories
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
$conn->query("DELETE FROM categories;");
$categories = [
    ['Kopi Arabika', 'kopi-arabika', 'Kopi Arabika asli pilihan dengan rasa halus dan aroma sedap'],
    ['Kopi Robusta', 'kopi-robusta', 'Kopi Robusta rasa kuat dan body tebal'],
    ['Kopi Wuetan', 'kopi-wuetan', 'Kopi Bubuk Wuetan racikan khas tradisional'],
    ['Kopi NTC', 'kopi-ntc', 'Kopi Bubuk NTC berkualitas premium'],
];

foreach ($categories as [$name, $slug, $desc]) {
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $slug, $desc);
    if ($stmt->execute()) {
        echo "✅ Kategori ditambahkan: $name<br>";
    } else {
        echo "❌ Gagal menambahkan kategori $name: " . $conn->error . "<br>";
    }
}

// Fetch new category IDs
$catIds = [];
$r = $conn->query("SELECT id, slug FROM categories");
while ($c = $r->fetch_assoc()) {
    $catIds[$c['slug']] = $c['id'];
}

// 5. Recreate Products
$conn->query("DELETE FROM products;");

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
    $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, name, slug, type, description, price, stock, weight, rating, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssdiids", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10]);
    if ($stmt->execute()) {
        echo "✅ Produk ditambahkan: {$p[2]}<br>";
    } else {
        echo "❌ Gagal menambahkan produk {$p[2]}: " . $conn->error . "<br>";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "<br><strong style='color:green;'>✅ Semua pembaruan produk dan alamat berhasil dilakukan!</strong>";
echo "<br><br><a href='/Aplikasi_kopi/' style='background:#c8703a;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;'>← Kembali ke Beranda</a>";
$conn->close();
?>
