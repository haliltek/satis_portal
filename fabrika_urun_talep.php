<?php
// siparis-olustur.php
ob_start(); // Output buffering başlat
require_once "fonk.php";
oturumkontrol();

// Ödeme planlarını Logo'dan çek
$firmNr = (int)($config['firmNr'] ?? 0);
$payPlans = $logoService->getPayPlans($firmNr);

// YENİ EKLENEN KISIM: Eğer "Yeni Teklif" butonuna basıldıysa, hafızadaki her şeyi temizle
if (isset($_GET['new_offer']) && $_GET['new_offer'] === '1') {
    // 1. Sepet çerezini sil - cart_actions.php ile aynı parametrelerle
    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])) {
        foreach (array_keys($_COOKIE['teklif']) as $key) {
            // Önceki cookie'yi silmek için süresini geçmişe ayarla
            setcookie("teklif[$key]", '', time() - 3600, '/');
            // Mevcut PHP çalışması için $_COOKIE dizisinden de sil
            unset($_COOKIE['teklif'][$key]);
        }
    }
    // Ana teklif cookie dizisini de silmeyi dene (garanti olsun)
    setcookie('teklif', '', time() - 3600, '/');
    unset($_COOKIE['teklif']);
    
    // 2. Müşteri/Firma seçimlerini sil (Session)
    unset($_SESSION['form_sozlesme_id']);
    unset($_SESSION['form_ekstra_bilgi']);
    unset($_SESSION['form_sozlesme_metin']);
    
    // 3. JS ile Redirect (Header yerine) - Cookie silme işleminin tarayıcıda kesinleşmesi için
    echo '<!DOCTYPE html><html><head><script>
    // JS tarafında da cookie temizliği yap (Garanti olsun)
    var cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
        if (name.indexOf("teklif") === 0) {
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
        }
    }
    // Temiz URLye yönlendir
    window.location.href = "teklif-olustur.php";
    </script></head><body>Yönlendiriliyor...</body></html>';
    exit();
}

// Kullanıcı tipi
$user_type = $_SESSION['user_type'] ?? '';

// Döviz kurlarını veritabanından oku
$kurQuery = mysqli_query($db, "SELECT dolaralis, dolarsatis, euroalis, eurosatis FROM dovizkuru LIMIT 1");
$dovizKurlari = mysqli_fetch_assoc($kurQuery);
$usdKuru = (float)($dovizKurlari['dolaralis'] ?? 32);
$eurKuru = (float)($dovizKurlari['euroalis'] ?? 35);

// Yurtiçi/Yurtdışı seçimi - Form'dan veya session'dan al
if (isset($_POST['pazar_tipi'])) {
    $_SESSION['pazar_tipi'] = $_POST['pazar_tipi'] === 'yurtdisi' ? 'yurtdisi' : 'yurtici';
} elseif (isset($_GET['pazar_tipi'])) {
    $_SESSION['pazar_tipi'] = $_GET['pazar_tipi'] === 'yurtdisi' ? 'yurtdisi' : 'yurtici';
}

// Genel İskonto - POST'tan al
$genel_iskonto = 0.00;
if (isset($_POST['genel_iskonto'])) {
    $genel_iskonto = floatval(str_replace(',', '.', $_POST['genel_iskonto']));
}

// Session'dan pazar tipini al, yoksa varsayılan olarak yurtiçi
$pazarTipi = $_SESSION['pazar_tipi'] ?? 'yurtici';
$isForeign = ($pazarTipi === 'yurtdisi');

// Ekstra bilgiyi session'dan al (geri dönüldüğünde)
$ekstra_bilgi = $_SESSION['form_ekstra_bilgi'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekstra_bilgi'])) {
    $ekstra_bilgi = xss(addslashes($_POST['ekstra_bilgi']));
    $_SESSION['form_ekstra_bilgi'] = $ekstra_bilgi; // Session'a kaydet
}

// Sözleşme metnini session'dan al (geri dönüldüğünde)
$sozlesme_metin_edited = $_SESSION['form_sozlesme_metin'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sozlesme_metin_edited'])) {
    $sozlesme_metin_edited = xss(addslashes($_POST['sozlesme_metin_edited']));
    $_SESSION['form_sozlesme_metin'] = $sozlesme_metin_edited; // Session'a kaydet
}

// Seçili sözleşme ID'sini al (session'dan veya varsayılan olarak 5)
$selected_sozlesme_id = $_SESSION['form_sozlesme_id'] ?? 5;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sozlesme_id'])) {
    $selected_sozlesme_id = (int)$_POST['sozlesme_id'];
    $_SESSION['form_sozlesme_id'] = $selected_sozlesme_id;
}

// Eğer GET parametresi varsa onu kullan
if (isset($_GET['sozlesme_id'])) {
    $selected_sozlesme_id = (int)$_GET['sozlesme_id'];
    $_SESSION['form_sozlesme_id'] = $selected_sozlesme_id;
}

// Seçili sözleşmenin orijinal metnini al
$selected_sozlesme_metin = '';
if ($selected_sozlesme_id > 0) {
    $sozlesmeSorgu = mysqli_query($db, "SELECT sozlesme_metin FROM sozlesmeler WHERE sozlesme_id = " . (int)$selected_sozlesme_id);
    if ($sozlesmeRow = mysqli_fetch_assoc($sozlesmeSorgu)) {
        $selected_sozlesme_metin = $sozlesmeRow['sozlesme_metin'] ?? '';
    }
}

// Hata raporlaması ayarları
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/htdocs/b2b-project/error.log');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Özel fiyat konfigürasyonunu yükle (Manuel Kampanya)
$specialPricesConfig = require __DIR__ . '/config/special_prices.php';

$toplam_sayfa = $toplam_sayfa ??1;

/**
 * Yönlendirme fonksiyonu
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * Sepete ürün ekleme
 */
function addProductToCart($id)
{
    if ($id) {
        setcookie('teklif[' . $id . ']', $id, time() + 86400, "/");
        redirect('siparis-olustur.php');
    }
}

/**
 * Sepetten ürün kaldırma
 */
function removeProductFromCart($id)
{
    if ($id && isset($_COOKIE['teklif'][$id])) {
        setcookie('teklif[' . $id . ']', '', time() - 3600, "/");
        redirect('siparis-olustur.php');
    }
}

/**
 * Sepeti temizleme
 */
function clearCart()
{
    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])) {
        foreach (array_keys($_COOKIE['teklif']) as $key) {
            setcookie('teklif[' . $key . ']', '', time() - 3600, "/");
        }
        redirect('siparis-olustur.php');
    }
}

/**
 * Seçili ürün ID'lerini çeker
 */
function getSelectedProductIds()
{
    $selected = [];
    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])) {
        foreach ($_COOKIE['teklif'] as $productId) {
            $id = filter_var($productId, FILTER_VALIDATE_INT);
            if ($id) {
                $selected[] = $id;
            }
        }
    }
    return $selected;
}

/**
 * Veritabanından seçili ürünlerin detaylarını çeker
 */
function getSelectedProductsDetails($db, $productIds)
{
    $details = [];
    if (!empty($productIds)) {
        // 3 tane ID varsa "?, ?, ?" gibi bir dize üretir
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $types        = str_repeat('i', count($productIds));

        // Burada IN ($placeholders) kullanıyoruz
        $sql = "SELECT urun_id, stokkodu, stokadi, fiyat, doviz, olcubirimi, LOGICALREF
                FROM urunler
                WHERE urun_id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            // PHP 5.6+ için variadic unpack ile:
            $stmt->bind_param($types, ...$productIds);
            $stmt->execute();
            $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            error_log("Prepare failed: " . $db->error);
        }
    }
    return $details;
}


// İşlemleri kontrol et: ekle, kaldır, temizle
if (isset($_GET['ekle'])) {
    $id = filter_input(INPUT_GET, 'ekle', FILTER_VALIDATE_INT);
    addProductToCart($id);
}

if (isset($_GET['cikart'])) {
    $cikartId = filter_input(INPUT_GET, 'cikart', FILTER_VALIDATE_INT);
    removeProductFromCart($cikartId);
}

if (isset($_GET['bosalt']) && $_GET['bosalt'] === 'true') {
    clearCart();
}

// Yönetici bilgileri ve yetki kontrolü
$yonetici_id = $_SESSION['yonetici_id'] ?? null;
if (!$yonetici_id) {
    redirect("login.php");
}

$stmt = $db->prepare("SELECT iskonto_max FROM yonetici WHERE yonetici_id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $db->error);
    exit("Bir hata oluştu.");
}
$stmt->bind_param("i", $yonetici_id);
$stmt->execute();
$result = $stmt->get_result();
$yonetici = $result->fetch_assoc();
$stmt->close();

// iskonto_max kolonundan yetki belirle
// 60 veya daha az ise Personel, yoksa Yönetici
$iskonto_max_db = isset($yonetici['iskonto_max']) ? floatval($yonetici['iskonto_max']) : 100.0;

if ($iskonto_max_db > 0 && $iskonto_max_db <= 60) {
    $iskonto_max = $iskonto_max_db; // Personel - veritabanındaki değer
    $yetki = 'Personel';
} else {
    $iskonto_max = 100.0; // Yönetici - sınırsız
    $yetki = 'Yönetici';
}

$discountDisabled = false;
$campaigns = $dbManager->getActiveCampaigns();
$campaignRatesMap = [];

// Sepetteki ürünlerin alınması
$selectedIds = getSelectedProductIds();
$selectedProductsDetails = getSelectedProductsDetails($db, $selectedIds);
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($sistemayar["title"]); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($sistemayar["description"]); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($sistemayar["keywords"]); ?>">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/icons.min.css" rel="stylesheet">
    <link href="assets/css/app.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ERP Profesyonel Görünüm - Resimdeki gibi */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
        }

        /* Modal ERP Stili */
        #offerModal .modal-content {
            border: none;
            border-radius: 0;
        }

        #offerForm .card-header {
            background: #6c5ce7;
            color: white;
            border-bottom: none;
            padding: 6px 10px;
        }

        #offerForm .card-header h5 {
            font-size: 13px;
            font-weight: 600;
            margin: 0;
        }

        #offerForm .card-body {
            background: #f5f5f5;
            padding: 10px;
            font-size: 12px;
        }

        #offerForm .row.mt-3:last-child {
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            padding: 6px 10px;
        }

        /* Form Elemanları ERP Stili */
        #offerForm .form-label {
            font-size: 11px;
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
            display: block;
        }

        #offerForm .form-control,
        #offerForm .form-select {
            font-size: 12px;
            padding: 2px 6px;
            border: 1px solid #ccc;
            border-radius: 0;
            background: white;
            height: 24px;
            line-height: 20px;
        }

        #offerForm .form-control:focus,
        #offerForm .form-select:focus {
            border-color: #6c5ce7;
            outline: 1px solid #6c5ce7;
            outline-offset: -1px;
        }

        #offerForm .form-control[readonly] {
            background-color: #f9f9f9;
        }

        /* Row ve Col ERP Stili */
        #offerForm .row {
            margin-left: -5px;
            margin-right: -5px;
            margin-bottom: 8px;
        }

        #offerForm .row > * {
            padding-left: 5px;
            padding-right: 5px;
        }

        #offerForm .col-md-3,
        #offerForm .col-md-6 {
            margin-bottom: 6px;
        }
        
        /* ERP Form Grid Stili */
        .erp-form-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 8px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .erp-form-label {
            font-size: 10px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
            display: block;
            white-space: nowrap;
        }
        
        .erp-form-input {
            font-size: 11px;
            padding: 2px 6px;
            border: 1px solid #ccc;
            border-radius: 0;
            background: white;
            height: 24px;
            line-height: 20px;
        }
        
        .erp-form-input:focus {
            border-color: #6c5ce7;
            outline: 1px solid #6c5ce7;
            outline-offset: -1px;
        }
        
        .erp-form-input[readonly] {
            background-color: #f9f9f9;
        }
        
        /* Select2 ERP Stili */
        .select2-container--default .select2-selection--single {
            height: 24px !important;
            border: 1px solid #ccc !important;
            border-radius: 0 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 22px !important;
            font-size: 11px !important;
            padding-left: 6px !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 22px !important;
        }
        
        /* Radio button ERP Stili */
        .erp-form-grid .form-check {
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            margin-right: 12px !important;
        }
        
        .erp-form-grid .form-check-input {
            width: 14px !important;
            height: 14px !important;
            margin-top: 0 !important;
            margin-right: 4px !important;
            flex-shrink: 0 !important;
        }
        
        .erp-form-grid .form-check-label {
            font-size: 10px !important;
            margin: 0 !important;
            padding-left: 0 !important;
            line-height: 14px !important;
            white-space: nowrap !important;
            cursor: pointer !important;
        }

        /* Tablo ERP Stili - Resimdeki gibi */
        #offerForm .table-responsive {
            border: 1px solid #999;
            background: white;
            margin-top: 10px;
            overflow-x: auto;
            overflow-y: visible;
        }
        
        /* Autocomplete dropdown için özel container */
        #product-autocomplete-global {
            position: fixed;
            z-index: 99999;
            background: white;
            border: 1px solid #ccc;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            visibility: visible;
            opacity: 1;
            display: none;
        }
        
        #product-autocomplete-global[style*="display: block"],
        #product-autocomplete-global.show,
        #product-autocomplete-global[style*="display:block"] {
            display: block !important;
        }
        
        #product-autocomplete-global ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
        }
        
        #product-autocomplete-global li {
            padding: 8px 12px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #eee !important;
            background: white !important;
            color: #333 !important;
        }
        
        #product-autocomplete-global li:hover {
            background: #f0f0f0 !important;
        }
        
        #product-autocomplete-global li strong {
            color: #333 !important;
            font-weight: bold !important;
        }
        
        /* Ekstra Bilgi ClassicEditor - 2 satır için küçük */
        #ekstraBilgi + .ck-editor,
        .ck-editor[data-id="ekstraBilgi"] {
            min-height: 40px !important;
        }
        
        #ekstraBilgi + .ck-editor .ck-content,
        .ck-editor[data-id="ekstraBilgi"] .ck-content {
            min-height: 40px !important;
            max-height: 100px !important;
            height: 40px !important;
            overflow-y: auto !important;
            font-size: 12px !important;
        }
        
        #ekstraBilgi + .ck-editor .ck-editor__editable,
        .ck-editor[data-id="ekstraBilgi"] .ck-editor__editable {
            min-height: 40px !important;
            max-height: 100px !important;
            height: 40px !important;
            overflow-y: auto !important;
        }

        #offerForm .table {
            font-size: 11px;
            margin-bottom: 0;
            background: white;
            border-collapse: collapse;
            width: 100%;
        }

        #offerForm .table thead th {
            background: #e8e8e8;
            border: 1px solid #999;
            padding: 4px 6px;
            font-weight: 600;
            font-size: 10px;
            text-align: left;
            color: #333;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #offerForm .table tbody td {
            border: 1px solid #ddd;
            padding: 2px 4px;
            background: white;
            vertical-align: middle;
            height: 24px;
        }
        
        /* Zebra striping - ERP görünümü için */
        #offerForm .table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }
        
        #offerForm .table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        
        /* Hover efekti */
        #offerForm .table tbody tr:hover td {
            background-color: #e9ecef !important;
        }
        
        /* Rakamları daha koyu yap */
        #offerForm .table tbody td[style*="text-align: right"],
        #offerForm .table tbody td .total-price-display,
        #offerForm .table tbody td input[type="text"][style*="text-align: right"],
        #offerForm .table tbody td input[type="number"][style*="text-align: right"] {
            color: #1a1a1a !important;
            font-weight: 500;
        }
        
        #offerForm .table tbody td:first-child {
            white-space: nowrap;
            padding: 0;
            vertical-align: middle;
        }
        
        #offerForm .table tbody td:first-child > div {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            width: 100%;
            height: 22px;
        }
        
        #offerForm .table tbody td:first-child input.editable-product-code,
        #offerForm .table tbody td:first-child input#newProductCode {
            flex: 1;
            min-width: 0;
            display: inline-block;
            vertical-align: middle;
        }
        
        #offerForm .table tbody td:first-child button.product-search-btn,
        #offerForm .table tbody td:first-child button.editable-product-search-btn {
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
            margin: 0;
        }
        
        /* Ürün adı sütunu için */
        #offerForm .table tbody td:nth-child(2) {
            white-space: nowrap;
            padding: 0;
            vertical-align: middle;
            position: relative;
        }
        
        #offerForm .table tbody td:nth-child(2) input.editable-product-name {
            display: inline-block;
            vertical-align: middle;
            width: calc(100% - 24px);
        }
        
        #offerForm .table tbody td:nth-child(2) button.product-search-btn-by-name,
        #offerForm .table tbody td:nth-child(2) button.editable-product-search-btn-by-name {
            display: inline-block;
            vertical-align: middle;
            margin: 0;
        }
        
        #offerForm .table tbody td:nth-child(2) .product-name-autocomplete {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 2px;
            z-index: 9999;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        #offerForm .table tbody td:nth-child(2) .product-name-autocomplete ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        #offerForm .table tbody td:nth-child(2) .product-name-autocomplete li {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            background: white;
        }
        
        #offerForm .table tbody td:nth-child(2) .product-name-autocomplete li:hover {
            background: #f0f0f0;
        }

        #offerForm .table tbody tr {
            height: 24px;
        }

        #offerForm .table tbody tr:hover {
            background-color: #f0f0f0;
        }

        #offerForm .table tbody tr:hover td {
            background-color: #f0f0f0;
        }

        #offerForm .table tbody input.form-control {
            border: none;
            padding: 2px 4px;
            font-size: 11px;
            height: 20px;
            width: 100%;
            background: transparent;
            margin: 0;
            box-sizing: border-box;
        }

        #offerForm .table tbody input.form-control:focus {
            border: 1px solid #6c5ce7;
            background: white;
            outline: none;
        }

        #offerForm .table tbody input[readonly] {
            background: transparent;
            border: none;
        }

        #offerForm .table tbody input[readonly]:focus {
            border: none;
            background: transparent;
        }

        /* Butonlar ERP Stili */
        #offerForm .btn {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 0;
            border: 1px solid #ccc;
            font-weight: 500;
            height: 22px;
            line-height: 18px;
        }

        #offerForm .btn-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        #offerForm .btn-success {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        #offerForm .btn-secondary {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        /* Select2 ERP Stili */
        #offerForm .select2-container--default .select2-selection--single {
            border: 1px solid #ccc;
            border-radius: 0;
            height: 24px;
            padding: 0;
        }

        #offerForm .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
            padding-left: 6px;
            font-size: 12px;
        }

        #offerForm .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 22px;
            right: 6px;
        }

        /* Form Check ERP Stili */
        #offerForm .form-check {
            margin-bottom: 2px;
            padding-left: 0;
        }

        #offerForm .form-check-input {
            margin-top: 0.2em;
            margin-right: 4px;
        }

        #offerForm .form-check-label {
            font-size: 11px;
            padding-left: 0;
            margin-bottom: 0;
        }

        /* Textarea ERP Stili */
        #offerForm textarea.form-control {
            height: auto;
            min-height: 60px;
            resize: vertical;
        }
        
        /* Modal içindeki ürün listesi - Teklif oluştur sayfasındaki gibi görünüm */
        #productListModal .modal-content {
            border: none;
            box-shadow: none;
        }
        
        #productListModal .modal-header {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 8px 15px;
        }
        
        #productListModal .modal-body {
            padding: 10px;
        }
        
        #productListModal .table {
            font-size: 11px;
            margin-bottom: 0;
            background: white;
            border-collapse: collapse;
            width: 100%;
        }
        
        #productListModal .table thead th {
            background: #e8e8e8;
            border: 1px solid #999;
            padding: 4px 6px;
            font-weight: 600;
            font-size: 10px;
            text-align: left;
            color: #333;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        #productListModal .table tbody td {
            border: 1px solid #ddd;
            padding: 2px 4px;
            background: white;
            vertical-align: middle;
            height: 24px;
        }
        
        #productListModal .table tbody tr:hover {
            background: #f5f5f5;
        }
        
        #productListModal .table tbody td:nth-child(2) {
            white-space: nowrap;
            padding: 0;
            vertical-align: middle;
        }
        
        #productListModal .table tbody td:nth-child(2) > div {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            width: 100%;
            height: 22px;
        }
        
        #productListModal .table tbody td:nth-child(2) > div > span {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        #productListModal .table tbody td:nth-child(2) button.product-search-btn-modal {
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
            margin: 0;
        }
        
        /* DataTable wrapper için */
        #productListModal .dataTables_wrapper {
            font-size: 11px;
        }
        
        #productListModal .dataTables_wrapper .dataTables_length,
        #productListModal .dataTables_wrapper .dataTables_filter,
        #productListModal .dataTables_wrapper .dataTables_info,
        #productListModal .dataTables_wrapper .dataTables_paginate {
            font-size: 11px;
            padding: 5px;
        }
        
        #productListModal .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 2px 6px;
            font-size: 11px;
        }
        
        /* Modal içindeki arama inputları */
        #productListModal #modalGlobalSearch,
        #productListModal #modalStokSearch {
            font-size: 11px;
            padding: 4px 8px;
            height: 28px;
            border: 1px solid #ccc;
        }
        
        #productListModal .form-check-label {
            font-size: 11px;
        }

        /* DataTable Genişlik */
        #example {
            width: 100% !important;
        }

        #example_wrapper {
            width: 100%;
        }
        
        /* Kampanya Butonu Animasyonu - Açık Yeşil */
        @keyframes blink {
            0%, 100% {
                opacity: 1;
                background: #d4edda;
                box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
            }
            50% {
                opacity: 0.7;
                background: #c3e6cb;
                box-shadow: 0 0 15px rgba(40, 167, 69, 0.9);
            }
        }
        
        #kampanyaBtn {
            font-weight: 600;
        }

        /* Enjoy Animation for Kampanya Button */
        .kampanya-anim-box {
            position: relative !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            overflow: visible !important;
            isolation: isolate;
            padding: 0 !important;
            display: inline-flex; /* Removed !important to allow JS toggling */
        }

        .kampanya-anim-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 50%;
            height: 50%;
            box-shadow: 0 0 17px 3px #0ff, 0 0 4px 2px #0ff;
            z-index: -1;
            animation-name: cyan-shadow-top;
            animation-timing-function: ease;
            animation-duration: 2s;
            animation-iteration-count: infinite;
            border-radius: 5px;
        }

        .kampanya-anim-box::after {
            content: '';
            position: absolute;
            right: 0;
            bottom: 0;
            width: 50%;
            height: 50%;
            box-shadow: 0 0 17px 3px #0ff, 0 0 4px 2px #0ff;
            z-index: -1;
            animation-name: cyan-shadow-bottom;
            animation-timing-function: ease;
            animation-duration: 2s;
            animation-iteration-count: infinite;
            border-radius: 5px;
        }

        .kampanya-content {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: #060C1F;
            color: #fff;
            border: 1px solid #0ff;
            border-radius: 3px;
            position: relative;
            z-index: 2;
            font-weight: 600;
        }

        @keyframes cyan-shadow-top {
            0% { top: 0; left: 0; }
            25% { top: 50%; left: 0; }
            50% { top: 50%; left: 50%; }
            75% { top: 0; left: 50%; }
            100% { top: 0; left: 0; }
        }

        @keyframes cyan-shadow-bottom {
            0% { right: 0; bottom: 0; }
            25% { right: 0; bottom: 50%; }
            50% { right: 50%; bottom: 50%; }
            75% { right: 50%; bottom: 0; }
            100% { right: 0; bottom: 0; }
        }
    </style>
    <script src="assets/libs/@ckeditor/ckeditor5-build-classic/build/ckeditor.js"></script>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <?php if (!empty($campaigns)): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header"><h5 class="mb-0">Aktif Kampanyalar</h5></div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr><th>Açıklama</th><th>İndirim %</th><th>Başlangıç</th><th>Bitiş</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($campaigns as $c): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($c['description']) ?></td>
                                                <td><?= $c['discount_rate'] ?></td>
                                                <td><?= $c['start_date'] ?></td>
                                                <td><?= $c['end_date'] ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Yurtiçi/Yurtdışı Seçimi -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="card shadow-sm" style="border: 1px solid #ddd;">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <label class="mb-0 fw-semibold" style="font-size: 12px; color: #333;">Pazar Tipi:</label>
                                            <form method="POST" id="pazarTipiForm" class="d-inline">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <input type="radio" class="btn-check" name="pazar_tipi" id="pazar_yurtici" value="yurtici" <?= ($pazarTipi === 'yurtici') ? 'checked' : '' ?> onchange="pazarTipiDegisti()">
                                                    <label class="btn btn-outline-primary" for="pazar_yurtici" style="font-size: 11px; padding: 4px 16px; border-radius: 0;">
                                                        <i class="mdi mdi-home me-1"></i> Yurtiçi
                                                    </label>
                                                    
                                                    <input type="radio" class="btn-check" name="pazar_tipi" id="pazar_yurtdisi" value="yurtdisi" <?= ($pazarTipi === 'yurtdisi') ? 'checked' : '' ?> onchange="pazarTipiDegisti()">
                                                    <label class="btn btn-outline-primary" for="pazar_yurtdisi" style="font-size: 11px; padding: 4px 16px; border-radius: 0;">
                                                        <i class="mdi mdi-earth me-1"></i> Yurtdışı
                                                    </label>
                                                </div>
                                            </form>
                                        </div>
                                        <small class="text-muted mb-0" style="font-size: 10px; font-style: italic;">
                                            Seçiminize göre ürün fiyatları ve müşteri listesi güncellenecektir.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Teklif Oluşturma Formu -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card" style="overflow: visible;">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <?php echo ($user_type === 'Müşteri') ? 'Sipariş Talebinizdeki Kalemler' : 'Teklif Kalemlerini İnceleyin'; ?>
                                    </h5>
                                    <div class="d-flex gap-2 align-items-center">
                                        <a href="urunler_senkron.php" class="btn btn-warning btn-sm">
                                            <i class="bi bi-arrow-repeat me-1"></i> Logo Ürün Senkronizasyonu
                                        </a>
                                        <div class="d-flex flex-column align-items-center">
                                            <small style="font-size: 10px; color: #666; margin-bottom: 2px;">
                                                <?php
                                                // Döviz kurlarını veritabanından oku
                                                $kurQuery = mysqli_query($db, "SELECT dolaralis, euroalis FROM dovizkuru LIMIT 1");
                                                $kurlar = mysqli_fetch_assoc($kurQuery);
                                                $usd = number_format((float)$kurlar['dolaralis'], 2, '.', '');
                                                $eur = number_format((float)$kurlar['euroalis'], 2, '.', '');
                                                echo "$ = {$usd} € = {$eur}";
                                                ?>
                                            </small>
                                            <a href="dovizguncelleme.php" class="btn btn-success btn-sm">
                                                <i class="bi bi-currency-exchange me-1"></i> Döviz Kurlarını Güncelle
                                            </a>
                                        </div>
                                        <button type="button" id="clearCartBtn" class="btn btn-danger btn-sm">
                                            <?php echo ($user_type === 'Müşteri') ? 'ÜRÜN LİSTESİNİ BOŞALT' : 'TEKLİF KALEMLERİNİ BOŞALT'; ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="overflow-x: visible; overflow-y: visible; padding-left: 1rem; padding-right: 1rem;">
                                    <form method="post" action="sipariskontrol.php?t=teklif" class="needs-validation" novalidate enctype="multipart/form-data" id="offerForm">
                                        <!-- ERP Görünümü - Kompakt Form Alanları -->
                                        <div class="erp-form-grid" style="grid-template-columns: repeat(5, 1fr);">
                                            <div>
                                                <label for="teklifno" class="erp-form-label"><?php echo ($user_type === 'Müşteri') ? 'Sipariş No' : 'Teklif No'; ?></label>
                                                <input type="text" name="teklifno" id="teklifno" class="form-control erp-form-input"
                                                    value="<?php echo htmlspecialchars(rand(79985, 997897797) . 'B' . $personelid . '-' . date("Y")); ?>"
                                                    readonly>
                                            </div>
                                            <div>
                                                <label for="gecerliliktarihi" class="erp-form-label">Tarih</label>
                                                <input type="date" name="teklifgecerlilik" id="gecerliliktarihi" class="form-control erp-form-input"
                                                    value="<?= date('Y-m-d', strtotime('+0 days')) ?>" required>
                                            </div>
                                            <div>
                                                <label for="gecerliliktarihi_time" class="erp-form-label">Zaman</label>
                                                <input type="time" name="teklifgecerlilik_time" id="gecerliliktarihi_time" class="form-control erp-form-input"
                                                    value="<?= date('H:i:s') ?>">
                                            </div>
                                            <div>
                                                <label for="teklif_gecerlilik_suresi" class="erp-form-label">Teklif Geçerlilik Süresi (Gün)</label>
                                                <input type="number" name="teklif_gecerlilik_suresi" id="teklif_gecerlilik_suresi" class="form-control erp-form-input" 
                                                    value="5" min="1" max="365">
                                            </div>
                                            <div>
                                                <label for="teslimyer" class="erp-form-label">Teslim Yeri</label>
                                                <input type="text" name="teslimyer" id="teslimyer" class="form-control erp-form-input">
                                            </div>
                                        </div>
                                        
                                        <div class="erp-form-grid" style="grid-template-columns: repeat(5, 1fr);">
                                            <div>
                                                <label for="musteri" class="erp-form-label">Cari Kodu</label>
                                                <select name="musteri" id="musteri" class="form-control select2 erp-form-input" style="width:100%;">
                                                    <option value="786" selected></option>
                                                </select>
                                                <?php 
                                                // Sadece Satış - Teklif Departmanı kullanıcısı için Özel Teklif seçeneği
                                                // Yetkilendirme kontrolü: Mevcut kullanıcı tipi kontrolü veya departman kontrolü
                                                // Şimdilik genele açık veya belirli rollere sınırla
                                                // TODO: Rol kontrolünü netleştir
