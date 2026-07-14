<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('seller');

$sellerId = (int)$_SESSION['user_id'];
$store = $conn->query("SELECT * FROM stores WHERE seller_id=$sellerId")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = escape($conn, $_POST['store_name']);
    $desc = escape($conn, $_POST['description'] ?? '');
    $address = escape($conn, $_POST['address'] ?? '');
    $phone = escape($conn, $_POST['phone'] ?? '');

    $logo = null;
    if (!empty($_FILES['logo']['name'])) {
        $logo = uploadImage($_FILES['logo'], 'stores');
    }

    if ($store) {
        $logSql = $logo ? ", logo='$logo'" : '';
        $conn->query("UPDATE stores SET name='$storeName', description='$desc', address='$address', phone='$phone' $logSql WHERE seller_id=$sellerId");
    } else {
        $logSql = $logo ? "'$logo'" : 'NULL';
        $conn->query("INSERT INTO stores (seller_id, name, description, address, phone, logo) VALUES ($sellerId, '$storeName', '$desc', '$address', '$phone', $logSql)");
    }

    // Also update user profile
    $userName = escape($conn, $_POST['user_name']);
    $userPhone = escape($conn, $_POST['user_phone'] ?? '');
    $userAddress = escape($conn, $_POST['user_address'] ?? '');
    $conn->query("UPDATE users SET name='$userName', phone='$userPhone', address='$userAddress' WHERE id=$sellerId");
    $_SESSION['name'] = $userName;

    $_SESSION['flash'] = ['message' => 'Profil toko berhasil disimpan.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/seller/profile.php');
}

$user = $conn->query("SELECT * FROM users WHERE id=$sellerId")->fetch_assoc();
$store = $conn->query("SELECT * FROM stores WHERE seller_id=$sellerId")->fetch_assoc();

$pageTitle = 'Profil Toko - Penjual KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_seller.php'; ?>
    <main class="main-content">
        <div class="section-header"><div><h1 class="section-title">🏪 Profil Toko</h1></div></div>
        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="card">
                    <div class="card-header"><span class="card-title">🏪 Info Toko</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nama Toko *</label>
                            <input type="text" name="store_name" class="form-control" required value="<?= htmlspecialchars($store['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi Toko</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($store['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat Toko</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon Toko</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($store['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Logo Toko</label>
                            <?php if (!empty($store['logo'])): ?>
                            <img src="/Aplikasi_kopi/uploads/stores/<?= $store['logo'] ?>" style="height:60px;border-radius:10px;margin-bottom:8px;">
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept="image/*" style="padding:8px;" data-preview="logoPreview">
                            <img id="logoPreview" src="" style="display:none;margin-top:6px;height:60px;border-radius:8px;">
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><span class="card-title">👤 Profil Penjual</span></div>
                    <div class="card-body">
                        <div style="width:60px;height:60px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:14px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.5rem;margin-bottom:16px;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="user_name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;">
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:3px;">Email tidak bisa diubah</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="tel" name="user_phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="user_address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div style="background:var(--bg-surface);border-radius:10px;padding:12px;font-size:0.82rem;">
                            <div style="color:var(--text-muted);">Bergabung: <?= date('d M Y', strtotime($user['created_at'])) ?></div>
                            <div style="color:var(--text-muted);">Role: <strong style="color:var(--primary);">Penjual</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary btn-lg">💾 Simpan Profil</button>
            </div>
        </form>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
