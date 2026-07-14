<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
requireLogin('buyer');

$buyerId = (int)$_SESSION['user_id'];

// Buy Now (single product)
$buyNow = (int)($_GET['buy_now'] ?? 0);
$buyNowQty = max(1, (int)($_GET['qty'] ?? 1));

// Get cart items or single product
$items = [];
$subtotal = 0;

if ($buyNow) {
    $p = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' AND stock > 0");
    $p->bind_param("i", $buyNow);
    $p->execute();
    $prod = $p->get_result()->fetch_assoc();
    if ($prod) {
        $qty = min($buyNowQty, (int)$prod['stock']);
        $prod['quantity'] = $qty;
        $prod['line_total'] = $prod['price'] * $qty;
        $items[] = $prod;
        $subtotal = $prod['line_total'];
    }
} else {
    $cartItems = $conn->query("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock, p.image, p.type, p.seller_id
        FROM carts c
        JOIN products p ON c.product_id = p.id
        WHERE c.buyer_id = $buyerId AND p.status = 'active' AND p.stock > 0
    ");
    while ($item = $cartItems->fetch_assoc()) {
        $item['line_total'] = $item['price'] * $item['quantity'];
        $subtotal += $item['line_total'];
        $items[] = $item;
    }
}

if (empty($items)) {
    $_SESSION['flash'] = ['message' => 'Keranjang kosong, tambahkan produk terlebih dahulu.', 'type' => 'warning'];
    redirect('/Aplikasi_kopi/buyer/cart.php');
}

$settings = getSettings($conn);
$shippingCost = (float)($settings['shipping_cost'] ?? 15000);

// Process checkout
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingName = escape($conn, $_POST['shipping_name'] ?? '');
    $shippingPhone = escape($conn, $_POST['shipping_phone'] ?? '');
    $shippingAddress = escape($conn, $_POST['shipping_address'] ?? '');
    $shippingCity = escape($conn, $_POST['shipping_city'] ?? '');
    $shippingProvince = escape($conn, $_POST['shipping_province'] ?? '');
    $shippingPostal = escape($conn, $_POST['shipping_postal'] ?? '');
    $paymentMethod = escape($conn, $_POST['payment_method'] ?? 'transfer_bank');
    $promoCode = escape($conn, $_POST['promo_code'] ?? '');
    $notes = escape($conn, $_POST['notes'] ?? '');

    if (empty($shippingName) || empty($shippingAddress) || empty($shippingCity)) {
        $error = 'Nama penerima, alamat, dan kota wajib diisi.';
    } else {
        // Check promo
        $discount = 0;
        $promoId = null;
        if ($promoCode) {
            $promo = $conn->query("SELECT * FROM promos WHERE code = '$promoCode' AND status = 'active' AND (expired_at IS NULL OR expired_at >= CURDATE()) AND (max_use = 0 OR used < max_use) AND min_order <= $subtotal")->fetch_assoc();
            if ($promo) {
                $promoId = $promo['id'];
                $discount = $promo['type'] === 'percent' ? round($subtotal * $promo['value'] / 100) : $promo['value'];
            }
        }

        // Discount can never exceed the subtotal (prevents negative totals)
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $total = max(0, $subtotal - $discount + $shippingCost);
        $invoice = generateInvoice();
        $sellerId = $items[0]['seller_id'] ?? null;

        $conn->begin_transaction();
        try {
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (buyer_id, seller_id, promo_id, invoice, subtotal, discount, shipping_cost, total, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_province, shipping_postal, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisddddssssssss", $buyerId, $sellerId, $promoId, $invoice, $subtotal, $discount, $shippingCost, $total, $shippingName, $shippingPhone, $shippingAddress, $shippingCity, $shippingProvince, $shippingPostal, $paymentMethod, $notes);
            $stmt->execute();
            $orderId = $conn->insert_id;

            // Insert order items
            foreach ($items as $item) {
                $pid = $item['product_id'] ?? $item['id'];
                $name = escape($conn, $item['name']);
                $price = (float)$item['price'];
                $qty = (int)$item['quantity'];
                $lineTot = $price * $qty;
                $imgVal = escape($conn, $item['image'] ?? $item['product_image'] ?? '');
                $imgSql = $imgVal ? "'$imgVal'" : 'NULL';
                // Reduce stock first; the WHERE guard makes this atomic against oversell
                $conn->query("UPDATE products SET stock = stock - $qty, total_sold = total_sold + $qty WHERE id = $pid AND stock >= $qty");
                if ($conn->affected_rows < 1) {
                    throw new Exception("Stok untuk \"{$item['name']}\" tidak mencukupi. Silakan kurangi jumlah dan coba lagi.");
                }
                $conn->query("INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity, subtotal) VALUES ($orderId, $pid, '$name', $imgSql, $price, $qty, $lineTot)");
            }


            // Insert payment record
            $conn->query("INSERT INTO payments (order_id, method, amount) VALUES ($orderId, '$paymentMethod', $total)");

            // Insert shipping record
            $conn->query("INSERT INTO shipping (order_id) VALUES ($orderId)");

            // Use promo
            if ($promoId) {
                $conn->query("UPDATE promos SET used = used + 1 WHERE id = $promoId");
            }

            // Clear cart (if not buy now)
            if (!$buyNow) {
                $conn->query("DELETE FROM carts WHERE buyer_id = $buyerId");
            }

            $conn->commit();

            // Notification
            $conn->query("INSERT INTO notifications (user_id, title, message, type, url) VALUES ($buyerId, 'Pesanan Berhasil Dibuat', 'Pesanan $invoice telah dibuat. Lakukan pembayaran sekarang.', 'order', '/Aplikasi_kopi/buyer/payment.php?order_id=$orderId')");

            $_SESSION['flash'] = ['message' => "Pesanan $invoice berhasil dibuat! Segera lakukan pembayaran.", 'type' => 'success'];
            redirect("/Aplikasi_kopi/buyer/payment.php?order_id=$orderId");

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Prefill user data
$user = $conn->query("SELECT * FROM users WHERE id = $buyerId")->fetch_assoc();

// Promos available
$promos = $conn->query("SELECT * FROM promos WHERE status='active' AND (expired_at IS NULL OR expired_at >= CURDATE()) AND (max_use = 0 OR used < max_use) AND min_order <= $subtotal");

$pageTitle = 'Checkout - KopiKu';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/navbar.php';
?>

<div class="container" style="padding:32px 20px 48px;">
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:8px;">Checkout</h1>

    <!-- Steps -->
    <div class="checkout-steps" style="margin-bottom:32px;">
        <div class="checkout-step done"><div class="step-number">✓</div><span class="step-label">Keranjang</span></div>
        <div class="step-line done"></div>
        <div class="checkout-step active"><div class="step-number">2</div><span class="step-label">Detail Pengiriman</span></div>
        <div class="step-line"></div>
        <div class="checkout-step"><div class="step-number">3</div><span class="step-label">Pembayaran</span></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;">
            <!-- Left: Shipping & Payment -->
            <div style="display:flex;flex-direction:column;gap:20px;">
                <!-- Shipping -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📍 Alamat Pengiriman</span></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nama Penerima *</label>
                                <input type="text" name="shipping_name" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['shipping_name'] ?? $user['name']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Telepon *</label>
                                <input type="tel" name="shipping_phone" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['shipping_phone'] ?? $user['phone']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat Lengkap *</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required
                                      placeholder="Jl. Nama Jalan No. RT/RW, Kelurahan, Kecamatan"><?= htmlspecialchars($_POST['shipping_address'] ?? $user['address']) ?></textarea>
                        </div>

                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Kota *</label>
                                <input type="text" name="shipping_city" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['shipping_city'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Provinsi</label>
                                <input type="text" name="shipping_province" class="form-control"
                                       value="<?= htmlspecialchars($_POST['shipping_province'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="shipping_postal" class="form-control" maxlength="5"
                                       value="<?= htmlspecialchars($_POST['shipping_postal'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card">
                    <div class="card-header"><span class="card-title">💳 Metode Pembayaran</span></div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <?php
                            $methods = [
                                'transfer_bank' => ['🏦', 'Transfer Bank', 'BCA, BNI, Mandiri, BRI'],
                                'ewallet' => ['📱', 'E-Wallet', 'OVO, DANA, GoPay, ShopeePay'],
                                'virtual_account' => ['🔢', 'Virtual Account', 'Bayar via ATM / M-Banking'],
                                'kartu_kredit' => ['💳', 'Kartu Kredit/Debit', 'Visa, Mastercard, JCB'],
                            ];
                            foreach ($methods as $val => [$icon, $label, $desc]):
                                $selected = ($_POST['payment_method'] ?? 'transfer_bank') === $val;
                            ?>
                            <label style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--bg-surface);border:1.5px solid <?= $selected ? 'var(--primary)' : 'var(--border)' ?>;border-radius:12px;cursor:pointer;transition:var(--transition);"
                                   onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='<?= $selected ? 'var(--primary)' : 'var(--border)' ?>'">
                                <input type="radio" name="payment_method" value="<?= $val ?>" <?= $selected ? 'checked' : '' ?>
                                       style="accent-color:var(--primary);" onchange="this.closest('.card-body').querySelectorAll('label').forEach(l=>{l.style.borderColor='var(--border)'});this.parentElement.style.borderColor='var(--primary)'">
                                <div>
                                    <div style="font-size:1.3rem;"><?= $icon ?></div>
                                    <div style="font-weight:600;font-size:0.85rem;"><?= $label ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= $desc ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📝 Catatan (Opsional)</span></div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Contoh: Tolong dikemas rapi, jangan biarkan basah"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div>
                <div class="card" style="position:sticky;top:88px;">
                    <div class="card-header"><span class="card-title">Ringkasan Pesanan</span></div>
                    <div class="card-body">
                        <!-- Items -->
                        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                            <?php foreach ($items as $item): ?>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <div style="width:42px;height:42px;background:var(--bg-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                                    <?= ($item['type'] ?? '') === 'biji' ? '🫘' : '☕' ?>
                                </div>
                                <div style="flex:1;font-size:0.82rem;">
                                    <div style="font-weight:600;"><?= htmlspecialchars($item['name']) ?></div>
                                    <div style="color:var(--text-muted);">x<?= $item['quantity'] ?></div>
                                </div>
                                <div style="font-weight:600;font-size:0.85rem;"><?= formatRupiah($item['line_total']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Promo Code -->
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Kode Promo</label>
                            <div style="display:flex;gap:8px;">
                                <input type="text" name="promo_code" id="promoCode" class="form-control"
                                       placeholder="Masukkan kode promo" value="<?= htmlspecialchars($_POST['promo_code'] ?? '') ?>">
                                <button type="button" id="applyPromoBtn" class="btn btn-secondary btn-sm">Pakai</button>
                            </div>
                            <?php if ($promos->num_rows > 0): ?>
                            <div style="margin-top:6px;font-size:0.75rem;color:var(--text-muted);">
                                Tersedia:
                                <?php $promos->data_seek(0); while ($promo = $promos->fetch_assoc()): ?>
                                <span style="color:var(--primary);cursor:pointer;font-weight:600;" onclick="document.getElementById('promoCode').value='<?= $promo['code'] ?>'">
                                    <?= $promo['code'] ?>
                                </span>
                                <?php endwhile; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="divider"></div>

                        <!-- Totals -->
                        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                            <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
                                <span style="color:var(--text-muted);">Subtotal</span>
                                <span><?= formatRupiah($subtotal) ?></span>
                            </div>
                            <div style="display:none;justify-content:space-between;font-size:0.875rem;" id="discountRow">
                                <span style="color:var(--success);">Diskon</span>
                                <span style="color:var(--success);" id="discountAmount">-</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
                                <span style="color:var(--text-muted);">Ongkos Kirim</span>
                                <span><?= formatRupiah($shippingCost) ?></span>
                            </div>
                            <div class="divider"></div>
                            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem;">
                                <span>Total</span>
                                <span style="color:var(--primary);" id="grandTotal"><?= formatRupiah($subtotal + $shippingCost) ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            Buat Pesanan →
                        </button>

                        <div style="font-size:0.72rem;color:var(--text-muted);text-align:center;margin-top:10px;">
                            🔒 Transaksi dijamin aman & terenkripsi
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/includes/footer.php'; ?>
