<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

$pageTitle = 'Mey Coffee - Toko Kopi Online';
$pageDesc = 'Belanja Kopi berkualitas premium. Tersedia Kopi robusta, Kopi Arabika, Kopi ntc, Kopi wuetan, Biji kopi robusta, dan Biji kopi Arabika.';

// Fetch featured products
$featuredProducts = $conn->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' AND p.stock > 0
    ORDER BY p.rating DESC, p.total_sold DESC
    LIMIT 8
");

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch stats
$totalProducts = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
$totalSellers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='seller' AND status='active'")->fetch_assoc()['c'];
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">

            <div class="hero-content">

                <div class="hero-badge">
                    ☕ Kopi Asli Manggarai
                </div>

                <h1>
                    Nikmati Cita Rasa
                    <span>Kopi Pilihan</span>
                </h1>

                <p>
                    Mey Coffee menghadirkan berbagai pilihan kopi pilihan berkualitas tinggi yang diproses dari biji kopi premium. Tersedia Kopi robusta, Kopi Arabika, Kopi ntc, Kopi wuetan, Biji kopi robusta, dan Biji kopi Arabika.
                </p>

                <div class="hero-actions">

                    <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-accent btn-lg">
                        🛒 Belanja Sekarang
                    </a>

                    <a href="/Aplikasi_kopi/buyer/products.php?type=biji" class="btn btn-outline btn-lg" style="border-color:rgba(251,248,243,0.3);color:rgba(251,248,243,0.9);">
                        Lihat Kopi Biji
                    </a>

                </div>

                <div style="display:flex;gap:32px;margin-top:36px;">

                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent-light);font-family:'Plus Jakarta Sans',sans-serif;">
                            <?= $totalProducts ?>+
                        </div>
                        <div style="font-size:.8rem;color:rgba(251,248,243,0.5);">
                            Produk Kopi
                        </div>
                    </div>

                    <div style="width:1px;background:rgba(251,248,243,0.15);"></div>

                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent-light);font-family:'Plus Jakarta Sans',sans-serif;">
                            <?= $totalOrders ?>+
                        </div>
                        <div style="font-size:.8rem;color:rgba(251,248,243,0.5);">
                            Pesanan
                        </div>
                    </div>

                    <div style="width:1px;background:rgba(251,248,243,0.15);"></div>

                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent-light);font-family:'Plus Jakarta Sans',sans-serif;">
                            4.9★
                        </div>
                        <div style="font-size:.8rem;color:rgba(251,248,243,0.5);">
                            Rating
                        </div>
                    </div>

                </div>

            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">

                <div
                    style="background:rgba(251,248,243,0.06);border:1px solid rgba(251,248,243,0.1);border-radius:20px;padding:24px;display:flex;align-items:center;gap:16px;backdrop-filter:blur(8px);">

                    <div
                        style="width:56px;height:56px;background:rgba(193,127,89,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;">
                        🫘
                    </div>

                    <div>
                        <div style="font-weight:700;color:#FBF8F3;">
                            Biji kopi Arabika
                        </div>

                        <div style="color:var(--accent-light);font-weight:700;">
                            Rp 85.000
                        </div>

                        <div style="color:var(--gold);font-size:.8rem;">
                            ⭐⭐⭐⭐⭐ 4.9
                        </div>
                    </div>

                    <div style="margin-left:auto;">
                        <span style="background:rgba(193,127,89,0.15);color:var(--accent-light);padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">
                            Terlaris
                        </span>
                    </div>

                </div>

                <div
                    style="background:rgba(251,248,243,0.06);border:1px solid rgba(251,248,243,0.1);border-radius:20px;padding:24px;display:flex;align-items:center;gap:16px;backdrop-filter:blur(8px);">

                    <div
                        style="width:56px;height:56px;background:rgba(193,127,89,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;">
                        ☕
                    </div>

                    <div>
                        <div style="font-weight:700;color:#FBF8F3;">
                            Kopi wuetan
                        </div>

                        <div style="color:var(--accent-light);font-weight:700;">
                            Rp 78.000
                        </div>

                        <div style="color:var(--gold);font-size:.8rem;">
                            ⭐⭐⭐⭐⭐ 4.9
                        </div>
                    </div>

                    <div style="margin-left:auto;">
                        <span style="background:rgba(196,132,29,0.15);color:var(--gold);padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">
                            Premium
                        </span>
                    </div>

                </div>

                <div
                    style="background:linear-gradient(135deg,rgba(193,127,89,0.15),rgba(74,44,42,0.3));border:1px solid rgba(251,248,243,0.1);border-radius:20px;padding:24px;text-align:center;backdrop-filter:blur(8px);">

                    <div style="font-size:1rem;font-weight:600;margin-bottom:6px;color:#FBF8F3;">
                        ☕ Produk Unggulan
                    </div>

                    <div style="color:var(--accent-light);font-weight:700;font-size:1.2rem;">
                        Kopi Pilihan Premium
                    </div>

                    <div style="color:rgba(251,248,243,0.5);font-size:.8rem;margin-top:4px;">
                        Robusta • Arabika • NTC • Wuetan
                    </div>

                </div>

            </div>

        </div>
    </div>
