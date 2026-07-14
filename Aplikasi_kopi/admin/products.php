<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$error = '';

// Delete product
if ($action === 'delete' && $id) {
    $conn->query("DELETE FROM products WHERE id = $id");
    $_SESSION['flash'] = ['message' => 'Produk berhasil dihapus.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/products.php');
}

// Toggle status
if ($action === 'toggle' && $id) {
    $p = $conn->query("SELECT status FROM products WHERE id = $id")->fetch_assoc();
    $newStatus = $p['status'] === 'active' ? 'inactive' : 'active';
    $conn->query("UPDATE products SET status = '$newStatus' WHERE id = $id");
    $_SESSION['flash'] = ['message' => 'Status produk diperbarui.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/products.php');
}

// Save product (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $name = escape($conn, $_POST['name']);
    $sellerId = (int) $_POST['seller_id'];
    $categoryId = (int) $_POST['category_id'];
    $type = escape($conn, $_POST['type']);
    $description = escape($conn, $_POST['description']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $weight = (int) $_POST['weight'];
    $status = escape($conn, $_POST['status'] ?? 'active');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image = uploadImage($_FILES['image'], 'products');
    }

    if ($action === 'add') {
        $imgSql = $image ? "'$image'" : 'NULL';
        $conn->query("INSERT INTO products (seller_id, category_id, name, slug, type, description, price, stock, weight, image, status) VALUES ($sellerId, $categoryId, '$name', '$slug', '$type', '$description', $price, $stock, $weight, $imgSql, '$status')");
        $_SESSION['flash'] = ['message' => 'Produk berhasil ditambahkan.', 'type' => 'success'];
    } else {
        $imgSql = $image ? ", image = '$image'" : '';
        $conn->query("UPDATE products SET seller_id=$sellerId, category_id=$categoryId, name='$name', slug='$slug', type='$type', description='$description', price=$price, stock=$stock, weight=$weight, status='$status' $imgSql WHERE id = $id");
        $_SESSION['flash'] = ['message' => 'Produk berhasil diperbarui.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/admin/products.php');
}

// Edit form
$editProduct = null;
if ($action === 'edit' && $id) {
    $editProduct = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
    if (!$editProduct)
        redirect('/Aplikasi_kopi/admin/products.php');
}

// Get filters
$search = escape($conn, $_GET['search'] ?? '');
$typeFilter = escape($conn, $_GET['type'] ?? '');
$where = ['1=1'];
if ($search)
    $where[] = "p.name LIKE '%$search%'";
if ($typeFilter)
    $where[] = "p.type = '$typeFilter'";
$whereStr = implode(' AND ', $where);

