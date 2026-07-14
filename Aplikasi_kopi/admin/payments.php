<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

// Verify payment
if ($action === 'verify' && $id) {
    $conn->query("UPDATE payments SET status='verified', verified_at=NOW(), verified_by={$_SESSION['user_id']} WHERE id=$id");
    $conn->query("UPDATE orders o JOIN payments p ON o.id = p.order_id SET o.status='processing' WHERE p.id=$id AND o.status='confirmed'");
    // Get order info for notification
    $pay = $conn->query("SELECT p.order_id, o.buyer_id, o.invoice FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = $id")->fetch_assoc();
    if ($pay) {
        $conn->query("INSERT INTO notifications (user_id, title, message, type, url) VALUES ({$pay['buyer_id']}, 'Pembayaran Terverifikasi', 'Pembayaran untuk pesanan {$pay['invoice']} telah diverifikasi.', 'success', '/Aplikasi_kopi/buyer/orders.php?id={$pay['order_id']}')");
    }
    $_SESSION['flash'] = ['message' => 'Pembayaran berhasil diverifikasi.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/payments.php');
}

// Reject payment
if ($action === 'reject' && $id) {
    $reason = escape($conn, $_POST['reason'] ?? 'Bukti pembayaran tidak valid');
    $conn->query("UPDATE payments SET status='rejected', notes='$reason' WHERE id=$id");
    $conn->query("UPDATE orders o JOIN payments p ON o.id = p.order_id SET o.status='pending' WHERE p.id=$id");
    $pay = $conn->query("SELECT p.order_id, o.buyer_id, o.invoice FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = $id")->fetch_assoc();
    if ($pay) {
        $conn->query("INSERT INTO notifications (user_id, title, message, type, url) VALUES ({$pay['buyer_id']}, 'Pembayaran Ditolak', 'Bukti pembayaran untuk pesanan {$pay['invoice']} ditolak. Alasan: $reason', 'danger', '/Aplikasi_kopi/buyer/payment.php?order_id={$pay['order_id']}')");
    }
    $_SESSION['flash'] = ['message' => 'Pembayaran ditolak.', 'type' => 'warning'];
    redirect('/Aplikasi_kopi/admin/payments.php');
}

// Process refund
if ($action === 'refund' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("UPDATE payments SET status='rejected' WHERE order_id=$id");
    $conn->query("UPDATE orders SET status='refunded' WHERE id=$id");
    $_SESSION['flash'] = ['message' => 'Refund berhasil diproses.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/payments.php');
}

$statusFilter = escape($conn, $_GET['status'] ?? '');
$where = $statusFilter ? "WHERE p.status = '$statusFilter'" : '';

$payments = $conn->query("
    SELECT p.*, o.invoice, o.total, o.buyer_id, o.status as order_status, u.name as buyer_name, u.email as buyer_email
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.buyer_id = u.id
    $where
    ORDER BY p.created_at DESC
");

$pageTitle = 'Kelola Pembayaran - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">💳 Pembayaran & Refund</h1>
                <p class="section-subtitle">Verifikasi pembayaran dan proses refund</p>
            </div>
        </div>

        <!-- Filter -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <?php foreach (['' => 'Semua', 'pending' => '🕐 Pending', 'verified' => '✅ Verified', 'rejected' => '❌ Rejected'] as $val => $label): ?>
            <a href="?status=<?= $val ?>" class="btn btn-sm <?= $statusFilter === $val ? 'btn-primary' : 'btn-secondary' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Pembeli</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pay = $payments->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <code style="font-size:0.78rem;"><?= $pay['invoice'] ?></code><br>
                                <span class="badge <?= $pay['order_status'] === 'delivered' ? 'badge-success' : 'badge-secondary' ?>" style="font-size:0.65rem;"><?= $pay['order_status'] ?></span>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($pay['buyer_name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($pay['buyer_email']) ?></div>
                            </td>
                            <td style="font-size:0.82rem;"><?= ucfirst(str_replace('_',' ',$pay['method'])) ?></td>
                            <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($pay['amount']) ?></td>
                            <td>
                                <span class="badge <?= $pay['status'] === 'verified' ? 'badge-success' : ($pay['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                                    <?= ucfirst($pay['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($pay['proof']): ?>
                                <a href="/Aplikasi_kopi/uploads/payments/<?= $pay['proof'] ?>" target="_blank">
                                    <img src="/Aplikasi_kopi/uploads/payments/<?= $pay['proof'] ?>"
                                         style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                                </a>
                                <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.78rem;">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.78rem;color:var(--text-muted);"><?= timeAgo($pay['created_at']) ?></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <?php if ($pay['status'] === 'pending'): ?>
                                    <a href="?action=verify&id=<?= $pay['id'] ?>" class="btn btn-success btn-sm"
                                       data-confirm="Verifikasi pembayaran ini?">✅ Verifikasi</a>
                                    <button type="button" data-modal="rejectModal<?= $pay['id'] ?>" class="btn btn-danger btn-sm">❌ Tolak</button>
                                    <?php elseif ($pay['status'] === 'verified' && $pay['order_status'] !== 'refunded'): ?>
                                    <button type="button" data-modal="refundModal<?= $pay['order_id'] ?>" class="btn btn-warning btn-sm">↩️ Refund</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Reject Modal -->
                        <div class="modal-overlay" id="rejectModal<?= $pay['id'] ?>">
                            <div class="modal" style="max-width:400px;">
                                <div class="modal-header"><span class="modal-title">❌ Tolak Pembayaran</span><button class="modal-close">✕</button></div>
                                <form method="POST" action="?action=reject&id=<?= $pay['id'] ?>">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Alasan Penolakan</label>
                                            <textarea name="reason" class="form-control" rows="3" required>Bukti pembayaran tidak valid atau tidak sesuai.</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="modal-close btn btn-secondary">Batal</button>
                                        <button type="submit" class="btn btn-danger">Tolak Pembayaran</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Refund Modal -->
                        <div class="modal-overlay" id="refundModal<?= $pay['order_id'] ?>">
                            <div class="modal" style="max-width:400px;">
                                <div class="modal-header"><span class="modal-title">↩️ Proses Refund</span><button class="modal-close">✕</button></div>
                                <form method="POST" action="?action=refund&id=<?= $pay['order_id'] ?>">
                                    <div class="modal-body">
                                        <div class="alert alert-warning">Proses refund untuk pesanan <strong><?= $pay['invoice'] ?></strong> sebesar <strong><?= formatRupiah($pay['amount']) ?></strong></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="modal-close btn btn-secondary">Batal</button>
                                        <button type="submit" class="btn btn-warning">Proses Refund</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
