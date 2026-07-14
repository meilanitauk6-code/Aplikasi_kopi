<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

// Stats
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$totalProducts = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT SUM(total) as s FROM orders WHERE status='delivered'")->fetch_assoc()['s'] ?? 0;
$pendingPayments = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status='pending'")->fetch_assoc()['c'];
$pendingOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $conn->query("
    SELECT o.*, u.name as buyer_name
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    ORDER BY o.created_at DESC LIMIT 8
");

// Revenue by month (last 6 months)
$monthlyRevenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(total) as revenue, COUNT(*) as orders
    FROM orders WHERE status = 'delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
    ORDER BY MIN(created_at) ASC
");

// Top products
$topProducts = $conn->query("
    SELECT p.name, p.type, p.total_sold, p.price, p.rating
    FROM products p ORDER BY p.total_sold DESC LIMIT 5
");

$pageTitle = 'Dashboard Admin - KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
            <div>
                <h1 style="font-size:1.4rem;font-weight:800;">Dashboard Admin</h1>
                <p style="color:var(--text-muted);font-size:0.85rem;">Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <?php if ($pendingPayments > 0): ?>
                <a href="/Aplikasi_kopi/admin/payments.php" class="btn btn-warning btn-sm">
                    💳 <?= $pendingPayments ?> Pembayaran Pending
                </a>
                <?php endif; ?>
                <span style="font-size:0.8rem;color:var(--text-muted);"><?= date('d M Y, H:i') ?></span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">💰</div>
                <div>
                    <div class="stat-value" style="font-size:1.2rem;"><?= formatRupiah($totalRevenue) ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-change up">dari pesanan selesai</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">📦</div>
                <div>
                    <div class="stat-value" data-target="<?= $totalOrders ?>"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-change up"><?= $pendingOrders ?> pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">☕</div>
                <div>
                    <div class="stat-value" data-target="<?= $totalProducts ?>"><?= $totalProducts ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">👥</div>
                <div>
                    <div class="stat-value" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;">
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📦 Pesanan Terbaru</span>
                    <a href="/Aplikasi_kopi/admin/orders.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                </div>
                <div class="table-container" style="border:none;border-radius:0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pembeli</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $statusBadges = [
                                'pending' => 'badge-warning', 'confirmed' => 'badge-info',
                                'processing' => 'badge-info', 'shipped' => 'badge-info',
                                'delivered' => 'badge-success', 'cancelled' => 'badge-danger',
                            ];
                            while ($ord = $recentOrders->fetch_assoc()):
                            ?>
                            <tr class="searchable-row">
                                <td><code style="font-size:0.78rem;"><?= $ord['invoice'] ?></code></td>
                                <td><?= htmlspecialchars($ord['buyer_name']) ?></td>
                                <td style="font-weight:600;"><?= formatRupiah($ord['total']) ?></td>
                                <td><span class="badge <?= $statusBadges[$ord['status']] ?? 'badge-secondary' ?>"><?= ucfirst($ord['status']) ?></span></td>
                                <td style="color:var(--text-muted);font-size:0.78rem;"><?= timeAgo($ord['created_at']) ?></td>
                                <td><a href="/Aplikasi_kopi/admin/orders.php?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm">Detail</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div style="display:flex;flex-direction:column;gap:16px;">
                <!-- Top Products -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🔥 Produk Terlaris</span>
                    </div>
                    <div class="card-body" style="padding:12px;">
                        <?php while ($tp = $topProducts->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;transition:var(--transition);" onmouseover="this.style.background='var(--bg-surface)'" onmouseout="this.style.background=''">
                            <div style="width:36px;height:36px;background:rgba(200,112,58,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <?= $tp['type'] === 'biji' ? '🫘' : '☕' ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($tp['name']) ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= $tp['total_sold'] ?> terjual • ⭐<?= $tp['rating'] ?></div>
                            </div>
                            <div style="font-size:0.82rem;font-weight:700;color:var(--primary);"><?= formatRupiah($tp['price']) ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header"><span class="card-title">⚡ Aksi Cepat</span></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
                        <a href="/Aplikasi_kopi/admin/products.php?action=add" class="btn btn-primary btn-sm btn-block">+ Tambah Produk</a>
                        <a href="/Aplikasi_kopi/admin/users.php" class="btn btn-secondary btn-sm btn-block">👥 Kelola Pengguna</a>
                        <a href="/Aplikasi_kopi/admin/payments.php" class="btn btn-warning btn-sm btn-block">💳 Verifikasi Pembayaran <?= $pendingPayments > 0 ? "($pendingPayments)" : '' ?></a>
                        <a href="/Aplikasi_kopi/admin/reports.php" class="btn btn-info btn-sm btn-block">📊 Laporan Penjualan</a>
                    </div>
                </div>

                <!-- Monthly Summary -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📈 Pendapatan Bulanan</span></div>
                    <div class="card-body" style="padding:12px;">
                        <?php $monthlyRevenue->data_seek(0); while ($mr = $monthlyRevenue->fetch_assoc()): ?>
                        <div style="margin-bottom:10px;">
                            <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:3px;">
                                <span style="color:var(--text-muted);"><?= $mr['month'] ?></span>
                                <span style="font-weight:600;"><?= formatRupiah($mr['revenue']) ?></span>
                            </div>
                            <div style="height:6px;background:var(--bg-surface);border-radius:3px;overflow:hidden;">
                                <div style="height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-light));width:<?= min(100, ($mr['revenue'] / max($totalRevenue, 1)) * 100) ?>%;border-radius:3px;transition:width 1s ease;"></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
