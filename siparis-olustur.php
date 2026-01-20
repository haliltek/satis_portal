<?php
// siparis-olustur.php
ob_start(); // Output buffering başlat
require_once "fonk.php";
oturumkontrol();

// YENİ EKLENEN KISIM: Eğer "Yeni Sipariş" butonuna basıldıysa, hafızadaki her şeyi temizle
if (isset($_GET['new_offer']) && $_GET['new_offer'] === '1') {
    // 1. Sepet çerezini sil - cart_actions.php ile aynı parametrelerle
    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif'])) {
        foreach (array_keys($_COOKIE['teklif']) as $key) {
             setcookie("teklif[$key]", '', time() - 3600, '/');
             unset($_COOKIE['teklif'][$key]);
        }
    }
    // Garanti temizlik
    setcookie('teklif', '', time() - 3600, '/');
    unset($_COOKIE['teklif']);
    
    // 2. Session verilerini temizle
    unset($_SESSION['form_ekstra_bilgi']);
    unset($_SESSION['form_sozlesme_id']);
    
    // 3. JS ile Redirect (Header yerine)
    echo '<!DOCTYPE html><html><head><script>
    var cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
        if (name.indexOf("teklif") === 0) {
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
        }
    }
    window.location.href = "siparis-olustur.php";
    </script></head><body>Yönlendiriliyor...</body></html>';
    exit();
}

// Kullanıcı tipi
$user_type = $_SESSION['user_type'] ?? '';
$isDealer = $user_type === 'Bayi';
$dealerCompany = null;
$countryCode = 'TR';

