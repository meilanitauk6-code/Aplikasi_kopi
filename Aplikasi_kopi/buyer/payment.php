<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('buyer');

$buyerId = (int) $_SESSION['user_id'];
$orderId = (int) ($_GET['order_id'] ?? 0);

$stmt = $conn->prepare("SELECT o.*, p.status as payment_status, p.proof FROM orders o LEFT JOIN payments p ON o.id = p.order_id WHERE o.id = ? AND o.buyer_id = ?");
$stmt->bind_param("ii", $orderId, $buyerId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order)
    redirect('/Aplikasi_kopi/buyer/orders.php');

// Upload payment proof
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['proof']['name'])) {
        $filename = uploadImage($_FILES['proof'], 'payments');
        if ($filename) {
            $conn->query("UPDATE payments SET proof = '$filename', status = 'pending' WHERE order_id = $orderId");
            $conn->query("UPDATE orders SET status = 'confirmed' WHERE id = $orderId");
            // Notify admin
            $admins = $conn->query("SELECT id FROM users WHERE role='admin'");
            while ($admin = $admins->fetch_assoc()) {
                $conn->query("INSERT INTO notifications (user_id, title, message, type, url) VALUES ({$admin['id']}, 'Bukti Pembayaran Baru', 'Pesanan #{$order['invoice']} menunggu verifikasi pembayaran.', 'payment', '/Aplikasi_kopi/admin/payments.php')");
            }
            $_SESSION['flash'] = ['message' => 'Bukti pembayaran berhasil diunggah! Menunggu verifikasi admin.', 'type' => 'success'];
            redirect("/Aplikasi_kopi/buyer/orders.php?id=$orderId");
        } else {
            $error = 'Gagal mengunggah file. Pastikan format JPG/PNG dan ukuran max 5MB.';
        }
    } else {
        $error = 'Pilih file bukti pembayaran terlebih dahulu.';
    }
}

// Get settings
$settings = [];
$settingsRows = $conn->query("SELECT `key`, `value` FROM settings");
while ($s = $settingsRows->fetch_assoc())
    $settings[$s['key']] = $s['value'];

$pageTitle = 'Pembayaran - ' . $order['invoice'] . ' - KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';

$methodMap = [
    'transfer_bank' => ['🏦', 'Transfer Bank'],
    'ewallet' => ['📱', 'E-Wallet'],
    'virtual_account' => ['🔢', 'Virtual Account'],
    'kartu_kredit' => ['💳', 'Kartu Kredit/Debit'],
];
$method = $methodMap[$order['payment_method']] ?? ['💳', ucfirst($order['payment_method'])];
?>

<div class="container" style="padding:32px 20px 48px;max-width:680px;">
    <!-- Steps -->
    <div class="checkout-steps" style="margin-bottom:32px;">
        <div class="checkout-step done">
            <div class="step-number">✓</div><span class="step-label">Keranjang</span>
        </div>
        <div class="step-line done"></div>
        <div class="checkout-step done">
            <div class="step-number">✓</div><span class="step-label">Detail Pengiriman</span>
        </div>
        <div class="step-line active"></div>
        <div class="checkout-step active">
            <div class="step-number">3</div><span class="step-label">Pembayaran</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Order Confirmed Banner -->
    <div
        style="background:linear-gradient(135deg,rgba(46,204,113,.15),rgba(46,204,113,.05));border:1px solid rgba(46,204,113,.3);border-radius:16px;padding:24px;margin-bottom:24px;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🎉</div>
        <h2 style="font-weight:800;margin-bottom:4px;">Pesanan Berhasil Dibuat!</h2>
        <p style="color:var(--text-muted);font-size:0.875rem;">Invoice: <strong
                style="color:var(--primary);"><?= $order['invoice'] ?></strong></p>
    </div>

    <!-- Payment Info -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title"><?= $method[0] ?> Instruksi Pembayaran</span></div>
        <div class="card-body">
            <div
                style="background:var(--bg-surface);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;">
                <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Total yang harus dibayar</div>
                <div style="font-size:2rem;font-weight:800;color:var(--primary);"><?= formatRupiah($order['total']) ?>
                </div>
            </div>

            <?php if ($order['payment_method'] === 'transfer_bank'): ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="background:var(--bg-surface);border-radius:10px;padding:14px;">
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">Bank Tujuan</div>
                        <div style="font-weight:700;font-size:1.1rem;"><?= $settings['bank_name'] ?? 'Bank BCA' ?></div>
                    </div>
                    <div style="background:var(--bg-surface);border-radius:10px;padding:14px;cursor:pointer;"
                        onclick="copyText('<?= $settings['bank_account'] ?? '1234567890' ?>')">
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">Nomor Rekening (klik untuk
                            copy)</div>
                        <div style="font-weight:700;font-size:1.2rem;font-family:monospace;">
                            <?= $settings['bank_account'] ?? '1234567890' ?></div>
                    </div>
                    <div style="background:var(--bg-surface);border-radius:10px;padding:14px;">
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">Atas Nama</div>
                        <div style="font-weight:700;"><?= $settings['bank_holder'] ?? 'PT Kopi Nusantara' ?></div>
                    </div>
                </div>
            <?php elseif ($order['payment_method'] === 'ewallet'): ?>
                <div style="background:var(--bg-surface);border-radius:10px;padding:14px;margin-bottom:10px;">
                    <p>Lakukan transfer ke salah satu e-wallet berikut:</p>
                    <ul style="margin-top:8px;color:var(--text-secondary);font-size:0.875rem;">
                        <li>OVO: 081236547850</li>
                        <li>DANA: 081236547850</li>
                        <li>GoPay: 081236547850</li>
                        <li>ShopeePay: 081236547850</li>
                    </ul>
                </div>
            <?php elseif ($order['payment_method'] === 'virtual_account'): ?>
                <?php $vaNumber = '7001' . str_pad($orderId, 12, '0', STR_PAD_LEFT); ?>
                <div style="background:var(--bg-surface);border-radius:10px;padding:14px;cursor:pointer;"
                    onclick="copyText('<?= $vaNumber ?>')">
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">Nomor Virtual Account (klik
                        untuk copy)</div>
                    <div style="font-weight:700;font-size:1.2rem;font-family:monospace;">
                        <?= $vaNumber ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="background:var(--bg-surface);border-radius:10px;padding:14px;">
                    <p style="color:var(--text-secondary);font-size:0.875rem;">Pembayaran kartu kredit/debit. Unggah bukti
                        pembayaran setelah transaksi berhasil.</p>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning" style="margin-top:16px;">
                ⏱️ Selesaikan pembayaran dalam <strong>24 jam</strong> sebelum pesanan otomatis dibatalkan.
            </div>
        </div>
    </div>

    <!-- Upload Proof -->
    <?php if ($order['payment_status'] !== 'verified'): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">📤 Upload Bukti Pembayaran</span></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Foto Bukti Transfer / Screenshot</label>
                        <input type="file" name="proof" accept="image/*" data-preview="proofPreview" class="form-control"
                            style="padding:8px;">
                        <img id="proofPreview" src=""
                            style="display:none;margin-top:10px;max-width:100%;border-radius:10px;max-height:250px;object-fit:contain;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">📤 Upload Bukti Pembayaran</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success">✅ Bukti pembayaran telah diverifikasi!</div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
        <a href="/Aplikasi_kopi/buyer/orders.php" class="btn btn-secondary">Lihat Semua Pesanan →</a>
    </div>
</div>

<script>
    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Berhasil disalin ke clipboard!', 'success');
        });
    }
</script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>