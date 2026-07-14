<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aplikasi_kopi');
define('DB_PORT', 3306);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Helper functions
function escape($conn, $str)
{
    return $conn->real_escape_string(trim($str));
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null)
{
    if (!isLoggedIn()) {
        redirect('/Aplikasi_kopi/auth/login.php');
    }
    if ($role && $_SESSION['role'] !== $role) {
        redirect('/Aplikasi_kopi/auth/login.php?error=unauthorized');
    }
}

function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);
    if ($time < 60)
        return $time . ' detik lalu';
    if ($time < 3600)
        return floor($time / 60) . ' menit lalu';
    if ($time < 86400)
        return floor($time / 3600) . ' jam lalu';
    return floor($time / 86400) . ' hari lalu';
}

function generateInvoice()
{
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function uploadImage($file, $dir = 'products')
{
    $uploadDir = dirname(__DIR__) . '/uploads/' . $dir . '/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed))
        return false;
    if ($file['size'] > 5 * 1024 * 1024)
        return false;

    $filename = uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return false;
}

/**
 * Resolve the displayable URL for a stored image value.
 * Supports both full external URLs (https://...) and locally uploaded filenames.
 * Returns '' when there is no usable image (caller should show a placeholder).
 */
function imageUrl($image, $dir = 'products')
{
    if (empty($image)) {
        return '';
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $basePath = str_replace($docRoot, '', $projectRoot);
    $basePath = '/' . ltrim($basePath, '/');
    if (substr($basePath, -1) !== '/') {
        $basePath .= '/';
    }

    // Check if the image exists directly in assets/images/
    $assetsPath = dirname(__DIR__) . '/assets/images/' . $image;
    if (file_exists($assetsPath)) {
        return $basePath . 'assets/images/' . $image;
    }

    // Fallback to uploads/products/
    $dirPath = $dir ? $dir . '/' : '';
    $path = dirname(__DIR__) . '/uploads/' . $dirPath . $image;
    if (file_exists($path)) {
        return $basePath . 'uploads/' . $dirPath . $image;
    }
    
    return '';
}

/**
 * Load all settings as an associative array (key => value).
 */
function getSettings($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    if ($res = $conn->query("SELECT `key`, `value` FROM settings")) {
        while ($row = $res->fetch_assoc()) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

?>