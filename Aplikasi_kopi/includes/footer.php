<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">☕ Mey Coffee</div>
                <p class="footer-desc">Toko kopi online terpercaya menyediakan Kopi Bubuk & Kopi Biji premium dari
                    seluruh Nusantara. Dikirim langsung ke pintu rumah Anda.</p>
            </div>
            <div>
                <div class="footer-heading">Menu</div>
                <ul class="footer-links">
                    <li><a href="/Aplikasi_kopi/">Beranda</a></li>
                    <li><a href="/Aplikasi_kopi/buyer/products.php">Produk</a></li>
                    <li><a href="/Aplikasi_kopi/buyer/products.php?type=bubuk">Kopi Bubuk</a></li>
                    <li><a href="/Aplikasi_kopi/buyer/products.php?type=biji">Kopi Biji</a></li>
                </ul>
            </div>
            <div>
                <div class="footer-heading">Akun</div>
                <ul class="footer-links">
                    <li><a href="/Aplikasi_kopi/auth/login.php">Masuk</a></li>
                    <li><a href="/Aplikasi_kopi/auth/register.php">Daftar</a></li>
                    <li><a href="/Aplikasi_kopi/buyer/orders.php">Pesanan</a></li>
                    <li><a href="/Aplikasi_kopi/buyer/cart.php">Keranjang</a></li>
                </ul>
            </div>
            <?php $footerSettings = function_exists('getSettings') && isset($conn) ? getSettings($conn) : []; ?>
            <div>
                <div class="footer-heading">Kontak</div>
                <ul class="footer-links">
                    <li><a href="/Aplikasi_kopi/contact.php">💬 Hubungi Kami</a></li>
                    <?php $fEmail = $footerSettings['site_email'] ?? 'meilanitauk@gmail.com'; ?>
                    <li><a href="mailto:<?= htmlspecialchars($fEmail) ?>">📧 <?= htmlspecialchars($fEmail) ?></a></li>
                    <?php $fPhone = $footerSettings['site_phone'] ?? '081246547850'; ?>
                    <li><a href="tel:<?= htmlspecialchars($fPhone) ?>">📱 <?= htmlspecialchars($fPhone) ?></a></li>
                    <li><span style="color:var(--text-muted);">📍 <?= htmlspecialchars($footerSettings['site_address'] ?? 'Jalan Soka No. 1 Gang IV.6, Denpasar Timur, Kesiman Kertalanggu') ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> Mey Coffee. All rights reserved.</span>
            <span>☕ Dibuat dengan semangat kopi Nusantara</span>
        </div>
    </div>
</footer>

<script src="/Aplikasi_kopi/assets/js/main.js"></script>
</body>

</html>