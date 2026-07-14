<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

$pageTitle = 'Produk Kopi - KopiKu';
$pageDesc = 'Temukan berbagai pilihan Kopi Bubuk dan Kopi Biji premium dari seluruh Nusantara';

// Filters
$search = escape($conn, $_GET['search'] ?? '');
$type = escape($conn, $_GET['type'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$sort = escape($conn, $_GET['sort'] ?? 'rating');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["p.status = 'active'", "p.stock > 0"];
if ($search) $where[] = "p.name LIKE '%$search%'";
if ($type) $where[] = "p.type = '$type'";
if ($category_id) $where[] = "p.category_id = $category_id";

$whereClause = 'WHERE ' . implode(' AND ', $where);

$orderMap = ['rating' => 'p.rating DESC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'newest' => 'p.created_at DESC', 'sold' => 'p.total_sold DESC'];
$orderBy = $orderMap[$sort] ?? 'p.rating DESC';

// Count
$totalRow = $conn->query("SELECT COUNT(*) as c FROM products p $whereClause")->fetch_assoc();
$total = (int)$totalRow['c'];
$totalPages = ceil($total / $perPage);

// Products
$products = $conn->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereClause
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");

// Categories for filter
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div style="background:var(--bg-surface);padding:24px 0;border-bottom:1px solid var(--border);">
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:1.5rem;font-weight:800;">
                    <?php if ($search): ?>🔍 Hasil pencarian: "<?= htmlspecialchars($search) ?>"
                    <?php elseif ($type === 'bubuk'): ?>☕ Kopi Bubuk
                    <?php elseif ($type === 'biji'): ?>🫘 Kopi Biji
                    <?php else: ?>☕ Semua Produk Kopi
                    <?php endif; ?>
                </h1>
                <p style="color:var(--text-muted);font-size:0.875rem;"><?= $total ?> produk ditemukan</p>
            </div>
            <!-- Search -->
            <form method="GET" style="flex:1;max-width:400px;">
                <?php if ($type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
                <?php if ($category_id): ?><input type="hidden" name="category" value="<?= $category_id ?>"><?php endif; ?>
                <div class="search-bar">
                    <input type="text" name="search" placeholder="Cari kopi..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">🔍</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:48px;">
    <div style="display:grid;grid-template-columns:220px 1fr;gap:28px;align-items:start;">
        <!-- Sidebar Filter -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:20px;position:sticky;top:88px;">
            <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:16px;">Filter Produk</h3>

            <!-- Type filter -->
            <div style="margin-bottom:20px;">
                <div style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Jenis Kopi</div>
                <?php
                $typeLinks = ['' => 'Semua Jenis', 'bubuk' => '☕ Kopi Bubuk', 'biji' => '🫘 Kopi Biji'];
                foreach ($typeLinks as $val => $label):
                    $params = $_GET; $params['type'] = $val; unset($params['page']);
                    $isActive = $type === $val;
                ?>
                <a href="?<?= http_build_query($params) ?>"
                   style="display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;color:<?= $isActive ? 'var(--primary)' : 'var(--text-secondary)' ?>;background:<?= $isActive ? 'rgba(200,112,58,.15)' : 'transparent' ?>;font-weight:<?= $isActive ? '600' : '400' ?>;margin-bottom:2px;transition:var(--transition);"
                   onmouseover="this.style.background='var(--border-light)'"
                   onmouseout="this.style.background='<?= $isActive ? 'rgba(200,112,58,.15)' : 'transparent' ?>'">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Category filter -->
            <div style="margin-bottom:20px;">
                <div style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Kategori</div>
                <?php
                $params = $_GET; $params['category'] = ''; unset($params['page']);
                $isActive = !$category_id;
                ?>
                <a href="?<?= http_build_query($params) ?>"
                   style="display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;color:<?= $isActive ? 'var(--primary)' : 'var(--text-secondary)' ?>;background:<?= $isActive ? 'rgba(200,112,58,.15)' : 'transparent' ?>;font-weight:<?= $isActive ? '600' : '400' ?>;margin-bottom:2px;">
                    Semua Kategori
                </a>
                <?php while ($cat = $categories->fetch_assoc()):
                    $params = $_GET; $params['category'] = $cat['id']; unset($params['page']);
                    $isActive = $category_id === (int)$cat['id'];
                ?>
                <a href="?<?= http_build_query($params) ?>"
                   style="display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;color:<?= $isActive ? 'var(--primary)' : 'var(--text-secondary)' ?>;background:<?= $isActive ? 'rgba(200,112,58,.15)' : 'transparent' ?>;font-weight:<?= $isActive ? '600' : '400' ?>;margin-bottom:2px;transition:var(--transition);">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
                <?php endwhile; ?>
            </div>

            <!-- Sort -->
            <div>
                <div style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Urutkan</div>
                <?php
                $sorts = ['rating' => '⭐ Rating Tertinggi', 'sold' => '🔥 Terlaris', 'newest' => '🆕 Terbaru', 'price_asc' => '💰 Harga Termurah', 'price_desc' => '💎 Harga Tertinggi'];
                foreach ($sorts as $val => $label):
                    $params = $_GET; $params['sort'] = $val; unset($params['page']);
                    $isActive = $sort === $val;
                ?>
                <a href="?<?= http_build_query($params) ?>"
                   style="display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;color:<?= $isActive ? 'var(--primary)' : 'var(--text-secondary)' ?>;background:<?= $isActive ? 'rgba(200,112,58,.15)' : 'transparent' ?>;font-weight:<?= $isActive ? '600' : '400' ?>;margin-bottom:2px;">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Grid -->
        <div>
            <?php if ($products->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">☕</div>
                <div class="empty-title">Tidak ada produk ditemukan</div>
                <div class="empty-desc">Coba ubah filter atau kata pencarian Anda</div>
                <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-primary" style="margin-top:16px;">Reset Filter</a>
            </div>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
                <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card" onclick="window.location='/Aplikasi_kopi/buyer/product_detail.php?id=<?= $product['id'] ?>'">
                    <div class="product-card-img">
                        <?php $imgUrl = imageUrl($product['image']); if ($imgUrl): ?>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;" loading="lazy">
                        <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;width:100%;aspect-ratio:1;background:var(--bg-surface);font-size:4rem;">
                            <?= $product['type'] === 'biji' ? '🫘' : '☕' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <span class="product-type-badge type-<?= $product['type'] ?>"><?= ucfirst($product['type']) ?></span>
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:6px;"><?= htmlspecialchars($product['category_name'] ?? '') ?></div>
                        <div class="product-rating">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <?= $s <= round($product['rating']) ? '⭐' : '<span style="color:#444">☆</span>' ?>
                            <?php endfor; ?>
                            <span style="color:var(--text-muted);font-size:0.75rem;"><?= number_format($product['rating'], 1) ?></span>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <div class="product-price"><?= formatRupiah($product['price']) ?></div>
                            <span style="font-size:0.72rem;color:var(--text-muted);"><?= $product['weight'] ?>g</span>
                        </div>
                        <div class="product-actions">
                            <a href="/Aplikasi_kopi/buyer/product_detail.php?id=<?= $product['id'] ?>"
                               class="btn btn-secondary btn-sm" style="flex:1;" onclick="event.stopPropagation()">Detail</a>
                            <?php if (isLoggedIn() && $_SESSION['role'] === 'buyer'): ?>
                            <a href="/Aplikasi_kopi/buyer/cart.php?action=add&product_id=<?= $product['id'] ?>&qty=1"
                               class="btn btn-primary btn-sm" style="flex:1;" onclick="event.stopPropagation()">+ Keranjang</a>
                            <?php else: ?>
                            <a href="/Aplikasi_kopi/auth/login.php" class="btn btn-primary btn-sm" style="flex:1;" onclick="event.stopPropagation()">Beli</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++):
                    $params = $_GET; $params['page'] = $p;
                ?>
                <a href="?<?= http_build_query($params) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
