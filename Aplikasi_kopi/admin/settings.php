<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $k = escape($conn, $key);
        $v = escape($conn, $value);
        $conn->query("INSERT INTO settings (`key`, `value`) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE `value`='$v'");
    }
    $_SESSION['flash'] = ['message' => 'Pengaturan berhasil disimpan.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/settings.php');
}

$settings = getSettings($conn);

$pageTitle = 'Pengaturan Sistem - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div><h1 class="section-title">⚙️ Pengaturan Sistem</h1></div>
        </div>

        <form method="POST">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <!-- Site Settings -->
                <div class="card">
                    <div class="card-header"><span class="card-title">🌐 Informasi Toko</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nama Toko</label>
                            <input type="text" name="settings[site_name]" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="settings[site_tagline]" class="form-control" value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="settings[site_email]" class="form-control" value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="settings[site_phone]" class="form-control" value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="settings[site_address]" class="form-control" rows="2"><?= htmlspecialchars($settings['site_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">🏦 Rekening Bank</span></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="settings[bank_name]" class="form-control" value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="text" name="settings[bank_account]" class="form-control" value="<?= htmlspecialchars($settings['bank_account'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Atas Nama</label>
                                <input type="text" name="settings[bank_holder]" class="form-control" value="<?= htmlspecialchars($settings['bank_holder'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="card-title">🚚 Pengiriman</span></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Ongkos Kirim Default (Rp)</label>
                                <input type="number" name="settings[shipping_cost]" class="form-control" value="<?= htmlspecialchars($settings['shipping_cost'] ?? 15000) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="card-title">📣 Media Sosial</span></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Instagram</label>
                                <input type="text" name="settings[instagram]" class="form-control" placeholder="@kopiku" value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" name="settings[whatsapp]" class="form-control" placeholder="628xxxxxxxxx" value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary btn-lg">💾 Simpan Pengaturan</button>
            </div>
        </form>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