?>
                                            </div>
                                            <div>
                                                <label for="sirketbilgi" class="erp-form-label">Cari Unvanı</label>
                                                <input type="text" name="sirketbilgi" id="sirketbilgi" class="form-control erp-form-input">
                                                <!-- Kampanya Butonu (Sadece 120.01.E04 için görünür) -->

                                                <!-- Fatura Durumu Butonu -->
                                                <button type="button" id="invoiceStatusBtn" class="btn btn-sm mt-1 ms-1" style="display: none; font-size: 10px; padding: 3px 8px; white-space: nowrap; background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
                                                    <i class="bi bi-exclamation-triangle me-1"></i> Fatura Durumu
                                                </button>

                                                

                                            </div>
                                            <div>
                                                <label for="acikhesap" class="erp-form-label">Açık Hesap Bakiye</label>
                                                <input type="text" id="acikhesap" class="form-control erp-form-input" readonly style="font-weight: bold;">
                                                <div id="risk-limit-info" style="margin-top: 2px;">
                                                    <small class="text-muted" style="font-size: 10px;">Risk Limiti: <span id="risk_limit_text">0,00 TL</span></small>
                                                </div>
                                                <div id="limit-uyari" style="display: none; margin-top: 2px;">
                                                    <small class="text-danger fw-bold" style="font-size: 9px;">Limit Aşıldı!</small>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="payplan" class="erp-form-label">Ödeme Planı</label>
                                                <select id="payplan" name="odemeturu" class="form-control erp-form-input">
                                                    <option value="">Ödeme Planı Seçiniz</option>
                                                    <?php foreach ($payPlans as $plan): ?>
                                                        <option value="<?php echo htmlspecialchars($plan['CODE']); ?>" 
                                                                data-ref="<?php echo (int)$plan['LOGICALREF']; ?>"
                                                                data-def="<?php echo htmlspecialchars($plan['DEFINITION_']); ?>">
                                                            <?php echo htmlspecialchars($plan['CODE'] . " - " . $plan['DEFINITION_']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="paydefref" id="paydefref">
                                                <input type="hidden" name="payplan_def" id="payplan_def">
                                                <div class="form-check mt-1">
                                                    <input type="checkbox" class="form-check-input" id="pesinOdeme" name="pesin_odeme" value="1">
                                                    <label class="form-check-label" for="pesinOdeme" style="font-size: 12px;">
                                                        <strong>Peşin Ödeme</strong> <span class="text-success">(+%10)</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="cariTelefon" class="erp-form-label">Cari Telefon</label>
                                                <input type="tel" name="projeadi" id="cariTelefon" class="form-control erp-form-input">
                                            </div>
                                        </div>
                                        
                                        <div class="erp-form-grid">
                                            <div>
                                                <label for="belgeno" class="erp-form-label">Belge No</label>
                                                <input type="text" name="belgeno" id="belgeno" class="form-control erp-form-input" placeholder="Opsiyonel">
                                            </div>
                                            <div>
                                                <?php
                                                // Sözleşmeleri çek
                                                $sozlesmelerSorgu = mysqli_query($db, "SELECT sozlesme_id, sozlesmeadi FROM sozlesmeler ORDER BY sozlesme_id");
                                                ?>
                                                <label for="sozlesme_id" class="erp-form-label">Sözleşme</label>
                                                <select name="sozlesme_id" id="sozlesme_id" class="form-control erp-form-input" required>
                                                    <option value=""></option>
                                                    <?php
                                                    if ($sozlesmelerSorgu) {
                                                        while ($soz = mysqli_fetch_assoc($sozlesmelerSorgu)) {
                                                            $selected = ($soz['sozlesme_id'] == $selected_sozlesme_id) ? 'selected' : '';
                                                            echo '<option value="' . (int)$soz['sozlesme_id'] . '" ' . $selected . '>' . htmlspecialchars($soz['sozlesmeadi']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="erp-form-label" style="text-align: right; display: block;">Görüntülenecek Para Birimi</label>
                                                <div style="display: flex; flex-direction: row; gap: 12px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                                    <div class="form-check" style="margin: 0; padding: 0; display: flex; align-items: center; white-space: nowrap;">
                                                        <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_eur" value="EUR" checked style="margin-top: 0; margin-right: 4px; width: 14px; height: 14px; flex-shrink: 0;">
                                                        <label class="form-check-label" for="doviz_eur" style="font-size: 10px; margin: 0; padding-left: 0; white-space: nowrap; cursor: pointer;">EUR</label>
                                                    </div>
                                                    <div class="form-check" style="margin: 0; padding: 0; display: flex; align-items: center; white-space: nowrap;">
                                                        <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_usd" value="USD" style="margin-top: 0; margin-right: 4px; width: 14px; height: 14px; flex-shrink: 0;">
                                                        <label class="form-check-label" for="doviz_usd" style="font-size: 10px; margin: 0; padding-left: 0; white-space: nowrap; cursor: pointer;">USD</label>
                                                    </div>
                                                    <div class="form-check" style="margin: 0; padding: 0; display: flex; align-items: center; white-space: nowrap;">
                                                        <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_tl" value="TL" style="margin-top: 0; margin-right: 4px; width: 14px; height: 14px; flex-shrink: 0;">
                                                        <label class="form-check-label" for="doviz_tl" style="font-size: 10px; margin: 0; padding-left: 0; white-space: nowrap; cursor: pointer;">TL</label>
                                                    </div>
                                                    <div class="form-check" style="margin: 0; padding: 0; display: flex; align-items: center; white-space: nowrap;">
                                                        <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_tumu" value="TUMU" style="margin-top: 0; margin-right: 4px; width: 14px; height: 14px; flex-shrink: 0;">
                                                        <label class="form-check-label" for="doviz_tumu" style="font-size: 10px; margin: 0; padding-left: 0; white-space: nowrap; cursor: pointer;">Tümü</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <label class="erp-form-label">&nbsp;</label>
                                                <!-- Kampanya Bilgi Butonu -->
                                                <!-- Kampanya Bilgi Butonu -->
                                                <button type="button" id="kampanyaBtn" class="btn btn-sm mb-1 kampanya-anim-box" style="width: auto; min-width: 140px; height: 28px; display: none; font-size: 12px; white-space: nowrap;" data-bs-toggle="modal" data-bs-target="#kampanyaModal">
                                                    <span class="kampanya-content" style="padding: 0 10px;">
                                                        <i class="bi bi-gift me-1"></i> Kampanya Bilgi
                                                    </span>
                                                </button>
                                                <button type="button" id="applyCampaignsBtn" class="btn btn-warning btn-sm" style="width: auto; min-width: 140px; height: 28px; font-size: 12px; font-weight: bold; color: #000; display: flex; align-items: center; justify-content: center; display: inline-flex;">
                                                    <i class="bi bi-percent me-1"></i> Kampanya Uygula
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive mt-3">
                                            <table id="cartTable" class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 120px;">Stok Kodu</th>
                                                        <th>Stok Adı</th>
                                                        <th>Açıklama</th>
                                                        <th style="width: 60px;">Miktar</th>
                                                        <th style="width: 120px;">Liste Fiyatı</th>
                                                        <th style="width: 70px;">İskonto (%)</th>
                                                        <th style="width: 120px;">İskontolu Birim Fiyat</th>
                                                        <th style="width: 120px;">İskontolu Toplam</th>
                                                        <th style="width: 80px;">Birim</th>
                                                        <th style="width: 60px;">KDV (%)</th>
                                                        <th style="width: 80px;">İşlem</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="cartTableBody">
                                                    <?php if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])):
                                                        foreach ($_COOKIE['teklif'] as $fihristId => $val):
                                                            $fihristId = filter_var($fihristId, FILTER_VALIDATE_INT);
                                                            if (!$fihristId) continue;
                                                            $stmt = $db->prepare("SELECT urun_id, stokkodu, stokadi, fiyat, export_fiyat, doviz, olcubirimi, LOGICALREF FROM urunler WHERE urun_id = ?");
                                                            $stmt->bind_param("i", $fihristId);
                                                            $stmt->execute();
                                                            $row = $stmt->get_result()->fetch_assoc();
                                                            $stmt->close();
                                                            if (!$row) continue;

                                                            // Pazar tipine göre fiyat seç
                                                            $liste        = $isForeign ? floatval($row['export_fiyat']) : floatval($row['fiyat']);
                                                            $hasPrice     = ($liste > 0);
                                                            $campaignRate = $dbManager->getCampaignDiscountForProduct((int)$row['LOGICALREF']) ?? 0.0;
                                                            $unit0        = $hasPrice ? number_format($liste * (1 - $campaignRate / 100), 2, '.', '') : 0;
                                                            $total0       = $unit0;
                                                            $readonlyAttr = ($discountDisabled || $campaignRate > 0) ? 'readonly' : '';
                                                            $campaignRatesMap[$row['urun_id']] = $campaignRate;
                                                            $qty = (is_numeric($val) && $val > 0) ? intval($val) : 1;
                                                    ?>
                                                        <tr data-id="<?= $row['urun_id'] ?>" data-currency="<?= $row['doviz'] ?>">
                                                            <td style="padding: 0;">
                                                                <input type="text" 
                                                                    name="product_code[<?= $row['urun_id'] ?>]"
                                                                    value="<?= htmlspecialchars($row['stokkodu']) ?>"
                                                                    class="form-control product-code-input editable-product-code"
                                                                    data-product-id="<?= $row['urun_id'] ?>"
                                                                    data-original-code="<?= htmlspecialchars($row['stokkodu']) ?>"
                                                                    style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">
                                                                <button type="button" 
                                                                    class="btn btn-sm product-search-btn editable-product-search-btn" 
                                                                    data-product-id="<?= $row['urun_id'] ?>"
                                                                    style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" 
                                                                    title="Ürün Ara">
                                                                    <span style="font-size: 12px;">⋯</span>
                                                                </button>
                                                            </td>
                                                            <td style="padding: 0; position: relative;">
                                                                <input type="text" 
                                                                    name="product_name[<?= $row['urun_id'] ?>]"
                                                                    value="<?= htmlspecialchars($row['stokadi']) ?>"
                                                                    class="form-control editable-product-name"
                                                                    data-product-id="<?= $row['urun_id'] ?>"
                                                                    data-original-name="<?= htmlspecialchars($row['stokadi']) ?>"
                                                                    style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">
                                                                <button type="button"
                                                                    class="btn btn-sm product-search-btn-by-name editable-product-search-btn-by-name"
                                                                    data-product-id="<?= $row['urun_id'] ?>"
                                                                    style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;"
                                                                    title="Ürün Ara">
                                                                    <span style="font-size: 12px;">⋯</span>
                                                                </button>
                                                                <div class="product-name-autocomplete" style="display: none; position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); top: 100%; left: 0; margin-top: 2px;"></div>
                                                            </td>
                                                            <td style="padding: 0;">
                                                                <input type="text"
                                                                    name="aciklama[<?= $row['urun_id'] ?>]"
                                                                    value=""
                                                                    class="form-control description-input"
                                                                    placeholder="Açıklama"
                                                                    style="text-align: left; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;">
                                                            </td>
                                                            <td style="padding: 0;">
                                                                <input type="number"
                                                                    name="miktarisi[<?= $row['urun_id'] ?>]"
                                                                    value="<?= $qty ?>"
                                                                    class="form-control quantity-input"
                                                                    min="1"
                                                                    style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;">
                                                            </td>
                                                            <td style="text-align: right; padding: 2px 4px; color: #1a1a1a; font-weight: 500; font-size: 13px; line-height: 28px;">
                                                                <?php if ($hasPrice): ?>
                                                                    <?php
                                                                    $dovizIkon = '';
                                                                    switch($row['doviz']) {
                                                                        case 'EUR': $dovizIkon = '€'; break;
                                                                        case 'USD': $dovizIkon = '$'; break;
                                                                        case 'TL': $dovizIkon = '₺'; break;
                                                                        default: $dovizIkon = htmlspecialchars($row['doviz']);
                                                                    }
                                                                    ?>
                                                                    <?= number_format($liste,2,',','.') ?> <?= $dovizIkon ?>
                                                                    <input type="hidden"
                                                                        name="fiyatsi[<?= $row['urun_id'] ?>]"
                                                                        value="<?= number_format($liste,2,',','.') ?>">
                                                                <?php else: ?>
                                                                    <?php if ($yetki === 'Personel'): ?>
                                                                        <span class="fiyat-yok-text" 
                                                                            style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline dotted;"
                                                                            data-urun-id="<?= $row['urun_id'] ?>"
                                                                            data-stokkodu="<?= htmlspecialchars($row['stokkodu']) ?>"
                                                                            data-stokadi="<?= htmlspecialchars($row['stokadi']) ?>"
                                                                            data-bs-toggle="popover"
                                                                            data-bs-trigger="hover focus"
                                                                            data-bs-placement="top"
                                                                            data-bs-html="true"
                                                                            data-bs-content="<div class='text-center'><small>Fiyat talebi oluşturmak için tıklayın</small></div>"
                                                                            title="Fiyat Bilgisi Yok">
                                                                            Fiyatı Yok
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span style="color: #dc3545; font-style: italic;">Fiyatı Yok</span>
                                                                    <?php endif; ?>
                                                                    <input type="hidden"
                                                                        name="fiyatsi[<?= $row['urun_id'] ?>]"
                                                                        value="0">
                                                                <?php endif; ?>
                                                            </td>
                                                            <td style="text-align: right; padding: 0;">
                                                                <input type="text"
                                                                    name="iskontosi[<?= $row['urun_id'] ?>]"
                                                                    value="<?= number_format($campaignRate,2,',','.') ?>"
                                                                    class="form-control discount-input"
                                                                    data-list-price="<?= $liste ?>"
                                                                    data-campaign="<?= $campaignRate ?>"
                                                                    <?= $readonlyAttr ?>
                                                                    style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;">
                                                            </td>
                                                            <td style="padding: 0; position: relative;">
                                                                <?php
                                                                $dovizIkon = '';
                                                                switch($row['doviz']) {
                                                                    case 'EUR': $dovizIkon = '€'; break;
                                                                    case 'USD': $dovizIkon = '$'; break;
                                                                    case 'TL': $dovizIkon = '₺'; break;
                                                                    default: $dovizIkon = htmlspecialchars($row['doviz']);
                                                                }
                                                                ?>
                                                                <input type="text"
                                                                    name="final_price_unit[<?= $row['urun_id'] ?>]"
                                                                    class="form-control final-price-input"
                                                                    value="<?= number_format($unit0,2,',','.') ?>"
                                                                    data-urun-id="<?= $row['urun_id'] ?>"
                                                                    data-list-price="<?= $liste ?>"
                                                                    data-original-discount="<?= $campaignRate ?>"
                                                                    style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 20px 2px 4px; height: 28px; font-size: 13px;">
                                                                <span class="currency-icon" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #666; pointer-events: none;"><?= $dovizIkon ?></span>
                                                                <input type="hidden"
                                                                    class="final-price-hidden"
                                                                    value="<?= $unit0 ?>">
                                                            </td>
                                                            <td style="text-align: right; padding: 2px 4px;">
                                                                <span class="total-price-display" style="font-size: 13px; line-height: 28px;"><?= number_format($unit0 * $qty,2,',','.') ?> <?= $dovizIkon ?></span>
                                                            </td>
                                                            <td style="white-space: nowrap; text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;">
                                                                <?= htmlspecialchars($row['olcubirimi']) ?>
                                                                <input type="hidden"
                                                                    name="olcubirimi[<?= $row['urun_id'] ?>]"
                                                                    value="<?= htmlspecialchars($row['olcubirimi']) ?>">
                                                            </td>
                                                            <td style="text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;">
                                                                20
                                                            </td>

                                                            <td style="text-align: center; padding: 2px;">
                                                                <button type="button" class="btn btn-danger btn-sm remove-btn" data-id="<?= $row['urun_id'] ?>" style="padding: 0 6px; font-size: 11px; height: 24px; line-height: 22px;">Kaldır</button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; endif; ?>
                                                    <!-- Boş satırlar JavaScript ile oluşturulacak -->
                                                </tbody>
                                                <tfoot id="cartTableFooter" style="display: none;">
                                                    <tr style="background: #f0f0f0; font-weight: bold;">
                                                        <td colspan="5" style="text-align: right; padding: 8px 4px;">ARA TOPLAM:</td>
                                                        <td style="text-align: right; padding: 8px 4px; font-size: 12px;">
                                                            <span id="subTotalAmount">0,00</span>
                                                        </td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                    <tr style="background: #ffffff;">
                                                        <td colspan="5" style="text-align: right; padding: 8px 4px; vertical-align: middle;">GENEL İSKONTO (%):</td>
                                                        <td style="text-align: right; padding: 4px;">
                                                            <div class="input-group input-group-sm justify-content-end" style="width: 120px; float: right;">
                                                                <input type="text" id="genelIskonto" name="genel_iskonto" class="form-control" value="<?php echo number_format($genel_iskonto, 2, ',', '.'); ?>" style="text-align: right;">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                    <tr style="background: #e9ecef; font-weight: bold; border-top: 2px solid #dee2e6;">
                                                        <td colspan="5" style="text-align: right; padding: 8px 4px;">NET TOPLAM:</td>
                                                        <td style="text-align: right; padding: 8px 4px; font-size: 12px;">
                                                            <span id="totalAmount">0,00</span>
                                                            <br>
                                                            <span style="font-size: 11px; font-weight: normal; color: #555;">(KDV Dahil: <span id="totalAmountWithVAT">0,00</span>)</span>
                                                        </td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                    <tr style="background: #f9f9f9;">
                                                        <td colspan="5" style="text-align: right; padding: 4px; font-size: 10px; color: #666;">TL Karşılığı:</td>
                                                        <td style="text-align: right; padding: 4px; font-size: 10px; color: #666;">
                                                            <span id="totalAmountTL">0,00 ₺</span>
                                                            <br>
                                                            <span>(KDV Dahil: <span id="totalAmountTLWithVAT">0,00 ₺</span>)</span>
                                                        </td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <label for="ekstraBilgi" class="form-label">Ekstra Bilgi / Notlar</label>
                                                <textarea
                                                    name="ekstra_bilgi"
                                                    id="ekstraBilgi"
                                                    class="form-control"
                                                    rows="2"
                                                    style="resize: vertical; min-height: 40px; max-height: 100px; overflow-y: auto; font-size: 12px;"><?php echo htmlspecialchars($ekstra_bilgi); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <label for="sozlesmeMetinEdited" class="form-label">Sözleşme Metni (Düzenlenebilir)</label>
                                                <textarea
                                                    name="sozlesme_metin_edited"
                                                    id="sozlesmeMetinEdited"
                                                    class="form-control"
                                                    rows="10"
                                                    style="resize: vertical; min-height: 250px;"><?php 
                                                    // Eğer düzenlenmiş metin varsa onu göster, yoksa orijinal metni göster
                                                    echo htmlspecialchars($sozlesme_metin_edited ?: $selected_sozlesme_metin); 
                                                    ?></textarea>
                                                <small class="form-text text-muted">Seçili sözleşmenin metnini buradan düzenleyebilirsiniz. Değişiklikler kaydedildiğinde teklifte görünecektir.</small>
                                            </div>
                                        </div>
                                        <script>
                                            // Sözleşme Metni için ClassicEditor
                                            var sozlesmeEditor;
                                            ClassicEditor
                                                .create(document.querySelector('#sozlesmeMetinEdited'), {
                                                    initialData: document.querySelector('#sozlesmeMetinEdited').value
                                                })
                                                .then(editor => {
                                                    sozlesmeEditor = editor; // Global değişkene kaydet
                                                    
                                                    const form = document.querySelector('#sozlesmeMetinEdited').form;
                                                    if (form) {
                                                        form.addEventListener('submit', () => {
                                                            document.querySelector('#sozlesmeMetinEdited').value = editor.getData();
                                                        });
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Sözleşme metni editor hatası:', error);
                                                });
                                        </script>

                                        <div class="row mt-3" style="overflow: visible; margin-left: -15px; margin-right: -15px;">
                                            <div class="col-12 text-center" style="overflow: visible; padding: 20px;">
                                                <input type="hidden" name="hazirlayanid" value="<?php echo $_SESSION['yonetici_id'] ?? 0; ?>">
                                                <div class="form-check d-inline-block mb-3 me-3">
                                                    <input class="form-check-input" type="checkbox" id="is_special_offer" name="is_special_offer" value="1">
                                                    <label class="form-check-label fw-bold text-danger" for="is_special_offer" style="font-size: 14px;">
                                                        Özel Teklif (Yönetici Onayı Gerektirir)
                                                    </label>
                                                </div>
                                                <br>
                                                <input type="submit" name="preview" id="submitCart" class="btn btn-success btn-lg" style="padding: 10px 50px 20px 50px; font-size: 20px; min-width: 250px; width: auto; display: inline-block; line-height: 1;" value="<?php echo ($user_type === "Müşteri") ? "Kaydet" : "Kaydet"; ?>">
                                            </div>
                                        </div>
                                    </form>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const specialOfferCheckbox = document.getElementById('is_special_offer');
                                            const submitBtn = document.getElementById('submitCart');
                                            
                                            // Store original state
                                            let originalBtnText = submitBtn ? submitBtn.value : 'Kaydet';
                                            let originalBtnClass = submitBtn ? submitBtn.className : 'btn btn-success btn-lg';

                                            if(specialOfferCheckbox && submitBtn) {
                                                specialOfferCheckbox.addEventListener('change', function() {
                                                    if(this.checked) {
                                                        submitBtn.value = 'YÖNETİCİ ONAYA GÖNDER';
                                                        submitBtn.classList.remove('btn-success');
                                                        submitBtn.classList.add('btn-warning'); 
                                                    } else {
                                                        submitBtn.value = originalBtnText; // Restore original text
                                                        submitBtn.classList.remove('btn-warning');
                                                        submitBtn.classList.add('btn-success');
                                                    }
                                                });
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        ClassicEditor
                            .create(document.querySelector('#ekstraBilgi'), {
                                initialData: document.querySelector('#ekstraBilgi').value
                            })
                            .then(editor => {
                                // Editor container'ına ID ekle
                                var editorElement = editor.ui.getEditableElement();
                                if (editorElement && editorElement.closest('.ck-editor')) {
                                    editorElement.closest('.ck-editor').setAttribute('data-id', 'ekstraBilgi');
                                }
                                
                                // Yüksekliği zorla ayarla
                                setTimeout(function() {
                                    var editable = editor.ui.view.editable.element;
                                    if (editable) {
                                        editable.style.setProperty('height', '40px', 'important');
                                        editable.style.setProperty('min-height', '40px', 'important');
                                        editable.style.setProperty('max-height', '100px', 'important');
                                        editable.style.setProperty('overflow-y', 'auto', 'important');
                                        editable.style.setProperty('font-size', '12px', 'important');
                                    }
                                    
                                    // CKEditor content container'ını da ayarla
                                    var contentElement = editable ? editable.closest('.ck-content') : null;
                                    if (contentElement) {
                                        contentElement.style.setProperty('height', '40px', 'important');
                                        contentElement.style.setProperty('min-height', '40px', 'important');
                                        contentElement.style.setProperty('max-height', '100px', 'important');
                                    }
                                    
                                    // CKEditor editable element'ini de ayarla
                                    var editorContainer = editable ? editable.closest('.ck-editor') : null;
                                    if (editorContainer) {
                                        var editableElement = editorContainer.querySelector('.ck-editor__editable');
                                        if (editableElement) {
                                            editableElement.style.setProperty('height', '40px', 'important');
                                            editableElement.style.setProperty('min-height', '40px', 'important');
                                            editableElement.style.setProperty('max-height', '100px', 'important');
                                        }
                                    }
                                }, 200);
                                
                                const form = document.querySelector('#ekstraBilgi').form;
                                if (form) {
                                    form.addEventListener('submit', () => {
                                        document.querySelector('#ekstraBilgi').value = editor.getData();
                                    });
                                }
                            })
                            .catch(error => {
                                console.error(error);
                            });
                    </script>

                    <!-- Ürün Listesi Modal (Gizli - 3 nokta butonundan açılacak) -->
                    <div class="modal fade" id="productListModal" tabindex="-1" aria-labelledby="productListModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productListModalLabel">Ürün Listesi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <input type="text" id="modalGlobalSearch" class="form-control" placeholder="Ürünlerde ara..." style="font-size: 11px; padding: 4px 8px; height: 28px; border: 1px solid #ccc;">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="modalOnlyPriced" style="margin-top: 0.25rem;">
                                            <label class="form-check-label" for="modalOnlyPriced" style="font-size: 11px;">Sadece fiyatı olanları göster</label>
                                        </div>
                                    </div>
                                    <div class="table-responsive" style="width: 100%; border: 1px solid #999; background: white;">
                                        <table id="modalExample" class="table table-bordered dt-responsive nowrap" style="width:100%; font-size: 11px;">
                                            <thead>
                                                <tr>
                                                    <th>İşlem</th>
                                                    <th>Kod</th>
                                                    <th>Adı</th>
                                                    <th>Birimi</th>
                                                    <th>Liste Fiyatı</th>
                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Marka</th>
                                                    <th class="d-none"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Veriler DataTable ile sunucudan yüklenecek -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Modal: Yardım -->
            <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="helpModalLabel">Yardım</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="list-unstyled small">
                                <li><span class="text-primary fw-bold">Arama:</span>
                                    <span>Ürünlerde ara</span> ve <span>Stok kodu ile ekle</span> alanlarını kullanarak filtreleme yapın.</li>
                                <li><span class="text-success fw-bold">Ekleme:</span>
                                    <kbd>Enter</kbd> tuşu veya <span class="text-success">Seç</span> butonu ile satırları sepete ekleyin.</li>
                                <li><span class="text-danger fw-bold">Kaldırma:</span>
                                    <kbd>Delete</kbd> tuşu veya <span class="text-danger">Kaldır</span> butonu ile seçilen ürünü çıkarın.</li>
                                <li><span class="text-info fw-bold">Gezinme:</span>
                                    Ok tuşları ile listede dolaşabilir, <kbd>Enter</kbd> ile ekleyebilirsiniz.</li>
                                <li><span class="text-warning fw-bold">Tamamlama:</span>
                                    İşlem sonunda <strong>İşlemi Tamamla</strong> butonuna basın.</li>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">TAMAM</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

    <!-- AJAX Spinner Overlay -->
    <div class="spinner-overlay" id="spinnerOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Yükleniyor...</span>
        </div>
    </div>

    <!-- Toast Bildirimi -->
    <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" id="toastNotification">
        <div class="d-flex">
            <div class="toast-body">
                İşlem başarıyla tamamlandı!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
        </div>
    </div>

    <!-- 2) Modal: fiyat düzenleme -->
    <div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceModalLabel">İskontolu Birim Fiyatı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="priceModalInput" class="form-label">Yeni Birim Fiyat</label>
                        <input type="number"
                            class="form-control"
                            id="priceModalInput"
                            step="0.01"
                            min="0">
                        <div class="invalid-feedback" id="priceModalError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="priceModalSave">Onayla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="priceInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fiyat Bilgisi Gerekli</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    Lütfen fiyat bilgisi talebinizi ilgililere iletin.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tamam</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/datatables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Global değişkenler
        var table;
        var iskontoMax = <?php echo $iskonto_max; ?>; // Kullanıcının maksimum iskonto yetkisi
        var userYetki = '<?php echo htmlspecialchars($yetki); ?>'; // Kullanıcının yetki seviyesi
        
        $(document).ready(function() {
            $('#stokSearch').val('');
            
            // Teklif Kalemlerini Boşalt butonu
            $('#clearCartBtn').on('click', function() {
                if (!confirm('Teklif kalemlerini tamamen temizlemek istediğinize emin misiniz?')) {
                    return;
                }
                
                // Önce session'dan müşteri bilgilerini temizle
                $.post('save_form_state.php', {
                    clear_customer: true,
                    musteri_id: '',
                    ekstra_bilgi: '',
                    sozlesme_metin: '',
                    sozlesme_id: ''
                }, function() {
                    // Sonra sepeti temizle
                    $.post('public/cart_actions.php', {
                        action: 'clear'
                    }, function(resp) {
                        if (resp.success) {
                            // Sayfayı yenile (aynı sayfada kal)
                            window.location.reload();
                        } else {
                            alert(resp.message || 'Sepet temizlenirken bir hata oluştu.');
                        }
                    }, 'json').fail(function() {
                        alert('Sunucu hatası: Sepet temizlenemedi.');
                    });
                });
            });
            
            // İskonto limiti kontrolü - Personel yetkisi için %60 sınırı
            $(document).on('input change blur', '.discount-input', function() {
                var $input = $(this);
                var value = $input.val();
                
                // Kampanya ile uygulanan iskontolar için kontrol atla
                if ($input.attr('data-logo-campaign') === 'true' || $input.data('logo-campaign') === true) {
                    console.log('Kampanya iskontosu - limit kontrolü atlandı');
                    return;
                }
                
                // Boş değer kontrolü
                if (!value || value.trim() === '') {
                    return;
                }
                
                // Virgülü noktaya çevir
                value = value.replace(',', '.');
                var discountValue = parseFloat(value);
                
                // Geçersiz değer kontrolü
                if (isNaN(discountValue)) {
                    return;
                }
                
                // Personel yetkisi için limit kontrolü
                if (userYetki.toLowerCase() === 'personel' && discountValue > iskontoMax) {
                    alert('⚠️ UYARI!\n\nPersonel yetkisi ile maksimum %' + iskontoMax + ' iskonto girebilirsiniz.\n\nGirilen değer: %' + discountValue.toFixed(2) + '\nİzin verilen maksimum: %' + iskontoMax);
                    
                    // Değeri sıfırla
                    $input.val('0,00');
                    $input.focus();
                    
                    // Satırı yeniden hesapla
                    var $row = $input.closest('tr');
                    if (typeof recalcRow === 'function') {
                        recalcRow($row);
                    }
                    
                    return false;
                }
            });
            
            // Sayfa yüklendiğinde boş satırları kontrol et ve ekle
            setTimeout(function() {
                // Mevcut toplam satır sayısını kontrol et (ürün satırları + boş satırlar)
                var totalRows = $('#cartTableBody tr').length;
                var emptyRowCount = $('tr[data-id="new"]').length;
                var productRowCount = totalRows - emptyRowCount;
                
                console.log('Sayfa yüklendi - Toplam satır:', totalRows, 'Boş satır:', emptyRowCount, 'Ürün satırı:', productRowCount);
                
                // Eğer toplam satır sayısı 10'dan azsa, boş satırlar ekle
                if (totalRows < 10) {
                    var neededRows = 10 - totalRows;
                    console.log('Eksik satır sayısı:', neededRows);
                    for (var i = 0; i < neededRows; i++) {
                        addNewEmptyRow();
                    }
                } else if (emptyRowCount === 0) {
                    // Eğer hiç boş satır yoksa, en az 1 tane ekle (dinamik ekleme için)
                    console.log('Boş satır yok, 1 tane ekleniyor');
                    addNewEmptyRow();
                }
                
                // Toplamı hesapla (birkaç kez çağır - DOM tam yüklenmesini bekle)
                updateTotalAmount();
                setTimeout(function() {
                    updateTotalAmount(); // 1. tekrar
                }, 200);
                setTimeout(function() {
                    updateTotalAmount(); // 2. tekrar (güncel kurlarla)
                }, 800);
                
                // İskontoları yükle (cookie'den ürünler yüklendikten sonra)
                if (window.savedDiscounts && Object.keys(window.savedDiscounts).length > 0) {
                    setTimeout(function() {
                        loadSavedDiscounts();
                        updateTotalAmount(); // İskontolardan sonra da hesapla
                    }, 300);
                }
            }, 500); // Cookie'den ürünler yüklendikten sonra çalışsın
            
            // Modal kaldırıldı - artık sayfa içeriği direkt görünüyor
            // Seçili müşteriyi geri yükle (AJAX ile session'dan al)
            $.ajax({
                url: 'get_form_state.php',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.musteri_id) {
                        // Müşteri bilgileri varsa Select2'ye ekle ve seç
                        if (response.musteri_data) {
                            var option = new Option(response.musteri_data.text, response.musteri_data.id, true, true);
                            $('#musteri').append(option).trigger('change');
                            // Unvanı da hemen yaz
                            if (response.musteri_data.text) {
                                var parts = response.musteri_data.text.split(' - ');
                                if (parts.length > 1) {
                                    var unvan = parts.slice(1).join(' - ');
                                    $('#sirketbilgi').val(unvan);
                                }
                            }
                        } else {
                            // Sadece ID varsa set et
                            $('#musteri').val(response.musteri_id).trigger('change');
                        }
                    }
                    
                    // İskontoları sakla - cookie'den ürünler yüklendikten sonra yüklenecek
                    if (response.success && response.iskontolar && Object.keys(response.iskontolar).length > 0) {
                        window.savedDiscounts = response.iskontolar;
                        console.log('İskontolar session\'dan alındı, yükleme bekleniyor:', response.iskontolar);
                    }
                    
                    // Sözleşme metnini yükle
                    if (response.success && response.sozlesme_metin) {
                        // ClassicEditor varsa setData ile güncelle, yoksa normal val ile
                        setTimeout(function() {
                            if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                                sozlesmeEditor.setData(response.sozlesme_metin);
                            } else {
                                $('#sozlesmeMetinEdited').val(response.sozlesme_metin);
                            }
                        }, 500); // Editor'un yüklenmesi için bekle
                    }
                    
                    // Sözleşme ID'sini yükle
                    if (response.success && response.sozlesme_id) {
                        $('#sozlesme_id').val(response.sozlesme_id).trigger('change');
                    }
                }
            });
            
            // İskontoları yükleme fonksiyonu
            function loadSavedDiscounts() {
                if (!window.savedDiscounts || Object.keys(window.savedDiscounts).length === 0) {
                    return;
                }
                
                console.log('İskontolar yükleniyor:', window.savedDiscounts);
                var loadedCount = 0;
                
                $.each(window.savedDiscounts, function(productId, discountValue) {
                    var $row = $('tr[data-id="' + productId + '"]');
                    if ($row.length) {
                        var $discountInput = $row.find('input[name="iskontosi[' + productId + '"]');
                        if ($discountInput.length === 0) {
                            // Alternatif selector dene
                            $discountInput = $row.find('input[name^="iskontosi"]');
                        }
                        if ($discountInput.length) {
                            // Virgülü noktaya çevir ve değeri set et
                            var discountStr = discountValue.toString();
                            // Hem nokta hem virgül olabilir
                            discountStr = discountStr.replace(/\./g, '').replace(',', '.');
                            var discount = parseFloat(discountStr) || 0;
                            $discountInput.val(discount.toFixed(2).replace('.', ','));
                            
                            console.log('İskonto yüklendi - Ürün ID:', productId, 'İskonto:', discount.toFixed(2));
                            
                            // Satırı yeniden hesapla (recalcRow fonksiyonu varsa)
                            if (typeof recalcRow === 'function') {
                                recalcRow($row);
                            } else {
                                // recalcRow yoksa manuel hesapla
                                var qty = parseFloat($row.find('.quantity-input').val()) || 0;
                                var listPriceVal = $row.find('input[name^="fiyatsi"]').val();
                                var listPrice = (listPriceVal && typeof listPriceVal === 'string') ? parseFloat(listPriceVal.replace(',','.')) : (parseFloat(listPriceVal) || 0);
                                var discPct = discount;
                                var unitPrice = listPrice * (1 - discPct / 100);
                                var total = unitPrice * qty;
                                
                                $row.find('.final-price-hidden').val(unitPrice.toFixed(2));
                                $row.find('.final-price-input').val(unitPrice.toFixed(2).replace('.',','));
                                $row.find('.total-price-display').text(total.toFixed(2).replace('.',','));
                            }
                            
                            loadedCount++;
                        } else {
                            console.warn('İskonto input bulunamadı - Ürün ID:', productId, 'Satır:', $row);
                        }
                    } else {
                        console.warn('Satır bulunamadı - Ürün ID:', productId);
                    }
                });
                
                console.log('Toplam', loadedCount, 'iskonto yüklendi');
                
                // Toplamı güncelle
                if (typeof updateTotalAmount === 'function') {
                    updateTotalAmount();
                }
            }
            
            // Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Select2 müşteri seçimi
            $('#musteri').select2({
                placeholder: "Lütfen bir şirket seçiniz",
                allowClear: true,
                minimumInputLength: 0,
                dropdownParent: $('#offerForm').closest('.card'),
                ajax: {
                    url: 'musteri-search.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        // Radio button'dan seçili değeri al
                        var pazarTipi = $('input[name="pazar_tipi"]:checked').val() || 'yurtici';
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                            pazar_tipi: pazarTipi
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    // Dropdown'da "KOD - UNVAN" formatında göster
                    if (!data.id) {
                        return data.text;
                    }
                    return data.text;
                },
                templateSelection: function(data) {
                    // Seçilen değerde sadece KOD'u göster
                    if (!data.id) {
                        return data.text;
                    }
                    var text = data.text || '';
                    var parts = text.split(' - ');
                    var kod = parts[0] || text;
                    
                    // Unvanı Cari Unvanı alanına yaz
                    if (text && parts.length > 1) {
                        var unvan = parts.slice(1).join(' - ');
                        var $sirketBilgi = $('#sirketbilgi');
                        if ($sirketBilgi.length) {
                            $sirketBilgi.val(unvan);
                        }
                    }
                    
                    return kod; // Sadece kod kısmını döndür
                }
            });

            // Ödeme planı için Select2 başlat
            $('#payplan').select2({
                placeholder: "Ödeme Planı Seçiniz",
                allowClear: true,
                dropdownParent: $('#offerForm').closest('.card')
            }).on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var ref = selectedOption.data('ref') || '';
                var def = selectedOption.data('def') || '';
                $('#paydefref').val(ref);
                $('#payplan_def').val(def);
            });
            
            // Modal içindeki DataTable'ı başlat
            var modalTable;
            $('#productListModal').on('shown.bs.modal', function() {
                if (!modalTable) {
                    modalTable = $('#modalExample').DataTable({
                        deferRender: true,
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "uruncekdatatable.php",
                            data: function (d) {
                                d.onlyPriced = $('#modalOnlyPriced').is(':checked') ? 1 : 0;
                                var pazarTipi = $('input[name="pazar_tipi"]:checked').val() || 'yurtici';
                                d.pazar_tipi = pazarTipi;
                            }
                        },
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json"
                        },
                        pageLength: 50,
                        select: { style: 'multi' },
                        scrollX: false,
                        autoWidth: true,
                        dom: '<"top"lf>rt<"bottom"ip><"clear">',
                        columnDefs: [
                            { targets: 0, width: "80px", className: "text-center" },
                            { targets: 1, width: "120px" },
                            { targets: 2, width: "auto" },
                            { targets: 3, width: "80px", className: "text-center" },
                            { targets: 4, width: "120px", className: "text-right" },
                            { targets: 5, width: "80px", className: "text-center" },
                            { targets: 6, width: "80px", className: "text-right" },
                            { targets: 7, width: "100px" },
                            { targets: 8, visible: false }
                        ],
                        createdRow: function (row, data) {
                            var price = parseFloat(data[8]) || 0;
                            $(row).css({
                                'height': '24px',
                                'font-size': '11px'
                            });
                            if (price <= 0) {
                                $(row).addClass('no-price');
                            }
                        }
                    });
                    
                    // Modal içindeki arama
                    $('#modalGlobalSearch').on('keyup', function() {
                        modalTable.search(this.value).draw();
                    });
                    
                    $('#modalOnlyPriced').on('change', function () {
                        modalTable.ajax.reload();
                    });
                    
                    // Modal'dan ürün seçildiğinde (Seç butonu)
                    $('#modalExample tbody').on('click', '.select-btn', function() {
                        var rowData = modalTable.row($(this).closest('tr')).data();
                        var productId = $(this).data('id');
                        var targetProductId = $('#productListModal').data('target-product-id');
                        
                        // Stok kodunu HTML'den parse et
                        var stokkodu = '';
                        if (rowData[1]) {
                            var $tempDiv = $('<div>').html(rowData[1]);
                            stokkodu = $tempDiv.find('span').first().text().trim() || rowData[1].replace(/<[^>]*>/g, '').trim();
                        }
                        
                        if (!stokkodu) {
                            alert('Stok kodu bulunamadı');
                            return;
                        }
                        
                        if (targetProductId) {
                            // Mevcut ürün satırını güncelle
                            var $targetRow = $('tr[data-id="' + targetProductId + '"]');
                            if ($targetRow.length > 0) {
                                var qty = parseInt($targetRow.find('.quantity-input').val()) || 1;
                                updateProductByCode(stokkodu, targetProductId, $targetRow);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('productListModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                // Backdrop'u temizle
                                setTimeout(function() {
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                    $('body').css('overflow', '');
                                    $('body').css('padding-right', '');
                                }, 300);
                            }
                        } else {
                            // Yeni ürün satırına ekle
                            var $targetRow = $('#newProductRow');
                            if ($targetRow.length > 0) {
                                searchProductByCode(stokkodu, $targetRow);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('productListModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                // Backdrop'u temizle
                                setTimeout(function() {
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                    $('body').css('overflow', '');
                                    $('body').css('padding-right', '');
                                }, 300);
                            } else {
                                alert('Yeni ürün satırı bulunamadı');
                            }
                        }
                    });
                    
                    // Modal'dan 3 nokta butonuna tıklandığında (stok kodu yanındaki)
                    $('#modalExample tbody').on('click', '.product-search-btn-modal', function(e) {
                        e.stopPropagation();
                        var stokkodu = $(this).data('stokkodu');
                        var productId = $(this).data('product-id');
                        var targetProductId = $('#productListModal').data('target-product-id');
                        
                        if (!stokkodu) {
                            alert('Stok kodu bulunamadı');
                            return;
                        }
                        
                        if (targetProductId) {
                            // Mevcut ürün satırını güncelle
                            var $targetRow = $('tr[data-id="' + targetProductId + '"]');
                            if ($targetRow.length > 0) {
                                updateProductByCode(stokkodu, targetProductId, $targetRow);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('productListModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                // Backdrop'u temizle
                                setTimeout(function() {
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                    $('body').css('overflow', '');
                                    $('body').css('padding-right', '');
                                }, 300);
                            }
                        } else {
                            // Yeni ürün satırına ekle
                            var $targetRow = $('#newProductRow');
                            if ($targetRow.length > 0) {
                                searchProductByCode(stokkodu, $targetRow);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('productListModal'));
                                if (modal) {
                                    modal.hide();
                                }
                                // Backdrop'u temizle
                                setTimeout(function() {
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                    $('body').css('overflow', '');
                                    $('body').css('padding-right', '');
                                }, 300);
                            } else {
                                alert('Yeni ürün satırı bulunamadı');
                            }
                        }
                    });
                    
                    // Modal'dan satıra tıklandığında da ürün seçilsin
                    $('#modalExample tbody').on('click', 'tr', function(e) {
                        // Eğer butona tıklanmadıysa
                        if (!$(e.target).closest('button').length) {
                            var $selectBtn = $(this).find('.select-btn');
                            if ($selectBtn.length) {
                                $selectBtn.trigger('click');
                            }
                        }
                    });
                } else {
                    modalTable.ajax.reload();
                }
            });
            
            // Ürün listesi modal'ını açma fonksiyonu
            function openProductListModal(productId) {
                // Hangi satırın güncelleneceğini modal'a kaydet
                $('#productListModal').data('target-product-id', productId || null);
                
                // Önceki backdrop'ları temizle
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('overflow', '');
                $('body').css('padding-right', '');
                
                // Modal'ı aç
                var modalElement = document.getElementById('productListModal');
                var modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true
                });
                modal.show();
                
                // Modal kapandığında backdrop'u temizle
                $(modalElement).off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    // Backdrop'u temizle
                    $('.modal-backdrop').remove();
                    // Body'den modal-open class'ını kaldır
                    $('body').removeClass('modal-open');
                    $('body').css('overflow', '');
                    $('body').css('padding-right', '');
                });
            }

            // DataTable: ürün listesini manuel başlat (artık kullanılmıyor ama silmeyelim)
            table = $('#example').DataTable({
                deferRender: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "uruncekdatatable.php",
                    data: function (d) {
                        d.onlyPriced = $('#onlyPriced').is(':checked') ? 1 : 0;
                        // Radio button'dan seçili değeri al
                        var pazarTipi = $('input[name="pazar_tipi"]:checked').val() || 'yurtici';
                        d.pazar_tipi = pazarTipi;
                    }
                },
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json"
                },
                pageLength: 200,
                select: { style: 'multi' },
                scrollX: false,
                autoWidth: true,
                columnDefs: [
                    { targets: 0, width: "80px" }, // İşlem sütunu
                    { targets: 1, width: "120px" }, // Kod sütunu
                    { targets: 2, width: "auto" }, // Ürün adı sütunu - otomatik genişlik
                    { targets: 3, width: "80px" }, // Birimi sütunu
                    { targets: 4, width: "120px" }, // Liste Fiyatı sütunu
                    { targets: 5, width: "80px" }, // Döviz sütunu
                    { targets: 6, width: "80px" }, // Stok sütunu
                    { targets: 7, width: "100px" }, // Marka sütunu
                    { targets: 8, visible: false } // export_fiyat sütunu
                ],
                createdRow: function (row, data) {
                    var price = parseFloat(data[8]) || 0;
                    if (price <= 0) {
                        $(row).addClass('no-price');
                    }
                }
            });
            updateCartInfo();

            $('#onlyPriced').on('change', function () {
                table.ajax.reload();
            });

            // Global arama kutusu
            $('#globalSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            var searchIndex = -1;
            $('#stokSearch').on('input', function() {
                table.column(1).search(this.value).draw();
                searchIndex = -1;
            });

            $('#stokSearch').on('keydown', function(e) {
                var visibleIndexes = table.rows({search:'applied', page:'current'}).indexes().toArray();
                var visibleCount = visibleIndexes.length;
                if (e.key === 'ArrowDown') {
                    if (visibleCount === 0) return;
                    searchIndex = (searchIndex + 1) % visibleCount;
                    table.rows().deselect();
                    var idx = visibleIndexes[searchIndex];
                    table.row(idx).select();
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    if (visibleCount === 0) return;
                    searchIndex = searchIndex <= 0 ? visibleCount - 1 : searchIndex - 1;
                    table.rows().deselect();
                    var idx = visibleIndexes[searchIndex];
                    table.row(idx).select();
                    e.preventDefault();
                } else if (e.key === 'Enter') {
                    if (visibleCount === 0) return;
                    var idx = searchIndex >= 0 ? visibleIndexes[searchIndex] : visibleIndexes[0];
                    var row = table.row(idx);
                    var price = parseFloat(row.data()[8]) || 0;
                    if (price > 0) {
                        $(row.node()).find('.select-btn').trigger('click');
                    } else {
                        $('#priceInfoModal').modal('show');
                    }
                    $(this).val('');
                    table.column(1).search('').draw();
                    searchIndex = -1;
                    e.preventDefault();
                }
            });

            function showToast(msg) {
                $('#toastNotification .toast-body').text(msg);
                new bootstrap.Toast($('#toastNotification')).show();
            }

            function updateCartInfo() {
                var count = $('#cartTableBody tr').length;
                var text = count > 0 ?
                    'Şu an Teklif Listenizde <strong class="text-danger">' + count + '</strong> ürün bulunuyor.' :
                    'Teklif için henüz hiçbir ürün eklememişsiniz!';
                $('#cartInfo').html(text);
                
                // --- FALLBACK CHECK ---
                // Her ürün değişiminde Ana Bayi/Ertek iskonto kontrolünü tetikle
                if (typeof updateAllSpecialPriceDiscounts === 'function') {
                    updateAllSpecialPriceDiscounts();
                }
                
                $('#submitCart').prop('disabled', count === 0);
                
                // Toplam hesapla ve göster
                updateTotalAmount();
            }
            
            // Toplam tutarı hesapla ve göster
            function updateTotalAmount() {
                var total = 0;
                var totalTL = 0;
                var totalWithVAT = 0;
                var totalTLWithVAT = 0;
                var hasProducts = false;
                
                // Tüm ürün satırlarını kontrol et (boş satırlar hariç)
                $('#cartTableBody tr').each(function() {
                    var $row = $(this);
                    var dataId = $row.attr('data-id');
                    
                    // Sadece ürün satırlarını hesapla (data-id sayısal olmalı)
                    if (dataId && dataId !== 'new' && !isNaN(parseInt(dataId))) {
                        hasProducts = true;
                        var totalPriceText = $row.find('.total-price-display').text();
                        if (totalPriceText) {
                            // Virgülü noktaya çevir ve sayıya dönüştür
                            var rowTotal = parseFloat(totalPriceText.replace('.', '').replace(',', '.')) || 0;
                            total += rowTotal;
                            
                            // KDV Oranını al (varsayılan 20)
                            var vatRateObj = $row.find('.kdv-display');
                            var vatRateText = vatRateObj.length ? vatRateObj.text() : '20';
                            var vatRate = parseFloat(vatRateText) || 20;
                            
                            var rowTotalWithVAT = rowTotal * (1 + vatRate / 100);
                            totalWithVAT += rowTotalWithVAT;
                            
                            // Döviz bilgisini al (önce data-currency attribute'undan, yoksa liste fiyatı sütünündan)
                            var currency = $row.attr('data-currency') || 'TL'; // data-currency attribute'u varsa kullan
                            
                            // Eğer data-currency yoksa, liste fiyatı sütunundan bulmaya çalış
                            if (!$row.attr('data-currency')) {
                                var $listPriceCell = $row.find('td').eq(8); // Liste Fiyatı sütunu (index 8)
                                var listPriceText = $listPriceCell.text() || $row.find('input[name^="fiyatsi"]').val() || '';
                                
                                if (listPriceText.includes('€') || listPriceText.includes('EUR')) {
                                    currency = 'EUR';
                                } else if (listPriceText.includes('$') || listPriceText.includes('USD')) {
                                    currency = 'USD';
                                } else {
                                    currency = 'TL';
                                }
                            }
                            
                            // TL karşılığını hesapla (veritabanından alınan güncel kurlar)
                            if (currency === 'TL') {
                                totalTL += rowTotal;
                                totalTLWithVAT += rowTotalWithVAT;
                            } else if (currency === 'EUR') {
                                // EUR -> TL (güncel kur)
                                totalTL += rowTotal * <?php echo $eurKuru; ?>;
                                totalTLWithVAT += rowTotalWithVAT * <?php echo $eurKuru; ?>;
                            } else if (currency === 'USD') {
                                // USD -> TL (güncel kur)
                                totalTL += rowTotal * <?php echo $usdKuru; ?>;
                                totalTLWithVAT += rowTotalWithVAT * <?php echo $usdKuru; ?>;
                            }
                        }
                    }
                });
                
                // Toplam satırını göster/gizle
                if (hasProducts) {
                    $('#cartTableFooter').show();
                    
                    // Genel İskonto Hesaplaması
                    var subTotal = total;
                    var subTotalTL = totalTL;
                    
                    // İskonto oranını al
                    var discountRateStr = $('#genelIskonto').val() || '0';
                    var discountRate = parseFloat(discountRateStr.replace(',', '.')) || 0;
                    
                    // İskonto uygula
                    var discountAmount = subTotal * (discountRate / 100);
                    var discountAmountTL = subTotalTL * (discountRate / 100);
                    
                    var netTotal = subTotal - discountAmount;
                    var netTotalTL = subTotalTL - discountAmountTL;
                    
                    // KDV'li toplamlara da aynı oranda indirim uygula (Basitleştirilmiş yaklaşım)
                    var netTotalWithVAT = totalWithVAT * (1 - discountRate / 100);
                    var netTotalTLWithVAT = totalTLWithVAT * (1 - discountRate / 100);
                    
                    // Değerleri Yazdır
                    // Ara Toplam
                    $('#subTotalAmount').text(subTotal.toFixed(2).replace('.', ',') + ' €');
                    
                    // Net Toplam (Genel İskonto düşülmüş)
                    $('#totalAmount').text(netTotal.toFixed(2).replace('.', ',') + ' €');
                    $('#totalAmountTL').text(netTotalTL.toFixed(2).replace('.', ',') + ' ₺');
                    
                    $('#totalAmountWithVAT').text(netTotalWithVAT.toFixed(2).replace('.', ',') + ' €');
                    $('#totalAmountTLWithVAT').text(netTotalTLWithVAT.toFixed(2).replace('.', ',') + ' ₺');
                } else {
                    $('#cartTableFooter').hide();
                }
            }
            
            // Genel İskonto değiştiğinde yeniden hesapla
            $(document).on('input', '#genelIskonto', function() {
                var $this = $(this);
                var valStr = $this.val();
                
                // Sadece rakam ve virgül/nokta
                // valStr = valStr.replace(/[^0-9.,]/g, '');
                
                // Virgül kontrolü
                var numVal = parseFloat(valStr.replace(',', '.')) || 0;
                
                if (numVal > 100) {
                    // 100'den büyükse uyar ve 100 yap
                    $this.val('100,00');
                    alert('Genel iskonto oranı %100\'den büyük olamaz.');
                }
                
                // Yeniden hesapla
                updateTotalAmount();
            });

            function refreshSelectedProducts(rowData, id, qty = 1) {
                if (!rowData) return;
                $('#noSelected').remove();
                $('#selectedProductsContainer').show();
                $('#selectedProducts tbody').append(
                    '<tr data-id="'+id+'">' +
                    '<td>'+rowData[1]+'</td>' +
                    '<td>'+rowData[2]+'</td>' +
                    '<td>'+qty+'</td>' +
                    '<td>'+rowData[3]+'</td>' +
                    '<td>'+rowData[4]+'</td>' +
                    '<td>'+rowData[5]+'</td>' +
                    '<td><button type="button" class="btn btn-danger btn-sm remove-btn" data-id="'+id+'">Kaldır</button></td>' +
                    '</tr>'
                );
                var price = parseFloat(rowData[4]) || 0;
                var rate = campaignRates[id] || 0;
                
                // ERTEK carisi için %45 iskonto (demo)
                if (musteriKampanyaIskonto > 0 && rate === 0) {
                    rate = musteriKampanyaIskonto;
                }
                
                var readonly = discountDisabled || rate > 0;
                var unit = price * (1 - rate/100);
                var total = unit * qty;
                var readonlyAttr = readonly ? 'readonly' : '';
                $('#cartTableBody').append(
                    '<tr data-id="'+id+'">'+
                    '<td style="padding: 0;">'+
                        '<input type="text" name="product_code['+id+']" class="form-control product-code-input editable-product-code" value="'+(rowData[1]||'')+'" data-original-code="'+(rowData[1]||'')+'" data-product-id="'+id+'" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                        '<button type="button" class="btn btn-sm product-search-btn editable-product-search-btn" data-product-id="'+id+'" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara"><span style="font-size: 12px;">⋯</span></button>'+
                    '</td>'+
                    '<td style="padding: 0; position: relative;">'+
                        '<input type="text" name="product_name['+id+']" value="'+(rowData[2]||'')+'" class="form-control editable-product-name" data-product-id="'+id+'" data-original-name="'+(rowData[2]||'')+'" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                        '<button type="button" class="btn btn-sm product-search-btn-by-name editable-product-search-btn-by-name" data-product-id="'+id+'" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara"><span style="font-size: 12px;">⋯</span></button>'+
                        '<div class="product-name-autocomplete" style="display: none; position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); top: 100%; left: 0; margin-top: 2px;"></div>'+
                    '</td>'+
                    '<td style="padding: 0;"><input type="text" name="aciklama['+id+']" class="form-control description-input" value="" placeholder="Açıklama" style="text-align: left; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                    '<td style="padding: 0;"><input type="number" name="miktarisi['+id+']" value="'+qty+'" class="form-control quantity-input" min="1" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                    '<td style="text-align: right; padding: 2px 4px; font-size: 13px; line-height: 28px;">'+(function(){var d=rowData[5]||'';var icon='';if(d==='EUR')icon='€';else if(d==='USD')icon='$';else if(d==='TL')icon='₺';else icon=d;return price.toFixed(2).replace('.',',')+' '+icon;})()+'<input type="hidden" name="fiyatsi['+id+']" value="'+price.toFixed(2).replace('.',',')+'"></td>'+
                    '<td style="text-align: right; padding: 0;"><input type="text" name="iskontosi['+id+']" value="'+rate.toFixed(2).replace('.',',')+'" class="form-control discount-input" data-list-price="'+price+'" data-campaign="'+rate+'" '+readonlyAttr+' style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                    '<td style="padding: 0; position: relative;">'+
                        '<input type="text" name="final_price_unit['+id+']" class="form-control final-price-input" value="'+unit.toFixed(2).replace('.',',')+'" data-urun-id="'+id+'" data-list-price="'+price+'" data-original-discount="'+rate+'" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 20px 2px 4px; height: 28px; font-size: 13px;">'+
                        '<span class="currency-icon" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #666; pointer-events: none;">'+(function(){var d=rowData[5]||'';if(d==='EUR')return '€';else if(d==='USD')return '$';else if(d==='TL')return '₺';return d;})()+'</span>'+
                        '<input type="hidden" class="final-price-hidden" value="'+unit.toFixed(2)+'">'+
                    '</td>'+
                    '<td style="text-align: right; padding: 2px 4px;"><span class="total-price-display" style="font-size: 13px; line-height: 28px;">'+total.toFixed(2).replace('.',',')+' '+(function(){var d=rowData[5]||'';if(d==='EUR')return '€';else if(d==='USD')return '$';else if(d==='TL')return '₺';return d;})()+'</span></td>'+
                    '<td style="white-space: nowrap; text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;">'+rowData[3]+'<input type="hidden" name="olcubirimi['+id+']" value="'+rowData[3]+'"></td>'+
                    '<td style="text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;">20</td>'+
                    '<td style="text-align: center; padding: 2px;"><button type="button" class="btn btn-danger btn-sm remove-btn" data-id="'+id+'" style="padding: 0 6px; font-size: 11px; height: 24px; line-height: 22px;">Kaldır</button></td>'+
                    '</tr>'
                );
                // ERTEK carisi için kampanya kontrolünü atla, direkt %45 kullan
                if (!campaignRates[id] && musteriKampanyaIskonto === 0) {
                    $.getJSON('public/get_campaign_rate.php', {id: id}, function(r){
                        if(r.success && r.rate > 0){
                            campaignRates[id] = r.rate;
                            var $row = $('#cartTableBody tr[data-id="'+id+'"]');
                            $row.find('.discount-input').val(r.rate.toFixed(2).replace('.',',')).prop('readonly', true).attr('data-campaign', r.rate);
                            recalcRow($row);
                        }
                    });
                }
                updateCartInfo();
            }

            $(document).on('click', '.select-btn', function() {
                var price = parseFloat($(this).data('price')) || 0;
                if (price <= 0) {
                    $('#priceInfoModal').modal('show');
                    return;
                }
                var id = $(this).data('id');
                var rowNode = $(this).closest('tr');
                var rowData = table.row(rowNode).data();
                
                // Miktarı al
                var qtyInput = $(this).siblings('.quantity-input-list');
                var qty = qtyInput.length ? parseInt(qtyInput.val()) : 1;
                if (isNaN(qty) || qty < 1) qty = 1;

                $.post('public/cart_actions.php', {action: 'add', id: id, qty: qty}, function(resp){
                    if(resp.success){
                        refreshSelectedProducts(rowData, id, qty);
                        showToast('Ürün eklendi');
                        $('#stokSearch').val('');
                        table.column(1).search('').draw();
                    }
                }, 'json');
            });

            $(document).on('click', '.remove-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $button = $(this);
                var id = $button.data('id');
                var $row = $button.closest('tr'); // Tıklanan butonun bulunduğu satırı al
                
                if (!id || !$row.length) {
                    console.error('Remove button: ID veya satır bulunamadı');
                    return;
                }
                
                // Onay iste
                if (!confirm('Bu ürünü kaldırmak istediğinize emin misiniz?')) {
                    return;
                }
                
                $.post('public/cart_actions.php', {action: 'remove', id: id}, function(resp){
                    if(resp.success){
                        // Sadece tıklanan butonun bulunduğu satırı sil
                        $row.remove();
                        
                        // Eğer tabloda hiç satır kalmadıysa boş mesaj göster
                        var $tbody = $('#offerForm tbody');
                        if($tbody.length === 0 || $tbody.find('tr').length === 0){
                            $tbody = $('#offerForm').find('tbody');
                            if($tbody.length > 0 && $tbody.find('tr').length === 0){
                                // Boş satırlar ekle
                                initializeEmptyRows();
                            }
                        }
                        
                        updateCartInfo();
                        showToast('Ürün kaldırıldı');
                    } else {
                        alert(resp.message || 'Ürün kaldırılırken bir hata oluştu.');
                    }
                }, 'json').fail(function(){
                    alert('Sunucu hatası: Ürün kaldırılamadı.');
                });
            });

            $('#selectedProducts').on('click', 'tr', function(){
                $('#selectedProducts tr').removeClass('selected');
                $(this).addClass('selected');
            });

            $(document).on('keydown', function(e){
                if(e.key === 'Enter' && $(document.activeElement).closest('#example').length){
                    var ids = [];
                    var showInfo = false;
                    table.rows({selected:true}).every(function(){
                        var id = $(this.node()).find('.select-btn').data('id');
                        var price = parseFloat(this.data()[8]) || 0;
                        if(id && price > 0){
                            ids.push({id:id,data:this.data()});
                        } else if(price <= 0){
                            showInfo = true;
                        }
                    });
                    ids.forEach(function(it){
                        $.post('public/cart_actions.php', {action:'add', id:it.id}, function(resp){
                            if(resp.success){
                                refreshSelectedProducts(it.data, it.id);
                                showToast('Ürün eklendi');
                            }
                        },'json');
                    });
                    if(showInfo){
                        $('#priceInfoModal').modal('show');
                    }
                    $('#stokSearch').val('');
                    table.column(1).search('').draw();
                }
                if(e.key === 'Delete'){
                    var removeBtn = $('#selectedProducts tr.selected .remove-btn');
                    if(!removeBtn.length){
                        removeBtn = $(document.activeElement).closest('tr').find('.remove-btn');
                    }
                    if(removeBtn.length){
                        removeBtn.trigger('click');
                    }
                }
            });

            // İskonto alanı validasyonu
            var maxPct = <?= json_encode($iskonto_max) ?>;
            var discountDisabled = <?= json_encode($discountDisabled) ?>;
            var campaignRates = <?= json_encode($campaignRatesMap) ?>;
            var musteriKampanyaIskonto = 0; // ERTEK/Ana Bayi fallback oranı

            function updateMusteriKampanyaIskonto() {
                const $musteriSelect = $('#musteri');
                const selectedText = $musteriSelect.find('option:selected').text() || '';
                const isMainDealer = $musteriSelect.find(':selected').data('ana-bayi') == 1;
                const isErtek = selectedText.indexOf('ERTEK') > -1 || selectedText.indexOf('Ana Bayi') > -1 || isMainDealer;
                
                if (isErtek) {
                    const isCash = $('#pesinOdeme').is(':checked');
                    musteriKampanyaIskonto = isCash ? 50.00 : 45.00;
                } else {
                    musteriKampanyaIskonto = 0;
                }
                console.log('Müşteri/Peşin durumuna göre fallback iskonto:', musteriKampanyaIskonto);
            }
            $(function() {

                // satır hesabını yapan fonksiyon
                // Cascading discount helper
                function calculateEffectiveDiscount(str) {
                    if (!str) return 0;
                    str = String(str).replace(',', '.').replace(/[+ ]/g, '-');
                    var parts = str.split('-');
                    var remaining = 1.0;
                    var hasValid = false;
                    for(var i=0; i<parts.length; i++) {
                        var val = parseFloat(parts[i]);
                        if(!isNaN(val) && val > 0) {
                            remaining *= (1 - val/100);
                            hasValid = true;
                        }
                    }
                    if(!hasValid) return 0;
                    return (1 - remaining) * 100;
                }

                // satır hesabını yapan fonksiyon
                function recalcRow($row) {
                    // Güvenli değer alma - undefined kontrolü
                    var qtyVal = $row.find('.quantity-input').val();
                    var listPriceVal = $row.find('input[name^="fiyatsi"]').val();
                    var discPctVal = $row.find('.discount-input').val();
                    
                    let qty = parseFloat(qtyVal) || 0,
                        listPrice = (listPriceVal && typeof listPriceVal === 'string') ? parseFloat(listPriceVal.replace(',','.')) : (parseFloat(listPriceVal) || 0),
                        // Use helper for cascading discount
                        discPct = calculateEffectiveDiscount(discPctVal),
                        pid = $row.data('id');

                    if (campaignRates[pid]) {
                        discPct = campaignRates[pid];
                        $row.find('.discount-input').val(discPct.toFixed(2).replace('.',','));
                    } else if (musteriKampanyaIskonto > 0 && discPct === 0) {
                        // ERTEK carisi için %45 iskonto (demo)
                        discPct = musteriKampanyaIskonto;
                        $row.find('.discount-input').val(discPct.toFixed(2).replace('.',','));
                    } else {
                        discPct = Math.min(Math.max(discPct, 0), maxPct);
                    }

                    let unitPrice = listPrice * (1 - discPct / 100),
                        total = unitPrice * qty;

                    // değerleri yaz
                    $row.find('.final-price-hidden').val(unitPrice.toFixed(2));
                    $row.find('.final-price-input').val(unitPrice.toFixed(2).replace('.',','));
                    
                    var currencyIcon = $row.find('.currency-icon').text().trim();
                    $row.find('.total-price-display').text(total.toFixed(2).replace('.',',') + (currencyIcon ? ' ' + currencyIcon : ''));
                    
                    // Toplamı güncelle
                    updateTotalAmount();
                }

                // focus: önceki değeri sakla, tümünü seç
                $(document).on('focus', '.discount-input, .quantity-input', function() {
                    const $this = $(this);
                    $this.data('prev-value', $this.val());
                    $this.select();
                });

                // discount-input blur: >maxPct ise uyar ve eski değere dön
                $(document).on('blur', '.discount-input', function() {
                    const $this = $(this),
                        raw = $this.val(),
                        prev = $this.data('prev-value');
                        
                    // Calculate effective rate
                    let effective = calculateEffectiveDiscount(raw);

                    if (effective > maxPct) {
                        alert(`Maksimum iskonto %${maxPct} aşamazsınız.`);
                        $this.val(prev); // Revert to previous valid string
                        recalcRow($this.closest('tr'));
                        return;
                    }

                    // If it's a simple number (no + or - or multiple parts), format it nicely
                    // If it's a formula, leave it as is (or minimal cleanup)
                    if (!raw.includes('+') && !raw.includes('-') && !raw.includes(' ')) {
                         let num = parseFloat(raw.replace(',','.'));
                         if (!isNaN(num)) {
                             $this.val(num.toFixed(2).replace('.',','));
                         }
                    } 
                    // else: leave the formula string as user typed (e.g. 50+10)

                    recalcRow($this.closest('tr'));
                });

                // miktar blur (aynı eski mantık)
                $(document).on('blur', '.quantity-input', function() {
                    const $this = $(this),
                        raw = $this.val(),
                        prev = $this.data('prev-value'),
                        num = parseInt(raw, 10);

                    let final;
                    if (raw === '' || isNaN(num) || num < 1) {
                        final = parseInt(prev, 10) || 1;
                    } else {
                        final = num;
                    }
                    $this.val(final);
                    recalcRow($this.closest('tr'));
                });

                // input anında sadece hesapla (input içeriğini değiştirme)
                $(document).on('input', '.discount-input, .quantity-input', function() {
                    recalcRow($(this).closest('tr'));
                });

                // İskontolu birim fiyat input'unu editlenebilir yap ve iskonto yüzdesini otomatik hesapla
                $(document).on('focus', '.final-price-input', function() {
                    const $this = $(this);
                    $this.data('prev-value', $this.val());
                    $this.select();
                });

                $(document).on('blur', '.final-price-input', function() {
                    const $this = $(this),
                        $row = $this.closest('tr'),
                        raw = $this.val().replace(',','.'),
                        prev = $this.data('prev-value'),
                        newPrice = parseFloat(raw),
                        listPrice = parseFloat($this.data('list-price')) || 0,
                        pid = $row.data('id');

                    let finalPrice;
                    if (raw === '' || isNaN(newPrice) || newPrice < 0) {
                        finalPrice = parseFloat(prev.replace(',','.')) || 0;
                    } else {
                        finalPrice = newPrice;
                    }

                    // Liste fiyatından daha yüksek olamaz
                    /*
                    if (finalPrice > listPrice) {
                        alert('İskontolu birim fiyat liste fiyatından yüksek olamaz.');
                        finalPrice = parseFloat(prev.replace(',','.')) || listPrice;
                    }
                    */

                    // İskonto yüzdesini hesapla
                    let discPct = listPrice > 0 ? (1 - finalPrice / listPrice) * 100 : 0;
                    
                    // Maksimum iskonto kontrolü
                    if (discPct > maxPct) {
                        alert(`Maksimum iskonto %${maxPct} aşamazsınız.`);
                        // Maksimum iskonto ile geri hesapla
                        finalPrice = listPrice * (1 - maxPct / 100);
                        discPct = maxPct;
                    }

                    // Negatif iskonto olamaz
                    if (discPct < 0) {
                        discPct = 0;
                        finalPrice = listPrice;
                    }

                    // Kampanya indirimi varsa readonly yap
                    if (campaignRates[pid] && campaignRates[pid] > 0) {
                        discPct = campaignRates[pid];
                        finalPrice = listPrice * (1 - discPct / 100);
                    }

                    // Değerleri güncelle
                    $this.val(finalPrice.toFixed(2).replace('.',','));
                    $row.find('.final-price-hidden').val(finalPrice.toFixed(2));
                    $row.find('.discount-input').val(discPct.toFixed(2).replace('.',','));
                    
                    // Toplamı güncelle
                    recalcRow($row);
                });

                $(document).on('input', '.final-price-input', function() {
                    recalcRow($(this).closest('tr'));
                });

                // modal ile manuel fiyat düzenleme (aynı eski kod)
                let $currentRow, listPrice;
                $(document).on('click', '.price-edit-btn', function() {
                    var id = $(this).data('urun-id');
                    if (discountDisabled || campaignRates[id]) {
                        return;
                    }
                    $currentRow = $(this).closest('tr');
                    listPrice = parseFloat($(this).data('list-price')) || 0;
                    $('#priceModalInput')
                        .val($currentRow.find('.final-price-hidden').val())
                        .removeClass('is-invalid');
                    $('#priceModalError').text('');
                    new bootstrap.Modal($('#priceModal')).show();
                });

                $('#priceModalSave').on('click', function() {
                    let newVal = parseFloat($('#priceModalInput').val());
                    if (isNaN(newVal) || newVal < 0) {
                        $('#priceModalInput').addClass('is-invalid');
                        $('#priceModalError').text('Lütfen geçerli bir sayı girin.');
                        return;
                    }
                    // modal'dan gelen değere göre iskonto yüzdesini hesapla ve clamp et
                    let discPct = listPrice > 0 ? (1 - newVal / listPrice) * 100 : 0;
                    if (discPct > maxPct) {
                        $('#priceModalInput').addClass('is-invalid');
                        $('#priceModalError').text(`Maksimum iskonto %${maxPct}`);
                        return;
                    }
                    discPct = Math.max(discPct, 0);

                    $('#priceModal').modal('hide');
                    $currentRow.find('.final-price-input').val(newVal.toFixed(2).replace('.',','));
                    $currentRow.find('.final-price-hidden').val(newVal.toFixed(2));
                    $currentRow.find('.discount-input').val(discPct.toFixed(2).replace('.',','));
                    recalcRow($currentRow);
                });
            });


            // AJAX spinner
            $(document).ajaxStart(function() {
                $('#spinnerOverlay').show();
            }).ajaxStop(function() {
                $('#spinnerOverlay').hide();
            });

            // Müşteri seçimi değiştiğinde açık hesap bakiyesini getirme ve unvanı yaz
            function updateCariUnvan() {
                var $musteri = $('#musteri');
                var sirket_id = $musteri.val();
                var select2Data = $musteri.select2('data');
                var selectedText = '';
                
                // Select2 data'dan text'i al
                if (select2Data && select2Data.length > 0) {
                    selectedText = select2Data[0].text || '';
                }
                
                // Eğer select2Data'dan text alınamadıysa, option'dan almayı dene
                if (!selectedText) {
                    var selectedOption = $musteri.find('option:selected');
                    if (selectedOption.length > 0) {
                        selectedText = selectedOption.text() || '';
                    }
                }
                
                if (sirket_id && sirket_id !== '786' && sirket_id !== null && sirket_id !== '') {
                    if (selectedText) {
                        var parts = selectedText.split(' - ');
                        if (parts.length > 1) {
                            var unvan = parts.slice(1).join(' - ');
                            $('#sirketbilgi').val(unvan);
                        } else {
                            $('#sirketbilgi').val(selectedText);
                        }
                    }
                }
            }
            
            $('#musteri').on('change', function() {
                var sirket_id = $(this).val();
                var $musteri = $(this);
                
                // Uyarıyı gizle
                $('#limit-uyari').hide();
                
                // Kampanya butonunu gizle (varsayılan)
                $('#kampanyaBtn').hide();
                
                if (sirket_id === '786' || sirket_id === null || sirket_id === '') {
                    $('.manual-fields').show();
                    $('#sirketbilgi').prop('readonly', false);
                    $('#acikhesap').val('');
                    $('#payplan').val('');
                    $('#sirketbilgi').val('');
                    return;
                } else {
                    $('.manual-fields').hide();
                    $('#sirketbilgi').prop('readonly', true).show(); // Cari ünvanı alanını göster
                    
                    // Select2 data'dan unvanı al ve yaz
                    var select2Data = $musteri.select2('data');
                    if (select2Data && select2Data.length > 0 && select2Data[0].text) {
                        var parts = select2Data[0].text.split(' - ');
                        if (parts.length > 1) {
                            var unvan = parts.slice(1).join(' - ');
                            $('#sirketbilgi').val(unvan);
                        }
                        
                        // Cari kodunu kontrol et (120.01.E04)
                        var cariKodu = parts[0].trim();
                        if (cariKodu === '120.01.E04') {
                            try {
                                // Kampanya butonunu göster
                                // Kampanya butonunu göster
                                $('#kampanyaBtn').show();
                                
                                // ERTEK carisi için %45 iskonto - İPTAL EDİLDİ
                                // Kullanıcı isteği üzerine otomatik %45 default iskonto kaldırıldı.
                                // Sadece özel fiyat listesindeki ürünlere özel fiyat uygulanacak.
                                musteriKampanyaIskonto = 0; 
                                
                                // console.log('Müşteri kampanyası aktif: %45 iskonto (yeni ürünlere uygulanacak)');
                                // İskonto sadece yeni ürün eklendiğinde uygulanır
                            } catch (err) {
                                console.error("Ertek Kampanya Hatası:", err);
                            }
                        } else {
                            // Diğer müşteriler için iskonto sıfırla
                            musteriKampanyaIskonto = 0;
                        }
                    }
                }
                var url = 'get_acikhesap.php?ts=' + new Date().getTime();
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: {
                        sirket_id: sirket_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var acikhesap = response.acikhesap;
                            var plan = '';
                            if (response.payplan_code || response.payplan_def) {
                                plan = (response.payplan_code || '') + ' - ' + (response.payplan_def || '');
                            }
                             if ($.isNumeric(acikhesap)) {
                                 var formatted = Number(acikhesap).toLocaleString('tr-TR', {
                                     minimumFractionDigits: 2,
                                     maximumFractionDigits: 2
                                 }) + ' TL';
                                 $('#acikhesap').val(formatted);
                                 
                                 // Limit kontrolü - Layout bozmadan renk değişimi
                                 if (response.limit_asildi === true) {
                                     // $('#limit-uyari').show();
                                     $('#acikhesap').css({'color': 'red', 'font-weight': 'bold'}).attr('title', 'Limit Aşıldı! Kredi limitiniz dolmuştur.');
                                 } else {
                                     $('#limit-uyari').hide();
                                     $('#acikhesap').css('color', '').removeAttr('title');
                                 }
                             }

                             // Risk Limiti Gösterimi
                             if (response.risk_limit !== undefined) {
                                 var riskLimit = response.risk_limit;
                                 var formattedRisk = Number(riskLimit).toLocaleString('tr-TR', {
                                     minimumFractionDigits: 2,
                                     maximumFractionDigits: 2
                                 }) + ' TL';
                                 $('#risk_limit_text').text(formattedRisk);

                                 // Eğer bakiye risk limitini aşmışsa görsel uyarı ver
                                 if ($.isNumeric(acikhesap) && Number(acikhesap) > Number(riskLimit) && Number(riskLimit) > 0) {
                                     $('#acikhesap').css({'color': 'red', 'font-weight': 'bold'});
                                     $('#risk-limit-info small').removeClass('text-muted').addClass('text-danger fw-bold');
                                 } else {
                                     // $('#acikhesap').css({'color': '', 'font-weight': ''}); // Balance may have its own color logic above
                                     $('#risk-limit-info small').removeClass('text-danger fw-bold').addClass('text-muted');
                                 }
                             } else {
                                 $('#risk_limit_text').text('0,00 TL');
                                 $('#risk-limit-info small').removeClass('text-danger fw-bold').addClass('text-muted');
                             }
                            
                            if (response.payplan_code) {
                                $('#payplan').val(response.payplan_code).trigger('change');
                            } else {
                                $('#payplan').val('').trigger('change');
                            }
                        } else {
                            $('#acikhesap').val('');
                            $('#payplan').val('').trigger('change');
                             $('#limit-uyari').hide();
                            console.error("Bakiye hatası:", response.message);
                            // alert(response.message); // Hata mesajını sessize al
                        }
                    },
                    error: function() {
                        $('#acikhesap').val('');
                        $('#payplan').val('').trigger('change');
                        $('#limit-uyari').hide();
                        console.error('Açık hesap bakiyesi alınırken bir hata oluştu.');
                    }
                });
            });
            // $('#musteri').trigger('change'); // Sayfa yüklenince gereksiz tetiklemeyi önle

            // Bootstrap form validasyonu: Sadece required alanlar kontrol edilecek
            // Form submit edildiğinde referrer URL'i, ekstra_bilgi ve seçili müşteriyi session'a kaydet
            $('#offerForm').on('submit', function(e) {
                // İskontoları topla ve kaydet
                var iskontolar = {};
                $('input[name^="iskontosi"]').each(function() {
                    var $input = $(this);
                    var name = $input.attr('name');
                    var match = name.match(/iskontosi\[(\d+)\]/);
                    if (match && match[1]) {
                        var productId = match[1];
                        var discountValue = $input.val();
                        iskontolar[productId] = discountValue;
                    }
                });
                
                // İskontoları session'a kaydet (asenkron, form submit'i beklemez)
                if (Object.keys(iskontolar).length > 0) {
                    console.log('İskontolar kaydediliyor:', iskontolar);
                    // Synchronous AJAX kullan - form submit edilmeden önce kaydedilsin
                    $.ajax({
                        url: 'save_form_state.php',
                        method: 'POST',
                        async: false, // Senkron - form submit edilmeden önce tamamlansın
                        data: {
                            iskontolar: JSON.stringify(iskontolar)
                        },
                        success: function(resp) {
                            console.log('İskontolar kaydedildi:', resp);
                        }
                    });
                }
                
                // Tarih ve zamanı birleştir
                var tarih = $('#gecerliliktarihi').val();
                var zaman = $('#gecerliliktarihi_time').val() || '17:00:00';
                if (tarih && zaman) {
                    var datetimeValue = tarih + 'T' + zaman;
                    // Hidden input oluştur veya güncelle
                    if ($('#teklifgecerlilik_combined').length) {
                        $('#teklifgecerlilik_combined').val(datetimeValue);
                    } else {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'teklifgecerlilik_combined',
                            name: 'teklifgecerlilik',
                            value: datetimeValue
                        }).appendTo('#offerForm');
                    }
                }
                
                // Ekstra bilgiyi session'a kaydet (AJAX ile)
                var ekstraBilgi = '';
                if (typeof ClassicEditor !== 'undefined' && ClassicEditor.instances && ClassicEditor.instances.ekstraBilgi) {
                    ekstraBilgi = ClassicEditor.instances.ekstraBilgi.getData();
                } else if ($('#ekstraBilgi').length) {
                    ekstraBilgi = $('#ekstraBilgi').val();
                }
                
                // Seçili müşteri ID'sini al
                var musteriId = $('#musteri').val() || '';
                
                // Sözleşme metnini al (ClassicEditor varsa getData ile, yoksa val ile)
                var sozlesmeMetinEdited = '';
                if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                    sozlesmeMetinEdited = sozlesmeEditor.getData() || '';
                } else {
                    sozlesmeMetinEdited = $('#sozlesmeMetinEdited').val() || '';
                }
                var sozlesmeId = $('#sozlesme_id').val() || '';
                
                // Referrer URL'i, ekstra_bilgi, müşteri ID'si ve sözleşme metnini session'a kaydet
                $.ajax({
                    url: 'save_form_state.php',
                    method: 'POST',
                    async: false, // Senkron yap ki form submit edilmeden önce kaydedilsin
                    data: {
                        referrer_url: window.location.href,
                        ekstra_bilgi: ekstraBilgi,
                        musteri_id: musteriId,
                        sozlesme_metin: sozlesmeMetinEdited,
                        sozlesme_id: sozlesmeId
                    }
                });
            });
            
            // Sözleşme seçildiğinde metnini yükle - her zaman güncelle
            $('#sozlesme_id').on('change', function() {
                var sozlesmeId = $(this).val();
                if (sozlesmeId && sozlesmeId !== '') {
                    $.ajax({
                        url: 'get_sozlesme_metin.php',
                        method: 'GET',
                        data: { sozlesme_id: sozlesmeId },
                        success: function(response) {
                            if (response.success && response.metin) {
                                // ClassicEditor varsa setData ile güncelle, yoksa normal val ile
                                if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                                    sozlesmeEditor.setData(response.metin);
                                } else {
                                    $('#sozlesmeMetinEdited').val(response.metin);
                                }
                            } else {
                                if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                                    sozlesmeEditor.setData('');
                                } else {
                                    $('#sozlesmeMetinEdited').val('');
                                }
                            }
                        },
                        error: function() {
                            console.error('Sözleşme metni yüklenemedi');
                            if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                                sozlesmeEditor.setData('');
                            } else {
                                $('#sozlesmeMetinEdited').val('');
                            }
                        }
                    });
                } else {
                    if (typeof sozlesmeEditor !== 'undefined' && sozlesmeEditor) {
                        sozlesmeEditor.setData('');
                    } else {
                        $('#sozlesmeMetinEdited').val('');
                    }
                }
            });
            
            (function() {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();

            // Boş satır - Stok kodu ile ürün ekleme (Event delegation kullan - dinamik elementler için)
            $(document).on('keydown', '#newProductCode, .new-product-code', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    var stokKodu = $(this).val().trim();
                    if (stokKodu === '') {
                        return;
                    }
                    // Bu input'un bulunduğu satırı kullan
                    var $currentRow = $(this).closest('tr');
                    searchProductByCode(stokKodu, $currentRow);
                }
            });

            // Mevcut ürün satırlarındaki stok kodlarını düzenlenebilir yap ve Tab ile güncelle
            $(document).on('keydown', '.editable-product-code', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    var stokKodu = $(this).val().trim();
                    var productId = $(this).data('product-id');
                    var $currentRow = $(this).closest('tr');
                    
                    console.log('Stok kodu değişikliği - Yeni kod:', stokKodu, 'Ürün ID:', productId);
                    
                    if (stokKodu === '') {
                        console.log('Stok kodu boş, işlem iptal edildi');
                        return;
                    }
                    
                    // Eğer kod değişmediyse devam et
                    var originalCode = $(this).data('original-code') || $(this).attr('data-original-code') || '';
                    console.log('Orijinal kod:', originalCode, 'Yeni kod:', stokKodu);
                    
                    if (stokKodu === originalCode) {
                        console.log('Kod değişmedi, işlem iptal edildi');
                        return;
                    }
                    
                    // Ürünü güncelle
                    console.log('Ürün güncelleniyor...');
                    updateProductByCode(stokKodu, productId, $currentRow);
                }
            });

            // 3 nokta butonu - Ürün listesi modal'ı aç (Event Delegation ile)
            $(document).on('click', '.product-search-btn', function() {
                var $input = $(this).siblings('input');
                if ($input.attr('id') === 'newProductCode') {
                    // Yeni ürün satırı için
                    $('#newProductCode').focus();
                } else {
                    // Mevcut ürün satırı için - modal aç
                    var productId = $(this).data('product-id');
                    openProductListModal(productId);
                }
            });
            
            // Ürün adı yanındaki 3 nokta butonu - Ürün listesi modal'ı aç
            $(document).on('click', '.product-search-btn-by-name', function(e) {
                e.stopPropagation();
                var productId = $(this).data('product-id');
                if (productId) {
                    // Mevcut ürün satırı için - modal aç
                    openProductListModal(productId);
                } else {
                    // Yeni ürün satırı için - modal aç
                    openProductListModal(null);
                }
            });
            
            // Ürün adı autocomplete - Dropdown'u body'ye append et
            var autocompleteTimeout;
            var $autocompleteContainer = null;
            
            // Autocomplete container'ı oluştur (document ready içinde)
            $(function() {
                if ($('#product-autocomplete-global').length === 0) {
                    $autocompleteContainer = $('<div id="product-autocomplete-global" class="product-autocomplete-container"></div>');
                    $('body').append($autocompleteContainer);
                    console.log('Autocomplete container oluşturuldu');
                } else {
                    $autocompleteContainer = $('#product-autocomplete-global');
                }
            });
            
            $(document).on('input', '.editable-product-name', function(e) {
                e.stopPropagation();
                var $input = $(this);
                var $row = $input.closest('tr');
                var searchTerm = $input.val().trim();
                
                // Container'ı kontrol et ve oluştur
                if (!$autocompleteContainer || $autocompleteContainer.length === 0) {
                    if ($('#product-autocomplete-global').length === 0) {
                        $autocompleteContainer = $('<div id="product-autocomplete-global" class="product-autocomplete-container"></div>');
                        $('body').append($autocompleteContainer);
                    } else {
                        $autocompleteContainer = $('#product-autocomplete-global');
                    }
                }
                
                console.log('Ürün adı yazılıyor:', searchTerm, 'Container:', $autocompleteContainer.length);
                
                // Eğer 2 karakterden azsa autocomplete gösterme
                if (searchTerm.length < 2) {
                    if ($autocompleteContainer && $autocompleteContainer.length) {
                        $autocompleteContainer.hide().empty();
                    }
                    return;
                }
                
                // Timeout ile debounce
                clearTimeout(autocompleteTimeout);
                autocompleteTimeout = setTimeout(function() {
                    console.log('AJAX isteği gönderiliyor:', searchTerm);
                    $.ajax({
                        url: 'product-search-by-name.php',
                        method: 'GET',
                        data: { name: searchTerm, limit: 1000 },
                        dataType: 'json',
                        success: function(response) {
                            console.log('AJAX yanıtı:', response);
                            if (!response.success || !response.products || response.products.length === 0) {
                                console.log('Ürün bulunamadı veya hata var');
                                if ($autocompleteContainer && $autocompleteContainer.length) {
                                    $autocompleteContainer.hide().empty();
                                }
                                return;
                            }
                            
                            // Container'ı tekrar kontrol et
                            if (!$autocompleteContainer || $autocompleteContainer.length === 0) {
                                if ($('#product-autocomplete-global').length === 0) {
                                    $autocompleteContainer = $('<div id="product-autocomplete-global" class="product-autocomplete-container"></div>');
                                    $('body').append($autocompleteContainer);
                                } else {
                                    $autocompleteContainer = $('#product-autocomplete-global');
                                }
                            }
                            
                            var html = '<ul style="list-style: none; padding: 0; margin: 0;">';
                            response.products.forEach(function(product) {
                                html += '<li data-product-id="' + product.id + '" ' +
                                       'data-product-code="' + product.code + '" ' +
                                       'style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; background: white;">' +
                                       '<strong style="color: #333;">' + product.code + '</strong> - ' + product.name + 
                                       ' <span style="color: #666; font-size: 0.9em;">(' + product.list_price.toFixed(2).replace('.',',') + ' ' + product.currency_icon + ')</span>' +
                                       '</li>';
                            });
                            html += '</ul>';
                            $autocompleteContainer.html(html);
                            console.log('HTML eklendi, uzunluk:', html.length, 'Ürün sayısı:', response.products.length);
                            
                            // Input'un pozisyonunu al ve dropdown'u konumlandır
                            // getBoundingClientRect kullan (scroll pozisyonunu hesaba katar)
                            var inputRect = $input[0].getBoundingClientRect();
                            var inputWidth = $input.outerWidth();
                            var inputHeight = $input.outerHeight();
                            
                            console.log('Input pozisyonu (getBoundingClientRect):', {
                                top: inputRect.top,
                                left: inputRect.left,
                                width: inputWidth,
                                height: inputHeight,
                                scrollY: window.scrollY || window.pageYOffset
                            });
                            
                            // Pozisyon hesapla (getBoundingClientRect zaten scroll'u hesaba katar)
                            var topPos = inputRect.top + inputHeight + 2;
                            var leftPos = inputRect.left;
                            
                            // Inline style ile set et (kesin çözüm için - !important kullan)
                            var inlineStyle = 'position: fixed !important; ' +
                                'z-index: 99999 !important; ' +
                                'background: white !important; ' +
                                'border: 1px solid #ccc !important; ' +
                                'box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important; ' +
                                'display: block !important; ' +
                                'visibility: visible !important; ' +
                                'opacity: 1 !important; ' +
                                'top: ' + topPos + 'px !important; ' +
                                'left: ' + leftPos + 'px !important; ' +
                                'width: ' + inputWidth + 'px !important; ' +
                                'max-height: 400px !important; ' +
                                'overflow-y: auto !important;';
                            
                            // Önce inline style set et
                            $autocompleteContainer.attr('style', inlineStyle);
                            
                            // Sonra class ekle
                            $autocompleteContainer.addClass('show');
                            
                            // jQuery CSS ile de set et (yedek)
                            $autocompleteContainer.css({
                                'position': 'fixed',
                                'z-index': 99999,
                                'top': topPos + 'px',
                                'left': leftPos + 'px',
                                'width': inputWidth + 'px',
                                'background': 'white',
                                'border': '1px solid #ccc',
                                'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                                'visibility': 'visible',
                                'opacity': '1',
                                'max-height': '400px',
                                'overflow-y': 'auto',
                                'display': 'block'
                            });
                            
                            // Show metodunu da çağır
                            $autocompleteContainer.show();
                            
                            console.log('Container gösterildi, style:', $autocompleteContainer.attr('style'));
                            console.log('Container display:', $autocompleteContainer.css('display'));
                            console.log('Container isVisible:', $autocompleteContainer.is(':visible'));
                            
                            console.log('Autocomplete gösteriliyor, pozisyon:', {
                                top: topPos,
                                left: leftPos,
                                width: inputWidth,
                                viewportHeight: window.innerHeight,
                                viewportWidth: window.innerWidth,
                                isInViewport: (topPos >= 0 && topPos <= window.innerHeight && leftPos >= 0 && leftPos <= window.innerWidth)
                            });
                            
                            // Container'ın görünür olduğundan emin ol
                            setTimeout(function() {
                                var containerInfo = {
                                    display: $autocompleteContainer.css('display'),
                                    visibility: $autocompleteContainer.css('visibility'),
                                    opacity: $autocompleteContainer.css('opacity'),
                                    zIndex: $autocompleteContainer.css('z-index'),
                                    htmlLength: $autocompleteContainer.html().length,
                                    isVisible: $autocompleteContainer.is(':visible'),
                                    offset: $autocompleteContainer.offset(),
                                    width: $autocompleteContainer.width(),
                                    height: $autocompleteContainer.height(),
                                    position: $autocompleteContainer.css('position'),
                                    top: $autocompleteContainer.css('top'),
                                    left: $autocompleteContainer.css('left')
                                };
                                console.log('Container durumu (100ms sonra):', JSON.stringify(containerInfo, null, 2));
                                
                                // Eğer görünmüyorsa tekrar dene
                                if (!$autocompleteContainer.is(':visible') || $autocompleteContainer.css('display') === 'none') {
                                    console.warn('Container görünmüyor, tekrar gösteriliyor...');
                                    var inputRect = $input[0].getBoundingClientRect();
                                    var topPos = inputRect.top + inputHeight + 2;
                                    var leftPos = inputRect.left;
                                    var inlineStyle = 'position: fixed !important; ' +
                                        'z-index: 99999 !important; ' +
                                        'background: white !important; ' +
                                        'border: 1px solid #ccc !important; ' +
                                        'box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important; ' +
                                        'display: block !important; ' +
                                        'visibility: visible !important; ' +
                                        'opacity: 1 !important; ' +
                                        'top: ' + topPos + 'px !important; ' +
                                        'left: ' + leftPos + 'px !important; ' +
                                        'width: ' + inputWidth + 'px !important; ' +
                                        'max-height: 400px !important; ' +
                                        'overflow-y: auto !important;';
                                    $autocompleteContainer.attr('style', inlineStyle);
                                    $autocompleteContainer.addClass('show');
                                    $autocompleteContainer.show();
                                }
                                
                                // Container'ı DOM'da kontrol et
                                console.log('Container DOM\'da mı?', $('#product-autocomplete-global').length > 0);
                                console.log('Container HTML:', $autocompleteContainer.html().substring(0, 200));
                            }, 100);
                            
                            // Autocomplete item'a tıklandığında
                            $autocompleteContainer.find('li').off('click').on('click', function(e) {
                                e.stopPropagation();
                                var productId = $(this).data('product-id');
                                var productCode = $(this).data('product-code');
                                var product = response.products.find(function(p) { return p.id == productId; });
                                
                                console.log('Ürün seçildi:', product);
                                
                                if (product) {
                                    var $currentRow = $input.closest('tr');
                                    var currentProductId = $currentRow.attr('data-id');
                                    
                                    if (currentProductId && currentProductId !== 'new') {
                                        // Mevcut ürün satırını güncelle
                                        console.log('Mevcut ürün güncelleniyor');
                                        updateProductByCode(productCode, currentProductId, $currentRow);
                                    } else {
                                        // Yeni ürün satırını doldur
                                        console.log('Yeni ürün ekleniyor');
                                        // ID veya class ile bul
                                        var qty = parseInt($currentRow.find('#newProductQty, .new-product-qty').first().val()) || 1;
                                        var discountRate = product.discount_rate || 0;
                                        var unitPrice = product.unit_price;
                                        var total = unitPrice * qty;
                                        
                                        $currentRow.find('#newProductCode, .new-product-code').first().val(product.code);
                                        $currentRow.find('#newProductName, .new-product-name').first().val(product.name);
                                        $currentRow.find('#newProductQty, .new-product-qty').first().val(qty);
                                        $currentRow.find('#newProductDiscount, .new-product-discount').first().val(discountRate.toFixed(2).replace('.',','));
                                        $currentRow.find('#newProductPrice, .new-product-price').first().val(unitPrice.toFixed(2).replace('.',','));
                                        $currentRow.find('#newProductUnit, .new-product-unit').first().text(product.unit);
                                        $currentRow.find('.kdv-display').first().text('20');
                                        
                                        // Liste fiyatı - fiyat yoksa "Fiyatı Yok" göster
                                        if (hasPrice) {
                                            $currentRow.find('#newProductListPrice, .new-product-list-price').first().html(product.list_price.toFixed(2).replace('.',',') + ' ' + product.currency_icon);
                                        } else {
                                            var noPriceHtml = '<span class="fiyat-yok-text" style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline;" ' +
                                                              'data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" ' +
                                                              'data-bs-content="Fiyat talebi oluşturmak için tıklayın" ' +
                                                              'data-urun-id="' + product.id + '" ' +
                                                              'data-stokkodu="' + product.code + '" ' +
                                                              'data-stokadi="' + product.name + '">Fiyatı Yok</span>';
                                            $currentRow.find('#newProductListPrice, .new-product-list-price').first().html(noPriceHtml);
                                            
                                            // Popover'ları hemen başlat
                                            if (window.initializePopovers) {
                                                setTimeout(window.initializePopovers, 100);
                                            }
                                        }
                                        
                                        $currentRow.find('.total-price-display').first().text(total.toFixed(2).replace('.',','));
                                        
                                        // Sepete ekle
                                        addProductToCartFromNewRow(product.id, qty, product, $currentRow);
                                    }
                                    
                                    if ($autocompleteContainer && $autocompleteContainer.length) {
                                        $autocompleteContainer.hide().empty();
                                    }
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX hatası:', status, error);
                            console.error('Response:', xhr.responseText);
                            if ($autocompleteContainer && $autocompleteContainer.length) {
                                $autocompleteContainer.hide().empty();
                            }
                        }
                    });
                }, 300);
            });
            
            // Input focus olduğunda dropdown'u güncelle
            $(document).on('focus', '.editable-product-name', function() {
                if (!$autocompleteContainer || $autocompleteContainer.length === 0) {
                    if ($('#product-autocomplete-global').length === 0) {
                        $autocompleteContainer = $('<div id="product-autocomplete-global" class="product-autocomplete-container"></div>');
                        $('body').append($autocompleteContainer);
                    } else {
                        $autocompleteContainer = $('#product-autocomplete-global');
                    }
                }
                
                var $input = $(this);
                var searchTerm = $input.val().trim();
                if (searchTerm.length >= 2 && $autocompleteContainer.is(':visible')) {
                    var inputOffset = $input.offset();
                    var inputWidth = $input.outerWidth();
                    var inputHeight = $input.outerHeight();
                    
                    $autocompleteContainer.css({
                        'top': (inputOffset.top + inputHeight + 2) + 'px',
                        'left': inputOffset.left + 'px',
                        'width': inputWidth + 'px'
                    });
                }
            });
            
            // Scroll olduğunda dropdown'u güncelle
            $(window).on('scroll resize', function() {
                if (!$autocompleteContainer || $autocompleteContainer.length === 0) return;
                
                if ($autocompleteContainer.is(':visible')) {
                    var $activeInput = $('.editable-product-name:focus');
                    if ($activeInput.length) {
                        var inputOffset = $activeInput.offset();
                        var inputWidth = $activeInput.outerWidth();
                        var inputHeight = $activeInput.outerHeight();
                        
                        $autocompleteContainer.css({
                            'top': (inputOffset.top + inputHeight + 2) + 'px',
                            'left': inputOffset.left + 'px',
                            'width': inputWidth + 'px'
                        });
                    }
                }
            });
            
            // Autocomplete'i dışarı tıklandığında gizle
            $(document).on('click', function(e) {
                if (!$autocompleteContainer || $autocompleteContainer.length === 0) return;
                
                if (!$(e.target).closest('.editable-product-name, #product-autocomplete-global').length) {
                    $autocompleteContainer.hide().empty();
                }
            });
            
            // Ürün adı yanındaki 3 nokta butonu - Ürün listesi modal'ı aç
            $(document).on('click', '.product-search-btn-by-name', function(e) {
                e.stopPropagation();
                var productId = $(this).data('product-id');
                if (productId) {
                    // Mevcut ürün satırı için - modal aç
                    openProductListModal(productId);
                } else {
                    // Yeni ürün satırı için - modal aç
                    openProductListModal(null);
                }
            });

            // Stok kodu ile ürün arama fonksiyonu
            function searchProductByCode(stokKodu, $targetRow) {
                // Eğer satır belirtilmemişse, en son boş satırı bul
                if (!$targetRow || $targetRow.length === 0) {
                    $targetRow = $('#newProductRow');
                }
                
                if ($targetRow.length === 0) {
                    alert('Boş satır bulunamadı. Lütfen sayfayı yenileyin.');
                    return;
                }
                
                // Loading göster - ID veya class ile bul
                var $codeInput = $targetRow.find('#newProductCode, .new-product-code').first();
                $codeInput.prop('disabled', true);
                
                console.log('Aranan kod:', stokKodu);
                console.log('Hedef satır:', $targetRow.attr('data-id'));
                
                $.ajax({
                    url: 'product-search-by-code.php',
                    method: 'GET',
                    data: { code: stokKodu },
                    dataType: 'json',
                    success: function(response) {
                        $codeInput.prop('disabled', false);
                        
                        console.log('Arama sonucu:', response);
                        
                        if (response.success && response.product) {
                            var product = response.product;
                            // Her seferinde güncel satırı kullan
                            var $currentNewRow = $targetRow.length > 0 ? $targetRow : $('#newProductRow');
                            // ID veya class ile bul
                            var qty = parseInt($currentNewRow.find('#newProductQty, .new-product-qty').first().val()) || 1;
                            var discountRate = product.discount_rate || 0;
                            
                            // ERTEK carisi kontrolü - %45 iskonto uygula (demo)
                            if (musteriKampanyaIskonto > 0 && discountRate === 0) {
                                discountRate = musteriKampanyaIskonto;
                            }
                            
                            var unitPrice = product.unit_price * (1 - discountRate/100);
                            var total = unitPrice * qty;

                            // Boş satırı doldur - ID veya class ile bul
                            $currentNewRow.find('#newProductCode, .new-product-code').first().val(product.code);
                            $currentNewRow.find('#newProductName, .new-product-name').first().val(product.name);
                            $currentNewRow.find('#newProductQty, .new-product-qty').first().val(qty);
                            $currentNewRow.find('#newProductDiscount, .new-product-discount').first().val(discountRate.toFixed(2).replace('.',','));
                            $currentNewRow.find('#newProductPrice, .new-product-price').first().val(unitPrice.toFixed(2).replace('.',','));
                            $currentNewRow.find('#newProductUnit, .new-product-unit').first().text(product.unit);
                            $currentNewRow.find('.kdv-display').first().text('20');
                            
                            // Liste fiyatı - fiyat yoksa "Fiyatı Yok" göster
                            var hasPrice = product.list_price && parseFloat(product.list_price) > 0;
                            
                            if (product.has_pending_request) {
                                // GÜNCELLEME BEKLENİYOR
                                var pendingHtml = '<span style="color: #ff9800; font-style: italic; cursor: default; font-size: 10px; white-space: nowrap; font-weight: bold; letter-spacing: -0.3px;">Güncelleme Bekliyor</span>';
                                $currentNewRow.find('#newProductListPrice, .new-product-list-price').first().html(pendingHtml);
                            } else if (hasPrice) {
                                $currentNewRow.find('#newProductListPrice, .new-product-list-price').first().html(product.list_price.toFixed(2).replace('.',',') + ' ' + product.currency_icon);
                            } else {
                                var noPriceHtml = '<span class="fiyat-yok-text" style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline;" ' +
                                                  'data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" ' +
                                                  'data-bs-content="Fiyat talebi oluşturmak için tıklayın" ' +
                                                  'data-urun-id="' + product.id + '" ' +
                                                  'data-stokkodu="' + product.code + '" ' +
                                                  'data-stokadi="' + product.name + '">Fiyatı Yok</span>';
                                $currentNewRow.find('#newProductListPrice, .new-product-list-price').first().html(noPriceHtml);
                                if (window.initializePopovers) setTimeout(window.initializePopovers, 100);
                            }
                            
                            $currentNewRow.find('.total-price-display').first().text(total.toFixed(2).replace('.',','));

                            // Sepete ekle
                            addProductToCartFromNewRow(product.id, qty, product, $currentNewRow);
                        } else {
                            alert(response.message || 'Ürün bulunamadı: ' + stokKodu);
                            $codeInput.focus();
                        }
                    },
                    error: function(xhr, status, error) {
                        $codeInput.prop('disabled', false);
                        console.error('AJAX Hatası:', status, error);
                        console.error('Response:', xhr.responseText);
                        console.error('Aranan Kod:', stokKodu);
                        var errorMsg = 'Ürün aranırken bir hata oluştu.';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch(e) {
                            errorMsg += ' (' + error + ')';
                        }
                        alert(errorMsg);
                        $codeInput.focus();
                    }
                });
            }

            // Mevcut ürün satırını stok kodu ile güncelle
            function updateProductByCode(stokKodu, oldProductId, $targetRow) {
                if (!$targetRow || $targetRow.length === 0) {
                    console.error('Hedef satır bulunamadı');
                    return;
                }
                
                var $codeInput = $targetRow.find('.editable-product-code');
                if ($codeInput.length === 0) {
                    console.error('Stok kodu input bulunamadı');
                    return;
                }
                
                $codeInput.prop('disabled', true);
                
                console.log('Ürün güncelleniyor - Stok Kodu:', stokKodu, 'Eski Ürün ID:', oldProductId);
                
                $.ajax({
                    url: 'product-search-by-code.php',
                    method: 'GET',
                    data: { code: stokKodu },
                    dataType: 'json',
                    success: function(response) {
                        $codeInput.prop('disabled', false);
                        
                        console.log('Ürün arama sonucu:', response);
                        
                        if (response.success && response.product) {
                            var product = response.product;
                            var qty = parseInt($targetRow.find('.quantity-input').val()) || 1;
                            var discountRate = product.discount_rate || 0;
                            
                            // ERTEK carisi kontrolü - %45 iskonto uygula (demo)
                            if (musteriKampanyaIskonto > 0 && discountRate === 0) {
                                discountRate = musteriKampanyaIskonto;
                            }
                            
                            var unitPrice = product.unit_price * (1 - discountRate/100);
                            var listPrice = product.list_price;
                            var total = unitPrice * qty;
                            
                            console.log('Yeni ürün bulundu - ID:', product.id, 'Ad:', product.name);
                            
                            // Eski ürünü sepetten kaldır
                            $.post('public/cart_actions.php', {
                                action: 'remove',
                                id: oldProductId
                            }, function(resp) {
                                console.log('Eski ürün kaldırıldı:', resp);
                                if (resp.success) {
                                    // Yeni ürünü sepete ekle
                                    $.post('public/cart_actions.php', {
                                        action: 'add',
                                        id: product.id,
                                        qty: qty
                                    }, function(resp2) {
                                        console.log('Yeni ürün eklendi:', resp2);
                                        if (resp2.success) {
                                            // Satırı güncelle
                                            $targetRow.attr('data-id', product.id);
                                            $codeInput.val(product.code).data('original-code', product.code).data('product-id', product.id);
                                            $targetRow.find('.editable-product-search-btn').attr('data-product-id', product.id);
                                            $targetRow.find('td').eq(1).text(product.name);
                                            // Description clears on product change (optional)
                                            // $targetRow.find('.description-input').val(''); 
                                            $targetRow.find('.quantity-input').attr('name', 'miktarisi['+product.id+']').val(qty);
                                            
                                            var readonlyAttr = (typeof discountDisabled !== 'undefined' && discountDisabled) || discountRate > 0 ? 'readonly' : '';
                                            $targetRow.find('.discount-input').attr('name', 'iskontosi['+product.id+']').val(discountRate.toFixed(2).replace('.',',')).data('list-price', listPrice).data('campaign', discountRate).prop('readonly', readonlyAttr !== '');
                                            
                                            $targetRow.find('.final-price-input').attr('name', 'final_price_unit['+product.id+']').val(unitPrice.toFixed(2).replace('.',',')).data('urun-id', product.id).data('list-price', listPrice).data('original-discount', discountRate);
                                            $targetRow.find('.currency-icon').text(product.currency_icon);
                                            $targetRow.find('.final-price-hidden').val(unitPrice.toFixed(2));
                                            $targetRow.find('.total-price-display').text(total.toFixed(2).replace('.',',') + ' ' + product.currency_icon);
                                            
                                            // Liste fiyatı ve Fiyatı Yok kontrolü
                                            var priceHtml = '';
                                            
                                            // 1. Bekleyen talep var mı?
                                            if (product.has_pending_request) {
                                                priceHtml = '<span style="color: #ff9800; font-style: italic; cursor: default; font-size: 10px; white-space: nowrap; font-weight: bold; letter-spacing: -0.3px;">Güncelleme Bekliyor</span>';
                                            }
                                            // 2. Fiyat var mı?
                                            else if (listPrice && parseFloat(listPrice) > 0) {
                                                priceHtml = listPrice.toFixed(2).replace('.',',') + ' ' + product.currency_icon;
                                            } 
                                            // 3. Fiyat yok -> Fiyatı Yok (Talep oluştur)
                                            else {
                                                priceHtml = '<span class="fiyat-yok-text" style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline;" ' +
                                                          'data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" ' +
                                                          'data-bs-content="Fiyat talebi oluşturmak için tıklayın" ' +
                                                          'data-urun-id="' + product.id + '" ' +
                                                          'data-stokkodu="' + product.code + '" ' +
                                                          'data-stokadi="' + product.name + '">Fiyatı Yok</span>';
                                                
                                                if (window.initializePopovers) setTimeout(window.initializePopovers, 100);
                                            }
                                            priceHtml += '<input type="hidden" name="fiyatsi['+product.id+']" value="'+listPrice.toFixed(2).replace('.',',')+'">';
                                            $targetRow.find('td').eq(4).html(priceHtml);
                                            $targetRow.find('td').eq(8).html(product.unit + '<input type="hidden" name="olcubirimi['+product.id+']" value="'+product.unit+'">');
                                            $targetRow.find('.remove-btn').attr('data-id', product.id);
                                            
                                            updateCartInfo();
                                            showToast('Ürün güncellendi');
                                        } else {
                                            alert('Yeni ürün sepete eklenirken bir hata oluştu.');
                                        }
                                    }, 'json');
                                } else {
                                    alert('Eski ürün sepetten kaldırılırken bir hata oluştu.');
                                }
                            }, 'json');
                        } else {
                            alert(response.message || 'Ürün bulunamadı: ' + stokKodu);
                            $codeInput.focus();
                        }
                    },
                    error: function(xhr, status, error) {
                        $codeInput.prop('disabled', false);
                        console.error('AJAX Hatası:', status, error);
                        console.error('Response:', xhr.responseText);
                        var errorMsg = 'Ürün aranırken bir hata oluştu.';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch(e) {
                            errorMsg += ' (' + error + ')';
                        }
                        alert(errorMsg);
                        $codeInput.focus();
                    }
                });
            }

            // Boş satırdan sepete ürün ekleme
            function addProductToCartFromNewRow(productId, qty, productData, $targetRow) {
                // Eğer satır belirtilmemişse, en son boş satırı bul
                if (!$targetRow || $targetRow.length === 0) {
                    $targetRow = $('#newProductRow');
                }
                
                $.post('public/cart_actions.php', {
                    action: 'add',
                    id: productId,
                    qty: qty
                }, function(resp) {
                    if (resp.success) {
                        // Cookie'nin kaydedildiğini kontrol et
                        console.log('Ürün cookie\'ye kaydedildi:', productId, 'Miktar:', qty);
                        // Modal açık kalması için session'a kaydet
                        $.post('save_form_state.php', {
                            keep_modal_open: true
                        });
                        
                        // Mevcut boş satırı gerçek satıra dönüştür
                        var $row = $targetRow.length > 0 ? $targetRow : $('#newProductRow');
                        var discountRate = productData.discount_rate || 0;
                        
                        var unitPrice = productData.unit_price * (1 - discountRate/100);
                        var listPrice = productData.list_price;
                        var total = unitPrice * qty;
                        
                        // Satırı ÖNCE güncelle - ID'yi değiştir ki kontrol doğru çalışsın
                        $row.attr('data-id', productId);
                        $row.removeAttr('id'); // ID'yi kaldır ki yeni boş satır eklenebilsin
                        
                        // Stok kodu input'unu güncelle - ID veya class ile bul
                        var $codeInput = $row.find('#newProductCode, .new-product-code').first();
                        $codeInput.prop('readonly', true).css('background', '#f9f9f9').removeAttr('id').removeClass('new-product-code').addClass('editable-product-code').attr('name', 'product_code['+productId+']');
                        
                        $row.find('.product-search-btn').remove();
                        
                        // Ürün adı - ID veya class ile bul
                        var $nameInput = $row.find('#newProductName, .new-product-name').first();
                        $nameInput.removeAttr('id').removeClass('new-product-name').val(productData.name);
                        
                        // Açıklama - ID veya class ile bul
                        var $descInput = $row.find('#newProductDesc, .new-product-desc').first();
                        $descInput.removeAttr('id').removeClass('new-product-desc').attr('name', 'aciklama['+productId+']');
                        
                        // Miktar - ID veya class ile bul
                        var $qtyInput = $row.find('#newProductQty, .new-product-qty').first();
                        $qtyInput.removeAttr('id').removeClass('new-product-qty').addClass('quantity-input').attr('name', 'miktarisi['+productId+']');
                        
                        // İskonto - ID veya class ile bul
                        var $discountInput = $row.find('#newProductDiscount, .new-product-discount').first();
                        var readonlyDiscount = (typeof discountDisabled !== 'undefined' && discountDisabled) || discountRate > 0;
                        $discountInput.removeAttr('id').removeClass('new-product-discount').attr('name', 'iskontosi['+productId+']').val(discountRate.toFixed(2).replace('.',',')).data('campaign', discountRate).prop('readonly', readonlyDiscount);
                        
                        // Fiyat - ID veya class ile bul
                        var $priceInput = $row.find('#newProductPrice, .new-product-price').first();
                        $priceInput.removeAttr('id').removeClass('new-product-price').attr('name', 'final_price_unit['+productId+']').val(unitPrice.toFixed(2).replace('.',','));
                        
                        // Para birimi ikonu
                        $row.find('.currency-icon').text(productData.currency_icon);
                        
                        // Toplam tutarı güncelle
                        if (typeof updateTotalAmount === 'function') {
                            updateTotalAmount();
                        }
                        
                        // Birim - ID veya class ile bul
                        var $unitCell = $row.find('#newProductUnit, .new-product-unit').first();
                        if ($unitCell.is('td')) {
                            $unitCell.text(productData.unit);
                        } else {
                            $unitCell.removeAttr('id').removeClass('new-product-unit').text(productData.unit);
                        }
                        
                        // Liste fiyatı - ID veya class ile bul
                        var $listPriceCell = $row.find('#newProductListPrice, .new-product-list-price').first();
                        var hasPrice = listPrice && parseFloat(listPrice) > 0;
                        if ($listPriceCell.is('td')) {
                            // 1. Bekleyen talep
                            if (productData.has_pending_request) {
                                $listPriceCell.html('<span style="color: #ff9800; font-style: italic; cursor: default; font-size: 10px; white-space: nowrap; font-weight: bold; letter-spacing: -0.3px;">Güncelleme Bekliyor</span>');
                            }
                            // 2. Fiyat var
                            else if (hasPrice) {
                                $listPriceCell.html(listPrice.toFixed(2).replace('.',',') + ' ' + productData.currency_icon);
                            } 
                            // 3. Fiyat yok
                            else {
                                var noPriceHtml = '<span class="fiyat-yok-text" style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline;" ' +
                                                  'data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" ' +
                                                  'data-bs-content="Fiyat talebi oluşturmak için tıklayın" ' +
                                                  'data-urun-id="' + productId + '" ' +
                                                  'data-stokkodu="' + productData.code + '" ' +
                                                  'data-stokadi="' + productData.name + '">Fiyatı Yok</span>';
                                $listPriceCell.html(noPriceHtml);
                                if (window.initializePopovers) setTimeout(window.initializePopovers, 100);
                            }
                        } else {
                            // 1. Bekleyen talep
                            if (productData.has_pending_request) {
                                $listPriceCell.removeAttr('id').removeClass('new-product-list-price')
                                              .html('<span style="color: #ff9800; font-style: italic; cursor: default; font-size: 10px; white-space: nowrap; font-weight: bold; letter-spacing: -0.3px;">Güncelleme Bekliyor</span>');
                            }
                            // 2. Fiyat var
                            else if (hasPrice) {
                                $listPriceCell.removeAttr('id').removeClass('new-product-list-price').html(listPrice.toFixed(2).replace('.',',') + ' ' + productData.currency_icon);
                            } 
                            // 3. Fiyat yok
                            else {
                                var noPriceHtml = '<span class="fiyat-yok-text" style="color: #dc3545; font-style: italic; cursor: pointer; text-decoration: underline;" ' +
                                                  'data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" ' +
                                                  'data-bs-content="Fiyat talebi oluşturmak için tıklayın" ' +
                                                  'data-urun-id="' + productId + '" ' +
                                                  'data-stokkodu="' + productData.code + '" ' +
                                                  'data-stokadi="' + productData.name + '">Fiyatı Yok</span>';
                                $listPriceCell.removeAttr('id').removeClass('new-product-list-price').html(noPriceHtml);
                                if (window.initializePopovers) setTimeout(window.initializePopovers, 100);
                            }
                        }
                        
                        // KDV - Ürün eklendiğinde 20 olarak göster
                        $row.find('.kdv-display').text('20');
                        
                        $row.find('.total-price-display').text(total.toFixed(2).replace('.',','));
                        
                        // Kaldır butonunu güncelle
                        var $removeBtn = $row.find('.remove-new-row-btn, .remove-btn').first();
                        if ($removeBtn.length) {
                            $removeBtn.show().removeClass('remove-new-row-btn').addClass('remove-btn').attr('data-id', productId).text('Kaldır');
                        } else {
                            // Eğer buton yoksa ekle
                            $row.find('td').last().html('<button type="button" class="btn btn-danger btn-sm remove-btn" data-id="' + productId + '" style="padding: 1px 6px; font-size: 10px; height: 20px; line-height: 18px;">Kaldır</button>');
                        }
                        
                        // Hidden input'lar ekle
                        $row.append('<input type="hidden" name="olcubirimi['+productId+']" value="'+productData.unit+'">');
                        $row.append('<input type="hidden" name="fiyatsi['+productId+']" value="'+listPrice.toFixed(2).replace('.',',')+'">');
                        $row.append('<input type="hidden" class="final-price-hidden" value="'+unitPrice.toFixed(2)+'">');
                        
                        // Yeni boş satır ekleme mantığı - SATIR GÜNCELLENDİKTEN SONRA KONTROL ET
                        // SADECE EN ALT SATIRA ÜRÜN EKLENDİĞİNDE YENİ SATIR EKLE
                        // Bu satırın altında ürün içeren (data-id !== 'new' ve sayısal) başka satır var mı kontrol et
                        var $allRows = $('#cartTableBody tr');
                        var currentRowIndex = $allRows.index($row);
                        var hasProductRowsAfter = false;
                        
                        // Bu satırdan sonraki satırlarda ürün içeren satır var mı?
                        // Sadece data-id değeri sayısal olan satırları kontrol et (boş satırlar data-id="new" olur)
                        for (var i = currentRowIndex + 1; i < $allRows.length; i++) {
                            var $nextRow = $($allRows[i]);
                            var nextRowDataId = $nextRow.attr('data-id');
                            // Eğer data-id varsa ve 'new' değilse ve sayısal ise, bu bir ürün satırıdır
                            if (nextRowDataId && nextRowDataId !== 'new' && !isNaN(parseInt(nextRowDataId))) {
                                hasProductRowsAfter = true;
                                break;
                            }
                        }
                        
                        // Bu satır tablonun en son satırı mı?
                        var isLastRow = (currentRowIndex === $allRows.length - 1);
                        
                        console.log('Satır ekleme kontrolü - Satır index:', currentRowIndex, 'Toplam satır:', $allRows.length, 'En son satır mı:', isLastRow, 'Altında ürün satırı var mı:', hasProductRowsAfter);
                        
                        // SADECE EN ALT SATIRA ÜRÜN EKLENDİĞİNDE YENİ SATIR EKLE
                        // Koşul: Bu satır tablonun en son satırı OLMALI VE altında ürün satırı olmamalı
                        if (isLastRow && !hasProductRowsAfter) {
                            // En son satır ve altında ürün satırı yok - yeni boş satır ekle
                            console.log('✓ En alt satıra ürün eklendi, yeni boş satır ekleniyor');
                            addNewEmptyRow();
                        } else if (hasProductRowsAfter) {
                            console.log('✗ Ortada bir satıra ürün eklendi (altında ürün satırı var), yeni satır eklenmeyecek');
                        } else if (!isLastRow) {
                            console.log('✗ Ortada bir satıra ürün eklendi (en son satır değil), yeni satır eklenmeyecek');
                        }
                        
                        // Tabloyu güncelle
                        updateCartInfo();
                        updateTotalAmount();
                        showToast('Ürün eklendi');
                    } else {
                        alert('Ürün sepete eklenirken bir hata oluştu.');
                    }
                }, 'json');
            }
            
            // Yeni boş satır ekleme fonksiyonu
            function addNewEmptyRow($afterRow) {
                // Eğer belirli bir satırdan sonra ekleme yapılacaksa
                var $targetContainer = $('#cartTableBody');
                var emptyRowCount = $('tr[data-id="new"]').length;
                var isFirstEmptyRow = emptyRowCount === 0;
                
                // Boş satır HTML'i
                var emptyRow = '';
                if (isFirstEmptyRow) {
                    // İlk boş satır - ID'li versiyon
                    emptyRow = '<tr id="newProductRow" data-id="new" class="new-product-row">'+
                        '<td style="padding: 0;">'+
                            '<input type="text" id="newProductCode" class="form-control product-code-input" placeholder="Stok Kodu" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                            '<button type="button" class="btn btn-sm product-search-btn" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara">'+
                                '<span style="font-size: 12px;">⋯</span>'+
                            '</button>'+
                        '</td>'+
                        '<td style="padding: 0; position: relative;">'+
                            '<input type="text" id="newProductName" class="form-control editable-product-name" placeholder="Ürün Adı" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                            '<button type="button" class="btn btn-sm product-search-btn-by-name" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara">'+
                                '<span style="font-size: 12px;">⋯</span>'+
                            '</button>'+
                            '<div class="product-name-autocomplete" style="display: none; position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); top: 100%; left: 0; margin-top: 2px;"></div>'+
                        '</td>'+
                        '<td style="padding: 0;"><input type="text" id="newProductDesc" class="form-control description-input" placeholder="Açıklama" style="text-align: left; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="padding: 0;"><input type="number" id="newProductQty" class="form-control quantity-input" value="1" min="1" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="text-align: right; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span id="newProductListPrice">0,00</span></td>'+
                        '<td style="padding: 0;"><input type="text" id="newProductDiscount" class="form-control discount-input" value="0,00" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="padding: 0; position: relative;">'+
                            '<input type="text" id="newProductPrice" class="form-control final-price-input" value="0,00" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 20px 2px 4px; height: 28px; font-size: 13px;">'+
                            '<span class="currency-icon" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #666; pointer-events: none;"></span>'+
                        '</td>'+
                        '<td style="text-align: right; padding: 2px 4px;"><span class="total-price-display" style="font-size: 13px; line-height: 28px;">0,00</span></td>'+
                        '<td style="white-space: nowrap; text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span id="newProductUnit">-</span></td>'+
                        '<td style="text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span class="kdv-display">-</span></td>'+
                        '<td style="text-align: center; padding: 2px;"><button type="button" class="btn btn-danger btn-sm remove-new-row-btn" style="padding: 0 6px; font-size: 11px; height: 24px; line-height: 22px; display: none;">Kaldır</button></td>'+
                    '</tr>';
                } else {
                    // Diğer boş satırlar - class'lı versiyon
                    emptyRow = '<tr data-id="new" class="new-product-row">'+
                        '<td style="padding: 0;">'+
                            '<input type="text" class="form-control product-code-input new-product-code" placeholder="Stok Kodu" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                            '<button type="button" class="btn btn-sm product-search-btn" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara">'+
                                '<span style="font-size: 12px;">⋯</span>'+
                            '</button>'+
                        '</td>'+
                        '<td style="padding: 0; position: relative;">'+
                            '<input type="text" class="form-control editable-product-name new-product-name" placeholder="Ürün Adı" style="text-align: left; width: calc(100% - 24px); border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px; display: inline-block;">'+
                            '<button type="button" class="btn btn-sm product-search-btn-by-name" style="padding: 0; width: 22px; height: 28px; border: 1px solid #ccc; background: white; vertical-align: top; display: inline-block;" title="Ürün Ara">'+
                                '<span style="font-size: 12px;">⋯</span>'+
                            '</button>'+
                            '<div class="product-name-autocomplete" style="display: none; position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); top: 100%; left: 0; margin-top: 2px;"></div>'+
                        '</td>'+
                        '<td style="padding: 0;"><input type="text" class="form-control description-input new-product-desc" placeholder="Açıklama" style="text-align: left; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="padding: 0;"><input type="number" class="form-control quantity-input new-product-qty" value="1" min="1" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="text-align: right; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span class="new-product-list-price">0,00</span></td>'+
                        '<td style="padding: 0;"><input type="text" class="form-control discount-input new-product-discount" value="0,00" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 4px; height: 28px; font-size: 13px;"></td>'+
                        '<td style="padding: 0; position: relative;">'+
                            '<input type="text" class="form-control final-price-input new-product-price" value="0,00" style="text-align: right; width: 100%; border: 1px solid #ccc; padding: 2px 20px 2px 4px; height: 28px; font-size: 13px;">'+
                            '<span class="currency-icon" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #666; pointer-events: none;"></span>'+
                        '</td>'+
                        '<td style="text-align: right; padding: 2px 4px;"><span class="total-price-display" style="font-size: 13px; line-height: 28px;">0,00</span></td>'+
                        '<td style="white-space: nowrap; text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span class="new-product-unit">-</span></td>'+
                        '<td style="text-align: center; padding: 2px 4px; font-size: 13px; line-height: 28px;"><span class="kdv-display">-</span></td>'+
                        '<td style="text-align: center; padding: 2px;"><button type="button" class="btn btn-danger btn-sm remove-new-row-btn" style="padding: 0 6px; font-size: 11px; height: 24px; line-height: 22px; display: none;">Kaldır</button></td>'+
                    '</tr>';
                }
                
                // Satırı ekle
                if ($afterRow && $afterRow.length) {
                    // Belirli bir satırdan sonra ekle
                    $afterRow.after(emptyRow);
                } else {
                    // En sona ekle
                    $targetContainer.append(emptyRow);
                }
                
                // Yeni eklenen input'a focus ver
                setTimeout(function() {
                    var $newCodeInput = isFirstEmptyRow ? $('#newProductCode') : $targetContainer.find('tr[data-id="new"]').last().find('.new-product-code');
                    if ($newCodeInput.length) {
                        $newCodeInput.focus();
                    }
                }, 100);
            }
            
            // 10 boş satır oluşturma fonksiyonu
            function initializeEmptyRows() {
                // Mevcut toplam satır sayısını kontrol et
                var totalRows = $('#cartTableBody tr').length;
                var emptyRowCount = $('tr[data-id="new"]').length;
                
                // Eğer toplam satır sayısı 10'dan azsa, boş satırlar ekle
                if (totalRows < 10) {
                    var neededRows = 10 - totalRows;
                    for (var i = 0; i < neededRows; i++) {
                        addNewEmptyRow();
                    }
                } else if (emptyRowCount === 0) {
                    // Eğer hiç boş satır yoksa, en az 1 tane ekle (dinamik ekleme için)
                    addNewEmptyRow();
                }
            }

            // Boş satırı temizleme (Event delegation ile)
            $(document).on('click', '.remove-new-row-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $button = $(this);
                var $row = $button.closest('tr'); // Tıklanan butonun bulunduğu satırı al
                
                if (!$row.length || $row.attr('data-id') !== 'new') {
                    return; // Sadece boş satırları temizle
                }
                
                // Boş satırdaki tüm input'ları temizle - ID veya class ile bul
                $row.find('#newProductCode, .new-product-code').first().val('');
                $row.find('#newProductName, .new-product-name').first().val('');
                $row.find('#newProductDesc, .new-product-desc').first().val('');
                $row.find('#newProductQty, .new-product-qty').first().val(1);
                $row.find('#newProductDiscount, .new-product-discount').first().val('0,00');
                $row.find('#newProductPrice, .new-product-price').first().val('0,00');
                $row.find('#newProductUnit, .new-product-unit').first().text('-');
                $row.find('.kdv-display').first().text('-');
                $row.find('#newProductListPrice, .new-product-list-price').first().html('0,00');
                $row.find('.total-price-display').first().text('0,00');
                $button.hide();
            });
        });
        
        // Pazar tipi değiştiğinde ürün ve cari listelerini yenile
        function pazarTipiDegisti() {
            var selectedValue = document.querySelector('input[name="pazar_tipi"]:checked').value;
            // Session'a kaydet (AJAX ile)
            $.ajax({
                url: 'teklif-olustur.php',
                method: 'POST',
                data: { pazar_tipi: selectedValue },
                success: function() {
                    // DataTable'ı yenile
                    if (typeof table !== 'undefined' && table) {
                        table.ajax.reload(null, false); // false = sayfa numarasını koru
                    }
                    // Müşteri listesini temizle (Select2)
                    if ($('#musteri').length && $('#musteri').data('select2')) {
                        $('#musteri').val(null).trigger('change');
                    }
                },
                error: function() {
                    alert('Pazar tipi güncellenirken bir hata oluştu.');
                }
            });
        }
    </script>
    
    <!-- Kampanya Bilgi Modal -->
    <div class="modal fade" id="kampanyaModal" tabindex="-1" aria-labelledby="kampanyaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="kampanyaModalLabel">
                        <i class="bi bi-gift me-2"></i>Kampanya Bilgileri
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Ana Bayi</strong> müşterisi için mevcut kampanyalar:
                    </div>
                    
                    <div class="row g-3" id="kampanyaListesi">
                        <?php
                        // DB bağlantısı (Karakter seti sorunu için özel bağlantı)
                        $config = require __DIR__ . '/config/config.php';
                        $dbConfig = $config['db'];
                        $campDb = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name'], $dbConfig['port']);
                        $campDb->set_charset("latin1"); // Veriler latin1 tabloda utf8 saklandığı için
                        
                        // Aktif kampanyaları çek
                        $sql = "SELECT * FROM custom_campaigns WHERE active = 1 ORDER BY priority DESC";
                        $result = $campDb->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($camp = $result->fetch_assoc()) {
                                $campId = $camp['id'];
                                
                                // Kampanya kurallarını çek
                                $rulesResult = $campDb->query("SELECT * FROM custom_campaign_rules WHERE campaign_id = $campId ORDER BY priority ASC");
                                $rules = [];
                                while($rule = $rulesResult->fetch_assoc()) $rules[] = $rule;

                                // Kampanya ürünlerini çek
                                $prodResult = $campDb->query("SELECT count(*) as total FROM custom_campaign_products WHERE campaign_id = $campId");
                                $prodCount = $prodResult->fetch_assoc()['total'];

                                // Tarih formatı
                                $start = date('d.m.Y', strtotime($camp['start_date']));
                                $end = date('d.m.Y', strtotime($camp['end_date']));
                                $validity = ($camp['start_date'] == '0000-00-00' || $camp['end_date'] == '0000-00-00') ? "Sürekli" : "$start - $end";
                                
                                // Renk seçimi
                                $colors = ['primary', 'success', 'warning', 'info'];
                                $color = $colors[$campId % count($colors)];

                                // GÖRÜNÜM DÜZELTMELERİ (User request overriding DB)
                                // 1. İsim düzeltmesi
                                $displayName = str_replace('Ertek', 'Ana Bayi', $camp['name']);
                                
                                // 2. Tarih düzeltmesi (DB 1970 ise veya her durumda 2026 isteniyorsa)
                                $start = '01.01.2026';
                                $end = '31.12.2026';
                                $validity = "$start - $end";
                        ?>
                        <div class="col-md-6">
                            <div class="card border-<?= $color ?> h-100">
                                <div class="card-header bg-<?= $color ?> text-white">
                                    <h6 class="mb-0"><i class="bi bi-gift-fill me-2"></i><?= htmlspecialchars($displayName) ?></h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>Geçerlilik:</strong> <?= $validity ?></p>
                                    <p class="card-text"><strong>Kapsam:</strong> <?= $prodCount > 0 ? $prodCount . " Adet Ürün" : "Tüm Ürünler" ?></p>
                                    
                                    <?php if($camp['min_quantity'] > 0): ?>
                                    <small class="text-muted d-block">Min. Sipariş: <?= number_format($camp['min_quantity'],0) ?> Adet</small>
                                    <?php endif; ?>
                                    
                                    <?php if($camp['min_total_amount'] > 0): ?>
                                    <small class="text-muted d-block">Min. Tutar: <?= number_format($camp['min_total_amount'],2,',','.') ?> <?= $camp['currency'] ?? 'EUR' ?></small>
                                    <?php endif; ?>

                                    <?php if(count($rules) > 0): ?>
                                    <hr class="my-2">
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach($rules as $rule): 
                                            // rule_name düzeltmesi yoksa veya hatalı ise manuel match
                                            $desc = match($rule['rule_type']) {
                                                'quantity_based' => number_format($rule['condition_value'],0)." adet+",
                                                'amount_based'   => number_format($rule['condition_value'],0)." ".($camp['currency'] ?? 'EUR')."+",
                                                'payment_based'  => "Peşin Ödeme",
                                                default          => "Genel"
                                            };
                                        ?>
                                        <li>
                                            <i class="bi bi-check-circle-fill text-<?= $color ?> me-1"></i>
                                            <strong><?= htmlspecialchars($rule['rule_name']) ?>:</strong> %<?= number_format($rule['discount_rate'], 2) ?>
                                            <span class="text-muted">(<?= $desc ?>)</span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php 
                            }
                        } else { 
                        ?>
                        <div class="col-12">
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-exclamation-triangle me-2"></i> Aktif kampanya bulunmamaktadır.
                            </div>
                        </div>
                        <?php } 
                        // Bağlantıyı kapat
                        if(isset($campDb)) $campDb->close();
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
<!-- Fatura Durumu Modal -->
<div class="modal fade" id="invoiceStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fs-6">60 Günü Geçen Faturalar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0" style="font-size: 11px;">
                        <thead class="table-light">
                            <tr>
                                <th>Fatura No</th>
                                <th>Vade Tarihi</th>
                                <th>Geçen Gün</th>
                                <th class="text-end">Fatura Tutarı</th>
                                <th class="text-end">Ödenen</th>
                                <th class="text-end">Kalan Bakiye</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceStatusTableBody">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
                <div id="invoiceStatusLoading" class="text-center p-3" style="display:none;">
                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div> Yükleniyor...
                </div>
                <div id="invoiceStatusEmpty" class="text-center p-3 text-muted" style="display:none;">
                    Kayıt bulunamadı.
                </div>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

