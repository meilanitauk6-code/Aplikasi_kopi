<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';

header('Content-Type: application/json');

$code = escape($conn, $_GET['code'] ?? '');
if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Kode promo tidak valid.']);
    exit;
}

// Calculate current cart subtotal if buyer
$subtotal = 0;
if (isLoggedIn() && $_SESSION['role'] === 'buyer') {
    $bid = (int)$_SESSION['user_id'];
    $r = $conn->query("SELECT SUM(c.quantity * p.price) as sub FROM carts c JOIN products p ON c.product_id=p.id WHERE c.buyer_id=$bid");
    $subtotal = (float)($r->fetch_assoc()['sub'] ?? 0);
}

$promo = $conn->query("SELECT * FROM promos WHERE code='$code' AND status='active' AND (expired_at IS NULL OR expired_at >= CURDATE()) AND (max_use=0 OR used<max_use)")->fetch_assoc();

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'Kode promo tidak ditemukan atau sudah tidak berlaku.']);
    exit;
}

if ($promo['min_order'] > 0 && $subtotal < $promo['min_order']) {
    echo json_encode(['success' => false, 'message' => 'Minimal order ' . formatRupiah($promo['min_order']) . ' untuk menggunakan promo ini.']);
    exit;
}

$discount = $promo['type'] === 'percent' ? round($subtotal * $promo['value'] / 100) : $promo['value'];
if ($discount > $subtotal) $discount = $subtotal;
$shippingCost = (float)(getSettings($conn)['shipping_cost'] ?? 15000);
$newTotal = max(0, $subtotal + $shippingCost - $discount);

echo json_encode([
    'success' => true,
    'name' => $promo['name'],
    'discount_text' => $promo['type'] === 'percent' ? $promo['value'].'%' : formatRupiah($promo['value']),
    'discount_amount' => $discount,
    'new_total_text' => formatRupiah($newTotal),
]);
?>
