<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('seller');

$sellerId = (int)$_SESSION['user_id'];

// Stats
$myProducts = $conn->query("SELECT COUNT(*) as c FROM products WHERE seller_id = $sellerId")->fetch_assoc()['c'];
$myOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE seller_id = $sellerId")->fetch_assoc()['c'];
$pendingOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE seller_id = $sellerId AND status='pending'")->fetch_assoc()['c'];
$myRevenue = $conn->query("SELECT SUM(total) as s FROM orders WHERE seller_id = $sellerId AND status='delivered'")->fetch_assoc()['s'] ?? 0;
$myStock = $conn->query("SELECT SUM(stock) as s FROM products WHERE seller_id = $sellerId AND status='active'")->fetch_assoc()['s'] ?? 0;

// Recent orders
$recentOrders = $conn->query("
    SELECT o.*, u.name as buyer_name
    FROM orders o JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = $sellerId
    ORDER BY o.created_at DESC LIMIT 6
");

// Low stock alerts
$lowStock = $conn->query("SELECT * FROM products WHERE seller_id = $sellerId AND stock <= 10 AND status='active' ORDER BY stock ASC LIMIT 5");

$pageTitle = 'Dashboard Penjual - KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_seller.php'; ?>
    <main class="main-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
            <div>
                <h1 style="font-size:1.4rem;font-weight:800;">Dashboard Penjual</h1>
                <p style="color:var(--text-muted);font-size:0.85rem;">Halo, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</p>
            </div>
            <a href="/Aplikasi_kopi/seller/products.php?action=add" class="btn btn-primary">+ Tambah Produk</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">💰</div>
                <div>
                    <div class="stat-value" style="font-size:1.1rem;"><?= formatRupiah($myRevenue) ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">📦</div>
                <div>
                    <div class="stat-value"><?= $myOrders ?></div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-change <?= $pendingOrders > 0 ? 'up' : '' ?>"><?= $pendingOrders ?> pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">☕</div>
                <div>
                    <div class="stat-value"><?= $myProducts ?></div>
                    <div class="stat-label">Produk Saya</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">📊</div>
                <div>
                    <div class="stat-value"><?= $myStock ?></div>
                    <div class="stat-label">Total Stok</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📦 Pesanan Terbaru</span>
                    <a href="/Aplikasi_kopi/seller/orders.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                </div>
                <div class="table-container" style="border:none;border-radius:0;">
                    <table>
                        <thead><tr><th>Invoice</th><th>Pembeli</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php
                            $sBadges = ['pending'=>'badge-warning','confirmed'=>'badge-info','processing'=>'badge-info','shipped'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
                            while ($ord = $recentOrders->fetch_assoc()):
                            ?>
                            <tr>
                                <td><code style="font-size:0.78rem;"><?= $ord['invoice'] ?></code></td>
                                <td style="font-size:0.875rem;"><?= htmlspecialchars($ord['buyer_name']) ?></td>
                                <td style="font-weight:600;"><?= formatRupiah($ord['total']) ?></td>
                                <td><span class="badge <?= $sBadges[$ord['status']] ?? 'badge-secondary' ?>"><?= ucfirst($ord['status']) ?></span></td>
                                <td><a href="/Aplikasi_kopi/seller/orders.php?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm">Detail</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right column -->
            <div style="display:flex;flex-direction:column;gap:16px;">
                <!-- Low Stock -->
                <?php if ($lowStock->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">⚠️ Stok Menipis</span>
                    </div>
                    <div class="card-body" style="padding:12px;">
                        <?php while ($ls = $lowStock->fetch_assoc()): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;" onmouseover="this.style.background='var(--bg-surface)'" onmouseout="this.style.background=''">
                            <div style="font-size:0.85rem;font-weight:600;"><?= htmlspecialchars(substr($ls['name'],0,25)) ?>...</div>
                            <span class="badge badge-danger"><?= $ls['stock'] ?> sisa</span>
                        </div>
                        <?php endwhile; ?>
                        <a href="/Aplikasi_kopi/seller/products.php" class="btn btn-warning btn-block btn-sm" style="margin-top:10px;">Kelola Stok</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header"><span class="card-title">⚡ Aksi Cepat</span></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
                        <a href="/Aplikasi_kopi/seller/products.php?action=add" class="btn btn-primary btn-sm btn-block">+ Tambah Produk</a>
                        <?php if ($pendingOrders > 0): ?>
                        <a href="/Aplikasi_kopi/seller/orders.php?status=pending" class="btn btn-warning btn-sm btn-block">📦 <?= $pendingOrders ?> Pesanan Pending</a>
                        <?php endif; ?>
                        <a href="/Aplikasi_kopi/seller/shipping.php" class="btn btn-info btn-sm btn-block">🚚 Kelola Pengiriman</a>
                        <a href="/Aplikasi_kopi/seller/profile.php" class="btn btn-secondary btn-sm btn-block">🏪 Profil Toko</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