<script>
// === MANUEL KAMPANYA SİSTEMİ - ÖZEL FİYATLANDIRMA ===
// Özel fiyat konfigürasyonu (PHP'den gelen)
const specialPricesConfig = <?php echo json_encode($specialPricesConfig['customer_special_prices'] ?? []); ?>;

/**
 * Müşterinin özel fiyat konfigürasyonunu döndürür
 */
function getCustomerSpecialConfig(customerCode) {
    return specialPricesConfig[customerCode] || null;
}

/**
 * Ürünün özel fiyatını döndürür
 */
function getSpecialPrice(customerCode, productCode) {
    const config = getCustomerSpecialConfig(customerCode);
    if (!config) return null;
    return config.products[productCode] || null;
}

/**
 * Sepetteki özel fiyatlı ürünlerin toplam miktarını hesaplar
 */
function getTotalSpecialPriceQuantity(customerCode) {
    const config = getCustomerSpecialConfig(customerCode);
    if (!config) return 0;
    
    let total = 0;
    $('#cartTableBody tr[data-id]').each(function() {
        const $row = $(this);
        const productCode = $row.find('input[name^="kod"]').val();
        
        // Bu ürün özel fiyat listesinde var mı?
        if (config.products[productCode]) {
            const qty = parseInt($row.find('.quantity-input').val()) || 0;
            total += qty;
        }
    });
    
    return total;
}

