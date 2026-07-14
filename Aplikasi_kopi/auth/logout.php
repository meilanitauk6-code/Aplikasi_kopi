<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
session_unset();
session_destroy();
header('Location: /Aplikasi_kopi/auth/login.php');
exit;
?>
