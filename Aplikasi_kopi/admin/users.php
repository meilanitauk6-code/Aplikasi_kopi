<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('admin');

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Activate/Deactivate
if ($action === 'toggle' && $id) {
    $user = $conn->query("SELECT status FROM users WHERE id = $id")->fetch_assoc();
    if ($user) {
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE users SET status = '$newStatus' WHERE id = $id");
        $_SESSION['flash'] = ['message' => "Status pengguna berhasil diubah menjadi $newStatus.", 'type' => 'success'];
    }
    redirect('/Aplikasi_kopi/admin/users.php');
}

// Delete
if ($action === 'delete' && $id) {
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    $_SESSION['flash'] = ['message' => 'Pengguna berhasil dihapus.', 'type' => 'success'];
    redirect('/Aplikasi_kopi/admin/users.php');
}

// Add user
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($conn, $_POST['name']);
    $email = escape($conn, $_POST['email']);
    $phone = escape($conn, $_POST['phone'] ?? '');
    $role = escape($conn, $_POST['role']);
    $password = $_POST['password'] ?? 'password';

    if (!in_array($role, ['buyer', 'seller'])) $role = 'buyer';

    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = 'Email sudah terdaftar.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$hash', '$role')");
        if ($role === 'seller') {
            $uid = $conn->insert_id;
            $storeName = escape($conn, $name . "'s Store");
            $conn->query("INSERT INTO stores (seller_id, name) VALUES ($uid, '$storeName')");
        }
        $_SESSION['flash'] = ['message' => 'Pengguna berhasil ditambahkan.', 'type' => 'success'];
        redirect('/Aplikasi_kopi/admin/users.php');
    }
}

// Filters
$search = escape($conn, $_GET['search'] ?? '');
$roleFilter = escape($conn, $_GET['role'] ?? '');
$statusFilter = escape($conn, $_GET['status'] ?? '');

$where = ['1=1'];
if ($search) $where[] = "(name LIKE '%$search%' OR email LIKE '%$search%')";
if ($roleFilter) $where[] = "role = '$roleFilter'";
if ($statusFilter) $where[] = "status = '$statusFilter'";
$whereClause = implode(' AND ', $where);

$users = $conn->query("SELECT * FROM users WHERE $whereClause ORDER BY created_at DESC");

$pageTitle = 'Kelola Pengguna - Admin KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/sidebar_admin.php'; ?>
    <main class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">👥 Kelola Pengguna</h1>
                <p class="section-subtitle">Manajemen akun Penjual & Pembeli</p>
            </div>
            <button data-modal="addUserModal" class="btn btn-primary">+ Tambah Pengguna</button>
        </div>

        <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>

        <!-- Filters -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:16px;">
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
                    <div class="search-bar" style="flex:1;min-width:200px;">
                        <input type="text" name="search" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($search) ?>" id="tableSearch">
                        <button type="submit" class="search-btn">🔍</button>
                    </div>
                    <select name="role" class="form-control" style="width:150px;" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        <option value="seller" <?= $roleFilter === 'seller' ? 'selected' : '' ?>>Penjual</option>
                        <option value="buyer" <?= $roleFilter === 'buyer' ? 'selected' : '' ?>>Pembeli</option>
                    </select>
                    <select name="status" class="form-control" style="width:150px;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
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
                            <th>Pengguna</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($u = $users->fetch_assoc()): ?>
                        <tr class="searchable-row">
                            <td style="color:var(--text-muted);"><?= $no++ ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <span style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($u['name']) ?></span>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : ($u['role'] === 'seller' ? 'badge-info' : 'badge-secondary') ?>">
                                    <?= $u['role'] === 'admin' ? '🔧 Admin' : ($u['role'] === 'seller' ? '🏪 Penjual' : '👤 Pembeli') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['status'] === 'active' ? '✅ Aktif' : '❌ Nonaktif' ?>
                                </span>
                            </td>
                            <td style="color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <a href="?action=toggle&id=<?= $u['id'] ?>" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>"
                                       data-confirm="Ubah status pengguna ini?">
                                        <?= $u['status'] === 'active' ? '🔒' : '🔓' ?>
                                    </a>
                                    <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                                       data-confirm="Yakin hapus pengguna ini? Data tidak bisa dipulihkan!">🗑️</a>
                                    <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">+ Tambah Pengguna Baru</span>
            <button class="modal-close">✕</button>
        </div>
        <form method="POST" action="?action=add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="buyer">Pembeli</option>
                            <option value="seller">Penjual</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" value="password" placeholder="Default: password">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-close btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
            </div>
        </form>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