</section>
<!-- CATEGORIES -->
<section style="padding:60px 0;background:var(--cream-dark);">
    <div class="container">

        <div class="section-header">
            <div>
                <h2 class="section-title">Kategori Produk</h2>
                <p class="section-subtitle">
                    Pilih varian Kopi Manggarai favorit Anda
                </p>
            </div>
        </div>

        <style>
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        <div class="categories-grid">

            <?php
            $catIcons = ['🫘', '☕', '🌱', '⭐', '🎁'];
            $i = 0;

            $categories->data_seek(0);

            while ($cat = $categories->fetch_assoc()):
                $icon = $catIcons[$i % count($catIcons)];
                $i++;
                ?>

                <a href="/Aplikasi_kopi/buyer/products.php?category=<?= $cat['id'] ?>" style="background:var(--bg-card);
                border:1px solid var(--border-light);
                border-radius:16px;
                padding:24px 16px;
                text-align:center;
                text-decoration:none;
                box-shadow:var(--shadow);
                transition:var(--transition);">

                    <div style="font-size:2.2rem;margin-bottom:10px;">
                        <?= $icon ?>
                    </div>

                    <div style="font-weight:600;color:var(--primary);">
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>

                </a>

            <?php endwhile; ?>

        </div>

    </div>
</section>


