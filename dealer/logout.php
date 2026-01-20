<?php
// Bayi Çıkış
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session'ı temizle
session_unset();
session_destroy();

// Cookie'leri temizle
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Giriş sayfasına yönlendir
header('Location: index.php');
exit;

