<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('buyer');

$buyerId = (int)$_SESSION['user_id'];

// Single order detail
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId) {
    // Check promo ajax
    $stmt = $conn->prepare("SELECT o.*, p.status as payment_status, p.proof as payment_proof, sh.courier, sh.tracking_number, sh.status as shipping_status FROM orders o LEFT JOIN payments p ON o.id = p.order_id LEFT JOIN shipping sh ON o.id = sh.order_id WHERE o.id = ? AND o.buyer_id = ?");
    $stmt->bind_param("ii", $orderId, $buyerId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) redirect('/Aplikasi_kopi/buyer/orders.php');

    $orderItems = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId");

    // Check if can review
    $canReview = $order['status'] === 'delivered';
    $hasReview = false;
    if ($canReview) {
        $rv = $conn->query("SELECT id FROM reviews WHERE order_id = $orderId AND buyer_id = $buyerId");
        $hasReview = $rv->num_rows > 0;
    }

    // Submit review
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'])) {
        $rating = (int)$_POST['review_rating'];
        $comment = escape($conn, $_POST['review_comment'] ?? '');
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $rs = $conn->prepare("INSERT IGNORE INTO reviews (order_id, product_id, buyer_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $rs->bind_param("iiiss", $orderId, $productId, $buyerId, $rating, $comment);
            $rs->execute();
            // Update product rating
            $conn->query("UPDATE products SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = $productId) WHERE id = $productId");
            $_SESSION['flash'] = ['message' => 'Ulasan berhasil dikirim!', 'type' => 'success'];
            redirect("/Aplikasi_kopi/buyer/orders.php?id=$orderId");
        }
    }

    $pageTitle = 'Detail Pesanan #' . $order['invoice'] . ' - KopiKu';
} else {
    // Order list
    $orders = $conn->query("
        SELECT o.*, COUNT(oi.id) as item_count, p.status as payment_status
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.buyer_id = $buyerId
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $pageTitle = 'Pesanan Saya - KopiKu';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';

$statusLabels = [
    'pending' => ['🕐', 'Menunggu Pembayaran', 'status-pending'],
    'confirmed' => ['✅', 'Dikonfirmasi', 'status-confirmed'],
    'processing' => ['⚙️', 'Diproses', 'status-processing'],
    'shipped' => ['🚚', 'Dikirim', 'status-shipped'],
    'delivered' => ['📦', 'Selesai', 'status-delivered'],
    'cancelled' => ['❌', 'Dibatalkan', 'status-cancelled'],
    'refunded' => ['↩️', 'Dikembalikan', 'status-refunded'],
];
?>

<div class="container" style="padding:32px 20px 48px;">
    <?php if ($orderId && isset($order)): ?>
    <!-- Single Order Detail -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="/Aplikasi_kopi/buyer/orders.php" class="btn btn-secondary btn-sm">← Kembali</a>
        <h1 style="font-size:1.3rem;font-weight:800;">Pesanan #<?= $order['invoice'] ?></h1>
        <span class="badge <?= $statusLabels[$order['status']][2] ?? 'badge-secondary' ?>">
            <?= $statusLabels[$order['status']][0] ?? '' ?> <?= $statusLabels[$order['status']][1] ?? $order['status'] ?>
        </span>
        <button onclick="window.print()" class="btn btn-secondary btn-sm" id="printInvoice" style="margin-left:auto;">🖨️ Cetak</button>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <!-- Order Items -->
            <div class="card">
                <div class="card-header"><span class="card-title">🛍️ Detail Produk</span></div>
                <div style="padding:8px;">
                    <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;gap:14px;padding:14px;border-bottom:1px solid var(--border-light);">
                        <div style="width:52px;height:52px;background:var(--bg-surface);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;">
                            <?php $oiImg = imageUrl($item['product_image']); if ($oiImg): ?>
                            <img src="<?= htmlspecialchars($oiImg) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" loading="lazy">
                            <?php else: ?>☕<?php endif; ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div style="font-size:0.78rem;color:var(--text-muted);"><?= formatRupiah($item['price']) ?> × <?= $item['quantity'] ?></div>
                        </div>
                        <div style="font-weight:700;"><?= formatRupiah($item['subtotal']) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Shipping -->
            <div class="card">
                <div class="card-header"><span class="card-title">📍 Alamat Pengiriman</span></div>
                <div class="card-body">
                    <div style="font-weight:600;"><?= htmlspecialchars($order['shipping_name']) ?></div>
                    <div style="color:var(--text-muted);font-size:0.875rem;"><?= htmlspecialchars($order['shipping_phone']) ?></div>
                    <div style="margin-top:6px;color:var(--text-secondary);font-size:0.875rem;">
                        <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?>
                        <?php if ($order['shipping_province']): ?>, <?= htmlspecialchars($order['shipping_province']) ?><?php endif; ?>
                        <?php if ($order['shipping_postal']): ?> <?= htmlspecialchars($order['shipping_postal']) ?><?php endif; ?>
                    </div>
                    <?php if ($order['courier'] || $order['tracking_number']): ?>
                    <div class="divider"></div>
                    <div style="font-size:0.875rem;">
                        <span style="color:var(--text-muted);">Kurir:</span> <strong><?= htmlspecialchars($order['courier'] ?? '-') ?></strong><br>
                        <span style="color:var(--text-muted);">No. Resi:</span> <strong><?= htmlspecialchars($order['tracking_number'] ?? '-') ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Review (if delivered & no review yet) -->
            <?php if ($canReview && !$hasReview): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">⭐ Berikan Ulasan</span></div>
                <div class="card-body">
                    <form method="POST">
                        <?php
                        $orderItems2 = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId LIMIT 1");
                        $firstItem = $orderItems2->fetch_assoc();
                        ?>
                        <input type="hidden" name="product_id" value="<?= $firstItem['product_id'] ?? 0 ?>">
                        <div class="form-group">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <span class="star" data-val="<?= $s ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="review_rating" value="5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Komentar (Opsional)</label>
                            <textarea name="review_comment" class="form-control" rows="3" placeholder="Bagaimana kopi ini?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Summary -->
        <div>
            <div class="card" style="position:sticky;top:88px;">
                <div class="card-header"><span class="card-title">💰 Ringkasan Pembayaran</span></div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:8px;font-size:0.875rem;margin-bottom:16px;">
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
                        <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.05rem;">
                            <span>Total</span>
                            <span style="color:var(--primary);"><?= formatRupiah($order['total']) ?></span>
                        </div>
                    </div>

                    <div style="background:var(--bg-surface);border-radius:10px;padding:12px;font-size:0.82rem;margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:var(--text-muted);">Metode</span>
                            <strong><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Status Bayar</span>
                            <span class="badge <?= $order['payment_status'] === 'verified' ? 'badge-success' : ($order['payment_status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                                <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($order['status'] === 'pending' && $order['payment_status'] !== 'verified'): ?>
                    <a href="/Aplikasi_kopi/buyer/payment.php?order_id=<?= $order['id'] ?>" class="btn btn-primary btn-block btn-sm">
                        💳 Bayar Sekarang
                    </a>
                    <?php endif; ?>

                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:10px;text-align:center;">
                        Dibuat: <?= date('d M Y H:i', strtotime($order['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Orders List -->
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.5rem;font-weight:800;">📦 Pesanan Saya</h1>
    </div>

    <!-- Status Tabs -->
    <?php
    $statusFilter = $_GET['status'] ?? '';
    $filterLinks = [
        '' => 'Semua', 'pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi',
        'processing' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'
    ];
    ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;">
        <?php foreach ($filterLinks as $val => $label):
            $isActive = $statusFilter === $val;
        ?>
        <a href="?status=<?= $val ?>"
           class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php
    $whereStatus = $statusFilter ? "AND o.status = '$statusFilter'" : '';
    $myOrders = $conn->query("
        SELECT o.*, COUNT(oi.id) as item_count, p.status as payment_status
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.buyer_id = $buyerId $whereStatus
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    ?>

    <?php if ($myOrders->num_rows === 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <div class="empty-title">Belum ada pesanan</div>
                <div class="empty-desc">Mulai berbelanja dan temukan kopi favoritmu</div>
                <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-primary" style="margin-top:16px;">Mulai Belanja</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px;">
        <?php while ($ord = $myOrders->fetch_assoc()):
            $sl = $statusLabels[$ord['status']] ?? ['📦', $ord['status'], 'badge-secondary'];
        ?>
        <div class="card" style="transition:var(--transition);" onmouseover="this.style.borderColor='var(--border)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="padding:18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div>
                        <div style="font-weight:700;font-size:0.9rem;"><?= $ord['invoice'] ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= date('d M Y H:i', strtotime($ord['created_at'])) ?> • <?= $ord['item_count'] ?> produk</div>
                    </div>
                    <span class="badge <?= $sl[2] ?>"><?= $sl[0] ?> <?= $sl[1] ?></span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <span style="color:var(--text-muted);font-size:0.82rem;">Total:</span>
                        <span style="font-weight:800;font-size:1.05rem;color:var(--primary);margin-left:6px;"><?= formatRupiah($ord['total']) ?></span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <?php if ($ord['status'] === 'pending' && $ord['payment_status'] !== 'verified'): ?>
                        <a href="/Aplikasi_kopi/buyer/payment.php?order_id=<?= $ord['id'] ?>" class="btn btn-primary btn-sm">💳 Bayar</a>
                        <?php endif; ?>
                        <a href="/Aplikasi_kopi/buyer/orders.php?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm">Detail →</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