/**
 * Özel fiyat iskonto hesaplama
 * ZİNCİR İSKONTO mantığıyla çalışır
 * Örnek: 100€ → %10 = 90€ → %5 = 85.5€ (toplam %14.5)
 * 
 * @param {string} customerCode - Cari kodu (örn: 120.01.E04)
 * @param {string} productCode  - Ürün kodu
 * @param {number} listPrice    - Liste fiyatı
 * @param {boolean} isCashPayment - Peşin ödeme mi?
 * @param {boolean} isMainDealer - Ana bayi mi?
 * @returns {object} - {totalDiscount: number, discounts: array, finalPrice: number}
 */
function calculateSpecialDiscount(customerCode, productCode, listPrice, isCashPayment, isMainDealer) {
    const config = getCustomerSpecialConfig(customerCode);
    if (!config) return {totalDiscount: 0, discounts: [], finalPrice: listPrice};
    
    const specialPriceData = config.products[productCode];
    if (!specialPriceData) return {totalDiscount: 0, discounts: [], finalPrice: listPrice};
    
    // Miktar kontrolü
    const totalQty = getTotalSpecialPriceQuantity(customerCode);
    if (totalQty < config.min_quantity) {
        return {totalDiscount: 0, discounts: [], finalPrice: listPrice}; // Minimum miktar sağlanmadı
    }
    
    const specialPrice = specialPriceData.price;
    
    // ZİNCİR İSKONTO HESAPLAMA
    let currentPrice = listPrice;
    let discounts = [];
    
    // 1. Temel iskonto (liste → özel fiyat)
    const baseDiscountPercent = ((listPrice - specialPrice) / listPrice) * 100;
    currentPrice = specialPrice;
    discounts.push({
        name: 'Özel Fiyat',
        percent: baseDiscountPercent,
        beforePrice: listPrice,
        afterPrice: currentPrice
    });
    
    // 2. Peşin ödeme iskontosu (özel fiyat üzerinden)
    if (isCashPayment && config.cash_discount > 0) {
        const cashDiscountAmount = currentPrice * (config.cash_discount / 100);
        const beforeCash = currentPrice;
        currentPrice = currentPrice - cashDiscountAmount;
        discounts.push({
            name: 'Peşin Ödeme',
            percent: config.cash_discount,
            beforePrice: beforeCash,
            afterPrice: currentPrice
        });
    }
    
    // 3. Ana bayi iskontosu (mevcut fiyat üzerinden)
    if (isMainDealer && totalQty >= config.main_dealer_min_quantity && config.main_dealer_discount > 0) {
        const dealerDiscountAmount = currentPrice * (config.main_dealer_discount / 100);
        const beforeDealer = currentPrice;
        currentPrice = currentPrice - dealerDiscountAmount;
        discounts.push({
            name: 'Ana Bayi',
            percent: config.main_dealer_discount,
            beforePrice: beforeDealer,
            afterPrice: currentPrice
        });
    }
    
    // Toplam iskonto yüzdesini hesapla
    const totalDiscountPercent = ((listPrice - currentPrice) / listPrice) * 100;
    
    return {
        totalDiscount: Math.min(totalDiscountPercent, 100),
        discounts: discounts,
        finalPrice: currentPrice
    };
}

