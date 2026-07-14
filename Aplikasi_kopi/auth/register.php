<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

if (isLoggedIn())
    redirect('/Aplikasi_kopi/');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($conn, $_POST['name'] ?? '');
    $email = escape($conn, $_POST['email'] ?? '');
    $phone = escape($conn, $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau <a href="/Aplikasi_kopi/auth/login.php">masuk</a>.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'buyer')");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                $_SESSION['user_id'] = $userId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'buyer';
                $_SESSION['flash'] = ['message' => 'Akun berhasil dibuat! Selamat berbelanja.', 'type' => 'success'];
                redirect('/Aplikasi_kopi/');
            } else {
                $error = 'Gagal membuat akun. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = 'Daftar Akun - Mey Coffee';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
?>
<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, var(--cream-dark) 0%, var(--cream-deep) 50%, var(--cream) 100%);
    padding: 30px 20px;
    position: relative;
    overflow: hidden;
}
.auth-page::before {
    content: '';
    position: absolute;
    top: -150px; right: -150px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
    border-radius: 50%;
}
.auth-card {
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-xl);
    padding: 44px;
    width: 100%;
    max-width: 480px;
    position: relative;
    z-index: 1;
    box-shadow: var(--shadow-lg);
}
.auth-logo { text-align: center; margin-bottom: 28px; }
.logo-icon {
    width: 60px; height: 60px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 10px;
    box-shadow: 0 8px 24px rgba(193,127,89,0.2);
}
</style>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <?php
            $logoUrl = imageUrl('logo.jpg', '');
            if ($logoUrl):
                ?>
                <img src="<?= $logoUrl ?>" alt="Logo"
                    style="height:60px;width:60px;border-radius:18px;object-fit:cover;margin:0 auto 10px;display:block;box-shadow:0 8px 24px rgba(193,127,89,0.2);">
            <?php else: ?>
                <div class="logo-icon">☕</div>
            <?php endif; ?>
            <h1 style="font-family:'DM Serif Display',serif;font-size:1.7rem;color:var(--primary);margin-bottom:4px;">
                Buat Akun Baru</h1>
            <p style="color:var(--text-muted);font-size:0.875rem;">Bergabung dengan ribuan pecinta kopi</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="name" class="form-control" placeholder="Masukkan nama lengkap" required
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" name="phone" class="form-control" placeholder="08xxxxxxxxxx"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password *</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password"
                        required>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label
                    style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:0.82rem;color:var(--text-muted);">
                    <input type="checkbox" required style="margin-top:2px;accent-color:var(--accent);">
                    Saya menyetujui <a href="#" style="color:var(--accent);">syarat & ketentuan</a> yang berlaku
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Daftar Sekarang</button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--text-muted);">
            Sudah punya akun?
            <a href="/Aplikasi_kopi/auth/login.php" style="color:var(--accent);font-weight:600;">Masuk di sini</a>
        </p>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>