$products = $conn->query("
    SELECT p.*, c.name as category_name, u.name as seller_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.seller_id = u.id
    WHERE $whereStr ORDER BY p.created_at DESC
");

// Get sellers and categories for form
$sellers = $conn->query("SELECT id, name FROM users WHERE role = 'seller' AND status = 'active'");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$pageTitle = 'Kelola Produk - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">☕ Kelola Produk</h1>
                <p class="section-subtitle">Manajemen produk Kopi Bubuk & Kopi Biji</p>
            </div>
            <a href="?action=add" class="btn btn-primary">+ Tambah Produk</a>
        </div>

        <?php if (in_array($action, ['add', 'edit'])): ?>
            <!-- Product Form -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <span class="card-title"><?= $action === 'add' ? '+ Tambah Produk Baru' : '✏️ Edit Produk' ?></span>
                    <a href="/Aplikasi_kopi/admin/products.php" class="btn btn-secondary btn-sm">← Kembali</a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label">Nama Produk *</label>
                                <input type="text" name="name" class="form-control" required
                                    value="<?= htmlspecialchars($editProduct['name'] ?? $_POST['name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Penjual *</label>
                                <select name="seller_id" class="form-control" required>
                                    <?php $sellers->data_seek(0);
                                    while ($s = $sellers->fetch_assoc()): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($editProduct['seller_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-control">
                                    <option value="0">-- Pilih Kategori --</option>
                                    <?php $categories->data_seek(0);
                                    while ($c = $categories->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Jenis *</label>
                                <select name="type" class="form-control" required>
                                    <option value="bubuk" <?= ($editProduct['type'] ?? '') === 'bubuk' ? 'selected' : '' ?>>☕
                                        Kopi Bubuk</option>
                                    <option value="biji" <?= ($editProduct['type'] ?? '') === 'biji' ? 'selected' : '' ?>>🫘
                                        Kopi Biji</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Harga (Rp) *</label>
                                <input type="number" name="price" class="form-control" required min="0" step="500"
                                    value="<?= $editProduct['price'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Stok *</label>
                                <input type="number" name="stock" class="form-control" required min="0"
                                    value="<?= $editProduct['stock'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Berat (gram)</label>
                                <input type="number" name="weight" class="form-control" min="0"
                                    value="<?= $editProduct['weight'] ?? 0 ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($editProduct['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="inactive" <?= ($editProduct['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea name="description" class="form-control"
                                rows="4"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Foto Produk
                                <?= $action === 'edit' ? '(kosongkan jika tidak diubah)' : '' ?></label>
                            <?php $editImg = imageUrl($editProduct['image'] ?? '');
                            if ($editImg): ?>
                                <img src="<?= htmlspecialchars($editImg) ?>" id="proofPreview"
                                    style="height:80px;border-radius:8px;margin-bottom:8px;object-fit:cover;">
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*"
                                data-preview="imagePreview" style="padding:8px;">
                            <img id="imagePreview" src=""
                                style="display:none;margin-top:8px;height:100px;border-radius:8px;object-fit:cover;">
                        </div>

                        <div style="display:flex;gap:10px;">
                            <button type="submit"
                                class="btn btn-primary"><?= $action === 'add' ? '+ Tambah Produk' : '💾 Simpan Perubahan' ?></button>
                            <a href="/Aplikasi_kopi/admin/products.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:14px;">
                <form method="GET" style="display:flex;gap:10px;">
                    <div class="search-bar" style="flex:1;">
                        <input type="text" name="search" placeholder="Cari nama produk..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>
                    <select name="type" class="form-control" style="width:160px;" onchange="this.form.submit()">
                        <option value="">Semua Jenis</option>
                        <option value="bubuk" <?= $typeFilter === 'bubuk' ? 'selected' : '' ?>> ☕Kopi Bubuk</option>
                        <option value="biji" <?= $typeFilter === 'biji' ? 'selected' : '' ?>>🫘 Kopi Biji</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Terjual</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($p = $products->fetch_assoc()): ?>
                            <tr class="searchable-row">
                                <td style="color:var(--text-muted);"><?= $no++ ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div
                                            style="width:40px;height:40px;background:var(--bg-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                            <?php $pImg = imageUrl($p['image']);
                                            if ($pImg): ?>
                                                <img src="<?= htmlspecialchars($pImg) ?>"
                                                    style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                                            <?php else: ?>
                                                <?= $p['type'] === 'biji' ? '🫘' : '☕' ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:0.85rem;">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);">
                                                <?= htmlspecialchars($p['seller_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span
                                        class="product-type-badge type-<?= $p['type'] ?>"><?= ucfirst($p['type']) ?></span>
                                </td>
                                <td style="font-weight:600;"><?= formatRupiah($p['price']) ?></td>
                                <td>
                                    <span
                                        style="font-weight:600;color:<?= $p['stock'] <= 5 ? 'var(--danger)' : ($p['stock'] <= 20 ? 'var(--warning)' : 'var(--success)') ?>">
                                        <?= $p['stock'] ?>
                                    </span>
                                </td>
                                <td><?= $p['total_sold'] ?></td>
                                <td>⭐ <?= number_format($p['rating'], 1) ?></td>
                                <td><span
                                        class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= $p['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                                        <a href="?action=toggle&id=<?= $p['id'] ?>" class="btn btn-warning btn-sm"
                                            data-confirm="Ubah status produk?"><?= $p['status'] === 'active' ? '🔒' : '🔓' ?></a>
                                        <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                            data-confirm="Hapus produk ini? Tidak bisa dipulihkan!">🗑️</a>
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
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>