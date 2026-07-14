<?php
// Global header - included in every page
// database.php already started session, just load it if not done
if (!defined('DB_HOST')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
}

$site_name = 'Mey Coffee';
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];

// Get cart count for buyer
$cartCount = 0;
if (isLoggedIn() && $_SESSION['role'] === 'buyer') {
    $bid = (int) $_SESSION['user_id'];
    $r = $conn->query("SELECT SUM(quantity) as total FROM carts WHERE buyer_id = $bid");
    $cartCount = (int) ($r->fetch_assoc()['total'] ?? 0);
}

// Get notification count
$notifCount = 0;
if (isLoggedIn()) {
    $uid = (int) $_SESSION['user_id'];
    $r = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = $uid AND is_read = 0");
    $notifCount = (int) ($r->fetch_assoc()['c'] ?? 0);
}

$pageTitle = $pageTitle ?? $site_name . ' - Toko Kopi Online';
$pageDesc = $pageDesc ?? 'Mey Coffee - Toko kopi online terpercaya menyediakan Kopi Bubuk & Kopi Biji premium dari seluruh Nusantara.';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
    <meta name="robots" content="index, follow">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/Aplikasi_kopi/assets/css/style.css">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
</head>

<body>

    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash-message" data-message="<?= htmlspecialchars($_SESSION['flash']['message']) ?>"
            data-type="<?= htmlspecialchars($_SESSION['flash']['type']) ?>" style="display:none;"></div>
        <?php unset($_SESSION['flash']); endif; ?>

    <div id="backToTop"
        style="position:fixed;bottom:24px;right:24px;z-index:999;width:44px;height:44px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;cursor:pointer;opacity:0;transition:opacity 0.3s;font-size:1.2rem;box-shadow:0 4px 14px rgba(193,127,89,0.3);pointer-events:none;">
        ↑</div>