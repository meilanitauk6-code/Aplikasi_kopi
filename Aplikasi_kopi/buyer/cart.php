<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('buyer');

$buyerId = (int)$_SESSION['user_id'];

// Handle cart actions
$action = $_GET['action'] ?? '';

if ($action === 'add') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $qty = max(1, (int)($_GET['qty'] ?? 1));

    if ($productId) {
        // Check product exists and has stock
        $p = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
        $p->bind_param("i", $productId);
        $p->execute();
        $prod = $p->get_result()->fetch_assoc();

        if ($prod) {
            // Check if already in cart
            $exists = $conn->prepare("SELECT id, quantity FROM carts WHERE buyer_id = ? AND product_id = ?");
            $exists->bind_param("ii", $buyerId, $productId);
            $exists->execute();
            $existing = $exists->get_result()->fetch_assoc();

            if ($existing) {
                $newQty = min($existing['quantity'] + $qty, $prod['stock']);
                $conn->query("UPDATE carts SET quantity = $newQty WHERE id = {$existing['id']}");
            } else {
                $safeQty = min($qty, $prod['stock']);
                $stmt = $conn->prepare("INSERT INTO carts (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $buyerId, $productId, $safeQty);
                $stmt->execute();
            }
            $_SESSION['flash'] = ['message' => 'Produk berhasil ditambahkan ke keranjang!', 'type' => 'success'];
        }
    }
    redirect('/Aplikasi_kopi/buyer/cart.php');
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtyInput = $_POST['qty'] ?? [];
    if (is_array($qtyInput)) {
        foreach ($qtyInput as $cartId => $qty) {
            $cartId = (int)$cartId;
            $qty = max(1, (int)$qty);
            // Cap the quantity to the available stock for this cart row
            $stmt = $conn->prepare("
                UPDATE carts c JOIN products p ON c.product_id = p.id
                SET c.quantity = LEAST(?, p.stock)
                WHERE c.id = ? AND c.buyer_id = ?
            ");
            $stmt->bind_param("iii", $qty, $cartId, $buyerId);
            $stmt->execute();
        }
    }
    $_SESSION['flash'] = ['message' => 'Keranjang berhasil diperbarui!', 'type' => 'success'];
    redirect('/Aplikasi_kopi/buyer/cart.php');
}

if ($action === 'remove') {
    $cartId = (int)($_GET['cart_id'] ?? 0);
    if ($cartId) {
        $conn->query("DELETE FROM carts WHERE id = $cartId AND buyer_id = $buyerId");
        $_SESSION['flash'] = ['message' => 'Produk dihapus dari keranjang.', 'type' => 'info'];
    }
    redirect('/Aplikasi_kopi/buyer/cart.php');
}

if ($action === 'clear') {
    $conn->query("DELETE FROM carts WHERE buyer_id = $buyerId");
    $_SESSION['flash'] = ['message' => 'Keranjang dikosongkan.', 'type' => 'info'];
    redirect('/Aplikasi_kopi/buyer/cart.php');
}

// Get cart items
$cartItems = $conn->query("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock, p.image, p.type, p.weight
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.buyer_id = $buyerId AND p.status = 'active'
    ORDER BY c.created_at DESC
");

$items = [];
$subtotal = 0;
while ($item = $cartItems->fetch_assoc()) {
    $item['line_total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['line_total'];
    $items[] = $item;
}

$shippingCost = $subtotal > 0 ? (float)(getSettings($conn)['shipping_cost'] ?? 15000) : 0;
$total = $subtotal + $shippingCost;

$pageTitle = 'Keranjang Belanja - KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="container" style="padding:32px 20px 48px;">
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.5rem;font-weight:800;">🛒 Keranjang Belanja</h1>
        <p style="color:var(--text-muted);font-size:0.875rem;"><?= count($items) ?> produk di keranjang Anda</p>
    </div>

    <?php if (empty($items)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <div class="empty-title">Keranjang Anda kosong</div>
                <div class="empty-desc">Tambahkan produk kopi favorit Anda ke keranjang</div>
                <a href="/Aplikasi_kopi/buyer/products.php" class="btn btn-primary" style="margin-top:16px;">Mulai Belanja</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
        <!-- Cart Items -->
        <div>
            <form method="POST" action="/Aplikasi_kopi/buyer/cart.php?action=update">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Daftar Produk</span>
                        <a href="/Aplikasi_kopi/buyer/cart.php?action=clear"
                           data-confirm="Yakin ingin mengosongkan keranjang?"
                           class="btn btn-danger btn-sm">🗑️ Kosongkan</a>
                    </div>
                    <div style="padding:0 8px;">
                        <?php foreach ($items as $item): ?>
                        <div style="display:flex;align-items:center;gap:16px;padding:16px;border-bottom:1px solid var(--border-light);">
                            <!-- Image -->
                            <div style="width:72px;height:72px;background:var(--bg-surface);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;overflow:hidden;">
                                <?php $itImg = imageUrl($item['image']); if ($itImg): ?>
                                <img src="<?= htmlspecialchars($itImg) ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                                <?php else: ?>
                                <?= $item['type'] === 'biji' ? '🫘' : '☕' ?>
                                <?php endif; ?>
                            </div>
                            <!-- Info -->
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <a href="/Aplikasi_kopi/buyer/product_detail.php?id=<?= $item['product_id'] ?>" style="color:var(--text-primary);">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-muted);">
                                    <?= ucfirst($item['type']) ?> • <?= $item['weight'] ?>g per pak
                                </div>
                                <div style="font-weight:700;color:var(--primary);margin-top:4px;"><?= formatRupiah($item['price']) ?></div>
                            </div>
                            <!-- Quantity -->
                            <div class="qty-wrapper" style="display:flex;align-items:center;background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                                <button type="button" class="qty-minus" style="background:none;border:none;padding:8px 12px;color:var(--text-primary);cursor:pointer;">-</button>
                                <input type="number" name="qty[<?= $item['cart_id'] ?>]" class="qty-input"
                                       value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                                       style="width:44px;background:transparent;border:none;text-align:center;color:var(--text-primary);font-family:inherit;font-weight:600;font-size:0.9rem;">
                                <button type="button" class="qty-plus" style="background:none;border:none;padding:8px 12px;color:var(--text-primary);cursor:pointer;">+</button>
                            </div>
                            <!-- Total -->
                            <div style="text-align:right;min-width:90px;">
                                <div style="font-weight:700;"><?= formatRupiah($item['line_total']) ?></div>
                                <a href="/Aplikasi_kopi/buyer/cart.php?action=remove&cart_id=<?= $item['cart_id'] ?>"
                                   data-confirm="Hapus produk ini dari keranjang?"
                                   style="font-size:0.75rem;color:var(--danger);">Hapus</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="padding:16px;display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-secondary btn-sm">🔄 Perbarui Keranjang</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="card" style="position:sticky;top:88px;">
                <div class="card-header"><span class="card-title">Ringkasan Pesanan</span></div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
                            <span style="color:var(--text-muted);">Subtotal (<?= count($items) ?> produk)</span>
                            <span><?= formatRupiah($subtotal) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
                            <span style="color:var(--text-muted);">Ongkos Kirim</span>
                            <span><?= formatRupiah($shippingCost) ?></span>
                        </div>
                        <div class="divider"></div>
                        <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem;">
                            <span>Total</span>
                            <span style="color:var(--primary);"><?= formatRupiah($total) ?></span>
                        </div>
                    </div>

                    <a href="/Aplikasi_kopi/buyer/checkout.php" class="btn btn-primary btn-block btn-lg">
                        Lanjut ke Checkout →
                    </a>

                    <a href="/Aplikasi_kopi/buyer/products.php"
                       style="display:block;text-align:center;margin-top:12px;font-size:0.85rem;color:var(--text-muted);">
                        ← Lanjut Belanja
                    </a>

                    <div class="divider"></div>
                    <div style="font-size:0.75rem;color:var(--text-muted);text-align:center;display:flex;gap:12px;justify-content:center;">
                        <span>🔒 Aman</span><span>🚚 Cepat</span><span>⭐ Terpercaya</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