/**
 * Tüm özel fiyatlı ürünlerin iskontolarını günceller
 */
function updateAllSpecialPriceDiscounts() {
    const customerCode = $('#musteri').val();
    const isCashPayment = $('#pesinOdeme').is(':checked');
    
    if (!customerCode) return;

    // Ana bayi kontrolü - müşteri bilgisinden al
    const $musteriSelect = $('#musteri');
    const selectedText = $musteriSelect.find('option:selected').text() || '';
    const isMainDealer = $musteriSelect.find(':selected').data('ana-bayi') == 1;
    const isErtek = selectedText.indexOf('ERTEK') > -1 || selectedText.indexOf('Ana Bayi') > -1 || isMainDealer;

    const config = getCustomerSpecialConfig(customerCode);
    
    // Eğer ne config var ne de Ertek/Ana Bayi, çık
    if (!config && !isErtek) {
        return;
    }
    
    $('#cartTableBody tr[data-id]').each(function() {
        const $row = $(this);
        const productCode = $row.find('input[name^="kod"]').val();
        const $discountInput = $row.find('.discount-input');
        
        // EĞER Logo Kampanyası uygulanmışsa, bu satırı ezme!
        if ($discountInput.data('logo-campaign') || $discountInput.attr('data-logo-campaign')) {
            return;
        }

        const specialPriceData = getSpecialPrice(customerCode, productCode);
        
        // --- ERTEK / ANA BAYİ FALLBACK LOGIC ---
        // Özel fiyat yoksa ve müşteri Ana Bayi/Ertek ise
        const $musteriName = $musteriSelect.find(':selected').text();
        const isErtek = $musteriName.indexOf('ERTEK') > -1 || $musteriName.indexOf('Ana Bayi') > -1 || isMainDealer;

        if (!specialPriceData && isErtek) {
            // Liste fiyatını al
            const $listPriceInput = $row.find('input[name^="fiyatsi"]');
            const listPrice = parseFloat($listPriceInput.val()) || 0;
            
            if (listPrice > 0) {
                let fallbackDiscount = 0;
                let discountName = '';

                if (isCashPayment) {
                    fallbackDiscount = 50.00; // Peşin %50
                    discountName = "Ana Bayi Peşin";
                } else {
                    fallbackDiscount = 45.00; // Vadeli %45
                    discountName = "Ana Bayi Vadeli";
                }

                $discountInput.val(fallbackDiscount.toFixed(2).replace('.', ','));
                $discountInput.attr('data-special-price', '1');
                
                // Chain bilgisi olarak tek seviye göster
                const fallbackChain = [{
                    name: discountName,
                    percent: fallbackDiscount,
                    beforePrice: listPrice,
                    afterPrice: listPrice * (1 - fallbackDiscount/100)
                }];
                $discountInput.attr('data-chain-discount', JSON.stringify(fallbackChain));

                // Satırı yeniden hesapla
                if (typeof recalcRow === 'function') {
                    // Geçici set - normal val zaten set edildi
                    recalcRow($row);
                }
                return; // Bu satır bitti
            }
        }
        // ---------------------------------------

        if (!specialPriceData) return; // Bu ürün için özel fiyat yok
        
        // Liste fiyatını al
        const $listPriceInput = $row.find('input[name^="fiyatsi"]');
        const listPrice = parseFloat($listPriceInput.val()) || 0;
        
        if (listPrice <= 0) return;
        
        // ZİNCİR İSKONTO hesapla
        const result = calculateSpecialDiscount(customerCode, productCode, listPrice, isCashPayment, isMainDealer);
        
        // İskonto alanını güncelle
        if (result.totalDiscount > 0) {
            // Birden fazla iskonto varsa %24+%10 formatında göster
            let discountDisplay = '';
            if (result.discounts.length > 1) {
                discountDisplay = result.discounts.map(d => d.percent.toFixed(2)).join('+');
            } else {
                discountDisplay = result.totalDiscount.toFixed(2);
            }
            
            $discountInput.val(discountDisplay.replace('.', ','));
            $discountInput.attr('data-special-price', '1'); // İşaretle
            $discountInput.attr('data-chain-discount', JSON.stringify(result.discounts)); // Zincir bilgisini sakla
            
            // Satırı yeniden hesapla - TOPLAM iskonto ile
            if (typeof recalcRow === 'function') {
                // Geçici olarak toplam iskonto değerini set et
                const tempVal = $discountInput.val();
                $discountInput.val(result.totalDiscount.toFixed(2).replace('.', ','));
                recalcRow($row);
                // Görünümü geri al
                $discountInput.val(tempVal);
            }
        } else {
            // Koşul sağlanmadıysa (örn. miktar az) iskontoyu SIFIRLA
            // Böylece genel kampanya (%45) uygulanması engellenir
            $discountInput.val('0,00');
            $discountInput.removeAttr('data-special-price');
            $discountInput.removeAttr('data-chain-discount');
            
            if (typeof recalcRow === 'function') {
                recalcRow($row);
            }
        }
    });
    
    // Toplam güncelle
    if (typeof updateTotalAmount === 'function') {
        updateTotalAmount();
    }
    
    // Bilgilendirme mesajı göster
    showSpecialPricingInfo(isMainDealer);
}

