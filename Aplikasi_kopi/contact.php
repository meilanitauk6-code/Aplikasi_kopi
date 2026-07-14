<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($conn, $_POST['name'] ?? '');
    $email = escape($conn, $_POST['email'] ?? '');
    $subject = escape($conn, $_POST['subject'] ?? '');
    $message = escape($conn, $_POST['message'] ?? '');
    $userId = isLoggedIn() ? (int)$_SESSION['user_id'] : 'NULL';

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Nama, email, dan pesan wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $conn->query("INSERT INTO contacts (user_id, name, email, subject, message) VALUES ($userId, '$name', '$email', '$subject', '$message')");
        $success = 'Pesan Anda berhasil terkirim! Kami akan segera menghubungi Anda.';
    }
}

// Get settings
$settingsRaw = $conn->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($s = $settingsRaw->fetch_assoc()) $settings[$s['key']] = $s['value'];

$pageTitle = 'Hubungi Kami - KopiKu';
$pageDesc = 'Hubungi tim KopiKu untuk pertanyaan, saran, atau bantuan seputar produk kopi kami.';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div style="background:var(--bg-surface);padding:40px 0 0;border-bottom:1px solid var(--border);">
    <div class="container">
        <h1 style="font-size:2rem;font-weight:800;margin-bottom:6px;">💬 Hubungi Kami</h1>
        <p style="color:var(--text-muted);margin-bottom:0;">Ada pertanyaan? Kami siap membantu Anda</p>
    </div>
</div>

<div class="container" style="padding:40px 20px 60px;">
    <div style="display:grid;grid-template-columns:1fr 400px;gap:40px;align-items:start;">
        <!-- Contact Form -->
        <div class="card">
            <div class="card-header"><span class="card-title">📧 Kirim Pesan</span></div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success" data-auto-dismiss>✅ <?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? (isLoggedIn() ? $_SESSION['name'] : '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? (isLoggedIn() ? $_SESSION['email'] : '')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subjek</label>
                        <select name="subject" class="form-control">
                            <option value="">-- Pilih Subjek --</option>
                            <option value="Pertanyaan Produk" <?= ($_POST['subject']??'')==='Pertanyaan Produk'?'selected':'' ?>>Pertanyaan Produk</option>
                            <option value="Status Pesanan" <?= ($_POST['subject']??'')==='Status Pesanan'?'selected':'' ?>>Status Pesanan</option>
                            <option value="Masalah Pembayaran" <?= ($_POST['subject']??'')==='Masalah Pembayaran'?'selected':'' ?>>Masalah Pembayaran</option>
                            <option value="Pengiriman" <?= ($_POST['subject']??'')==='Pengiriman'?'selected':'' ?>>Pengiriman</option>
                            <option value="Refund" <?= ($_POST['subject']??'')==='Refund'?'selected':'' ?>>Refund</option>
                            <option value="Saran & Masukan" <?= ($_POST['subject']??'')==='Saran & Masukan'?'selected':'' ?>>Saran & Masukan</option>
                            <option value="Lainnya" <?= ($_POST['subject']??'')==='Lainnya'?'selected':'' ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pesan *</label>
                        <textarea name="message" class="form-control" rows="6" required
                                  placeholder="Tulis pesan Anda di sini..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">📤 Kirim Pesan</button>
                </form>
            </div>
        </div>

        <!-- Contact Info -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-body">
                    <h3 style="font-weight:700;margin-bottom:16px;">Informasi Kontak</h3>
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;background:rgba(200,112,58,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📧</div>
                            <div>
                                <div style="font-size:0.75rem;color:var(--text-muted);">Email</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($settings['site_email'] ?? 'meilanitauk@gmailcom.com') ?></div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;background:rgba(200,112,58,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📱</div>
                            <div>
                                <div style="font-size:0.75rem;color:var(--text-muted);">Telepon/WhatsApp</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($settings['site_phone'] ?? '081246547850') ?></div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;background:rgba(200,112,58,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📍</div>
                            <div>
                                <div style="font-size:0.75rem;color:var(--text-muted);">Alamat</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($settings['site_address'] ?? 'Bali,Denpasar Timur,Kesiman Kertalanggu') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 style="font-weight:700;margin-bottom:12px;">⏰ Jam Operasional</h3>
                    <div style="display:flex;flex-direction:column;gap:6px;font-size:0.875rem;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">Senin - Jumat</span><span>08:00 - 17:00</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">Sabtu</span><span>09:00 - 15:00</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">Minggu</span><span style="color:var(--danger);">Libur</span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 style="font-weight:700;margin-bottom:12px;">❓ FAQ</h3>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:0.85rem;">
                        <div>
                            <div style="font-weight:600;margin-bottom:2px;">Berapa lama pengiriman?</div>
                            <div style="color:var(--text-muted);">1-3 hari kerja tergantung lokasi</div>
                        </div>
                        <div class="divider"></div>
                        <div>
                            <div style="font-weight:600;margin-bottom:2px;">Bagaimana cara refund?</div>
                            <div style="color:var(--text-muted);">Hubungi kami dalam 7 hari setelah pesanan diterima</div>
                        </div>
                        <div class="divider"></div>
                        <div>
                            <div style="font-weight:600;margin-bottom:2px;">Apakah ada garansi kualitas?</div>
                            <div style="color:var(--text-muted);">Ya! Semua produk bergaransi kualitas 100%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
