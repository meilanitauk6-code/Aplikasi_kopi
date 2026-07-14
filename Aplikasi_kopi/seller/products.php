<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('seller');

$sellerId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Delete
if ($action === 'delete' && $id) {
    $conn->query("DELETE FROM products WHERE id = $id AND seller_id = $sellerId");
    $_SESSION['flash'] = ['message' => 'Produk berhasil dihapus.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/seller/products.php');
}

// Toggle
if ($action === 'toggle' && $id) {
    $p = $conn->query("SELECT status FROM products WHERE id=$id AND seller_id=$sellerId")->fetch_assoc();
    if ($p) {
        $ns = $p['status'] === 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE products SET status='$ns' WHERE id=$id");
        $_SESSION['flash'] = ['message' => 'Status produk diperbarui.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/seller/products.php');
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $name = escape($conn, $_POST['name']);
    $catId = (int)$_POST['category_id'];
    $type = escape($conn, $_POST['type']);
    $desc = escape($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $weight = (int)$_POST['weight'];
    $status = escape($conn, $_POST['status'] ?? 'active');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image = uploadImage($_FILES['image'], 'products');
    }

    if ($action === 'add') {
        $imgSql = $image ? "'$image'" : 'NULL';
        $conn->query("INSERT INTO products (seller_id,category_id,name,slug,type,description,price,stock,weight,image,status) VALUES ($sellerId,$catId,'$name','$slug','$type','$desc',$price,$stock,$weight,$imgSql,'$status')");
        $_SESSION['flash'] = ['message' => 'Produk berhasil ditambahkan.', 'type' => 'success'];
    } else {
        $imgSql = $image ? ", image='$image'" : '';
        $conn->query("UPDATE products SET category_id=$catId,name='$name',slug='$slug',type='$type',description='$desc',price=$price,stock=$stock,weight=$weight,status='$status'$imgSql WHERE id=$id AND seller_id=$sellerId");
        $_SESSION['flash'] = ['message' => 'Produk berhasil diperbarui.', 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/seller/products.php');
}

$editProduct = null;
if ($action === 'edit' && $id) {
    $editProduct = $conn->query("SELECT * FROM products WHERE id=$id AND seller_id=$sellerId")->fetch_assoc();
    if (!$editProduct) redirect('/Aplikasi_kopi/seller/products.php');
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.seller_id=$sellerId ORDER BY p.created_at DESC");

$pageTitle = 'Kelola Produk - Penjual KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>
<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_seller.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div><h1 class="section-title">☕ Produk Saya</h1></div>
            <a href="?action=add" class="btn btn-primary">+ Tambah Produk</a>
        </div>

        <?php if (in_array($action, ['add', 'edit'])): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title"><?= $action==='add'?'+ Tambah Produk Baru':'✏️ Edit Produk' ?></span>
                <a href="/Aplikasi_kopi/seller/products.php" class="btn btn-secondary btn-sm">← Kembali</a>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group" style="grid-column:span 2;">
                            <label class="form-label">Nama Produk *</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editProduct['name']??'') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-control">
                                <option value="0">-- Pilih Kategori --</option>
                                <?php while($c=$categories->fetch_assoc()): ?>
                                <option value="<?=$c['id']?>" <?=($editProduct['category_id']??0)==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis *</label>
                            <select name="type" class="form-control" required>
                                <option value="bubuk" <?=($editProduct['type']??'')==='bubuk'?'selected':''?>>☕ Kopi Bubuk</option>
                                <option value="biji" <?=($editProduct['type']??'')==='biji'?'selected':''?>>🫘 Kopi Biji</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">Harga (Rp) *</label>
                            <input type="number" name="price" class="form-control" required min="0" step="500" value="<?=$editProduct['price']??''?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stok *</label>
                            <input type="number" name="stock" class="form-control" required min="0" value="<?=$editProduct['stock']??''?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Berat (gram)</label>
                            <input type="number" name="weight" class="form-control" min="0" value="<?=$editProduct['weight']??0?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4"><?=htmlspecialchars($editProduct['description']??'')?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Foto Produk</label>
                            <?php $editImg = imageUrl($editProduct['image'] ?? ''); if($editImg): ?>
                            <img src="<?= htmlspecialchars($editImg) ?>" style="height:60px;border-radius:8px;margin-bottom:6px;object-fit:cover;">
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*" data-preview="imagePreview" style="padding:8px;">
                            <img id="imagePreview" src="" style="display:none;margin-top:6px;height:80px;border-radius:8px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?=($editProduct['status']??'active')==='active'?'selected':''?>>Aktif</option>
                                <option value="inactive" <?=($editProduct['status']??'')==='inactive'?'selected':''?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary"><?=$action==='add'?'+ Tambah':'💾 Simpan'?></button>
                        <a href="/Aplikasi_kopi/seller/products.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container" style="border:none;border-radius:0;">
                <table>
                    <thead><tr><th>Produk</th><th>Jenis</th><th>Harga</th><th>Stok</th><th>Terjual</th><th>Rating</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php while($p=$products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:38px;height:38px;background:var(--bg-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                        <?php $pImg = imageUrl($p['image']); if($pImg): ?>
                                        <img src="<?= htmlspecialchars($pImg) ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                                        <?php else: ?><?=$p['type']==='biji'?'🫘':'☕'?><?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.85rem;"><?=htmlspecialchars($p['name'])?></div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);"><?=htmlspecialchars($p['cat_name']??'-')?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="product-type-badge type-<?=$p['type']?>"><?=ucfirst($p['type'])?></span></td>
                            <td style="font-weight:600;"><?=formatRupiah($p['price'])?></td>
                            <td><span style="font-weight:700;color:<?=$p['stock']<=5?'var(--danger)':($p['stock']<=20?'var(--warning)':'var(--success)')?>"><?=$p['stock']?></span></td>
                            <td><?=$p['total_sold']?></td>
                            <td>⭐ <?=number_format($p['rating'],1)?></td>
                            <td><span class="badge <?=$p['status']==='active'?'badge-success':'badge-danger'?>"><?=$p['status']==='active'?'Aktif':'Nonaktif'?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="?action=edit&id=<?=$p['id']?>" class="btn btn-info btn-sm">✏️</a>
                                    <a href="?action=toggle&id=<?=$p['id']?>" class="btn btn-warning btn-sm" data-confirm="Ubah status produk?"><?=$p['status']==='active'?'🔒':'🔓'?></a>
                                    <a href="?action=delete&id=<?=$p['id']?>" class="btn btn-danger btn-sm" data-confirm="Hapus produk ini?">🗑️</a>
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