/**
 * Özel fiyat bilgilendirme mesajı
 */
function showSpecialPricingInfo(isMainDealer) {
    const customerCode = $('#musteri').val();
    const config = getCustomerSpecialConfig(customerCode);
    if (!config) return;
    
    const totalQty = getTotalSpecialPriceQuantity(customerCode);
    const isCashPayment = $('#pesinOdeme').is(':checked');
    
    let message = '';
    let messageType = 'info';
    
    if (totalQty === 0) {
        // Özel fiyatlı ürün yok
        return;
    } else if (totalQty < config.min_quantity) {
        // Minimum miktar sağlanmadı
        message = `<strong>Özel Fiyat Uyarısı:</strong> Minimum ${config.min_quantity} adet gerekli. Mevcut: ${totalQty} adet.`;
        messageType = 'warning';
    } else {
        // Özel fiyat aktif
        message = `<strong>Özel Fiyat Aktif!</strong> Toplam ${totalQty} adet.`;
        
        if (isCashPayment) {
            message += ` <span class="text-success">+ Peşin İskonto (%${config.cash_discount})</span>`;
        }
        
        if (isMainDealer && totalQty >= config.main_dealer_min_quantity) {
            message += ` <span class="text-primary">+ Ana Bayi İskontosu (%${config.main_dealer_discount})</span>`;
        } else if (isMainDealer) {
            message += ` <small class="text-muted">(Ana Bayi iskontosu için ${config.main_dealer_min_quantity}+ adet gerekli)</small>`;
        }
        
        messageType = 'success';
    }
    
    // Mesaj var olan bir alert div'de göster veya oluştur
    let $alertDiv = $('#specialPriceAlert');
    if ($alertDiv.length === 0) {
        $alertDiv = $('<div id="specialPriceAlert" class="alert mb-3" role="alert"></div>');
        $('#cartTable').before($alertDiv);
    }
    
    $alertDiv
        .removeClass('alert-info alert-warning alert-success alert-danger')
        .addClass('alert-' + messageType)
        .html(message)
        .show();
}

