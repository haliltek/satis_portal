<?php
// Başlangıç: Ortak fonksiyonlar ve oturum kontrolü
include "fonk.php";
oturumkontrol();

// Basit loglama fonksiyonu
$logFile = __DIR__ . '/debug.log';
function auditLog($msg)
{
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

// Güvenli HTML çıktısı için kısa fonksiyon
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Virgülü noktaya çevirme fonksiyonu
function convert($data)
{
    return (strpos($data, ",") !== false) ? str_replace(",", ".", $data) : $data;
}

// GET ve POST verilerinin alınması
$tu = isset($_GET['t']) ? $_GET['t'] : '';
$teklif_id = isset($_GET['tid']) ? $_GET['tid'] : '';

// Kullanıcı (personel) bilgilerini güvenli şekilde çekelim
$personel_id = $_SESSION['personel_id'] ?? '';
$stmt = $db->prepare("SELECT * FROM personel WHERE personel_id = ?");
$stmt->bind_param("s", $personel_id);
$stmt->execute();
$result = $stmt->get_result();
$personelprofil = $result->fetch_assoc();
$stmt->close();

// İşlem türü belirleniyor: Sipariş mi, Teklif mi?
if ($tu === 'siparis') {
    $islemTip = 'Sipariş';
    $durum = 'Sipariş Onay Bekleniyor';
    $status = 'Yöneticilerimiz tarafından siparişiniz inceleniyor.';
} else {
    $islemTip = 'Teklif';
    $durum = 'Teklif Onay Bekleniyor';
    $status = 'Yöneticilerimiz tarafından teklifiniz inceleniyor.';
}

// Form gönderiminde çalışacak işlem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kayitet'])) {
    // POST verilerini al
    $hazirlayanid     = $_POST['hazirlayanid'] ?? '';
    $musteriid        = $_POST['musteriid'] ?? '';
    $musteriadi       = $_POST['musteriadi'] ?? '';
    $kime             = $_POST['kime'] ?? '';
    $projeadi         = $_POST['projeadi'] ?? '';
    $tekliftarihi     = $_POST['tekliftarihi'] ?? '';
    $teklifkodu       = $_POST['teklifkodu'] ?? '';
    $teklifsartid     = $_POST['teklifsartid'] ?? '';
    $odemeturu        = $_POST['odemeturu'] ?? '';
    $sirketid         = $sirketim['sirket_id'] ?? '';
    $tltutar          = $_POST['tltutar'] ?? '';
    $dolartutar       = $_POST['dolartutar'] ?? '';
    $eurotutar        = $_POST['eurotutar'] ?? '';
    $toplamtutar      = $_POST['toplamtutar'] ?? '';
    $kdv              = $_POST['kdv'] ?? '';
    $geneltoplam      = $_POST['geneltoplam'] ?? '';
    $kurtarih         = $_POST['kurtarih'] ?? '';
    $eurokur          = $_POST['eurokur'] ?? '';
    $dolarkur         = $_POST['dolarkur'] ?? '';
    $teklifgecerlilik = $_POST['teklifgecerlilik'] ?? '';
    $teklifsiparis    = $_POST['teklifsiparis'] ?? '';
    $teslimyer        = $_POST['teslimyer'] ?? '';
    $teklif_id        = $_POST['teklifidsi'] ?? '';

    // Ürün listesini session veya cookie üzerinden al
    $productList = [];
    if (isset($_SESSION['teklif_products'])) {
        $productList = $_SESSION['teklif_products'];
    } elseif (isset($_COOKIE['teklif_products'])) {
        $productList = $_COOKIE['teklif_products'];
    }
    // Şirketin ticari grubu ile satış tipi eşleşiyor mu kontrol et
    $salespersonId = $_SESSION['yonetici_id'] ?? 0;
    $stmt = $db->prepare('SELECT satis_tipi FROM yonetici WHERE yonetici_id = ?');
    $stmt->bind_param('i', $salespersonId);
    $stmt->execute();
    $salesRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $salesType = strtolower($salesRow['satis_tipi'] ?? '');

    $companyId = (int)$musteriid;
    $stmt = $db->prepare('SELECT trading_grp FROM sirket WHERE sirket_id = ?');
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $compRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $companyGrp = strtolower($compRow['trading_grp'] ?? '');

    $companyForeign = strpos($companyGrp, 'yd') !== false;
    $salesForeign   = strpos($salesType, 'dışı') !== false;

    if ($companyForeign !== $salesForeign) {
        auditLog("Unauthorized order attempt: user {$salespersonId}, company {$companyId}, salesType={$salesType}, tradingGrp={$companyGrp}");
        $errorMessage = 'Yetkisiz şirket tipi seçimi.';
    } else {
        // Transaction
        $db->begin_transaction();
        try {
            // Her ürün için insert işlemi
            foreach ($productList as $urun_id => $val) {
                // Ürün bilgilerini çek
                $stmt = $db->prepare("SELECT * FROM urunler WHERE urun_id = ?");
                $stmt->bind_param("s", $urun_id);
                $stmt->execute();
                $urun = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Formdan gelen değerler (ürüne ait)
                $kod      = $_POST['kod'][$urun_id] ?? '';
                $adi      = $_POST['adi'][$urun_id] ?? '';
                $miktar   = $_POST['miktar'][$urun_id] ?? '';
                $iskonto  = $_POST['iskontoyolla'][$urun_id] ?? '';
                $birim    = $_POST['birim'][$urun_id] ?? '';
                $liste    = $_POST['liste'][$urun_id] ?? '';
                $doviz    = $_POST['doviz'][$urun_id] ?? '';
                $nettutar = $_POST['nettutar'][$urun_id] ?? '';
                $tutar    = $_POST['tutar'][$urun_id] ?? '';

                // Insert sorgusu
                $stmt = $db->prepare("INSERT INTO ogteklifurun2
                    (teklifid, kod, adi, miktar, birim, liste, doviz, iskonto, nettutar, tutar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "ssssssssss",
                    $teklif_id,
                    $kod,
                    $adi,
                    $miktar,
                    $birim,
                    $liste,
                    $doviz,
                    $iskonto,
                    $nettutar,
                    $tutar
                );
                $stmt->execute();

                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Ürün kaydı yapılamadı: " . $urun['stokadi']);
                }
                $stmt->close();
            }

            // Tüm ürünler eklendiyse commit
            $db->commit();

            // Ürün listesini temizle
            unset($_SESSION['teklif_products']);
            if (isset($_COOKIE['teklif_products'])) {
                foreach ($_COOKIE['teklif_products'] as $key => $val) {
                    setcookie('teklif_products[' . $key . ']', '', time() - 86400);
                }
            }

            $successMessage = $islemTip . " başarıyla oluşturuldu. Lütfen bekleyiniz...";
            // Dinamik yönlendirme: teklif id ve işlem türü parametrelerini ekliyoruz.
            header("Refresh: 1; url=teklifsiparisler-duzenle.php?te=" . urlencode($teklif_id) . "&sta=" . urlencode($islemTip));
        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = $islemTip . " kaydedilemedi. Lütfen tekrar deneyiniz. Hata: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title><?php echo h($sistemayar["title"]); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo h($sistemayar["description"]); ?>" />
    <meta name="keywords" content="<?php echo h($sistemayar["keywords"]); ?>" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons CSS (örn. Bootstrap Icons veya Font Awesome) -->
    <!-- Örnek: Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- App CSS -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />

    <!-- Datatables CSS -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- CKEditor -->
    <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        main#content {
            padding: 20px 0;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Başlık için */
        .page-header {
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .page-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0;
        }

        /* Bilgi kutusu (alert) */
        .info-box {
            margin-bottom: 20px;
        }

        /* Kart görünümü */
        .card {
            border: none;
            border-radius: .5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, .05);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 500;
        }

        .card-body h4 {
            margin-top: 0;
        }

        /* Tablolar */
        .table thead th {
            background-color: #f1f1f1;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        /* Buton ikon örneği */
        .btn i {
            margin-right: 5px;
        }

        .footer-wrapper {
            background-color: #f1f1f1;
            padding: 20px 0;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <main id="content" role="main">
        <div class="main-container">

            <!-- Sayfa Başlık Alanı -->
            <div class="page-header">
                <h1 class="text-primary">
                    <i class="bi bi-file-earmark-text"></i>
                    <?php echo h($islemTip); ?> Onaylama
                </h1>
                <small class="text-muted">
                    Bu sayfada eklediğiniz yeni <?php echo strtolower(h($islemTip)); ?> detaylarını inceleyip onaylayabilirsiniz.
                </small>
            </div>

            <!-- Başarılı/Hata Mesajları -->
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success d-flex align-items-center info-box" role="alert">
                    <i class="bi bi-check-circle-fill mr-2" style="font-size:1.3rem;"></i>
                    <div><?php echo h($successMessage); ?></div>
                </div>
            <?php elseif (!empty($errorMessage)): ?>
                <div class="alert alert-danger d-flex align-items-center info-box" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mr-2" style="font-size:1.3rem;"></i>
                    <div><?php echo h($errorMessage); ?></div>
                </div>
            <?php endif; ?>

            <!-- Bilgilendirme Kutusu -->
            <div class="alert alert-info info-box" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <span class="ml-2">
                    Lütfen tüm bilgileri dikkatlice kontrol ediniz. Eğer geri dönüp düzenleme yapmak isterseniz
                    <strong>"Geri Dön"</strong> butonunu kullanabilirsiniz.
                </span>
            </div>

            <!-- Kart Başlangıcı -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="text-secondary">
                        <strong><?php echo h($islemTip); ?></strong> Detayları
                    </span>
                    <a href="teklifsiparisler-duzenle.php?te=<?php echo urlencode($teklif_id); ?>&sta=<?php echo urlencode($islemTip); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Geri Dön
                    </a>

                </div>
                <div class="card-body">

                    <!-- Form Başlangıcı -->
                    <form method="post" action="">
                        <!-- Onay Butonu -->
                        <button type="submit" name="kayitet" class="btn btn-success mb-3">
                            <i class="bi bi-check-circle"></i>
                            <?php echo h($tu); ?> Onayladım Kaydet
                        </button>
                        <input type="hidden" name="teklifidsi" value="<?php echo h($teklif_id); ?>">

                        <hr>

                        <!-- Üst Bilgiler Tablosu -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped table-hover">
                                <tbody>
                                    <tr>
                                        <th width="140">Şirket Adı</th>
                                        <td>
                                            <?php
                                            $musteri = $_POST["musteri"] ?? '';
                                            if ($musteri == '786') {
                                                $sirketAd = $_POST["sirketbilgi"] ?? '';
                                            } else {
                                                $stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
                                                $stmt->bind_param("s", $musteri);
                                                $stmt->execute();
                                                $sirketInfo = $stmt->get_result()->fetch_assoc();
                                                $sirketAd = $sirketInfo['s_adi'] ?? '';
                                                $stmt->close();
                                            }
                                            echo h($sirketAd);
                                            ?>
                                            <input type="hidden" name="musteriid" value="<?php echo h($musteri); ?>">
                                            <input type="hidden" name="musteriadi" value="<?php echo h($sirketAd); ?>">
                                            <input type="hidden" name="hazirlayanid" value="<?php echo h($yoneticisorgula["yonetici_id"] ?? ''); ?>">
                                            <input type="hidden" name="kime" value="<?php echo ($musteri == '786') ? 'Carisiz Müşteriye' : 'Müşteriye'; ?>">
                                            <input type="hidden" name="projeadi" value="<?php echo h($_POST["projeadi"] ?? ''); ?>">
                                            <input type="hidden" name="tekliftarihi" value="<?php echo date("Y-m-d H:i"); ?>">
                                            <input type="hidden" name="teklifkodu" value="<?php echo h($_POST["teklifno"] ?? ''); ?>">
                                        </td>
                                        <th width="140">Hazırlayan</th>
                                        <td><?php echo h($yoneticisorgula["adsoyad"] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cari Telefon</th>
                                        <td><?php echo h($_POST["projeadi"] ?? ''); ?></td>
                                        <th>E-Posta Adresi</th>
                                        <td><?php echo h($yoneticisorgula["eposta"] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>E-Posta</th>
                                        <td>
                                            <?php
                                            if ($musteri != '786') {
                                                $stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
                                                $stmt->bind_param("s", $musteri);
                                                $stmt->execute();
                                                $sirketInfo = $stmt->get_result()->fetch_assoc();
                                                $yetkiliId = $sirketInfo["yetkili"] ?? '';
                                                $stmt->close();

                                                $stmt = $db->prepare("SELECT * FROM personel WHERE personel_id = ?");
                                                $stmt->bind_param("s", $yetkiliId);
                                                $stmt->execute();
                                                $personelInfo = $stmt->get_result()->fetch_assoc();
                                                echo h($personelInfo["p_eposta"] ?? '');
                                                $stmt->close();
                                            }
                                            ?>
                                        </td>
                                        <th>Telefon</th>
                                        <td><?php echo h($yoneticisorgula["telefon"] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo h($islemTip); ?> Tarihi</th>
                                        <td><?php echo date("d.m.Y") . ' Saat: ' . date("H:i"); ?></td>
                                        <th><?php echo h($islemTip); ?> Kodu</th>
                                        <td><?php echo h($_POST["teklifno"] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Telefon</th>
                                        <td>
                                            <?php
                                            $stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
                                            $stmt->bind_param("s", $musteri);
                                            $stmt->execute();
                                            $sirketInfo = $stmt->get_result()->fetch_assoc();
                                            $yetkiliId = $sirketInfo["yetkili"] ?? '';
                                            $stmt->close();

                                            $stmt = $db->prepare("SELECT * FROM personel WHERE personel_id = ?");
                                            $stmt->bind_param("s", $yetkiliId);
                                            $stmt->execute();
                                            $personelInfo = $stmt->get_result()->fetch_assoc();
                                            echo h($personelInfo["p_cep"] ?? '');
                                            $stmt->close();
                                            ?>
                                        </td>
                                        <td colspan="2" class="bg-light"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p>
                            Sayın <strong>
                                <?php
                                if ($musteri == '786') {
                                    echo h($_POST["sirketbilgi"] ?? '');
                                } else {
                                    $stmt = $db->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
                                    $stmt->bind_param("s", $musteri);
                                    $stmt->execute();
                                    $sirketInfo = $stmt->get_result()->fetch_assoc();
                                    echo h($sirketInfo["s_adi"] ?? '');
                                    $stmt->close();
                                }
                                ?>
                            </strong>;<br>
                            Talep ettiğiniz ürünlerle ilgili ticari koşulları içeren
                            <strong><?php echo h($islemTip); ?></strong> bilgileri aşağıda sunulmuştur.
                            Lütfen tüm bilgileri kontrol ediniz. Onaylamadan önce herhangi bir eksiklik veya hata varsa
                            <strong>Geri Dön</strong> butonunu kullanarak düzenleme yapınız.
                            <br><br>Saygılarımızla.
                        </p>

                        <!-- Ürünler Tablosu -->
                        <div class="table-responsive mb-4">
                            <table id="datatabsle" class="table table-bordered table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Stok Kodu</th>
                                        <th>Stok Adı</th>
                                        <th>Miktar</th>
                                        <th>Birimi</th>
                                        <th>Liste Fiyatı</th>
                                        <th>İskonto</th>
                                        <th>Net Fiyat</th>
                                        <th>Tutar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Döviz kurlarını al
                                    $dovizkurbag = $db->query("SELECT * FROM dovizkuru LIMIT 1");
                                    $dovizkuru   = $dovizkurbag->fetch_assoc();
                                    $dolarkurStr = number_format($dovizkuru["dolarsatis"], 2, ',', '.');
                                    $eurokurStr  = number_format($dovizkuru["eurosatis"], 2, ',', '.');
                                    $dolarkur    = convert($dolarkurStr);
                                    $eurokur     = convert($eurokurStr);

                                    $usd_toplam  = 0;
                                    $tl_toplam   = 0;
                                    $eur_toplam  = 0;

                                    // Ürün listesi: session ya da cookie
                                    $products = [];
                                    if (isset($_SESSION['teklif_products'])) {
                                        $products = $_SESSION['teklif_products'];
                                    } elseif (isset($_COOKIE['teklif_products'])) {
                                        $products = $_COOKIE['teklif_products'];
                                    }

                                    foreach ($products as $urun_id => $val) {
                                        // Ürünü çek
                                        $stmt = $db->prepare("SELECT * FROM urunler WHERE urun_id = ?");
                                        $stmt->bind_param("s", $urun_id);
                                        $stmt->execute();
                                        $urun = $stmt->get_result()->fetch_assoc();
                                        $stmt->close();

                                        $miktar   = $_POST['miktarisi'][$urun_id] ?? 0;
                                        $fiyat    = convert($_POST['fiyatsi'][$urun_id] ?? 0);
                                        $iskonto  = $_POST['iskontosi'][$urun_id] ?? 0;
                                        $netFiyat = $fiyat * (100 - $iskonto) / 100;
                                        $sonTutar = $miktar * $netFiyat;

                                        // Döviz cinsine göre toplam
                                        switch ($urun["doviz"]) {
                                            case 'USD':
                                                $usd_toplam += $sonTutar;
                                                break;
                                            case 'TL':
                                                $tl_toplam += $sonTutar;
                                                break;
                                            case 'EUR':
                                                $eur_toplam += $sonTutar;
                                                break;
                                        }

                                        // Para birimi simgesi
                                        switch ($urun["doviz"]) {
                                            case 'TL':
                                                $dovizm = "₺";
                                                break;
                                            case 'USD':
                                                $dovizm = "$";
                                                break;
                                            case 'EUR':
                                                $dovizm = "€";
                                                break;
                                            default:
                                                $dovizm = "";
                                        }
                                    ?>
                                        <tr>
                                            <!-- Gizli inputlar -->
                                            <input type="hidden" name="kod[<?php echo h($urun_id); ?>]" value="<?php echo h($urun["stokkodu"]); ?>">
                                            <input type="hidden" name="adi[<?php echo h($urun_id); ?>]" value="<?php echo h($urun["stokadi"]); ?>">
                                            <input type="hidden" name="miktar[<?php echo h($urun_id); ?>]" value="<?php echo h($miktar); ?>">
                                            <input type="hidden" name="birim[<?php echo h($urun_id); ?>]" value="<?php echo h($urun["olcubirimi"]); ?>">
                                            <input type="hidden" name="liste[<?php echo h($urun_id); ?>]" value="<?php echo h($fiyat); ?>">
                                            <input type="hidden" name="doviz[<?php echo h($urun_id); ?>]" value="<?php echo h($urun["doviz"]); ?>">
                                            <input type="hidden" name="iskontoyolla[<?php echo h($urun_id); ?>]" value="<?php echo h($iskonto); ?>">
                                            <input type="hidden" name="nettutar[<?php echo h($urun_id); ?>]" value="<?php echo h($netFiyat); ?>">
                                            <input type="hidden" name="tutar[<?php echo h($urun_id); ?>]" value="<?php echo h($sonTutar); ?>">

                                            <td><small><?php echo h($urun["stokkodu"]); ?></small></td>
                                            <td><small><?php echo h($urun["stokadi"]); ?></small></td>
                                            <td><?php echo h($miktar); ?></td>
                                            <td><small><?php echo h($urun["olcubirimi"]); ?></small></td>
                                            <td><?php echo h($fiyat) . ' ' . h($dovizm); ?></td>
                                            <td>%<?php echo h($iskonto); ?></td>
                                            <td><?php echo number_format($netFiyat, 2, ',', '.') . ' ' . h($urun["doviz"]); ?></td>
                                            <td>
                                                <p style="font-size:12px;">
                                                    <?php echo h($dovizm) . ' ' . number_format($sonTutar, 2, ',', '.'); ?>
                                                </p>
                                                <?php if ($urun["doviz"] == 'USD'): ?>
                                                    <hr>
                                                    <p style="font-size:11px;">
                                                        <?php echo number_format($sonTutar * $dolarkur, 2, ',', '.') . ' TL'; ?>
                                                    </p>
                                                <?php elseif ($urun["doviz"] == 'EUR'): ?>
                                                    <hr>
                                                    <p style="font-size:11px;">
                                                        <?php echo number_format($sonTutar * $eurokur, 2, ',', '.') . ' TL'; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- TOPLAM Hesaplamaları -->
                        <?php
                        $tltop       = number_format($tl_toplam, 2, ',', '.');
                        $euroFiyatM  = $eur_toplam * $eurokur;
                        $eur_fiyat   = number_format($euroFiyatM, 2, ',', '.');
                        $dolarFiyat  = $usd_toplam * $dolarkur;
                        $dol_fiyat   = number_format($dolarFiyat, 2, ',', '.');
                        $genelToplam = $tl_toplam + $dolarFiyat + $euroFiyatM;
                        $toplami     = number_format($genelToplam, 2, ',', '.');
                        $kdvtop      = ($genelToplam * 20) / 100;
                        $gentop      = $genelToplam + $kdvtop;
                        $kdvtopf     = number_format($kdvtop, 2, ',', '.');
                        $gentopf     = number_format($gentop, 2, ',', '.');
                        ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped table-hover">
                                <tbody>
                                    <tr>
                                        <td width="60%"></td>
                                        <td width="15%" class="text-right"></td>
                                        <td width="15%" class="text-right"><?php echo h($tltop); ?> TL</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 13px;">
                                            <?php echo h($dovizkuru["tarih"]); ?> tarihli TCMB EURO Kuru üzerinden hesaplanmıştır.
                                            Güncel Kur: €<?php echo h($eurokurStr); ?>
                                        </td>
                                        <td class="text-right"></td>
                                        <td class="text-right">
                                            <?php echo h($eur_toplam); ?> €
                                            <br><?php echo h($eur_fiyat); ?> ₺
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 13px;">
                                            <?php echo h($dovizkuru["tarih"]); ?> tarihli TCMB USD Kuru üzerinden hesaplanmıştır.
                                            Güncel Kur: $<?php echo h($dolarkurStr); ?>
                                        </td>
                                        <td class="text-right"></td>
                                        <td class="text-right">
                                            <?php echo h($usd_toplam); ?> $
                                            <br><?php echo h($dol_fiyat); ?> ₺
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-right"><strong>TOPLAM</strong></td>
                                        <td class="text-right"><strong><?php echo h($toplami); ?> TL</strong></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-right"><strong>KDV</strong></td>
                                        <td class="text-right"><strong><?php echo h($kdvtopf); ?> TL</strong></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-right"><strong>GENEL TOPLAM</strong></td>
                                        <td class="text-right text-primary">
                                            <strong><?php echo h($gentopf); ?> TL</strong>
                                        </td>
                                    </tr>
                                    <!-- Gizli inputlar -->
                                    <input type="hidden" name="teklifsiparis" value="<?php echo h($tu); ?>">
                                    <input type="hidden" name="tltutar" value="<?php echo h($tl_toplam); ?>">
                                    <input type="hidden" name="dolartutar" value="<?php echo h($usd_toplam); ?>">
                                    <input type="hidden" name="eurotutar" value="<?php echo h($eur_toplam); ?>">
                                    <input type="hidden" name="toplamtutar" value="<?php echo h($genelToplam); ?>">
                                    <input type="hidden" name="kdv" value="<?php echo h($kdvtop); ?>">
                                    <input type="hidden" name="geneltoplam" value="<?php echo h($gentop); ?>">
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <strong><u>Genel Şartlar ve Koşullar</u></strong><br>
                        <p><?php echo h($islemTip); ?> Geçerlilik Tarihi:
                            <strong><?php echo date("d.m.Y"); ?> 17:00'a kadar geçerlidir</strong>
                        </p>
                        <input type="hidden" name="teklifgecerlilik" value="<?php echo date("d.m.Y") . ' 17:00'; ?>">

                        <p>Teslim Yeri: <strong><?php echo h($_POST["teslimyer"] ?? ''); ?></strong></p>
                        <input type="hidden" name="teslimyer" value="<?php echo h($_POST["teslimyer"] ?? ''); ?>">

                        <p>Ödeme Türü: <strong>60 Gün Vade</strong></p>
                        <input type="hidden" name="odemeturu" value="60 Gün Vadeli">

                        <?php
                        $sirid = $sirketim["sirket_id"] ?? '';
                        $musteriss = $_POST["musteri"] ?? '';
                        if ($musteriss == 'kendim') {
                            $teklifbag2 = $db->query("SELECT * FROM gemasrteklifsartlari");
                        } else {
                            $stmt = $db->prepare("SELECT * FROM teklifsartlari WHERE sirketid = ?");
                            $stmt->bind_param("s", $sirid);
                            $stmt->execute();
                            $teklifbag2 = $stmt->get_result();
                            $stmt->close();
                        }
                        while ($teklifsart = $teklifbag2->fetch_assoc()) {
                            echo '<ul><li>' . h($teklifsart["aciklama"]) . '</li></ul>';
                        }
                        ?>
                        <input type="hidden" name="teklifsartid" value="<?php echo h($_POST["teklifsartid"] ?? ''); ?>">

                    </form><!-- /Form -->
                </div><!-- /card-body -->
            </div><!-- /card -->

        </div><!-- /main-container -->
    </main>

    <!-- Footer -->
    <footer class="footer-wrapper">
        <div class="container text-center">
            <p class="mb-0">© 2025 Gemas A.Ş - Tüm Hakları Saklıdır</p>
        </div>
    </footer>

    <!-- Gerekli JS -->
    <!-- Local JS dependencies -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>