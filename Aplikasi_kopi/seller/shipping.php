<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('seller');

$sellerId = (int)$_SESSION['user_id'];

// Update shipping
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['order_id'];
    $courier = escape($conn, $_POST['courier']);
    $trackingNumber = escape($conn, $_POST['tracking_number']);
    $estimatedDate = escape($conn, $_POST['estimated_date'] ?? '');
    $notes = escape($conn, $_POST['notes'] ?? '');
    $status = escape($conn, $_POST['status']);

    // Verify order belongs to seller
    $check = $conn->query("SELECT id FROM orders WHERE id=$orderId AND seller_id=$sellerId");
    if ($check->num_rows > 0) {
        $estSql = $estimatedDate ? "'$estimatedDate'" : 'NULL';
        $conn->query("UPDATE shipping SET courier='$courier', tracking_number='$trackingNumber', estimated_date=$estSql, status='$status', notes='$notes' WHERE order_id=$orderId");
        if ($status === 'delivered') {
            $conn->query("UPDATE shipping SET delivered_at=NOW() WHERE order_id=$orderId");
            $conn->query("UPDATE orders SET status='delivered' WHERE id=$orderId");
            // Notify buyer
            $ord = $conn->query("SELECT buyer_id, invoice FROM orders WHERE id=$orderId")->fetch_assoc();
            $conn->query("INSERT INTO notifications (user_id, title, message, type, url) VALUES ({$ord['buyer_id']}, 'Pesanan Diterima', 'Pesanan {$ord['invoice']} telah diterima.', 'success', '/Aplikasi_kopi/buyer/orders.php?id=$orderId')");
        } elseif ($trackingNumber && $status === 'shipped') {
            $conn->query("UPDATE orders SET status='shipped' WHERE id=$orderId AND status IN ('confirmed','processing')");
        }
        $_SESSION['flash'] = ['message' => 'Info pengiriman berhasil diperbarui.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/seller/shipping.php');
}

// Get orders that need shipping info
$shipOrders = $conn->query("
    SELECT o.*, u.name as buyer_name, sh.courier, sh.tracking_number, sh.status as ship_status, sh.estimated_date, sh.notes as ship_notes, sh.id as ship_id
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    LEFT JOIN shipping sh ON o.id = sh.order_id
    WHERE o.seller_id = $sellerId AND o.status IN ('confirmed','processing','shipped','delivered')
    ORDER BY o.updated_at DESC
");

$pageTitle = 'Kelola Pengiriman - Penjual KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_seller.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div><h1 class="section-title">🚚 Kelola Pengiriman</h1></div>
        </div>

        <?php if ($shipOrders->num_rows === 0): ?>
        <div class="card"><div class="card-body"><div class="empty-state">
            <div class="empty-icon">🚚</div>
            <div class="empty-title">Tidak ada pesanan yang perlu dikirim</div>
        </div></div></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <?php while ($ord = $shipOrders->fetch_assoc()):
                $shipStatus = ['pending'=>'Belum Diproses','processing'=>'Diproses','shipped'=>'Dalam Pengiriman','in_transit'=>'Dalam Perjalanan','delivered'=>'Sudah Diterima'];
                $shipBadge = ['pending'=>'badge-secondary','processing'=>'badge-warning','shipped'=>'badge-info','in_transit'=>'badge-info','delivered'=>'badge-success'];
            ?>
            <div class="card">
                <div style="padding:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
                        <div>
                            <code style="font-size:0.8rem;"><?= $ord['invoice'] ?></code>
                            <span class="badge badge-primary" style="margin-left:8px;">Pesanan <?= ucfirst($ord['status']) ?></span>
                            <div style="margin-top:4px;font-size:0.82rem;color:var(--text-muted);">Pembeli: <strong><?= htmlspecialchars($ord['buyer_name']) ?></strong> | Total: <strong><?= formatRupiah($ord['total']) ?></strong></div>
                            <div style="font-size:0.82rem;color:var(--text-muted);">📍 <?= htmlspecialchars($ord['shipping_address']) ?>, <?= htmlspecialchars($ord['shipping_city']) ?></div>
                        </div>
                        <span class="badge <?= $shipBadge[$ord['ship_status']] ?? 'badge-secondary' ?>"><?= $shipStatus[$ord['ship_status']] ?? 'Belum Diproses' ?></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                        <div class="form-row-3">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Kurir</label>
                                <select name="courier" class="form-control">
                                    <?php foreach(['JNE','J&T','SiCepat','Pos Indonesia','Tiki','Gojek','Grab','Anteraja'] as $c): ?>
                                    <option value="<?=$c?>" <?=$ord['courier']===$c?'selected':''?>><?=$c?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">No. Resi</label>
                                <input type="text" name="tracking_number" class="form-control" value="<?= htmlspecialchars($ord['tracking_number']??'') ?>" placeholder="XXXXXXXXXXXXX">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Est. Tiba</label>
                                <input type="date" name="estimated_date" class="form-control" value="<?= $ord['estimated_date'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="form-row" style="margin-top:10px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Status Pengiriman</label>
                                <select name="status" class="form-control">
                                    <?php foreach($shipStatus as $val=>$label): ?>
                                    <option value="<?=$val?>" <?=$ord['ship_status']===$val?'selected':''?>><?=$label?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($ord['ship_notes']??'') ?>" placeholder="Catatan pengiriman...">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:12px;">💾 Update Pengiriman</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
