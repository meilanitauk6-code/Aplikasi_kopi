<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

// Delete promo
if ($action === 'delete' && $id) {
    $conn->query("DELETE FROM promos WHERE id = $id");
    $_SESSION['flash'] = ['message' => 'Promo berhasil dihapus.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/promos.php');
}

// Toggle promo
if ($action === 'toggle' && $id) {
    $p = $conn->query("SELECT status FROM promos WHERE id=$id")->fetch_assoc();
    $new = $p['status'] === 'active' ? 'inactive' : 'active';
    $conn->query("UPDATE promos SET status='$new' WHERE id=$id");
    $_SESSION['flash'] = ['message' => 'Status promo diperbarui.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/promos.php');
}

// Save promo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(escape($conn, $_POST['code']));
    $name = escape($conn, $_POST['name']);
    $type = escape($conn, $_POST['type']);
    $value = (float)$_POST['value'];
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $maxUse = (int)($_POST['max_use'] ?? 0);
    $expiredAt = escape($conn, $_POST['expired_at'] ?? '');
    $status = escape($conn, $_POST['status'] ?? 'active');
    $expSql = $expiredAt ? "'$expiredAt'" : 'NULL';

    if ($action === 'edit' && $id) {
        $conn->query("UPDATE promos SET code='$code', name='$name', type='$type', value=$value, min_order=$minOrder, max_use=$maxUse, expired_at=$expSql, status='$status' WHERE id=$id");
        $_SESSION['flash'] = ['message' => 'Promo berhasil diperbarui.', 'type' => 'success'];
    } else {
        $conn->query("INSERT INTO promos (code, name, type, value, min_order, max_use, expired_at, status) VALUES ('$code','$name','$type',$value,$minOrder,$maxUse,$expSql,'$status')");
        $_SESSION['flash'] = ['message' => 'Promo berhasil ditambahkan.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/admin/promos.php');
}

$editPromo = null;
if ($action === 'edit' && $id) {
    $editPromo = $conn->query("SELECT * FROM promos WHERE id=$id")->fetch_assoc();
}

$promos = $conn->query("SELECT * FROM promos ORDER BY created_at DESC");

$pageTitle = 'Kelola Promo - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div><h1 class="section-title">🎁 Promo & Diskon</h1></div>
            <button data-modal="addPromoModal" class="btn btn-primary">+ Tambah Promo</button>
        </div>

        <?php if ($action === 'edit' && $editPromo): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><span class="card-title">✏️ Edit Promo</span><a href="/Aplikasi_kopi/admin/promos.php" class="btn btn-secondary btn-sm">← Batal</a></div>
            <div class="card-body">
                <form method="POST" action="?action=edit&id=<?= $editPromo['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kode Promo *</label>
                            <input type="text" name="code" class="form-control" required style="text-transform:uppercase;" value="<?= htmlspecialchars($editPromo['code']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $editPromo['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $editPromo['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Promo *</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editPromo['name']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipe Diskon *</label>
                            <select name="type" class="form-control" required>
                                <option value="percent" <?= $editPromo['type'] === 'percent' ? 'selected' : '' ?>>Persen (%)</option>
                                <option value="fixed" <?= $editPromo['type'] === 'fixed' ? 'selected' : '' ?>>Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nilai Diskon *</label>
                            <input type="number" name="value" class="form-control" required min="1" value="<?= $editPromo['value'] ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Min. Order (Rp)</label>
                            <input type="number" name="min_order" class="form-control" min="0" value="<?= $editPromo['min_order'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Maks. Penggunaan (0=∞)</label>
                            <input type="number" name="max_use" class="form-control" min="0" value="<?= $editPromo['max_use'] ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berlaku Hingga</label>
                        <input type="date" name="expired_at" class="form-control" value="<?= $editPromo['expired_at'] ?? '' ?>">
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary">💾 Simpan</button>
                        <a href="/Aplikasi_kopi/admin/promos.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Nilai</th><th>Min. Order</th><th>Digunakan</th><th>Expired</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php while ($promo = $promos->fetch_assoc()): ?>
                        <tr>
                            <td><code style="background:var(--bg-surface);padding:3px 8px;border-radius:6px;font-size:0.85rem;color:var(--primary);"><?= htmlspecialchars($promo['code']) ?></code></td>
                            <td style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($promo['name']) ?></td>
                            <td><span class="badge <?= $promo['type'] === 'percent' ? 'badge-info' : 'badge-primary' ?>"><?= $promo['type'] === 'percent' ? '%' : 'Rp' ?></span></td>
                            <td style="font-weight:600;"><?= $promo['type'] === 'percent' ? $promo['value'].'%' : formatRupiah($promo['value']) ?></td>
                            <td style="font-size:0.85rem;"><?= formatRupiah($promo['min_order']) ?></td>
                            <td><?= $promo['used'] ?>/<?= $promo['max_use'] ?: '∞' ?></td>
                            <td style="font-size:0.82rem;color:var(--text-muted);"><?= $promo['expired_at'] ? date('d M Y', strtotime($promo['expired_at'])) : 'Tidak terbatas' ?></td>
                            <td><span class="badge <?= $promo['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= $promo['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="?action=edit&id=<?= $promo['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                                    <a href="?action=toggle&id=<?= $promo['id'] ?>" class="btn btn-warning btn-sm" data-confirm="Ubah status promo?"><?= $promo['status'] === 'active' ? '🔒' : '🔓' ?></a>
                                    <a href="?action=delete&id=<?= $promo['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Hapus promo ini?">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Promo Modal -->
<div class="modal-overlay" id="addPromoModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">+ Tambah Promo Baru</span><button class="modal-close">✕</button></div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode Promo *</label>
                        <input type="text" name="code" class="form-control" required placeholder="Contoh: KOPI10" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Promo *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Nama promo">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe Diskon *</label>
                        <select name="type" class="form-control" required>
                            <option value="percent">Persen (%)</option>
                            <option value="fixed">Nominal (Rp)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nilai Diskon *</label>
                        <input type="number" name="value" class="form-control" required min="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Min. Order (Rp)</label>
                        <input type="number" name="min_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maks. Penggunaan (0=∞)</label>
                        <input type="number" name="max_use" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Berlaku Hingga</label>
                    <input type="date" name="expired_at" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-close btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">+ Tambah Promo</button>
            </div>
        </form>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
