<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin': redirect('/Aplikasi_kopi/admin/dashboard.php');
        case 'seller': redirect('/Aplikasi_kopi/seller/dashboard.php');
        case 'buyer': redirect('/Aplikasi_kopi/');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = escape($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Akun Anda telah dinonaktifkan. Hubungi administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['flash'] = ['message' => 'Selamat datang, ' . $user['name'] . '!', 'type' => 'success'];

                switch ($user['role']) {
                    case 'admin': redirect('/Aplikasi_kopi/admin/dashboard.php');
                    case 'seller': redirect('/Aplikasi_kopi/seller/dashboard.php');
                    default: redirect('/Aplikasi_kopi/');
                }
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$pageTitle = 'Masuk - Mey Coffee';
$pageDesc = 'Login ke akun Mey Coffee Anda';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
?>
<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, var(--cream-dark) 0%, var(--cream-deep) 50%, var(--cream) 100%);
    padding: 20px;
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
.auth-page::after {
    content: '';
    position: absolute;
    bottom: -100px; left: -100px;
    width: 400px; height: 400px;
    background: radial-gradient(circle, var(--accent-pale) 0%, transparent 70%);
    border-radius: 50%;
}
.auth-card {
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-xl);
    padding: 44px;
    width: 100%;
    max-width: 440px;
    position: relative;
    z-index: 1;
    box-shadow: var(--shadow-lg);
}
.auth-logo {
    text-align: center;
    margin-bottom: 32px;
}
.auth-logo .logo-icon {
    width: 68px; height: 68px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    margin: 0 auto 12px;
    box-shadow: 0 8px 24px rgba(193,127,89,0.25);
}
.auth-logo h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.8rem;
    color: var(--primary);
    margin-bottom: 4px;
}
.auth-logo p { color: var(--text-muted); font-size: 0.875rem; }
.auth-divider {
    display: flex; align-items: center; gap: 12px;
    color: var(--text-muted); font-size: 0.8rem;
    margin: 20px 0;
}
.auth-divider::before, .auth-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}
.demo-accounts {
    background: var(--cream-dark);
    border: 1px solid var(--border-light);
    border-radius: 10px;
    padding: 14px;
    margin-top: 20px;
    font-size: 0.78rem;
}
.demo-accounts .demo-title {
    font-weight: 600; color: var(--text-secondary);
    margin-bottom: 8px; font-size: 0.8rem;
}
.demo-account {
    display: flex; justify-content: space-between;
    padding: 4px 0; color: var(--text-muted);
    border-bottom: 1px solid var(--border-light);
}
.demo-account:last-child { border-bottom: none; }
.demo-account strong { color: var(--text-secondary); }
.clickable-demo { cursor: pointer; }
.clickable-demo:hover { color: var(--accent); }
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
            <h1>Mey Coffee</h1>
            <p>Masuk ke akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
            <div class="alert alert-warning">⚠️ Anda tidak memiliki akses ke halaman tersebut.</div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="contoh@email.com" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Masukkan password" required style="padding-right:44px;">
                    <button type="button" onclick="togglePwd()" id="pwdToggle"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
                Masuk ke Mey Coffee
            </button>
        </form>

        <div class="auth-divider">atau</div>

        <p style="text-align:center;font-size:0.875rem;color:var(--text-muted);">
            Belum punya akun?
            <a href="/Aplikasi_kopi/auth/register.php" style="color:var(--accent);font-weight:600;">Daftar Sekarang</a>
        </p>

        <div class="demo-accounts">
            <div class="demo-title">🔑 Akun Demo (Password: password)</div>
            <div class="demo-account clickable-demo" onclick="fillDemo('admin@kopiku.com')">
                <strong>Admin</strong> <span>admin@kopiku.com</span>
            </div>
            <div class="demo-account clickable-demo" onclick="fillDemo('penjual@kopiku.com')">
                <strong>Penjual</strong> <span>penjual@kopiku.com</span>
            </div>
            <div class="demo-account clickable-demo" onclick="fillDemo('pembeli@kopiku.com')">
                <strong>Pembeli</strong> <span>pembeli@kopiku.com</span>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePwd() {
        const p = document.getElementById('password');
        const btn = document.getElementById('pwdToggle');
        if (p.type === 'password') { p.type = 'text'; btn.textContent = '🙈'; }
        else { p.type = 'password'; btn.textContent = '👁️'; }
    }

    function fillDemo(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
    }
</script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>