// Yurtiçi/Yurtdışı seçimi - Form'dan veya session'dan al
if (isset($_POST['pazar_tipi'])) {
    $_SESSION['pazar_tipi'] = $_POST['pazar_tipi'] === 'yurtdisi' ? 'yurtdisi' : 'yurtici';
} elseif (isset($_GET['pazar_tipi'])) {
    $_SESSION['pazar_tipi'] = $_GET['pazar_tipi'] === 'yurtdisi' ? 'yurtdisi' : 'yurtici';
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

if ($isDealer) {
    $cid = (int)($_SESSION['dealer_company_id'] ?? 0);
    if ($cid) {
        $stmt = $db->prepare('SELECT sirket_id, s_adi, s_arp_code, s_country_code, trading_grp FROM sirket WHERE sirket_id = ?');
        $stmt->bind_param('i', $cid);
        $stmt->execute();
        $dealerCompany = $stmt->get_result()->fetch_assoc();
        if ($dealerCompany) {
            $grp = strtolower($dealerCompany['trading_grp'] ?? '');
            if (strpos($grp, 'yd') !== false) {
                $countryCode = 'FOREIGN';
            } elseif (isset($dealerCompany['s_country_code'])) {
                $countryCode = trim($dealerCompany['s_country_code'] ?: 'TR');
            }
        }
        $stmt->close();
    }
}

// Hata raporlaması ayarları
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/htdocs/b2b-project/error.log');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$toplam_sayfa = $toplam_sayfa ?? 1;

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
        $sql = "SELECT urun_id, stokkodu, stokadi, fiyat, export_fiyat, doviz, olcubirimi, LOGICALREF
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

// Yönetici bilgileri
$yonetici_id = $_SESSION['yonetici_id'] ?? null;
if (!$yonetici_id) {
    redirect("login.php");
}

$stmt = $db->prepare("SELECT iskonto_max, satis_tipi FROM yonetici WHERE yonetici_id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $db->error);
    exit("Bir hata oluştu.");
}
$stmt->bind_param("i", $yonetici_id);
$stmt->execute();
$result = $stmt->get_result();
$yonetici = $result->fetch_assoc();
$stmt->close();

if (!$isDealer) {
    $satisTipi = strtolower($yonetici['satis_tipi'] ?? '');
    if (strpos($satisTipi, 'dışı') !== false) {
        $countryCode = 'FOREIGN';
    }
}
$iskonto_max = isset($yonetici['iskonto_max']) ? floatval($yonetici['iskonto_max']) : 0.0;
$discountDisabled = ($iskonto_max <= 0);
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
    <!-- Select2 ve DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.3.1/css/select.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #343a40;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--background-color);
        }

        .card {
            margin-bottom: 1.5rem;
            border: 1px solid #e3e6f0;
            border-radius: 0.25rem;
        }

        .card-header,
        .modal-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
        }

        .form-control {
            border-radius: 0.25rem;
        }

        .table thead th {
            background-color: #f1f3f5;
            color: #212529;
            border-bottom: 2px solid #dee2e6;
        }

        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
            display: none;
        }

        @media (min-width: 1200px) {
            .modal-xl {
                max-width: 90%;
            }
        }

        /* 2. Form-control grid içinde taşmasın */
        .modal-body .form-control {
            min-width: 0;
        }

        /* 3. Tablo responsive yatay scroll */
        .table-responsive {
            overflow-x: auto;
        }

        .table th,
        .table td {
            white-space: nowrap;
        }

        /* 4. Hücre içi uzun metinler için truncation */
        .table td .text-truncate {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Tablodaki tüm hücreleri dikeyde ortala */
        .table td,
        .table th {
            vertical-align: middle;
        }

        /* Küçük ikon-butonu sabit boyutlu ve ortalanmış yap */
        .btn-icon {
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Pazar Tipi Seçimi</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="pazarTipiForm" class="d-inline">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="pazar_tipi" id="pazar_yurtici" value="yurtici" <?= ($pazarTipi === 'yurtici') ? 'checked' : '' ?> onchange="pazarTipiDegisti()">
                                            <label class="btn btn-outline-primary" for="pazar_yurtici">
                                                <i class="mdi mdi-home"></i> Yurtiçi
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="pazar_tipi" id="pazar_yurtdisi" value="yurtdisi" <?= ($pazarTipi === 'yurtdisi') ? 'checked' : '' ?> onchange="pazarTipiDegisti()">
                                            <label class="btn btn-outline-primary" for="pazar_yurtdisi">
                                                <i class="mdi mdi-earth"></i> Yurtdışı
                                            </label>
                                        </div>
                                    </form>
                                    <small class="text-muted d-block mt-2">
                                        Seçiminize göre ürün fiyatları ve müşteri listesi güncellenecektir.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sepetteki Ürünler -->
                    <div class="row" id="selectedProductsContainer" style="<?php echo empty($selectedProductsDetails) ? 'display:none;' : 'display:block;'; ?>">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Seçili Ürünleriniz</h5>
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#offerModal">Cari Seç</button>
                                        <a href="siparis-olustur.php?bosalt=true" class="btn btn-danger btn-sm">
                                            <?php echo ($isDealer || $user_type === 'Müşteri') ? 'Ürün Listesini Boşalt' : 'Teklif Kalemlerini Boşalt'; ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="selectedProducts" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Kod</th>
                                                    <th>Adı</th>
                                                    <th>Miktar</th>
                                                   <th>Birim</th>
                                                   <th>Liste Fiyatı</th>
                                                   <th>Döviz</th>
                                                   <th>İşlem</th>
                                               </tr>
                                           </thead>
                                           <tbody>
                                               <?php if (!empty($selectedProductsDetails)): ?>
                                                   <?php foreach ($selectedProductsDetails as $product): ?>
                                                       <?php
                                                           $domesticRaw = is_numeric($product['fiyat']) ? $product['fiyat'] : 0;
                                                           $exportRaw   = is_numeric($product['export_fiyat']) ? $product['export_fiyat'] : 0;
                                                           $priceRaw    = ($countryCode !== 'TR') ? ($exportRaw > 0 ? $exportRaw : $domesticRaw) : $domesticRaw;
                                                           $priceDisp   = $priceRaw > 0 ? $priceRaw : '-';
                                                       ?>
                                                      <tr data-id="<?php echo (int)$product['urun_id']; ?>" data-price="<?php echo htmlspecialchars($priceRaw); ?>">
                                                          <td><?php echo htmlspecialchars($product['stokkodu']); ?></td>
                                                          <td><?php echo htmlspecialchars($product['stokadi']); ?></td>
                                                          <td><?php 
                                                                $qty = isset($_COOKIE['teklif'][$product['urun_id']]) ? (int)$_COOKIE['teklif'][$product['urun_id']] : 1;
                                                                echo $qty > 0 ? $qty : 1; 
                                                            ?></td>
                                                          <td><?php echo htmlspecialchars($product['olcubirimi']); ?></td>
                                                          <td><?php echo htmlspecialchars($priceDisp); ?></td>
                                                          <td><?php echo htmlspecialchars($product['doviz']); ?></td>
                                                          <td>
                                                              <button type="button" class="btn btn-danger btn-sm remove-btn" data-id="<?php echo htmlspecialchars($product['urun_id']); ?>">Kaldır</button>
                                                          </td>
                                                      </tr>
                                                     <?php endforeach; ?>
                                                 <?php else: ?>
                                                      <tr id="noSelected">
                                                          <td colspan="7" class="text-center">Henüz ürün seçilmedi.</td>
                                                      </tr>
                                                 <?php endif; ?>
                                           </tbody>
                                       </table>
                                   </div>
                               </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ürün Listeleme ve Global Arama -->
                    <div class="row">
                        <div class="col-lg-12">
                            <a href="urunler_senkron.php" class="btn btn-warning btn-sm float-end ms-2">
                                <i class="bi bi-arrow-repeat me-1"></i> Logo Ürün Senkronizasyonu
                            </a>
                            <button type="button" class="btn btn-info btn-sm float-end" data-bs-toggle="modal" data-bs-target="#helpModal">Yardım</button>
                            <hr>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">
                                        Sn. (<small><?php echo htmlspecialchars($yoneticisorgula["adsoyad"]); ?>. Tüm ürünleri filtreleyerek bulabilirsiniz. <?php echo ($isDealer || $user_type === 'Müşteri') ? 'Siparişe' : 'Teklife'; ?> eklenecek ürünleri seçtikten sonra "İşlemi Tamamla" butonuna basın.</small>)
                                    </h4>
                                    <div class="mb-3">
                                        <input type="text" id="globalSearch" class="form-control" placeholder="Ürünlerde ara...">
                                        <input type="text" id="stokSearch" class="form-control mt-2" placeholder="Stok kodu ile ekle...">
                                    </div>
                                    <div class="table-responsive">
                                        <table id="example" class="table table-bordered dt-responsive" style="width:100%;">
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- DataTables tarafından doldurulacak -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>İşlem</th>
                                                    <th>Kod</th>
                                                    <th>Adı</th>
                                                    <th>Birimi</th>
                                                    <th>Liste Fiyatı</th>
                                                    <th>Döviz</th>
                                                    <th>Stok</th>
                                                    <th>Marka</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-center">
                                        <nav aria-label="Sayfa navigasyonu">
                                            <ul class="pagination">
                                                <?php for ($s = 1; $s <= $toplam_sayfa; $s++): ?>
                                                    <li class="page-item <?php echo ($sayfa == $s) ? 'active' : ''; ?>">
                                                        <?php if ($sayfa == $s): ?>
                                                            <span class="page-link"><?php echo $s; ?> <span class="visually-hidden">(current)</span></span>
                                                        <?php else: ?>
                                                            <a class="page-link" href="?sayfa=<?php echo $s; ?>"><?php echo $s; ?></a>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Teklif İnceleme ve Tamamlama -->
            <div class="modal fade" id="offerModal" tabindex="-1" aria-labelledby="offerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <form method="post" action="sipariskontrol.php?t=<?= $isDealer ? 'siparis' : ($user_type === 'Müşteri' ? 'siparis' : 'teklif') ?>" class="needs-validation" novalidate enctype="multipart/form-data" id="offerForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="offerModalLabel">
                                    <?php echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş Talebinizdeki Kalemler' : 'Teklifinize Ait Kalemleri İnceleyin'; ?>
                                </h5>
                                <a href="siparis-olustur.php?bosalt=true" class="btn btn-danger btn-sm">
                                    <?php echo ($isDealer || $user_type === 'Müşteri') ? 'ÜRÜN LİSTESİNİ BOŞALT' : 'TEKLİF KALEMLERİNİ BOŞALT'; ?>
                                </a>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info text-center" id="cartInfo">
                                    <?php
                                    if (isset($_COOKIE['teklif']) && is_array($_COOKIE['teklif']) && !empty($_COOKIE['teklif'])) {
                                        $listText = ($isDealer || $user_type === 'Müşteri') ? 'Sipariş Listenizde' : 'Teklif Listenizde';
                                        echo 'Şu an ' . $listText . ' <strong class="text-danger">' . count($_COOKIE['teklif']) . '</strong> ürün bulunuyor.';
                                    } else {
                                        echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş için henüz hiçbir ürün eklememişsiniz!' : 'Teklif için henüz hiçbir ürün eklememişsiniz!';
                                    }
                                    ?>
                                </div>
                                <?php /* Modal içeriği her zaman yüklensin. */ ?>
                                <div class="row g-3">
                                    <?php if ($isDealer && $dealerCompany): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Cari</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($dealerCompany['s_adi'] . ' (' . $dealerCompany['s_arp_code'] . ')') ?>" readonly>
                                        <input type="hidden" name="musteri" value="<?= (int)$dealerCompany['sirket_id'] ?>">
                                        <input type="hidden" name="sirket_id" value="<?= (int)$dealerCompany['sirket_id'] ?>">
                                        <input type="hidden" name="sirketbilgi" value="<?= htmlspecialchars($dealerCompany['s_adi']) ?>">
                                    </div>
                                    <?php else: ?>
                                    <div class="col-md-6">
                                        <label for="musteri" class="form-label">Müşteri Seçiniz</label>
                                        <select name="musteri" id="musteri" class="form-control select2" data-bs-toggle="tooltip" data-bs-placement="top" title="Bir müşteri seçiniz" style="width:100%;">
                                            <option value="786" selected>Şirket Seçmeyeceğim - Cari Yok</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 manual-fields">
                                        <label for="sirketbilgi" class="form-label">Şirket Adı</label>
                                        <input type="text" name="sirketbilgi" id="sirketbilgi" placeholder="Cari seçmediyseniz şirket adı giriniz" class="form-control" data-bs-toggle="tooltip" data-bs-placement="top" title="Şirket adını giriniz">
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label for="teklifno" class="form-label"><?php echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş No' : 'Teklif No'; ?></label>
                                        <input type="text" name="teklifno" id="teklifno" class="form-control"
                                            value="<?php echo htmlspecialchars(rand(79985, 997897797) . 'B' . $personelid . '-' . date("Y")); ?>"
                                            readonly title="<?php echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş numarası otomatik oluşturulmuştur' : 'Teklif numarası otomatik oluşturulmuştur'; ?>">
                                    </div>
                                    <?php if(!$isDealer): ?>
                                    <div class="col-md-6 manual-fields">
                                        <label for="teslimyer" class="form-label">Teslim Yeri</label>
                                        <input type="text" name="teslimyer" id="teslimyer" placeholder="Nereye teslim edilecek?" class="form-control" data-bs-toggle="tooltip" data-bs-placement="top" title="Teslim yeri giriniz">
                                    </div>
                                    <?php else: ?>
                                    <input type="hidden" name="teslimyer" value="">
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label for="gecerliliktarihi" class="form-label"><?php echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş Tarihi' : 'Teklif Geçerlilik Tarihi'; ?></label>
                                        <input
                                            type="datetime-local"
                                            name="teklifgecerlilik"
                                            id="gecerliliktarihi"
                                            class="form-control"
                                            value="<?= date('Y-m-d\T17:00', strtotime('+0 days')) ?>"
                                            required
                                        >
                                        <small class="form-text text-muted">
                                            <?php echo ($isDealer || $user_type === 'Müşteri') ? 'Sipariş için tarih seçin.' : 'Teklif geçerlilik tarihini ve saatini seçin.'; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cariTelefon" class="form-label">Cari Telefon</label>
                                        <input type="tel" name="projeadi" id="cariTelefon" placeholder="Cari kayıtlı değilse telefon numarası giriniz" class="form-control" data-bs-toggle="tooltip" data-bs-placement="top" title="Telefon numarası giriniz">
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label for="acikhesap" class="form-label">Açık Hesap Bakiye</label>
                                        <input type="text" id="acikhesap" class="form-control" readonly placeholder="Açık hesap bakiyesi burada görünecek">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="payplan" class="form-label">Ödeme Planı</label>
                                        <input type="text" id="payplan" name="odemeturu" class="form-control" readonly placeholder="Ödeme planı burada görünecek">
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <?php
                                    // Sözleşmeleri çek
                                    $sozlesmelerSorgu = mysqli_query($db, "SELECT sozlesme_id, sozlesmeadi FROM sozlesmeler ORDER BY sozlesme_id");
                                    ?>
                                    <div class="col-md-6">
                                        <label for="sozlesme_id" class="form-label">Sözleşme Seçimi</label>
                                        <select name="sozlesme_id" id="sozlesme_id" class="form-control" required>
                                            <option value="">-- Sözleşme Seçiniz --</option>
                                            <?php
                                            if ($sozlesmelerSorgu) {
                                                while ($soz = mysqli_fetch_assoc($sozlesmelerSorgu)) {
                                                    $selected = ($soz['sozlesme_id'] == 5) ? 'selected' : '';
                                                    echo '<option value="' . (int)$soz['sozlesme_id'] . '" ' . $selected . '>' . htmlspecialchars($soz['sozlesmeadi']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                        <small class="form-text text-muted">Müşteriye gönderilecek teklif/siparişte kullanılacak sözleşme</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Müşteriye Gösterilecek Döviz</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_eur" value="EUR" checked>
                                            <label class="form-check-label" for="doviz_eur">Euro (EUR)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_usd" value="USD">
                                            <label class="form-check-label" for="doviz_usd">Dolar (USD)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_tl" value="TL">
                                            <label class="form-check-label" for="doviz_tl">Türk Lirası (TL)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="doviz_goster" id="doviz_tumu" value="TUMU">
                                            <label class="form-check-label" for="doviz_tumu">Tümü</label>
                                        </div>
                                        <small class="form-text text-muted">Müşteriye gönderilecek teklif/siparişte hangi dövizlerin gösterileceğini seçin</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="table-responsive">
                                    <table id="cartTable" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Stok Kodu</th>
                                                <th>Stok Adı</th>
                                                <th>Miktar</th>
                                                <th>İskonto (%)</th>
                                                <th>İskontolu Birim Fiyat</th>
                                                <th>İskontolu Toplam</th>
                                                <th>Birim</th>
                                                <th>Liste Fiyatı</th>
                                                <th>İşlem</th>
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

                                                    $domesticRaw = is_numeric($row['fiyat']) ? (float)$row['fiyat'] : 0;
                                                    $exportRaw   = is_numeric($row['export_fiyat']) ? (float)$row['export_fiyat'] : 0;
                                                    $basePrice   = ($countryCode !== 'TR') ? ($exportRaw > 0 ? $exportRaw : $domesticRaw) : $domesticRaw;
                                                    $campaignRate = $dbManager->getCampaignDiscountForProduct((int)$row['LOGICALREF']) ?? 0.0;
                                                    $unit0        = number_format($basePrice * (1 - $campaignRate / 100), 2, '.', '');
                                                    $total0       = $unit0;
                                                    $readonlyAttr = ($discountDisabled || $campaignRate > 0) ? 'readonly' : '';
                                                    $campaignRatesMap[$row['urun_id']] = $campaignRate;
                                            ?>
                                                <tr data-id="<?= $row['urun_id'] ?>">
                                                    <td><?= htmlspecialchars($row['stokkodu']) ?></td>
                                                    <td><?= htmlspecialchars($row['stokadi']) ?></td>
                                                    <td>
                                                        <input type="number"
                                                            name="miktarisi[<?= $row['urun_id'] ?>]"
                                                            value="1"
                                                            class="form-control quantity-input"
                                                            min="1">
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="iskontosi[<?= $row['urun_id'] ?>]"
                                                            value="<?= number_format($campaignRate,2,'.','') ?>"
                                                            class="form-control discount-input"
                                                            min="0"
                                                            max="<?= htmlspecialchars($iskonto_max) ?>"
                                                            step="0.01"
                                                            data-campaign="<?= $campaignRate ?>"
                                                            <?= $readonlyAttr ?>>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="input-group input-group-sm">
                                                            <input
                                                                type="text"
                                                                class="form-control final-price-display"
                                                                value="<?= $unit0 ?>"
                                                                readonly
                                                                data-urun-id="<?= $row['urun_id'] ?>"
                                                                data-list-price="<?= number_format($basePrice,2,'.','') ?>">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary btn-icon price-edit-btn"
                                                                data-urun-id="<?= $row['urun_id'] ?>"
                                                                data-list-price="<?= number_format($basePrice,2,'.','') ?>"
                                                                title="Fiyatı Düzenle"
                                                                <?= ($discountDisabled || $campaignRate > 0) ? 'disabled' : '' ?>>
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                        </div>
                                                        <input
                                                            type="hidden"
                                                            name="final_price_unit[<?= $row['urun_id'] ?>]"
                                                            value="<?= $unit0 ?>"
                                                            class="final-price-hidden">
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="final_price_total[<?= $row['urun_id'] ?>]"
                                                            value="<?= $total0 ?>"
                                                            class="form-control total-price-input"
                                                            readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="olcubirimi[<?= $row['urun_id'] ?>]"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($row['olcubirimi']) ?>"
                                                            readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="fiyatsi[<?= $row['urun_id'] ?>]"
                                                            value="<?= number_format($basePrice,2,'.','') ?>"
                                                            class="form-control"
                                                            readonly> <?= htmlspecialchars($row['doviz']) ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm remove-btn" data-id="<?= $row['urun_id'] ?>">Kaldır</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php if(!$isDealer): ?>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-secondary mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            Ekstra Bilgi / Notlar
                                        </div>
                                        <div class="card-body">
                                            <div class="form-floating">
                                                <textarea
                                                    name="ekstra_bilgi"
                                                    id="ekstraBilgi"
                                                    class="form-control"
                                                    placeholder="Proformaya eklemek istediğiniz tüm detayları buraya yazın..."
                                                    style="height: 150px;"><?php echo htmlspecialchars($ekstra_bilgi); ?></textarea>
                                                <label for="ekstraBilgi">Detayları buraya yazın...</label>
                                            </div>
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
                                        editor.ui.view.editable.element.style.height = '120px';
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
                            <?php endif; ?>


                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-center align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_special_offer" name="is_special_offer" value="1" style="transform: scale(1.2);">
                                        <label class="form-check-label ms-2 fw-bold text-danger" for="is_special_offer">
                                            Özel Teklif (Yönetici Onayı Gerektirir)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
                                <input type="submit" name="preview" id="submitCart" class="btn btn-success" value="<?php echo ($user_type === "Müşteri") ? "SİPARİŞİ GÖNDER" : "TEKLİFİ KONTROL ET"; ?>">
                            </div>
                            </div>
                        </form>
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
        var countryCode = <?= json_encode($countryCode) ?>;
        var companyForeign = countryCode !== 'TR';
        var salesForeign = <?= strpos(strtolower($yonetici['satis_tipi'] ?? ''), 'dışı') !== false ? 'true' : 'false' ?>;
        
        $(document).ready(function() {
            $('#stokSearch').val('');
            
            // URL'de modal=open parametresi varsa modal'ı otomatik aç
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('modal') === 'open') {
                // Modal'ı aç
                var offerModal = new bootstrap.Modal(document.getElementById('offerModal'));
                offerModal.show();
                
                // Seçili müşteriyi geri yükle (AJAX ile session'dan al)
                $.ajax({
                    url: 'get_form_state.php',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.musteri_id && !isDealer) {
                            // Müşteri bilgileri varsa Select2'ye ekle ve seç
                            if (response.musteri_data) {
                                var option = new Option(response.musteri_data.text, response.musteri_data.id, true, true);
                                $('#musteri').append(option).trigger('change');
                            } else {
                                // Sadece ID varsa set et
                                $('#musteri').val(response.musteri_id).trigger('change');
                            }
                        }
                    }
                });
                
                // URL'den parametreyi temizle (geri dön butonuna basıldığında tekrar açılmasın)
                var newUrl = window.location.pathname;
                var newParams = new URLSearchParams(window.location.search);
                newParams.delete('modal');
                if (newParams.toString()) {
                    newUrl += '?' + newParams.toString();
                }
                window.history.replaceState({}, '', newUrl);
            }
            
            // Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            var isDealer = <?= json_encode($isDealer) ?>;
            if (!isDealer) {
                // Select2 müşteri seçimi
                $('#musteri').select2({
                    placeholder: "Lütfen bir şirket seçiniz",
                    allowClear: true,
                    minimumInputLength: 0,
                    dropdownParent: $('#offerModal'),
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
                    }
                });
            } else {
                // Dealer: doldur bakiye
                $.ajax({
                    url: 'get_acikhesap.php',
                    method: 'GET',
                    data: { sirket_id: <?= (int)($dealerCompany['sirket_id'] ?? 0) ?> },
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            var acikhesap = response.acikhesap;
                            var grp = (response.trading_grp || '').toLowerCase();
                            countryCode = grp.indexOf('yd') !== -1 ? 'FOREIGN' : 'TR';
                            companyForeign = countryCode !== 'TR';
                            var plan = '';
                            if (response.payplan_code || response.payplan_def) {
                                plan = (response.payplan_code || '') + ' - ' + (response.payplan_def || '');
                            }
                            if ($.isNumeric(acikhesap)) {
                                var formatted = Number(acikhesap).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' TL';
                                $('#acikhesap').val(formatted);
                            }
                            if (plan !== '') {
                                $('#payplan').val(plan);
                            }
                        }
                    }
                });
            }

            // DataTable: ürün listesini manuel başlat
            table = $('#example').DataTable({
                deferRender: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "uruncekdatatable.php",
                    data: function(d) {
                        // Radio button'dan seçili değeri al
                        var pazarTipi = $('input[name="pazar_tipi"]:checked').val() || 'yurtici';
                        d.pazar_tipi = pazarTipi;
                    }
                },
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json"
                },
                pageLength: 200,
                select: {style: 'multi'},
                columnDefs: [
                    { targets: 2, width: "25%" } // Ürün adı sütunu genişliğini sabitle
                ]
            });
            updateCartInfo();

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
                    var row = table.row(idx).node();
                    if (row) {
                        $(row).find('.select-btn').trigger('click');
                    }
                    $(this).val('');
                    table.column(1).search('').draw();
                    searchIndex = -1;
                    e.preventDefault();
                }
            });

            function showToast(msg, isError) {
                var toastEl = $('#toastNotification');
                toastEl.removeClass('text-bg-success text-bg-danger')
                    .addClass(isError ? 'text-bg-danger' : 'text-bg-success');
                toastEl.find('.toast-body').text(msg);
                new bootstrap.Toast(toastEl).show();
            }

            function updateCartInfo() {
                var count = $('#cartTableBody tr').length;
                var text;
                if (count > 0) {
                    text = 'Şu an ' + (isDealer ? 'Sipariş Listenizde' : 'Teklif Listenizde') +
                        ' <strong class="text-danger">' + count + '</strong> ürün bulunuyor.';
                } else {
                    text = isDealer ? 'Sipariş için henüz hiçbir ürün eklememişsiniz!' : 'Teklif için henüz hiçbir ürün eklememişsiniz!';
                }
                $('#cartInfo').html(text);
                $('#submitCart').prop('disabled', count === 0);
            }

            function refreshSelectedProducts(rowNode, rowData, id, qty = 1) {
                if (!rowData) return;
                if ($('#cartTableBody tr[data-id="'+id+'"]').length) {
                    showToast('Bu ürün zaten listede', true);
                    return;
                }
                $('#noSelected').remove();
                $('#selectedProductsContainer').show();
                var price = parseFloat(rowData[4]) || 0;
                var priceDisp = price > 0 ? price : '-';
                $('#selectedProducts tbody').append(
                    '<tr data-id="'+id+'" data-price="'+price+'">' +
                    '<td>'+rowData[1]+'</td>' +
                    '<td>'+rowData[2]+'</td>' +
                    '<td>'+qty+'</td>' +
                    '<td>'+rowData[3]+'</td>' +
                    '<td>'+priceDisp+'</td>' +
                    '<td>'+rowData[5]+'</td>' +
                    '<td><button type="button" class="btn btn-danger btn-sm remove-btn" data-id="'+id+'">Kaldır</button></td>' +
                    '</tr>'
                );
                var rate = campaignRates[id] || 0;
                var readonly = discountDisabled || rate > 0;
                var unit = price * (1 - rate/100);
                $('#cartTableBody').append(
                    '<tr data-id="'+id+'">'+
                    '<td>'+rowData[1]+'</td>'+
                    '<td>'+rowData[2]+'</td>'+
                    '<td><input type="number" name="miktarisi['+id+']" value="'+qty+'" class="form-control quantity-input" min="1"></td>'+
                    '<td><input type="number" name="iskontosi['+id+']" value="'+rate.toFixed(2)+'" class="form-control discount-input" min="0" max="'+maxPct+'" step="0.01" '+(readonly ? 'readonly' : '')+' data-campaign="'+rate+'"></td>'+
                    '<td class="align-middle"><div class="input-group input-group-sm">'+
                    '<input type="text" class="form-control final-price-display" value="'+unit.toFixed(2)+'" readonly data-urun-id="'+id+'" data-list-price="'+price.toFixed(2)+'">'+
                    '<button type="button" class="btn btn-outline-secondary btn-icon price-edit-btn" data-urun-id="'+id+'" data-list-price="'+price.toFixed(2)+'" title="Fiyatı Düzenle" '+(readonly ? 'disabled' : '')+'><i class="bi bi-pencil"></i></button>'+ 
                    '</div><input type="hidden" name="final_price_unit['+id+']" value="'+unit.toFixed(2)+'" class="final-price-hidden"></td>'+
                    '<td><input type="number" name="final_price_total['+id+']" value="'+unit.toFixed(2)+'" class="form-control total-price-input" readonly></td>'+
                    '<td><input type="text" name="olcubirimi['+id+']" class="form-control" value="'+rowData[3]+'" readonly></td>'+
                    '<td><input type="text" name="fiyatsi['+id+']" value="'+price.toFixed(2)+'" class="form-control" readonly> '+rowData[5]+'</td>'+
                    '<td><button type="button" class="btn btn-danger btn-sm remove-btn" data-id="'+id+'">Kaldır</button></td>'+
                    '</tr>'
                );
                if (!campaignRates[id]) {
                    $.getJSON('public/get_campaign_rate.php', {id: id}, function(r){
                        if(r.success && r.rate > 0){
                            campaignRates[id] = r.rate;
                            var $row = $('#cartTableBody tr[data-id="'+id+'"]');
                            $row.find('.discount-input').val(r.rate.toFixed(2)).prop('readonly', true).attr('data-campaign', r.rate);
                            $row.find('.price-edit-btn').prop('disabled', true);
                            recalcRow($row);
                        }
                    });
                }
                updateCartInfo();
            }

            $(document).on('click', '.select-btn', function() {
                var id = $(this).data('id');
                if ($('#cartTableBody tr[data-id="'+id+'"]').length) {
                    showToast('Bu ürün zaten listede', true);
                    return;
                }
                var rowNode = $(this).closest('tr');
                var rowData = table.row(rowNode).data();
                if (!rowData) {
                    rowData = rowNode.children().map(function(){
                        return $(this).text().trim();
                    }).get();
                }
                
                // Miktarı al
                var qtyInput = $(this).siblings('.quantity-input-list');
                var qty = qtyInput.length ? parseInt(qtyInput.val()) : 1;
                if (isNaN(qty) || qty < 1) qty = 1;

                $.post('public/cart_actions.php', {action: 'add', id: id, qty: qty}, function(resp){
                    if(resp.success){
                        refreshSelectedProducts(rowNode, rowData, id, qty);
                        showToast('Ürün eklendi');
                        $('#stokSearch').val('');
                        table.column(1).search('').draw();
                    }
                }, 'json');
            });

            $(document).on('click', '.remove-btn', function() {
                var id = $(this).data('id');
                $.post('public/cart_actions.php', {action: 'remove', id: id}, function(resp){
                    if(resp.success){
                        $('tr[data-id="'+id+'"]').remove();
                        if($('#selectedProducts tbody tr').length === 0){
                            $('#selectedProducts tbody').append('<tr id="noSelected"><td colspan="7" class="text-center">Henüz ürün seçilmedi.</td></tr>');
                            $('#selectedProductsContainer').show();
                        }
                        updateCartInfo();
                        showToast('Ürün kaldırıldı');
                    } else {
                        showToast(resp.message || 'İşlem başarısız', true);
                    }
                }, 'json').fail(function(){
                    showToast('Sunucu hatası', true);
                });
            });

            $('#selectedProducts').on('click', 'tr', function(){
                $('#selectedProducts tr').removeClass('selected');
                $(this).addClass('selected');
            });

            $(document).on('keydown', function(e){
                if(e.key === 'Enter' && $(document.activeElement).closest('#example').length){
                    var ids = [];
                    table.rows({selected:true}).every(function(){
                        var node = $(this.node());
                        var id = node.find('.select-btn').data('id');
                        if(id) ids.push({id:id,data:this.data(),node:node});
                    });
                    ids.forEach(function(it){
                        if ($('#cartTableBody tr[data-id="'+it.id+'"]').length) {
                            showToast('Bu ürün zaten listede', true);
                            return;
                        }
                        $.post('public/cart_actions.php', {action:'add', id:it.id}, function(resp){
                            if(resp.success){
                                refreshSelectedProducts(it.node, it.data, it.id);
                                showToast('Ürün eklendi');
                            }
                        },'json');
                    });
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

            function recalcRow($row) {
                let qty = parseFloat($row.find('.quantity-input').val()) || 0,
                    listPrice = parseFloat($row.find('input[name^="fiyatsi"]').val()) || 0,
                    discPct = parseFloat($row.find('.discount-input').val()) || 0,
                    pid = $row.data('id');

                if (campaignRates[pid]) {
                    discPct = campaignRates[pid];
                } else {
                    discPct = Math.min(Math.max(discPct, 0), maxPct);
                }

                let unitPrice = listPrice * (1 - discPct / 100),
                    total = unitPrice * qty;

                // değerleri yaz
                $row.find('.final-price-hidden').val(unitPrice.toFixed(2));
                $row.find('.final-price-display').val(unitPrice.toFixed(2));
                $row.find('.total-price-input').val(total.toFixed(2));
            }

            $(function() {

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
                        prev = $this.data('prev-value'),
                        num = parseFloat(raw);

                    let final;
                    if (raw === '' || isNaN(num)) {
                        final = parseFloat(prev) || 0;
                    } else {
                        final = num;
                    }

                    if (final > maxPct) {
                        alert(`Maksimum iskonto %${maxPct} aşamazsınız.`);
                        final = parseFloat(prev) || 0;
                    }

                    // yine de negatif olmasın
                    final = Math.max(final, 0);

                    $this.val(final.toFixed(2));
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
                    // modal’dan gelen değere göre iskonto yüzdesini hesapla ve clamp et
                    let discPct = listPrice > 0 ? (1 - newVal / listPrice) * 100 : 0;
                    if (discPct > maxPct) {
                        $('#priceModalInput').addClass('is-invalid');
                        $('#priceModalError').text(`Maksimum iskonto %${maxPct}`);
                        return;
                    }
                    discPct = Math.max(discPct, 0);

                    $('#priceModal').modal('hide');
                    $currentRow.find('.final-price-display').text(newVal.toFixed(2));
                    $currentRow.find('.final-price-hidden').val(newVal.toFixed(2));
                    $currentRow.find('.discount-input').val(discPct.toFixed(2));
                    recalcRow($currentRow);
                });
            });


            // AJAX spinner
            $(document).ajaxStart(function() {
                $('#spinnerOverlay').show();
            }).ajaxStop(function() {
                $('#spinnerOverlay').hide();
            });

            if (!isDealer) {
                // Müşteri seçimi değiştiğinde açık hesap bakiyesini getirme
                $('#musteri').on('change', function() {
                    var sirket_id = $(this).val();
                    if (sirket_id === '786' || sirket_id === null) {
                        $('.manual-fields').show();
                        $('#acikhesap').val('0,00 TL');
                        $('#payplan').val('-');
                        countryCode = 'TR';
                        companyForeign = false;
                        return;
                    } else {
                        $('.manual-fields').hide();
                    }
                    $.ajax({
                        url: 'get_acikhesap.php',
                        method: 'GET',
                        data: { sirket_id: sirket_id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                var acikhesap = response.acikhesap;
                                var grp = (response.trading_grp || '').toLowerCase();
                                countryCode = grp.indexOf('yd') !== -1 ? 'FOREIGN' : 'TR';
                                companyForeign = countryCode !== 'TR';
                                var plan = '';
                                if (response.payplan_code || response.payplan_def) {
                                    plan = (response.payplan_code || '') + ' - ' + (response.payplan_def || '');
                                }
                                if ($.isNumeric(acikhesap)) {
                                    var formatted = Number(acikhesap).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' TL';
                                    $('#acikhesap').val(formatted);
                                } else {
                                    $('#acikhesap').val('0,00 TL');
                                }
                                if (plan !== '') {
                                    $('#payplan').val(plan);
                                } else {
                                    $('#payplan').val('-');
                                }
                            } else {
                                $('#acikhesap').val('0,00 TL');
                                $('#payplan').val('-');
                                alert(response.message);
                            }
                        },
                        error: function() {
                            $('#acikhesap').val('0,00 TL');
                            $('#payplan').val('-');
                            alert('Açık hesap bakiyesi alınırken bir hata oluştu.');
                        }
                    });
                });
                $('#musteri').trigger('change');
            }

            // Bootstrap form validasyonu: Sadece required alanlar kontrol edilecek
            (function() {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    // Form submit edildiğinde referrer URL'i, ekstra_bilgi ve seçili müşteriyi session'a kaydet
                    $('#offerForm').on('submit', function() {
                        // Ekstra bilgiyi session'a kaydet (AJAX ile)
                        var ekstraBilgi = '';
                        if (typeof ClassicEditor !== 'undefined' && ClassicEditor.instances && ClassicEditor.instances.ekstraBilgi) {
                            ekstraBilgi = ClassicEditor.instances.ekstraBilgi.getData();
                        } else if ($('#ekstraBilgi').length) {
                            ekstraBilgi = $('#ekstraBilgi').val();
                        }
                        
                        // Seçili müşteri ID'sini al
                        var musteriId = $('#musteri').val() || '';
                        
                        // Referrer URL'i, ekstra_bilgi ve müşteri ID'sini session'a kaydet
                        $.ajax({
                            url: 'save_form_state.php',
                            method: 'POST',
                            async: false, // Senkron yap ki form submit edilmeden önce kaydedilsin
                            data: {
                                referrer_url: window.location.href,
                                ekstra_bilgi: ekstraBilgi,
                                musteri_id: musteriId
                            }
                        });
                    });
                    
                    form.addEventListener('submit', function(event) {
                        if (companyForeign !== salesForeign) {
                            event.preventDefault();
                            alert('Seçilen cariye işlem yetkiniz bulunmuyor.');
                            return;
                        }
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });
        
        // Pazar tipi değiştiğinde ürün ve cari listelerini yenile
        function pazarTipiDegisti() {
            var selectedValue = document.querySelector('input[name="pazar_tipi"]:checked').value;
            // Session'a kaydet (AJAX ile)
            $.ajax({
                url: 'siparis-olustur.php',
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
</body>

</html>