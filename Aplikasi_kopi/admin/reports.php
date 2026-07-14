<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

// Date filter
$startDate = escape($conn, $_GET['start'] ?? date('Y-m-01'));
$endDate = escape($conn, $_GET['end'] ?? date('Y-m-d'));
$groupBy = escape($conn, $_GET['group'] ?? 'day');

// Summary stats
$stats = $conn->query("
    SELECT
        COUNT(*) as total_orders,
        SUM(total) as total_revenue,
        SUM(subtotal) as total_subtotal,
        SUM(discount) as total_discount,
        SUM(shipping_cost) as total_shipping,
        AVG(total) as avg_order
    FROM orders
    WHERE status = 'delivered' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
")->fetch_assoc();

// Orders by status in period
$byStatus = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total) as revenue
    FROM orders WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY status
");

// Revenue by period
$groupExpr = $groupBy === 'month' ? "DATE_FORMAT(created_at,'%Y-%m')" : "DATE(created_at)";
$groupLabel = $groupBy === 'month' ? "DATE_FORMAT(created_at,'%b %Y')" : "DATE_FORMAT(created_at,'%d %b')";
$revenueData = $conn->query("
    SELECT $groupLabel as label, COUNT(*) as orders, SUM(total) as revenue
    FROM orders WHERE status = 'delivered' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY $groupExpr, $groupLabel ORDER BY MIN(created_at)
");

// Top selling products
$topProducts = $conn->query("
    SELECT p.name, p.type, SUM(oi.quantity) as qty_sold, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered' AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.id, p.name, p.type ORDER BY qty_sold DESC LIMIT 10
");

// Top buyers
$topBuyers = $conn->query("
    SELECT u.name, COUNT(o.id) as orders, SUM(o.total) as spent
    FROM orders o JOIN users u ON o.buyer_id = u.id
    WHERE o.status = 'delivered' AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY u.id, u.name ORDER BY spent DESC LIMIT 5
");

$pageTitle = 'Laporan Penjualan - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div><h1 class="section-title">📈 Laporan Penjualan</h1></div>
            <button onclick="window.print()" id="printInvoice" class="btn btn-secondary btn-sm">🖨️ Cetak Laporan</button>
        </div>

        <!-- Filter -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:16px;">
                <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start" class="form-control" value="<?= $startDate ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end" class="form-control" value="<?= $endDate ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Kelompokkan</label>
                        <select name="group" class="form-control">
                            <option value="day" <?= $groupBy === 'day' ? 'selected' : '' ?>>Per Hari</option>
                            <option value="month" <?= $groupBy === 'month' ? 'selected' : '' ?>>Per Bulan</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan</button>
                    <a href="?start=<?= date('Y-m-01') ?>&end=<?= date('Y-m-d') ?>" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon primary">💰</div>
                <div>
                    <div class="stat-value" style="font-size:1.1rem;"><?= formatRupiah($stats['total_revenue'] ?? 0) ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">📦</div>
                <div>
                    <div class="stat-value"><?= (int)($stats['total_orders'] ?? 0) ?></div>
                    <div class="stat-label">Pesanan Selesai</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">🎁</div>
                <div>
                    <div class="stat-value" style="font-size:1.1rem;"><?= formatRupiah($stats['total_discount'] ?? 0) ?></div>
                    <div class="stat-label">Total Diskon</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">📊</div>
                <div>
                    <div class="stat-value" style="font-size:1.1rem;"><?= formatRupiah($stats['avg_order'] ?? 0) ?></div>
                    <div class="stat-label">Rata-Rata Pesanan</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <!-- Revenue Table -->
            <div class="card">
                <div class="card-header"><span class="card-title">📊 Pendapatan per Periode</span></div>
                <div class="table-container" style="border:none;border-radius:0;max-height:350px;overflow-y:auto;">
                    <table>
                        <thead><tr><th>Periode</th><th>Pesanan</th><th>Pendapatan</th></tr></thead>
                        <tbody>
                            <?php while ($rd = $revenueData->fetch_assoc()): ?>
                            <tr>
                                <td><?= $rd['label'] ?></td>
                                <td><?= $rd['orders'] ?></td>
                                <td style="font-weight:600;"><?= formatRupiah($rd['revenue']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="card">
                <div class="card-header"><span class="card-title">📋 Status Pesanan</span></div>
                <div class="card-body">
                    <?php
                    $statusColors = [
                        'pending' => 'var(--warning)', 'confirmed' => 'var(--info)', 'processing' => '#9b59b6',
                        'shipped' => 'var(--info)', 'delivered' => 'var(--success)',
                        'cancelled' => 'var(--danger)', 'refunded' => 'var(--text-muted)'
                    ];
                    $byStatus->data_seek(0);
                    while ($bs = $byStatus->fetch_assoc()):
                    ?>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:4px;">
                            <span style="color:<?= $statusColors[$bs['status']] ?? 'var(--text-primary)' ?>;font-weight:600;"><?= ucfirst($bs['status']) ?></span>
                            <span><?= $bs['count'] ?> pesanan</span>
                        </div>
                        <div style="height:8px;background:var(--bg-surface);border-radius:4px;overflow:hidden;">
                            <div style="height:100%;background:<?= $statusColors[$bs['status']] ?? 'var(--primary)' ?>;width:<?= min(100, ($bs['count'] / max(1, $stats['total_orders'] ?? 1)) * 100) ?>%;border-radius:4px;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <!-- Top Products -->
            <div class="card">
                <div class="card-header"><span class="card-title">🔥 Produk Terlaris</span></div>
                <div class="table-container" style="border:none;border-radius:0;">
                    <table>
                        <thead><tr><th>#</th><th>Produk</th><th>Terjual</th><th>Revenue</th></tr></thead>
                        <tbody>
                            <?php $no=1; while ($tp = $topProducts->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $no++ ?></td>
                                <td>
                                    <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($tp['name']) ?></div>
                                    <span class="product-type-badge type-<?= $tp['type'] ?>" style="font-size:0.65rem;padding:2px 6px;"><?= ucfirst($tp['type']) ?></span>
                                </td>
                                <td><?= $tp['qty_sold'] ?> pcs</td>
                                <td style="font-weight:700;font-size:0.85rem;"><?= formatRupiah($tp['revenue']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Buyers -->
            <div class="card">
                <div class="card-header"><span class="card-title">👑 Pembeli Terbanyak</span></div>
                <div class="card-body">
                    <?php while ($tb = $topBuyers->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:8px;margin-bottom:4px;" onmouseover="this.style.background='var(--bg-surface)'" onmouseout="this.style.background=''">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                            <?= strtoupper(substr($tb['name'],0,1)) ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($tb['name']) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= $tb['orders'] ?> pesanan</div>
                        </div>
                        <div style="font-weight:700;color:var(--primary);font-size:0.875rem;"><?= formatRupiah($tb['spent']) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
