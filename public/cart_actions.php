<?php
require_once __DIR__ . '/../fonk.php';
oturumkontrol();
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// Clear action için id gerekmez
if ($action === 'clear') {
    // Tüm cookie'leri temizle
    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])) {
        foreach (array_keys($_COOKIE['teklif']) as $key) {
            setcookie("teklif[$key]", '', time() - 3600, '/');
            unset($_COOKIE['teklif'][$key]);
        }
    }
    echo json_encode(['success'=>true,'message'=>'Sepet temizlendi']);
    exit;
}

if (!$id || !in_array($action, ['add','remove'])) {
    echo json_encode(['success'=>false,'message'=>'Parametre hatası']);
    exit;
}
if ($action === 'add') {
    $qty = intval($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;
    // Cookie'yi güvenli bir şekilde kaydet
    $cookieName = "teklif[$id]";
    $cookieValue = $qty;
    $expire = time() + 86400; // 24 saat
    $path = '/';
    $domain = ''; // Mevcut domain
    $secure = false; // HTTPS gerektirmez
    $httponly = false; // JavaScript'ten erişilebilir
    
    // Cookie'yi kaydet
    setcookie($cookieName, $cookieValue, $expire, $path, $domain, $secure, $httponly);
    
    // Aynı zamanda $_COOKIE dizisine de ekle (mevcut istek için)
    $_COOKIE['teklif'][$id] = $qty;
    
    echo json_encode(['success'=>true,'message'=>'Ürün eklendi', 'cookie_set'=>true]);
} elseif ($action === 'remove') {
    if (isset($_COOKIE['teklif'][$id])) {
        $cookieName = "teklif[$id]";
        setcookie($cookieName, '', time() - 3600, '/');
        unset($_COOKIE['teklif'][$id]);
    }
    echo json_encode(['success'=>true,'message'=>'Ürün kaldırıldı']);
}