// Peşin ödeme checkbox değiştiğinde
$(document).on('change', '#pesinOdeme', function() {
    updateAllSpecialPriceDiscounts();
});

// Miktar değiştiğinde kontrol et
$(document).on('change input', '.quantity-input', function() {
    // Kısa bir gecikme ile güncelleyelim (çok sık tetiklenmemesi için)
    clearTimeout(window.specialPriceUpdateTimeout);
    window.specialPriceUpdateTimeout = setTimeout(function() {
        updateAllSpecialPriceDiscounts();
    }, 300);
});

// Sayfa yüklendiğinde kontrol et
$(document).ready(function() {
    // Seçili müşteri varsa başlangıçta fallback oranını hesapla
    if ($('#musteri').val()) {
        if (typeof updateMusteriKampanyaIskonto === 'function') updateMusteriKampanyaIskonto();
    }

    // Müşteri select2'den seçildiğinde
    $('#musteri').on('select2:select', function() {
        if (typeof updateMusteriKampanyaIskonto === 'function') updateMusteriKampanyaIskonto();
        setTimeout(() => {
            updateAllSpecialPriceDiscounts();
        }, 500);
    });
});

// Peşin ödeme checkbox değiştiğinde
$(document).on('change', '#pesinOdeme', function() {
    if (typeof updateMusteriKampanyaIskonto === 'function') updateMusteriKampanyaIskonto();
    updateAllSpecialPriceDiscounts();
});

