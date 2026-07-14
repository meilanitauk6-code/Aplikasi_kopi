<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

// Categories CRUD
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    $conn->query("DELETE FROM categories WHERE id = $id");
    $_SESSION['flash'] = ['message' => 'Kategori berhasil dihapus.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/categories.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($conn, $_POST['name']);
    $description = escape($conn, $_POST['description'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();

    if ($action === 'edit' && $id) {
        $conn->query("UPDATE categories SET name='$name', description='$description' WHERE id=$id");
        $_SESSION['flash'] = ['message' => 'Kategori berhasil diperbarui.', 'type' => 'success'];
    } else {
        $conn->query("INSERT INTO categories (name, slug, description) VALUES ('$name', '$slug', '$description')");
        $_SESSION['flash'] = ['message' => 'Kategori berhasil ditambahkan.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/admin/categories.php');
}

$editCat = null;
if ($action === 'edit' && $id) {
    $editCat = $conn->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();
}

$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id ORDER BY c.name
");

$pageTitle = 'Kelola Kategori - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">🏷️ Kelola Kategori</h1>
            </div>
            <button data-modal="addCatModal" class="btn btn-primary">+ Tambah Kategori</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;">
            <div class="card">
                <div class="table-container" style="border:none;border-radius:0;">
                    <table>
                        <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Produk</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php $no=1; while ($c = $categories->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $no++ ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></td>
                                <td style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars(substr($c['description']??'',0,60)) ?>...</td>
                                <td><span class="badge badge-primary"><?= $c['product_count'] ?> produk</span></td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-info btn-sm">✏️ Edit</a>
                                        <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Hapus kategori '<?= htmlspecialchars($c['name']) ?>'? Produk yang terkait tidak akan dihapus.">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Edit Form -->
            <?php if ($editCat): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">✏️ Edit Kategori</span></div>
                <div class="card-body">
                    <form method="POST" action="?action=edit&id=<?= $editCat['id'] ?>">
                        <div class="form-group">
                            <label class="form-label">Nama Kategori *</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editCat['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editCat['description']??'') ?></textarea>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-primary">💾 Simpan</button>
                            <a href="/Aplikasi_kopi/admin/categories.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addCatModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header"><span class="modal-title">+ Tambah Kategori</span><button class="modal-close">✕</button></div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Kategori *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Kopi Arabika">
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi kategori..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-close btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">+ Tambah</button>
            </div>
        </form>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
