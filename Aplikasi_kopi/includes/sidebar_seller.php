<?php
// Seller Sidebar
$current_path = $_SERVER['PHP_SELF'];
?>
<aside class="sidebar">
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
        <div class="user-role">Penjual</div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-label">Menu Utama</div>
        <a href="/Aplikasi_kopi/seller/dashboard.php" class="<?= strpos($current_path,'dashboard') !== false ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="sidebar-label" style="margin-top:12px;">Toko</div>
        <a href="/Aplikasi_kopi/seller/products.php" class="<?= strpos($current_path,'seller/products') !== false ? 'active' : '' ?>">
            <span class="nav-icon">☕</span> Kelola Produk
        </a>
        <a href="/Aplikasi_kopi/seller/orders.php" class="<?= strpos($current_path,'seller/orders') !== false ? 'active' : '' ?>">
            <span class="nav-icon">📦</span> Kelola Pesanan
        </a>
        <a href="/Aplikasi_kopi/seller/shipping.php" class="<?= strpos($current_path,'shipping') !== false ? 'active' : '' ?>">
            <span class="nav-icon">🚚</span> Kelola Pengiriman
        </a>

        <div class="sidebar-label" style="margin-top:12px;">Profil</div>
        <a href="/Aplikasi_kopi/seller/profile.php" class="<?= strpos($current_path,'profile') !== false ? 'active' : '' ?>">
            <span class="nav-icon">🏪</span> Profil Toko
        </a>
        <a href="/Aplikasi_kopi/contact.php">
            <span class="nav-icon">💬</span> Hubungi Admin
        </a>
        <a href="/Aplikasi_kopi/auth/logout.php" style="color:var(--danger);" data-confirm="Yakin ingin logout?">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
