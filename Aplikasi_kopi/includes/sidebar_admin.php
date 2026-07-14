<?php
// Admin Sidebar
$current_path = $_SERVER['PHP_SELF'];
function sidebarLink($href, $icon, $label, $current_path) {
    $active = strpos($current_path, $href) !== false ? 'active' : '';
    echo "<a href='/Aplikasi_kopi/admin/$href' class='$active'><span class='nav-icon'>$icon</span> $label</a>";
}
?>
<aside class="sidebar">
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
        <div class="user-role">Administrator</div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-label">Menu Utama</div>
        <a href="/Aplikasi_kopi/admin/dashboard.php" class="<?= strpos($current_path,'dashboard') !== false ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="sidebar-label" style="margin-top:12px;">Manajemen</div>
        <a href="/Aplikasi_kopi/admin/users.php" class="<?= strpos($current_path,'users') !== false ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Kelola Pengguna
        </a>
        <a href="/Aplikasi_kopi/admin/products.php" class="<?= strpos($current_path,'admin/products') !== false ? 'active' : '' ?>">
            <span class="nav-icon">☕</span> Kelola Produk
        </a>
        <a href="/Aplikasi_kopi/admin/categories.php" class="<?= strpos($current_path,'categories') !== false ? 'active' : '' ?>">
            <span class="nav-icon">🏷️</span> Kelola Kategori
        </a>
        <a href="/Aplikasi_kopi/admin/orders.php" class="<?= strpos($current_path,'admin/orders') !== false ? 'active' : '' ?>">
            <span class="nav-icon">📦</span> Kelola Pesanan
        </a>
        <a href="/Aplikasi_kopi/admin/payments.php" class="<?= strpos($current_path,'payments') !== false ? 'active' : '' ?>">
            <span class="nav-icon">💳</span> Pembayaran & Refund
        </a>
        <a href="/Aplikasi_kopi/admin/promos.php" class="<?= strpos($current_path,'promos') !== false ? 'active' : '' ?>">
            <span class="nav-icon">🎁</span> Promo & Diskon
        </a>

        <div class="sidebar-label" style="margin-top:12px;">Laporan</div>
        <a href="/Aplikasi_kopi/admin/reports.php" class="<?= strpos($current_path,'reports') !== false ? 'active' : '' ?>">
            <span class="nav-icon">📈</span> Laporan Penjualan
        </a>

        <div class="sidebar-label" style="margin-top:12px;">Lainnya</div>
        <a href="/Aplikasi_kopi/admin/settings.php" class="<?= strpos($current_path,'settings') !== false ? 'active' : '' ?>">
            <span class="nav-icon">⚙️</span> Pengaturan
        </a>
        <a href="/Aplikasi_kopi/contact.php">
            <span class="nav-icon">💬</span> Pesan Masuk
        </a>
        <a href="/Aplikasi_kopi/auth/logout.php" style="color:var(--danger);" data-confirm="Yakin ingin logout?">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
