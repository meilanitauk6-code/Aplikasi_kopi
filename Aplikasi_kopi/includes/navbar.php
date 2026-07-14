<?php
// Public Navbar - for buyer-facing pages
$isLoggedIn = isLoggedIn();
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';
$isSeller = $isLoggedIn && $_SESSION['role'] === 'seller';
$isBuyer = $isLoggedIn && $_SESSION['role'] === 'buyer';
?>
<nav class="navbar">
    <div class="navbar-inner">
        <?php
        $projectRoot = str_replace('\\', '/', dirname(dirname(__FILE__)));
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $basePath = str_replace($docRoot, '', $projectRoot);
        $basePath = '/' . ltrim($basePath, '/');
        if (substr($basePath, -1) !== '/') {
            $basePath .= '/';
        }
        $logoUrl = imageUrl('logo.jpg', '');
        ?>
        <a href="<?= $basePath ?>" class="navbar-brand">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" alt="Logo" style="height: 38px; width: 38px; border-radius: 10px; object-fit: cover;">
            <?php else: ?>
                <div class="brand-icon">☕</div>
            <?php endif; ?>
            Mey Coffee
        </a>

        <ul class="navbar-menu">
            <li><a href="/Aplikasi_kopi/" <?= $current_page === 'index.php' ? 'class="active"' : '' ?>>Beranda</a></li>
            <li><a href="/Aplikasi_kopi/buyer/products.php" <?= strpos($current_path, '/buyer/products') !== false ? 'class="active"' : '' ?>>Produk</a></li>
            <li><a href="/Aplikasi_kopi/contact.php" <?= $current_page === 'contact.php' ? 'class="active"' : '' ?>>Kontak</a></li>
            <?php if ($isAdmin): ?>
                <li><a href="/Aplikasi_kopi/admin/dashboard.php">🔧 Admin</a></li>
            <?php elseif ($isSeller): ?>
                <li><a href="/Aplikasi_kopi/seller/dashboard.php">🏪 Dashboard</a></li>
            <?php endif; ?>
        </ul>

        <div class="navbar-actions">
            <?php if ($isBuyer || !$isLoggedIn): ?>
                <a href="/Aplikasi_kopi/buyer/cart.php" class="cart-btn" title="Keranjang">
                    🛒
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <div style="position:relative;">
                    <button onclick="toggleUserMenu()"
                        style="background:var(--cream-dark);border:1px solid var(--border);color:var(--text-primary);padding:8px 16px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:8px;font-family:inherit;font-size:0.875rem;font-weight:500;">
                        <span
                            style="width:28px;height:28px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:white;"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></span>
                        <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>
                        <?php if ($notifCount > 0): ?>
                            <span class="cart-badge" style="position:relative;top:auto;right:auto;"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="userMenu"
                        style="display:none;position:absolute;right:0;top:calc(100% + 8px);background:var(--bg-card);border:1px solid var(--border-light);border-radius:12px;min-width:200px;z-index:100;overflow:hidden;box-shadow:var(--shadow-lg);">
                        <?php if ($isBuyer): ?>
                            <a href="/Aplikasi_kopi/buyer/orders.php"
                                style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-secondary);font-size:0.875rem;transition:var(--transition);"
                                onmouseover="this.style.background='var(--border-light)'"
                                onmouseout="this.style.background=''">📦 Pesanan Saya</a>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <a href="/Aplikasi_kopi/admin/dashboard.php"
                                style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-secondary);font-size:0.875rem;transition:var(--transition);"
                                onmouseover="this.style.background='var(--border-light)'"
                                onmouseout="this.style.background=''">🔧 Admin Panel</a>
                        <?php elseif ($isSeller): ?>
                            <a href="/Aplikasi_kopi/seller/dashboard.php"
                                style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--text-secondary);font-size:0.875rem;transition:var(--transition);"
                                onmouseover="this.style.background='var(--border-light)'"
                                onmouseout="this.style.background=''">🏪 Dashboard Penjual</a>
                        <?php endif; ?>
                        <div style="height:1px;background:var(--border);margin:4px 0;"></div>
                        <a href="/Aplikasi_kopi/auth/logout.php"
                            style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--danger);font-size:0.875rem;transition:var(--transition);"
                            onmouseover="this.style.background='var(--border-light)'"
                            onmouseout="this.style.background=''">🚪 Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/Aplikasi_kopi/auth/login.php" class="btn btn-secondary btn-sm">Masuk</a>
                <a href="/Aplikasi_kopi/auth/register.php" class="btn btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script>
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[onclick="toggleUserMenu()"]') && !e.target.closest('#userMenu')) {
            const m = document.getElementById('userMenu');
            if (m) m.style.display = 'none';
        }
    });
</script>