<!-- PRODUCT -->
<section style="padding:60px 0;">

    <div class="container">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">

            <div>

                <h2 class="section-title">
                    Produk Pilihan
                </h2>

                <p class="section-subtitle">
                    Kopi khas Manggarai dengan kualitas terbaik.
                </p>

            </div>

            <div style="display:flex;gap:10px;">

                <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-secondary btn-sm">
                    Semua
                </a>

                <a href="/Aplikasi_kopi/buyer/products.php?type=biji" class="btn btn-secondary btn-sm">
                    Kopi Biji
                </a>

                <a href="/Aplikasi_kopi/buyer/products.php?type=bubuk" class="btn btn-secondary btn-sm">
                    Bubuk Kopi
                </a>

                <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-primary btn-sm">
                    Lihat Semua →
                </a>

            </div>

        </div>

        <!-- Featured products grid -->
        <?php if ($featuredProducts && $featuredProducts->num_rows > 0): ?>
        <style>
        .featured-products-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 18px;
        }
        @media (max-width: 1200px) {
            .featured-products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .featured-products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .featured-products-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <div class="featured-products-grid">
            <?php while ($fp = $featuredProducts->fetch_assoc()): ?>
            <div class="product-card" onclick="window.location='/Aplikasi_kopi/buyer/product_detail.php?id=<?= $fp['id'] ?>'">
                <div class="product-card-img">
                    <?php $fpImg = imageUrl($fp['image']); if ($fpImg): ?>
                    <img src="<?= htmlspecialchars($fpImg) ?>" alt="<?= htmlspecialchars($fp['name']) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;" loading="lazy">
                    <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;width:100%;aspect-ratio:1;background:var(--cream-dark);font-size:3.5rem;">
                        <?= $fp['type'] === 'biji' ? '🫘' : '☕' ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <span class="product-type-badge type-<?= $fp['type'] ?>"><?= ucfirst($fp['type']) ?></span>
                    <div class="product-name"><?= htmlspecialchars($fp['name']) ?></div>
                    <div class="product-rating">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <?= $s <= round($fp['rating']) ? '⭐' : '<span style="color:var(--border)">☆</span>' ?>
                        <?php endfor; ?>
                        <span style="color:var(--text-muted);font-size:0.75rem;"><?= number_format($fp['rating'], 1) ?></span>
                    </div>
                    <div class="product-price" style="margin-top:6px;"><?= formatRupiah($fp['price']) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- WHY CHOOSE US -->

        <section style="padding:60px 0;">

            <div class="container">

                <div style="text-align:center;margin-bottom:40px;">

                    <h2 style="font-size:1.8rem;">

                        Mengapa Pilih
                        <span style="color:var(--accent);">
                            Mey Coffee?
                        </span>

                    </h2>

                    <p style="color:var(--text-muted);margin-top:8px;">

                        Nikmati cita rasa khas Kopi Manggarai dengan kualitas terbaik.

                    </p>

                </div>

                <?php

                $features = [

                    [
                        '🌱',
                        '100% Kopi Berkualitas',
                        'Diolah dari biji kopi pilihan hasil perkebunan Nusantara.'
                    ],

                    [
                        '☕',
                        'Beragam Varian',
                        'Tersedia Kopi robusta, Kopi Arabika, Kopi ntc, Kopi wuetan, Biji kopi robusta, dan Biji kopi Arabika.'
                    ],

                    [
                        '📦',
                        'Pengemasan Aman',
                        'Produk dikemas dengan baik agar aroma dan kualitas kopi tetap terjaga.'
                    ],

                    [
                        '⭐',
                        'Kualitas Terbaik',
                        'Dipilih dari biji kopi berkualitas dengan cita rasa khas Manggarai.'
                    ]

                ];

                ?>

                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;">

                    <?php foreach ($features as $f): ?>

                        <div
                            style="text-align:center;padding:28px 20px;background:var(--bg-card);border:1px solid var(--border-light);border-radius:16px;box-shadow:var(--shadow);">

                            <div style="font-size:2.5rem;margin-bottom:15px;">
                                <?= $f[0] ?>
                            </div>

                            <div style="font-weight:700;margin-bottom:10px;color:var(--primary);">
                                <?= $f[1] ?>
                            </div>

                            <div style="font-size:.85rem;color:var(--text-muted);line-height:1.6;">
                                <?= $f[2] ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>



        <!-- PROMO -->

        <section style="padding:60px 0;">

            <div class="container">

                <div style="background:linear-gradient(135deg,var(--primary),#2D1B19);
padding:50px;
border-radius:24px;
display:flex;
justify-content:space-between;
align-items:center;">

                    <div>

                        <div style="color:var(--accent-light);font-weight:700;margin-bottom:10px;">
                            ☕ Kopi Nusantara
                        </div>

                        <h2 style="font-size:2rem;margin-bottom:10px;color:#FBF8F3;">
                            Rasakan Aroma Kopi Asli Pilihan
                        </h2>

                        <p style="color:rgba(251,248,243,0.6);margin-bottom:20px;max-width:600px;">

                            Nikmati berbagai pilihan Kopi robusta, Kopi Arabika, Kopi ntc, Kopi wuetan, Biji kopi robusta, dan Biji kopi Arabika dengan kualitas terbaik.

                        </p>

                        <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-accent btn-lg">

                            Belanja Sekarang →

                        </a>

                    </div>

                    <div style="font-size:6rem;opacity:.15;color:#FBF8F3;">
                        ☕
                    </div>

                </div>

            </div>

        </section>

        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>