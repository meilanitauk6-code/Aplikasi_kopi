<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/Aplikasi_kopi/buyer/products.php');

$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, u.name as seller_name, s.name as store_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.seller_id = u.id
    LEFT JOIN stores s ON s.seller_id = u.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) redirect('/Aplikasi_kopi/buyer/products.php');

// Reviews
$reviews = $conn->query("
    SELECT r.*, u.name as buyer_name
    FROM reviews r
    JOIN users u ON r.buyer_id = u.id
    WHERE r.product_id = $id
    ORDER BY r.created_at DESC
    LIMIT 10
");

// Similar products
$similar = $conn->query("
    SELECT * FROM products
    WHERE category_id = {$product['category_id']} AND id != $id AND status = 'active' AND stock > 0
    ORDER BY rating DESC LIMIT 4
");

$pageTitle = htmlspecialchars($product['name']) . ' - KopiKu';
$pageDesc = htmlspecialchars(substr($product['description'] ?? '', 0, 160));
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="container" style="padding:32px 20px;">
    <!-- Breadcrumb -->
    <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:24px;display:flex;align-items:center;gap:6px;">
        <a href="/Aplikasi_kopi/">Beranda</a> › 
        <a href="/Aplikasi_kopi/buyer/products.php">Produk</a> ›
        <span style="color:var(--text-primary);"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:48px;">
        <!-- Product Image -->
        <div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;overflow:hidden;aspect-ratio:1;display:flex;align-items:center;justify-content:center;">
                <?php $imgUrl = imageUrl($product['image']); if ($imgUrl): ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                     style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                <div style="font-size:8rem;opacity:0.5;"><?= $product['type'] === 'biji' ? '🫘' : '☕' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div>
            <span class="product-type-badge type-<?= $product['type'] ?>" style="font-size:0.8rem;padding:5px 14px;"><?= ucfirst($product['type']) ?></span>
            <?php if ($product['category_name']): ?>
            <span style="font-size:0.8rem;color:var(--text-muted);margin-left:6px;"><?= htmlspecialchars($product['category_name']) ?></span>
            <?php endif; ?>

            <h1 style="font-size:1.8rem;font-weight:800;margin:12px 0;"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Rating -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <div style="display:flex;gap:2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span style="font-size:1.1rem;"><?= $s <= round($product['rating']) ? '⭐' : '<span style="color:#333">☆</span>' ?></span>
                    <?php endfor; ?>
                </div>
                <span style="font-weight:700;"><?= number_format($product['rating'], 1) ?></span>
                <span style="color:var(--text-muted);font-size:0.85rem;">(<?= $reviews->num_rows ?> ulasan)</span>
                <span style="color:var(--text-muted);font-size:0.85rem;">• <?= $product['total_sold'] ?> terjual</span>
            </div>

            <!-- Price -->
            <div style="font-size:2.2rem;font-weight:800;color:var(--primary);margin-bottom:20px;">
                <?= formatRupiah($product['price']) ?>
            </div>

            <!-- Details -->
            <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.85rem;">
                    <div><span style="color:var(--text-muted);">Stok:</span> <strong><?= $product['stock'] ?> tersedia</strong></div>
                    <div><span style="color:var(--text-muted);">Berat:</span> <strong><?= $product['weight'] ?>g</strong></div>
                    <div><span style="color:var(--text-muted);">Jenis:</span> <strong><?= ucfirst($product['type']) ?></strong></div>
                    <div><span style="color:var(--text-muted);">Toko:</span> <strong><?= htmlspecialchars($product['store_name'] ?? $product['seller_name']) ?></strong></div>
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom:24px;">
                <h3 style="font-size:0.9rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Deskripsi</h3>
                <p style="color:var(--text-secondary);line-height:1.7;font-size:0.9rem;"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <!-- Add to Cart -->
            <?php if ($product['stock'] > 0): ?>
            <?php if (isLoggedIn() && $_SESSION['role'] === 'buyer'): ?>
            <form action="/Aplikasi_kopi/buyer/cart.php" method="GET" style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="qty-wrapper" style="display:flex;align-items:center;background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                    <button type="button" class="qty-minus" style="background:none;border:none;padding:10px 14px;color:var(--text-primary);cursor:pointer;font-size:1rem;">-</button>
                    <input type="number" name="qty" class="qty-input" value="1" min="1" max="<?= $product['stock'] ?>"
                           style="width:50px;background:transparent;border:none;text-align:center;color:var(--text-primary);font-family:inherit;font-weight:600;">
                    <button type="button" class="qty-plus" style="background:none;border:none;padding:10px 14px;color:var(--text-primary);cursor:pointer;font-size:1rem;">+</button>
                </div>
                <button type="submit" class="btn btn-primary" style="flex:1;">🛒 Tambah ke Keranjang</button>
            </form>
            <a href="/Aplikasi_kopi/buyer/checkout.php?buy_now=<?= $product['id'] ?>" class="btn btn-accent btn-block" style="margin-bottom:8px;">⚡ Beli Sekarang</a>
            <?php else: ?>
            <a href="/Aplikasi_kopi/auth/login.php" class="btn btn-primary btn-block btn-lg">Masuk untuk Membeli</a>
            <?php endif; ?>
            <?php else: ?>
            <div class="alert alert-danger">⚠️ Stok habis</div>
            <?php endif; ?>

            <!-- Guarantees -->
            <div style="display:flex;gap:16px;margin-top:16px;font-size:0.78rem;color:var(--text-muted);">
                <span>🔒 Pembayaran Aman</span>
                <span>🚚 Pengiriman Cepat</span>
                <span>↩️ Garansi Kualitas</span>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:28px;margin-bottom:40px;">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:20px;">Ulasan Pembeli</h2>
        <?php
        $reviews->data_seek(0);
        if ($reviews->num_rows === 0): ?>
        <div class="empty-state" style="padding:30px 0;">
            <div class="empty-icon" style="font-size:2.5rem;">💬</div>
            <div class="empty-title">Belum ada ulasan</div>
            <div class="empty-desc">Jadilah yang pertama mengulas produk ini</div>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <?php while ($rev = $reviews->fetch_assoc()): ?>
            <div style="background:var(--bg-surface);border-radius:12px;padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                            <?= strtoupper(substr($rev['buyer_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($rev['buyer_name']) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= timeAgo($rev['created_at']) ?></div>
                        </div>
                    </div>
                    <div style="color:var(--accent);">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <?= $s <= $rev['rating'] ? '⭐' : '<span style="color:#333">☆</span>' ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if ($rev['comment']): ?>
                <p style="color:var(--text-secondary);font-size:0.875rem;line-height:1.6;"><?= htmlspecialchars($rev['comment']) ?></p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Similar Products -->
    <?php if ($similar->num_rows > 0): ?>
    <div>
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:20px;">Produk Serupa</h2>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
            <?php while ($sim = $similar->fetch_assoc()): ?>
            <div class="product-card" onclick="window.location='/Aplikasi_kopi/buyer/product_detail.php?id=<?= $sim['id'] ?>'">
                <?php $simImg = imageUrl($sim['image']); ?>
                <div style="display:flex;align-items:center;justify-content:center;height:140px;background:var(--bg-surface);font-size:3rem;overflow:hidden;">
                    <?php if ($simImg): ?>
                    <img src="<?= htmlspecialchars($simImg) ?>" alt="<?= htmlspecialchars($sim['name']) ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                    <?php else: ?>
                    <?= $sim['type'] === 'biji' ? '🫘' : '☕' ?>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-name" style="font-size:0.85rem;"><?= htmlspecialchars($sim['name']) ?></div>
                    <div class="product-price" style="font-size:0.95rem;"><?= formatRupiah($sim['price']) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
