<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('seller');

$sellerId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $status = escape($conn, $_POST['status']);
    $cancelReason = escape($conn, $_POST['cancel_reason'] ?? '');
    $stmt = $conn->prepare("UPDATE orders SET status=? " . ($cancelReason ? ", cancel_reason=?" : '') . " WHERE id=? AND seller_id=?");
    if ($cancelReason) {
        $stmt->bind_param("ssii", $status, $cancelReason, $id, $sellerId);
    } else {
        $stmt->bind_param("sii", $status, $id, $sellerId);
    }
    $stmt->execute();
    $_SESSION['flash'] = ['message' => 'Status pesanan diperbarui.', 'type' => 'success'];
    redirect("/Aplikasi_kopi/seller/orders.php?id=$id");
}

$statusBadges = ['pending'=>'badge-warning','confirmed'=>'badge-info','processing'=>'badge-info','shipped'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger','refunded'=>'badge-secondary'];

if ($id) {
    $stmt = $conn->prepare("SELECT o.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone FROM orders o JOIN users u ON o.buyer_id=u.id WHERE o.id=? AND o.seller_id=?");
    $stmt->bind_param("ii", $id, $sellerId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) redirect('/Aplikasi_kopi/seller/orders.php');
    $items = $conn->query("SELECT * FROM order_items WHERE order_id=$id");
    $pageTitle = 'Detail Pesanan #' . $order['invoice'];
} else {
    $statusFilter = escape($conn, $_GET['status'] ?? '');
    $where = "o.seller_id = $sellerId";
    if ($statusFilter) $where .= " AND o.status = '$statusFilter'";
    $orders = $conn->query("SELECT o.*, u.name as buyer_name FROM orders o JOIN users u ON o.buyer_id=u.id WHERE $where ORDER BY o.created_at DESC");
    $pageTitle = 'Kelola Pesanan - Penjual';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_seller.php'; ?>
    <main class="main-content">
        <?php if ($id && isset($order)): ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <a href="/Aplikasi_kopi/seller/orders.php" class="btn btn-secondary btn-sm">← Kembali</a>
            <h1 style="font-size:1.3rem;font-weight:800;">Pesanan #<?= $order['invoice'] ?></h1>
            <span class="badge <?= $statusBadges[$order['status']] ?? 'badge-secondary' ?>"><?= ucfirst($order['status']) ?></span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="card">
                    <div class="card-header"><span class="card-title">🛍️ Item Pesanan</span></div>
                    <div style="padding:4px;">
                        <?php while($item=$items->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:14px;padding:12px;border-bottom:1px solid var(--border-light);">
                            <div style="width:44px;height:44px;background:var(--bg-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">☕</div>
                            <div style="flex:1;">
                                <div style="font-weight:600;font-size:0.875rem;"><?=htmlspecialchars($item['product_name'])?></div>
                                <div style="font-size:0.78rem;color:var(--text-muted);"><?=formatRupiah($item['price'])?> × <?=$item['quantity']?></div>
                            </div>
                            <div style="font-weight:700;"><?=formatRupiah($item['subtotal'])?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="card"><div class="card-header"><span class="card-title">👤 Pembeli</span></div>
                    <div class="card-body" style="font-size:0.875rem;">
                        <div style="font-weight:600;"><?=htmlspecialchars($order['buyer_name'])?></div>
                        <div style="color:var(--text-muted);"><?=htmlspecialchars($order['buyer_email'])?></div>
                    </div></div>
                    <div class="card"><div class="card-header"><span class="card-title">📍 Alamat</span></div>
                    <div class="card-body" style="font-size:0.85rem;">
                        <div style="font-weight:600;"><?=htmlspecialchars($order['shipping_name'])?></div>
                        <div style="color:var(--text-muted);"><?=htmlspecialchars($order['shipping_address'])?>, <?=htmlspecialchars($order['shipping_city'])?></div>
                    </div></div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="card">
                    <div class="card-header"><span class="card-title">⚡ Ubah Status</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                                    <option value="<?=$s?>" <?=$order['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alasan (jika dibatalkan)</label>
                                <textarea name="cancel_reason" class="form-control" rows="2"><?=htmlspecialchars($order['cancel_reason']??'')?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><span class="card-title">💰 Total</span></div>
                    <div class="card-body">
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;margin-bottom:6px;"><span style="color:var(--text-muted);">Subtotal</span><span><?=formatRupiah($order['subtotal'])?></span></div>
                        <?php if($order['discount']>0): ?><div style="display:flex;justify-content:space-between;font-size:0.875rem;margin-bottom:6px;"><span style="color:var(--success);">Diskon</span><span style="color:var(--success);">-<?=formatRupiah($order['discount'])?></span></div><?php endif; ?>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;margin-bottom:6px;"><span style="color:var(--text-muted);">Kirim</span><span><?=formatRupiah($order['shipping_cost'])?></span></div>
                        <div class="divider"></div>
                        <div style="display:flex;justify-content:space-between;font-weight:800;"><span>Total</span><span style="color:var(--primary);"><?=formatRupiah($order['total'])?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="section-header"><div><h1 class="section-title">📦 Kelola Pesanan</h1></div></div>
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <?php foreach([''=> 'Semua','pending'=>'Pending','confirmed'=>'Dikonfirmasi','processing'=>'Diproses','shipped'=>'Dikirim','delivered'=>'Selesai','cancelled'=>'Dibatalkan'] as $val=>$label): ?>
            <a href="?status=<?=$val?>" class="btn btn-sm <?=($statusFilter??'')===$val?'btn-primary':'btn-secondary'?>"><?=$label?></a>
            <?php endforeach; ?>
        </div>
        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead><tr><th>Invoice</th><th>Pembeli</th><th>Total</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php while($ord=$orders->fetch_assoc()): ?>
                        <tr>
                            <td><code style="font-size:0.78rem;"><?=$ord['invoice']?></code></td>
                            <td style="font-weight:600;font-size:0.875rem;"><?=htmlspecialchars($ord['buyer_name'])?></td>
                            <td style="font-weight:700;"><?=formatRupiah($ord['total'])?></td>
                            <td><span class="badge <?=$statusBadges[$ord['status']]??'badge-secondary'?>"><?=ucfirst($ord['status'])?></span></td>
                            <td style="font-size:0.78rem;color:var(--text-muted);"><?=timeAgo($ord['created_at'])?></td>
                            <td><a href="?id=<?=$ord['id']?>" class="btn btn-secondary btn-sm">Detail</a></td>
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