</script>

<script>
// Kullanıcı Tipi ve İskonto Limiti
var userRole = "<?= $user_type ?>"; 
var maxDiscountLimit = (userRole === 'Yönetici') ? 75 : 50;

$(document).on('input change', '.discount-input', function(e) {
    // Kampanya ile geldiyse (data-logo-campaign="true") limiti aşabilir.
    // Ancak kullanıcı elle değiştiriyorsa (input event), bu ayrıcalığı kaldır.
    if (e.type === 'input') {
        $(this).removeAttr('data-logo-campaign');
        $(this).removeData('logo-campaign');
    }

    var isCampaign = $(this).attr('data-logo-campaign') === 'true';
    var val = $(this).val().replace(',', '.');
    var num = parseFloat(val);

    // Kampanyalı değilse ve limit aşıldıysa engelle
    if (!isNaN(num) && num > maxDiscountLimit && !isCampaign) {
        alert('Maksimum iskonto oranı: %' + maxDiscountLimit);
        $(this).val(maxDiscountLimit.toFixed(2).replace('.', ','));
        $(this).trigger('change'); // Recalculate triggers
    }
});
</script>

<script>
$(document).ready(function() {
    // Müşteri seçildiğinde butonu göster
    $('#musteri').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.id) {
            $('#invoiceStatusBtn').show();
            
            // Ana Bayi kontrolü (ERTEK veya Ana Bayi)
            // Select2 verisinden text'i al (örn: "120.01.E04 - ERTEK MÜHENDİSLİK...")
            var customerName = data.text || '';
            if (customerName.indexOf('ERTEK') > -1 || customerName.indexOf('Ana Bayi') > -1) {
                $('#kampanyaBtn').css('display', 'inline-flex'); // Flex kullanıyoruz
                $('#applyCampaignsBtn').show();
            } else {
                $('#kampanyaBtn').hide();
                $('#applyCampaignsBtn').hide();
            }
            
        } else {
            $('#invoiceStatusBtn').hide();
            $('#kampanyaBtn').hide();
            $('#applyCampaignsBtn').hide();
        }
    });

    // Sayfa yüklendiğinde seçili varsa göster
    if($('#musteri').val()) {
        $('#invoiceStatusBtn').show();
    }

    // Butona tıklama
    $('#invoiceStatusBtn').click(function() {
        var sirketId = $('#musteri').val();
        if (!sirketId) return;

        $('#invoiceStatusModal').modal('show');
        $('#invoiceStatusTableBody').empty();
        $('#invoiceStatusLoading').show();
        $('#invoiceStatusEmpty').hide();

        $.ajax({
            url: 'api/get_overdue_invoices.php',
            data: { sirket_id: sirketId },
            dataType: 'json',
            success: function(response) {
                $('#invoiceStatusLoading').hide();
                if (response.success && response.data && response.data.length > 0) {
                    var html = '';
                    response.data.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + (item['Fatura No'] || '') + '</td>';
                        html += '<td>' + (item['Vade Tarihi'] || '') + '</td>';
                        html += '<td class="fw-bold text-danger">' + (item['Geçen Gün Sayısı'] || 0) + ' Gün</td>';
                        html += '<td class="text-end">' + parseFloat(item['TOTAL']).toLocaleString('tr-TR', {minimumFractionDigits:2}) + '</td>';
                        html += '<td class="text-end">' + parseFloat(item['PAID']).toLocaleString('tr-TR', {minimumFractionDigits:2}) + '</td>';
                        html += '<td class="text-end fw-bold">' + parseFloat(item['Kalan Bakiye']).toLocaleString('tr-TR', {minimumFractionDigits:2}) + '</td>';
                        html += '</tr>';
                    });
                    $('#invoiceStatusTableBody').html(html);
                } else {
                    $('#invoiceStatusEmpty').show();
                    if(response.error) {
                        $('#invoiceStatusEmpty').text(response.error);
                    }
                }
            },
            error: function() {
                $('#invoiceStatusLoading').hide();
                $('#invoiceStatusEmpty').text('Bir hata oluştu.').show();
            }
        });
    });
});
</script>

<script>
$(document).ready(function() {
    var campaignApplied = false;

    // Miktar değişince kontrol et
    $(document).on('input', '.quantity-input', function() {
        if (campaignApplied) {
            $('#submitCart').prop('disabled', true);
            $('#submitCart').val('Teklif Miktarı Değişti - Kampanyaları Tekrar Uygulayın');
            $('#applyCampaignsBtn').addClass('btn-danger').text('⚠️ Yeniden Kampanya Uygula');
            
            // Kullanıcıya uyarı ver (Toast varsa toast, yoksa alert)
            if (typeof showToast === 'function') {
                showToast('Miktarlar değişti! Lütfen kampanyaları tekrar uygulayın.');
            }
        }
    });

    // Kampanya Uygula Butonu Mantığı
    $('#applyCampaignsBtn').on('click', function() {
        console.log('=== KAMPANYA BUTONU BASILDI ===');
        var $btn = $(this);
        // İkonlu orijinal HTML içeriğini koru
        var originalHtml = '<i class="bi bi-percent me-1"></i> Kampanya Uygula';
        
        // Sepetteki ürünleri topla
        var items = [];
        var hasItems = false;
        
        $('.editable-product-code').each(function() {
            var $input = $(this);
            var $row = $input.closest('tr');
            var productId = $row.attr('data-id');
            var productCode = $input.val();
            var quantity = parseFloat($row.find('.quantity-input').val()) || 0;
            
            // Sadece geçerli, kaydedilmiş (new olmayan) satırları al
            if (productId && productId !== 'new' && productCode && quantity > 0) {
                // Liste fiyatını bul
                // Liste fiyatını bul - Gelişmiş Yöntem
                var listPrice = 0;
                var $discountInput = $row.find('.discount-input');
                var dataPrice = $discountInput.attr('data-list-price');
                
                if (dataPrice) {
                    // Virgülü noktaya çevirip parse et
                    listPrice = parseFloat(dataPrice.toString().replace(',', '.')) || 0;
                }
                
                // Eğer data attribute'den fiyat alınamadıysa veya 0 ise, hücre metninden al
                if (listPrice <= 0) {
                     // Miktar hücresinin (4. sütun) yanındaki hücre (5. sütun) fiyat hücresidir
                     var $priceCell = $row.find('.quantity-input').closest('td').next('td');
                     var priceText = $priceCell.text().trim();
                     
                     // "5,50 €" gibi metinlerden sadece sayıyı al
                     // Önce para birimi sembollerini ve boşlukları temizle
                     priceText = priceText.replace(/[€$₺\s]/g, '');
                     // Binlik ayracı (.) varsa kaldır, ondalık ayracı (,) ise (.) yap
                     if (priceText.indexOf(',') > -1 && priceText.indexOf('.') > -1) {
                         // Hem nokta hem virgül var (örn: 1.500,50) -> noktayı kaldır, virgülü nokta yap
                         priceText = priceText.replace(/\./g, '').replace(',', '.');
                     } else if (priceText.indexOf(',') > -1) {
                         // Sadece virgül var -> nokta yap
                         priceText = priceText.replace(',', '.');
                     }
                     // Sadece sayı ve nokta kalsın
                     listPrice = parseFloat(priceText) || 0;
                }
                
                items.push({
                    code: productCode,
                    quantity: quantity,
                    price: listPrice  // Fiyatı da gönder
                });
                hasItems = true;
            }
        });
        
        if (!hasItems) {
            alert('Kampanya uygulanacak ürün bulunamadı! Lütfen önce ürünleri ekleyip satırın kaydedilmesini bekleyin.');
            return;
        }
        
        // Loading durumu
        $btn.prop('disabled', true).removeClass('btn-danger').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Hesaplanıyor...');
        
        // Müşterinin cari kodunu al (select2 data-cari-code attribute'ünden)
        var customerCode = null;
        var $musteriSelect = $('#musteri');
        if ($musteriSelect.length && $musteriSelect.val()) {
            var selectedOption = $musteriSelect.find('option:selected');
            // Option text'ten cari kodunu çıkar (format: "120.01.E04 - Firma Adı")
            var optionText = selectedOption.text();
            if (optionText) {
                customerCode = optionText.split(' - ')[0].trim();
            }
        }
        
        if (!customerCode) {
            alert('Lütfen önce müşteri seçin!');
            $btn.prop('disabled', false).html(originalHtml);
            return;
        }
        
        // API İsteği - Manuel Kampanya Sistemi
        $.ajax({
            url: 'api/apply_manual_campaigns.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ 
                items: items,
                customerCode: customerCode,
                isCashPayment: $('#pesinOdeme').is(':checked'),
                paymentPlan: $('#payplan').val() || ''  // Ödeme planını gönder
            }),
            dataType: 'json',
            success: function(response) {
                console.log('Kampanya Yanıtı:', response);
                
                if (response.success) {
                    campaignApplied = true;
                    // Butonu tekrar aktif et ve eski haline getir
                    $('#submitCart').prop('disabled', false).val('Kaydet');
                    $('#applyCampaignsBtn').html(originalHtml);

                    var appliedCount = 0;
                    var campaignNames = [];
                    var validCampaignCodes = [];

                    // İskontoları uygula ve geçerli kodları topla
                    if (response.discounts) {
                        $.each(response.discounts, function(code, discountData) {
                            validCampaignCodes.push(code);
                            
                            // Tabloda bu koda sahip satırları bul
                            $('.editable-product-code').each(function() {
                                var $input = $(this);
                                if ($input.val() === code) {
                                    var $row = $input.closest('tr');
                                    var $discountInput = $row.find('.discount-input');
                                    
                                    // YENİ FORMAT: discountData artık bir object
                                    // { rates: [15, 5], display: "15,00-5,00", campaigns: [...], total: 19.25 }
                                    var isObject = typeof discountData === 'object' && discountData !== null && !Array.isArray(discountData);
                                    
                                    var displayValue, totalDiscount, campaignInfo;
                                    
                                    if (isObject) {
                                        // Yeni cascade format
                                        displayValue = discountData.display || '0,00';
                                        totalDiscount = discountData.total || 0;
                                        campaignInfo = (discountData.campaigns || []).join(' + ');
                                        
                                        // Data attributes'e bilgileri kaydet
                                        $discountInput.attr('data-cascade-rates', JSON.stringify(discountData.rates || []));
                                        $discountInput.attr('data-total-discount', totalDiscount);
                                        $discountInput.attr('data-campaigns', campaignInfo);
                                        
                                        // Fiyatları yeniden hesapla
                                        var listPrice = parseFloat($discountInput.attr('data-list-price')) || 0;
                                        var quantity = parseFloat($row.find('.quantity-input').val()) || 0;
                                        if (listPrice > 0 && quantity > 0) {
                                            var discountedPrice = listPrice * (1 - (totalDiscount / 100));
                                            var discountedTotal = discountedPrice * quantity;
                                            
                                            // İskontolu birim fiyat ve toplam hücrelerini güncelle
                                            
                                            // 1. İskontolu Birim Fiyat (.final-price-input)
                                            var $finalPriceInput = $row.find('.final-price-input');
                                            if ($finalPriceInput.length) {
                                                $finalPriceInput.val(discountedPrice.toFixed(2).replace('.', ','));
                                            }
                                            
                                            // 2. İskontolu Toplam (.total-price-display)
                                            // Span'ı hedefle ki updateTotalAmount fonksiyonu okuyabilsin
                                            var $totalPriceDisplay = $row.find('.total-price-display');
                                            if ($totalPriceDisplay.length) {
                                                var currencyIcon = '€'; // Varsayılan
                                                var currentText = $totalPriceDisplay.text();
                                                if (currentText.indexOf('$') > -1) currencyIcon = '$';
                                                if (currentText.indexOf('₺') > -1) currencyIcon = '₺';
                                                if (currentText.indexOf('TL') > -1) currencyIcon = 'TL';
                                                
                                                $totalPriceDisplay.text(discountedTotal.toFixed(2).replace('.', ',') + ' ' + currencyIcon);
                                            } else {
                                                // Eğer span yoksa (eski yapı), 7. sütunu bul
                                                var $totalCell = $row.find('td').eq(7);
                                                if ($totalCell.length) {
                                                    $totalCell.html('<span class="total-price-display">' + discountedTotal.toFixed(2).replace('.', ',') + ' €</span>');
                                                }
                                            }
                                            
                                            console.log(code + ': Birim=' + discountedPrice.toFixed(2) + '€, Toplam=' + discountedTotal.toFixed(2) + '€');
                                        }
                                    } else {
                                        // Eski format (backward compatibility)
                                        displayValue = parseFloat(discountData).toFixed(2).replace('.', ',');
                                        totalDiscount = parseFloat(discountData);
                                        campaignInfo = response.applied_campaigns ? response.applied_campaigns[code] : '';
                                    }
                                    
                                    var currentDiscount = $discountInput.val();
                                    
                                    // Eğer yeni iskonto farklıysa güncelle
                                    if (displayValue !== currentDiscount) {
                                        // İskonto kolonuna cascade formatı yaz (ör: "15,00-5,00")
                                        $discountInput.val(displayValue);
                                        $discountInput.attr('data-logo-campaign', 'true');
                                        $discountInput.data('logo-campaign', true);
                                        $discountInput.removeAttr('data-special-price');
                                        $discountInput.removeAttr('data-chain-discount');
                                        
                                        // Kampanya uygulandıktan sonra iskonto alanını kilitli yap
                                        $discountInput.prop('readonly', true);
                                        $discountInput.addClass('campaign-locked');
                                        $discountInput.css({
                                            'background-color': '#e3f2fd',
                                            'font-weight': 'bold',
                                            'color': '#1976d2',
                                            'cursor': 'not-allowed'
                                        });
                                        $discountInput.attr('title', 'Kampanya İskontosu - Değiştirilemez');
                                        
                                        // Change event'i tetikle - TOPLAM iskonto ile hesaplama yapılacak
                                        $discountInput.trigger('change'); 
                                        
                                        // recalcRow varsa çağır
                                        if (typeof recalcRow === 'function') {
                                            recalcRow($row);
                                        } 

                                        $row.addClass('table-warning');
                                        setTimeout(function(){ $row.removeClass('table-warning'); }, 1500);
                                        
                                        appliedCount++;
                                        
                                        // Kampanya ismini ekle (Tooltip için kullanılabilir)
                                        if (campaignInfo && campaignNames.indexOf(campaignInfo) === -1) {
                                            campaignNames.push(campaignInfo);
                                        }
                                    } else {
                                        $discountInput.attr('data-logo-campaign', 'true');
                                        
                                        // Mevcut kampanya iskontosunu da kilitle
                                        $discountInput.prop('readonly', true);
                                        $discountInput.addClass('campaign-locked');
                                        $discountInput.css({
                                            'background-color': '#e3f2fd',
                                            'font-weight': 'bold',
                                            'color': '#1976d2',
                                            'cursor': 'not-allowed'
                                        });
                                        $discountInput.attr('title', 'Kampanya İskontosu - Değiştirilemez');
                                    }
                                }
                            });
                        });
                    }
                    
                    // Tüm iskonto inputlarında change event'i tetikle (ara toplamı günceller)
                    $('.discount-input').each(function() {
                        if ($(this).val() && $(this).val() !== '0' && $(this).val() !== '0,00') {
                            $(this).trigger('change');
                        }
                    });


                     // Stale Discount Clearing Block - Eski kodu da dahil et
                    $('.editable-product-code').each(function() {
                        var $input = $(this);
                        var code = $input.val();
                        var $row = $input.closest('tr');
                        var $discountInput = $row.find('.discount-input');
                        
                        // Eğer kampanya artık geçerli değilse temizle
                        if ($discountInput.attr('data-logo-campaign') === 'true') {
                            if (validCampaignCodes.indexOf(code) === -1) {
                                console.log('Cleaning stale discount for:', code);
                                $discountInput.val('0,00');
                                $discountInput.removeAttr('data-logo-campaign');
                                $discountInput.removeData('logo-campaign');
                                $discountInput.removeAttr('data-cascade-rates');
                                $discountInput.removeAttr('data-total-discount');
                                $discountInput.removeAttr('data-campaigns');
                                
                                $discountInput.trigger('change');
                                if (typeof recalcRow === 'function') {
                                    recalcRow($row);
                                }
                                $row.addClass('table-danger');
                                setTimeout(function(){ $row.removeClass('table-danger'); }, 1500);
                            }
                        }
                    });

                    // --- FALLBACK: KAMPANYA DIŞI ÜRÜNLER İÇİN ANA BAYİ İSKONTOSU ---
                    // Kampanyalar uygulandıktan ve temizlendikten sonra,
                    // kampanya almamış ürünler için standart mantığı çalıştır.
                    setTimeout(function() {
                        console.log('Running Fallback Logic for non-campaign items...');
                        updateAllSpecialPriceDiscounts();
                    }, 200);
                    // ----------------------------------------------------------------

                    // Loglardan kampanya isimlerini çıkar

                    // Loglardan kampanya isimlerini çıkar
                    if (response.applied_campaigns) {
                         $.each(response.applied_campaigns, function(code, name) {
                             if (name && campaignNames.indexOf(name) === -1) {
                                 campaignNames.push(name);
                             }
                         });
                    }
                    
                    // Sonuç mesajı
                    if (appliedCount > 0) {
                        var msg = appliedCount + ' ürüne kampanya indirimi uygulandı.';
                        if (campaignNames.length > 0) {
                            msg += '\n\nUygulanan Kampanyalar:\n- ' + campaignNames.join('\n- ');
                        }
                        
                        if (typeof showToast === 'function') {
                            showToast('Kampanyalar Güncellendi');
                        } else {
                            alert(msg);
                        }
                    } else {
                        // Hiçbir değişiklik olmadı
                        if (typeof showToast === 'function') {
                             showToast('Kampanyalar kontrol edildi: Değişiklik yok.');
                        } else {
                             // Kullanıcıya işlemin yapıldığını hissettirmek için alert verilebilir ama toast daha iyi
                             console.log('Kampanya kontrol edildi, değişiklik yok.');
                        } 
                    }
                    
                    if (typeof updateCartInfo === 'function') updateCartInfo();
                    if (typeof updateTotalAmount === 'function') updateTotalAmount();
                    
                } else {
                    alert('Kampanya sorgusu başarısız: ' + (response.message || 'Bilinmeyen hata'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Kampanya Hatası:', xhr.responseText);
                alert('Kampanya servisine ulaşılamadı: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>

<?php include "includes/fiyat_talep_modal.php"; ?>

</body>
</html>
