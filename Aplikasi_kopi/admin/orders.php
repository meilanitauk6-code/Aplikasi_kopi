<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $status = escape($conn, $_POST['status']);
    $cancelReason = escape($conn, $_POST['cancel_reason'] ?? '');
    $conn->query("UPDATE orders SET status = '$status'" . ($cancelReason ? ", cancel_reason = '$cancelReason'" : '') . " WHERE id = $id");
    $_SESSION['flash'] = ['message' => 'Status pesanan berhasil diperbarui.', 'type' => 'success'];
    redirect("/Aplikasi_kopi/admin/orders.php?id=$id");
}

$statusBadges = [
    'pending' => 'badge-warning', 'confirmed' => 'badge-info',
    'processing' => 'badge-info', 'shipped' => 'badge-info',
    'delivered' => 'badge-success', 'cancelled' => 'badge-danger', 'refunded' => 'badge-secondary'
];

if ($id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone,
               p.status as payment_status, p.method as payment_method_v, p.proof as payment_proof, p.amount as payment_amount
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) redirect('/Aplikasi_kopi/admin/orders.php');

    $orderItems = $conn->query("SELECT * FROM order_items WHERE order_id = $id");
    $shipping = $conn->query("SELECT * FROM shipping WHERE order_id = $id")->fetch_assoc();
    $pageTitle = 'Detail Pesanan #' . $order['invoice'];
} else {
    $search = escape($conn, $_GET['search'] ?? '');
    $statusFilter = escape($conn, $_GET['status'] ?? '');
    $where = ['1=1'];
    if ($search) $where[] = "(o.invoice LIKE '%$search%' OR u.name LIKE '%$search%')";
    if ($statusFilter) $where[] = "o.status = '$statusFilter'";
    $orders = $conn->query("
        SELECT o.*, u.name as buyer_name, p.status as payment_status
        FROM orders o JOIN users u ON o.buyer_id = u.id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY o.created_at DESC
    ");
    $pageTitle = 'Kelola Pesanan - Admin';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <?php if ($id && isset($order)): ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <a href="/Aplikasi_kopi/admin/orders.php" class="btn btn-secondary btn-sm">← Kembali</a>
            <h1 style="font-size:1.3rem;font-weight:800;">Pesanan #<?= $order['invoice'] ?></h1>
            <span class="badge <?= $statusBadges[$order['status']] ?? 'badge-secondary' ?>"><?= ucfirst($order['status']) ?></span>
            <button onclick="window.print()" class="btn btn-secondary btn-sm" style="margin-left:auto;">🖨️ Cetak</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">
            <div style="display:flex;flex-direction:column;gap:16px;">
                <!-- Items -->
                <div class="card">
                    <div class="card-header"><span class="card-title">🛍️ Item Pesanan</span></div>
                    <div style="padding:4px;">
                        <?php while ($item = $orderItems->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:14px;padding:14px;border-bottom:1px solid var(--border-light);">
                            <div style="width:48px;height:48px;background:var(--bg-surface);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">☕</div>
                            <div style="flex:1;">
                                <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div style="font-size:0.78rem;color:var(--text-muted);"><?= formatRupiah($item['price']) ?> × <?= $item['quantity'] ?></div>
                            </div>
                            <div style="font-weight:700;"><?= formatRupiah($item['subtotal']) ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Buyer & Shipping Info -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">👤 Data Pembeli</span></div>
                        <div class="card-body" style="font-size:0.875rem;">
                            <div style="font-weight:600;margin-bottom:4px;"><?= htmlspecialchars($order['buyer_name']) ?></div>
                            <div style="color:var(--text-muted);"><?= htmlspecialchars($order['buyer_email']) ?></div>
                            <div style="color:var(--text-muted);"><?= htmlspecialchars($order['buyer_phone'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><span class="card-title">📍 Alamat Kirim</span></div>
                        <div class="card-body" style="font-size:0.85rem;">
                            <div style="font-weight:600;"><?= htmlspecialchars($order['shipping_name']) ?></div>
                            <div style="color:var(--text-muted);"><?= htmlspecialchars($order['shipping_phone']) ?></div>
                            <div style="color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Proof -->
                <?php if ($order['payment_proof']): ?>
                <div class="card">
                    <div class="card-header"><span class="card-title">📷 Bukti Pembayaran</span></div>
                    <div class="card-body">
                        <img src="/Aplikasi_kopi/uploads/payments/<?= $order['payment_proof'] ?>"
                             style="max-width:100%;border-radius:12px;max-height:300px;object-fit:contain;">
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Summary & Actions -->
            <div style="display:flex;flex-direction:column;gap:16px;">
                <!-- Update Status -->
                <div class="card">
                    <div class="card-header"><span class="card-title">⚡ Ubah Status</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Status Pesanan</label>
                                <select name="status" class="form-control">
                                    <?php
                                    $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];
                                    foreach ($statuses as $s):
                                    ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" id="cancelReasonField" style="display:none;">
                                <label class="form-label">Alasan Pembatalan</label>
                                <textarea name="cancel_reason" class="form-control" rows="2"><?= htmlspecialchars($order['cancel_reason'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Simpan Status</button>
                        </form>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card">
                    <div class="card-header"><span class="card-title">💰 Pembayaran</span></div>
                    <div class="card-body">
                        <div style="display:flex;flex-direction:column;gap:8px;font-size:0.85rem;">
                            <div style="display:flex;justify-content:space-between;">
                                <span style="color:var(--text-muted);">Subtotal</span>
                                <span><?= formatRupiah($order['subtotal']) ?></span>
                            </div>
                            <?php if ($order['discount'] > 0): ?>
                            <div style="display:flex;justify-content:space-between;">
                                <span style="color:var(--success);">Diskon</span>
                                <span style="color:var(--success);">-<?= formatRupiah($order['discount']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div style="display:flex;justify-content:space-between;">
                                <span style="color:var(--text-muted);">Ongkos Kirim</span>
                                <span><?= formatRupiah($order['shipping_cost']) ?></span>
                            </div>
                            <div class="divider"></div>
                            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1rem;">
                                <span>Total</span>
                                <span style="color:var(--primary);"><?= formatRupiah($order['total']) ?></span>
                            </div>
                        </div>
                        <div style="margin-top:12px;font-size:0.82rem;">
                            <div>Metode: <strong><?= ucfirst(str_replace('_',' ',$order['payment_method'])) ?></strong></div>
                            <div>Status: <span class="badge <?= $order['payment_status'] === 'verified' ? 'badge-success' : ($order['payment_status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= ucfirst($order['payment_status'] ?? 'pending') ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.querySelector('[name="status"]').addEventListener('change', function() {
            document.getElementById('cancelReasonField').style.display = ['cancelled','refunded'].includes(this.value) ? 'block' : 'none';
        });
        </script>

        <?php else: ?>
        <div class="section-header">
            <div>
                <h1 class="section-title">📦 Kelola Pesanan</h1>
                <p class="section-subtitle">Lihat dan kelola semua pesanan</p>
            </div>
        </div>

        <!-- Filter -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:14px;">
                <form method="GET" style="display:flex;gap:10px;">
                    <div class="search-bar" style="flex:1;">
                        <input type="text" name="search" placeholder="Cari invoice atau nama pembeli..." value="<?= htmlspecialchars($search ?? '') ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>
                    <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead><tr><th>#</th><th>Invoice</th><th>Pembeli</th><th>Total</th><th>Metode</th><th>Pembayaran</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $no=1; while ($ord = $orders->fetch_assoc()): ?>
                        <tr class="searchable-row">
                            <td style="color:var(--text-muted);"><?= $no++ ?></td>
                            <td><code style="font-size:0.78rem;"><?= $ord['invoice'] ?></code></td>
                            <td style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($ord['buyer_name']) ?></td>
                            <td style="font-weight:700;"><?= formatRupiah($ord['total']) ?></td>
                            <td style="font-size:0.8rem;color:var(--text-muted);"><?= ucfirst(str_replace('_',' ',$ord['payment_method'])) ?></td>
                            <td><span class="badge <?= $ord['payment_status'] === 'verified' ? 'badge-success' : ($ord['payment_status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= ucfirst($ord['payment_status'] ?? 'pending') ?></span></td>
                            <td><span class="badge <?= $statusBadges[$ord['status']] ?? 'badge-secondary' ?>"><?= ucfirst($ord['status']) ?></span></td>
                            <td style="font-size:0.78rem;color:var(--text-muted);"><?= timeAgo($ord['created_at']) ?></td>
                            <td><a href="?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm">Detail</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
