<?php
include "fonk.php";
oturumkontrol();

$userType = $_SESSION['user_type'] ?? '';
$campaigns = [];
$dealerCompany = null;
$recentOrders = [];
if ($userType === 'Bayi') {
    $campaigns = $dbManager->getActiveCampaigns();
    $companyId = $_SESSION['dealer_company_id'] ?? 0;
    if ($companyId) {
        $stmt = $db->prepare('SELECT * FROM sirket WHERE sirket_id = ?');
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $dealerCompany = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $recentOrders = $dbManager->getOrdersForCompany((int)$companyId, 5);
    }
}

function getRowCount($db, $query) {
    $result = mysqli_query($db, $query);
    return $result ? mysqli_num_rows($result) : 0;
}

function getMonthlyOrders($db, $limit = 6) {
    $data = [];
    $sql = "SELECT DATE_FORMAT(tekliftarihi,'%Y-%m') AS m, COUNT(*) AS c FROM ogteklif2 GROUP BY m ORDER BY m DESC LIMIT " . intval($limit);
    if ($res = mysqli_query($db, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    }
    return array_reverse($data);
}



function renderStatCard($db, $query, $title, $icon, $link = '', $color = 'primary') {
    $count = getRowCount($db, $query);
    echo '<div class="col-sm-6 col-lg-3 mb-4">';
    if ($link) echo '<a class="text-decoration-none" href="' . $link . '">';
    echo '<div class="card shadow-sm">';
    echo '<div class="card-body d-flex align-items-center">';
    echo '<div class="flex-shrink-0 me-3"><i class="bx ' . $icon . ' fs-1 text-' . $color . '"></i></div>';
    echo '<div class="flex-grow-1 text-' . $color . '">';
    echo '<h6 class="mb-0">' . $title . '</h6>';
    echo '<span class="fs-4 fw-semibold">' . $count . '</span>';
    echo '</div></div></div>';
    if ($link) echo '</a>';
    echo '</div>';
}

/**
 * Mail gönderme kartını oluşturur.
 *
 * @param string $url  Tıklanabilir link URL'si
 * @param string $bgClass Kart arka plan sınıfı ("bg-success" gibi)
 * @param string $title Kart başlığı
 * @param string $mail  Mail adresi
 */
function renderMailCard($url, $bgClass, $title, $mail) {
    echo '<div class="col-md-6 mb-3">';
    echo '<a href="' . $url . '" class="text-decoration-none">';
    echo '<div class="card">';
    echo '<div class="card-body ' . $bgClass . ' text-white">';
    echo '<p class="mb-0 fw-semibold">' . $title . '<br><small>' . htmlspecialchars($mail) . '</small></p>';
    echo '</div>';
    echo '</div>';
    echo '</a>';
    echo '</div>';
}

/**
 * Genel amaçlı bağlantı kartı oluşturur.
 *
 * @param string $url
 * @param string $bgClass
 * @param string $title
 * @param string $text
 */
function renderLinkCard($url, $bgClass, $title, $text)
{
    echo '<div class="col-md-6 mb-3">';
    echo '<a href="' . $url . '" class="text-decoration-none">';
    echo '<div class="card">';
    echo '<div class="card-body ' . $bgClass . ' text-white">';
    echo '<p class="mb-0 fw-semibold">' . $title . '<br><small>' . htmlspecialchars($text) . '</small></p>';
    echo '</div>';
    echo '</div>';
    echo '</a>';
    echo '</div>';
}
function getSyncTime($type) {
    $file = __DIR__ . "/logs/{$type}_sync_time.txt";
    return file_exists($file) ? trim(file_get_contents($file)) : null;
}

function formatRelative($timeStr) {
    if (!$timeStr) return null;
    $ts = strtotime($timeStr);
    if (!$ts) return $timeStr;
    $diff = time() - $ts;
    if ($diff < 60) return $diff . ' sn önce';
    $diff = floor($diff / 60); // minutes
    if ($diff < 60) return $diff . ' dk önce';
    $diff = floor($diff / 60); // hours
    if ($diff < 24) return $diff . ' saat önce';
    $diff = floor($diff / 24); // days
    if ($diff < 7) return $diff . ' gün önce';
    $diff = floor($diff / 7); // weeks
    if ($diff < 4) return $diff . ' hafta önce';
    $diff = floor($diff / 4.348); // months
    if ($diff < 12) return $diff . ' ay önce';
    $diff = floor($diff / 12); // years
    return $diff . ' yıl önce';
}

function formatDateTimeTurkish($timeStr) {
    $ts = strtotime($timeStr);
    return $ts ? date('d.m.Y H:i', $ts) : $timeStr;
}

function getStatusBadgeClass(string $status): string {
    switch ($status) {
        case 'Sipariş Ödeme Alındı / Tamamlandı':
            return 'badge-status-success';
        case 'Teklif Reddedildi':
        case 'Sipariş İptal Edildi':
            return 'badge-status-danger';
        case 'Teklif Oluşturuldu / Gönderilecek':
        case 'Teklif Gönderildi / Onay Bekleniyor':
        case 'Sipariş Onay Bekliyor':
        case 'Teklif Onay Bekleniyor':
        case 'Teklife Revize Talep Edildi / İnceleme Bekliyor':
        case 'Teklif Revize Edildi / Onay Bekleniyor':
            return 'badge-status-warning';
        case 'Sipariş Onaylandı / Logoya Aktarım Bekliyor':
        case 'Sipariş Logoya Aktarıldı / Ödemesi Bekleniyor':
        case 'Sipariş Ödemesi Bekleniyor':
            return 'badge-status-info';
        default:
            return 'badge-status-secondary';
    }
}


$siparisOnayBekleyen = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Sipariş Onay Bekliyor'");
$teklifOnayBekleyen  = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Teklif Onay Bekleniyor'");
$teklifIptal         = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Teklif İptal Edildi'");
$tamamlanan          = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Tamamlandı'");
$kontrolAsamasinda   = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Kontrol Aşamasında'");
$hazirlaniyor        = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Hazırlanıyor'");
$beklemede           = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Beklemede'");
$eksikMalzeme        = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Eksik Malzeme Tedariği'");
$sevkiyatta          = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Sevkiyatta'");
$aracYolda           = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum='Araç Yolda'");
$monthly             = getMonthlyOrders($db);
$pendingDealers      = getRowCount($db, "SELECT * FROM b2b_users WHERE status = 0");
$activeDealers       = getRowCount($db, "SELECT * FROM b2b_users WHERE status = 1");
$logoTransferWait    = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum = 'Sipariş Onaylandı / Logoya Aktarım Bekliyor' AND (logo_transfer_status IS NULL OR logo_transfer_status != 'Aktarıldı')");

$hasStatusData = ($siparisOnayBekleyen + $teklifOnayBekleyen + $teklifIptal + $tamamlanan +
                  $kontrolAsamasinda + $hazirlaniyor + $beklemede + $eksikMalzeme +
                  $sevkiyatta + $aracYolda) > 0;
$hasMonthlyData = count($monthly) > 0;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Panel</h4>
        </div>
    </div>
</div>

<?php if ($userType !== 'Bayi'): ?>
<form method="post" action="dovizguncelleme.php" class="mb-4">
    <button type="submit" name="duzenleme" class="btn btn-success">Döviz Kurlarını Güncelleyin!</button>
</form>
<!-- Kısayol Butonları -->
<div class="row mb-4">
    <!-- 1. Ürün Fiyatlar (Logo Fiyatları) -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="urun_fiyat_onerisi.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-dollar fs-1 text-info"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Ürün Fiyatlar</h6>
                    <p class="mb-0 small text-muted">Logo ürün fiyatlarını görüntüle</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Güncellenen Fiyatlar (Loglar) -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="urun_fiyat_log.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-history fs-1 text-warning"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Güncellenen Fiyatlar</h6>
                    <p class="mb-0 small text-muted">Fiyat değişiklik geçmişi</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Bekleyen Fiyat Talepleri (Yeni) -->
    <?php 
    $countPriceRequests = 0;
    try {
        $countPriceRequests = getRowCount($db, "SELECT * FROM fiyat_onerileri WHERE durum='Beklemede'");
        $prColor = $countPriceRequests > 0 ? 'text-warning' : 'text-secondary';
        $prBadge = $countPriceRequests > 0 ? '<span class="badge bg-warning ms-2">' . $countPriceRequests . '</span>' : '';
    } catch (mysqli_sql_exception $e) {
        $prColor = 'text-danger';
        $prBadge = '<span class="badge bg-danger ms-2" title="Tablo Eksik!">!</span>';
    }
    ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="fiyat_talepleri.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-tag-alt fs-1 <?= $prColor ?>"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Bekleyen Fiyat Talepleri <?= $prBadge ?></h6>
                    <p class="mb-0 small text-muted">Personel fiyat önerilerini yönet</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 4. Onay Bekleyenler -->
    <?php 
    // Hem eski 'Bekliyor' hem yeni 'Bekleniyor' durumlarını sayalım (geçiş süreci için)
    $countPending = getRowCount($db, "SELECT * FROM ogteklif2 WHERE durum IN ('Yönetici Onayı Bekliyor', 'Yönetici Onayı Bekleniyor')");
    $iconColor = $countPending > 0 ? 'text-danger' : 'text-secondary';
    $badge = $countPending > 0 ? '<span class="badge bg-danger ms-2">' . $countPending . '</span>' : '';
    ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="teklifsiparisler.php?status=<?= urlencode('Yönetici Onayı Bekleniyor') ?>" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-time-five fs-1 <?= $iconColor ?>"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Onay Bekleyenler <?= $badge ?></h6>
                    <p class="mb-0 small text-muted">Özel teklif onaylarını yönet</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 5. Teklif / Sipariş Oluştur -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="teklif-olustur.php?new_offer=1" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-plus-circle fs-1 text-primary"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Teklif / Sipariş Oluştur</h6>
                    <p class="mb-0 small text-muted">Yeni teklif veya sipariş oluştur</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 6. Teklifler / Siparişler -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="teklifsiparisler.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-list-ul fs-1 text-success"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Teklifler / Siparişler</h6>
                    <p class="mb-0 small text-muted">Tüm teklif ve siparişleri listele</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 7. Logo Aktarım -->
    <?php 
    $ltColor = $logoTransferWait > 0 ? 'text-primary' : 'text-secondary';
    $ltBadge = $logoTransferWait > 0 ? '<span class="badge bg-primary ms-2">' . $logoTransferWait . '</span>' : '';
    ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="admin_logo_transfer.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn border-primary border-1" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-transfer fs-1 <?= $ltColor ?>"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Logo Aktarım <?= $ltBadge ?></h6>
                    <p class="mb-0 small text-muted">Bekleyen siparişleri Logo'ya aktar</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 8. Satış Performansı -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="satis-performans.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-line-chart fs-1 text-info"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Satış Performansı</h6>
                    <p class="mb-0 small text-muted">Personel satış verilerini yönet</p>
                </div>
            </div>
        </a>
    </div>

    <?php if(isset($raporlar) && $raporlar == 'Evet') { ?>
    <!-- 9. Cari Durum Analizi -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="cari-durum-analiz.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-search-alt fs-1 text-danger"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Cari Durum Analizi</h6>
                    <p class="mb-0 small text-muted">Müşteri risk ve analiz raporları</p>
                </div>
            </div>
        </a>
    </div>

    <!-- 10. Yaşlandırma Raporu -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="yaslandirma_raporu.php" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-time-five fs-1 text-primary"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Yaşlandırma Raporu</h6>
                    <p class="mb-0 small text-muted">Borç/Alacak Yaşlandırma</p>
                </div>
            </div>
        </a>
    </div>
    <?php } ?>

    <!-- 11. Bayi Siparişleri (En sona) -->
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="teklifsiparisler.php?bayi_filter=1" class="text-decoration-none">
            <div class="card shadow-sm h-100 shortcut-btn" style="transition: transform 0.3s;">
                <div class="card-body text-center p-3">
                    <div class="mb-2">
                        <i class="bx bx-store fs-1 text-info"></i>
                    </div>
                    <h6 class="mb-1 fw-semibold text-dark">Bayi Siparişleri</h6>
                    <p class="mb-0 small text-muted">Bayilerden gelen siparişleri listele</p>
                </div>
            </div>
        </a>
    </div>

</div>
<script>
// Hover efekti için
document.querySelectorAll('.shortcut-btn').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px)';
        this.style.boxShadow = '0 .5rem 1rem rgba(0,0,0,.15)';
    });
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '';
    });
});
</script>

<?php
$companiesLast = getSyncTime('companies');
$productsLast  = getSyncTime('products');
?>
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Şirket Senkronizasyonu</h5>
                <p class="small text-muted">Son Güncelleme:
                    <?= $companiesLast ? formatDateTimeTurkish($companiesLast) : '—' ?></p>
                <div class="d-grid gap-2">
                    <a href="sirket_cek.php" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Detayları Görüntüle</a>
                    <button type="button" class="btn btn-success" onclick="triggerSync('companies')"><i class="bx bx-sync me-1"></i>Anlık Güncelle</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-center h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Ürün Senkronizasyonu</h5>
                <p class="small text-muted">Son Güncelleme:
                    <?= $productsLast ? formatDateTimeTurkish($productsLast) : '—' ?></p>
                <div class="d-grid gap-2">
                    <a href="urunler_senkron.php" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Detayları Görüntüle</a>
                    <button type="button" class="btn btn-success" onclick="triggerSync('products')"><i class="bx bx-sync me-1"></i>Anlık Güncelle</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="sync-container" class="card mb-4 d-none">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong id="sync-title"></strong>
        <button type="button" class="btn-close" onclick="hideSync()"></button>
    </div>
    <div class="card-body" style="max-height:300px;overflow:auto">
        <pre id="sync-output" class="mb-0 text-bg-dark p-2"></pre>
    </div>
</div>
<?php if ($userType !== 'Bayi'): 
    $myUserId = $_SESSION['yonetici_id'] ?? 0;
    if ($myUserId > 0) {
        $myTotalOffers = getRowCount($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid = '$myUserId'");
        $myPendingOffers = getRowCount($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid = '$myUserId' AND durum IN ('Teklif Onay Bekleniyor', 'Yönetici Onayı Bekleniyor')");
        $myApprovedOffers = getRowCount($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid = '$myUserId' AND durum = 'Tamamlandı'");
    } else {
        $myTotalOffers = 0; $myPendingOffers = 0; $myApprovedOffers = 0;
    }
?>
<div class="row mb-3">
    <div class="col-12"><h5 class="text-muted mb-3">Sizin İstatistikleriniz</h5></div>
    <?php 
    renderStatCard($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid='$myUserId'", 'Toplam Tekliflerim', 'bx-briefcase-alt-2', 'teklifsiparisler.php?hazirlayan='.$myUserId, 'primary');
    renderStatCard($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid='$myUserId' AND durum IN ('Teklif Onay Bekleniyor', 'Yönetici Onayı Bekleniyor')", 'Onay Bekleyen Tekliflerim', 'bx-time-five', 'teklifsiparisler.php?hazirlayan='.$myUserId.'&status=OnayBekleyen', 'warning');
    renderStatCard($db, "SELECT * FROM ogteklif2 WHERE hazirlayanid='$myUserId' AND durum='Tamamlandı'", 'Tamamlanan Tekliflerim', 'bx-check-double', 'teklifsiparisler.php?hazirlayan='.$myUserId.'&status=Tamamlandi', 'success');
    ?>
</div>
<?php endif; ?>

<h5 class="section-title">Genel Durum</h5>
<div class="row">
<?php

renderStatCard($db, "SELECT * FROM ogteklif2 WHERE durum='Teklif Onay Bekleniyor'", 'Bekleyen Teklifler', 'bx-time', 'teklifsiparisler.php', 'primary');
renderStatCard($db, "SELECT * FROM ogteklif2 WHERE durum!='Tamamlandı' and durum!='Teklif İptal Edildi'", 'Süreçteki Teklifler', 'bx-loader', 'teklifsiparisler.php', 'info');
renderStatCard($db, "SELECT * FROM ogteklif2 WHERE durum='Tamamlandı'", 'Teslim Edilen Siparişler', 'bx-check-circle', 'teklifsiparisler.php', 'success');
renderStatCard($db, "SELECT * FROM ogteklif2 WHERE durum='Teklif İptal Edildi'", 'İptal Siparişler', 'bx-x-circle', 'teklifsiparisler.php', 'danger');
renderStatCard($db, "SELECT * FROM sirket", 'Kayıtlı Şirketler', 'bx-buildings', 'tumsirketler.php', 'secondary');
renderStatCard($db, "SELECT * FROM personel WHERE p_durum='Beklemede'", 'Bekleyen Personeller', 'bx-user', 'beklemedekiuyeler.php', 'warning');
renderStatCard($db, "SELECT * FROM personel WHERE p_durum='Red'", 'Red Edilen Personeller', 'bx-user-voice', 'reddedilenuyeler.php', 'danger');
renderStatCard($db, "SELECT * FROM personel WHERE p_durum='Onaylı'", 'Onaylanan Personeller', 'bx-user-check', '', 'success');
?>
</div>
<div class="row">
    <?php if ($hasStatusData): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <canvas id="statusChart" height="200"></canvas>
    </div>
    <?php endif; ?>
    <?php if ($hasMonthlyData): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <canvas id="monthlyChart" height="200"></canvas>
    </div>
    <?php endif; ?>
</div>
<h5 class="mt-4">Mail İşlemleri</h5>
<div class="row mb-5">
    <?php renderMailCard("mailgonderin.php", "bg-primary", "E-MAİL GÖNDERİN", $yoneticisorgula["mailposta"]); ?>
    <?php renderMailCard("tummailgonderin.php", "bg-info", "TÜM MAİLLERDE E-MAİL GÖNDERİN", $yoneticisorgula["mailposta"]); ?>
</div>
<h5 class="mt-4">Bayi İşlemleri</h5>
<div class="row mb-5">
    <?php renderLinkCard("dealer_register.php", "bg-warning", "Bayi Kayıt Formu", "Başvuru Yap"); ?>
    <?php renderLinkCard("pending_dealers.php", "bg-secondary", "Bekleyen Bayi Hesapları", $pendingDealers . ' bekliyor'); ?>
    <?php renderLinkCard("dealer_list.php", "bg-primary", "Bayi Listesi", $activeDealers . ' aktif'); ?>
    <?php renderLinkCard("dealer_bulk_upload.php", "bg-success", "Toplu Bayi Yükle", "Excel/CSV" ); ?>
</div>
<?php else: ?>
<div class="row mb-5">
    <?php renderLinkCard("siparis-olustur.php?new_offer=1", "bg-primary", "Yeni Sipariş", "Sipariş Oluştur"); ?>
    <?php renderLinkCard("dealer_orders.php", "bg-info", "Siparişlerim", "Tüm Siparişler"); ?>
</div>
<?php if (!empty($recentOrders)): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Son Siparişlerim</h5></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Teklif No</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th class="text-end">Genel Toplam</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['teklifkodu']) ?></td>
                    <td><?= htmlspecialchars($r['tekliftarihi']) ?></td>
                    <td><span class="badge <?= getStatusBadgeClass($r['durum']) ?>" aria-label="<?= htmlspecialchars($r['durum']) ?>"><?= htmlspecialchars($r['durum']) ?></span></td>
                    <?php
                        $amount = (float)$r['eurotutar'];
                        $curCode = 'EUR';
                        if ($amount <= 0) {
                            $amount = (float)$r['dolartutar'];
                            $curCode = $amount > 0 ? 'USD' : 'TRY';
                            if ($curCode === 'TRY') {
                                $amount = (float)$r['tltutar'];
                            }
                        }
                        $curSymbol = $curCode === 'TRY' ? '₺' : ($curCode === 'USD' ? '$' : '€');
                    ?>
                    <td class="text-end">
                        <span class="order-total" data-value="<?= number_format($amount, 2, '.', '') ?>" data-currency="<?= $curCode ?>">
                            <?= number_format($amount, 2, ',', '.') ?> <?= $curSymbol ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="offer_detail.php?te=<?= (int)$r['id'] ?>&sta=Sipariş">Görüntüle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($campaigns)): ?>
<div class="card mb-5">
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
<?php endif; ?>
<?php endif; ?>
</div>
</div>
<?php include "menuler/footer.php"; ?>
</div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<script>
var statusCanvas = document.getElementById('statusChart');
if (statusCanvas) {
var statusCtx = statusCanvas.getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Sipariş Onay Bekliyor','Teklif Onay Bekliyor','Kontrol Aşamasında','Beklemede','Hazırlanıyor','Eksik Malzeme Tedariği','Sevkiyatta','Araç Yolda'],
        datasets: [{
            data: [<?php echo $siparisOnayBekleyen; ?>,<?php echo $teklifOnayBekleyen; ?>,<?php echo $kontrolAsamasinda; ?>,<?php echo $beklemede; ?>,<?php echo $hazirlaniyor; ?>,<?php echo $eksikMalzeme; ?>,<?php echo $sevkiyatta; ?>,<?php echo $aracYolda; ?>],
            backgroundColor:['#4e79a7','#59a14f','#9c755f','#f28e2b','#e15759','#76b7b2','#edc948','#b07aa1']
        }]
    },
    options:{legend:{position:'bottom'}}
});
}
var monthCanvas = document.getElementById('monthlyChart');
if (monthCanvas) {
var monthCtx = monthCanvas.getContext('2d');
var monthLabels = <?php echo json_encode(array_column($monthly,'m')); ?>;
  var monthData = <?php echo json_encode(array_map('intval',array_column($monthly,'c'))); ?>;
  new Chart(monthCtx, {
      type: 'line',
      data: {labels: monthLabels, datasets:[{label:'Aylık Sipariş', data: monthData, fill:true, backgroundColor:'rgba(0,123,255,0.2)', borderColor:'#007bff'}]},
      options:{scales:{yAxes:[{ticks:{beginAtZero:true}}]}}
  });
}

document.querySelectorAll('.order-total').forEach(function(el){
    var val = parseFloat(el.dataset.value);
    var cur = el.dataset.currency;
    try {
        var fmt = new Intl.NumberFormat('tr-TR',{style:'currency',currency:cur});
        var text = fmt.format(val);
        el.textContent = text;
        el.setAttribute('aria-label','Genel Toplam: '+text);
        el.setAttribute('title','Genel Toplam: '+text);
        el.setAttribute('data-bs-toggle','tooltip');
    } catch(e) {}
});

$(document).ready(function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

function hideSync(){
  document.getElementById('sync-container').classList.add('d-none');
}

function triggerSync(type){
  const out = document.getElementById('sync-output');
  const box = document.getElementById('sync-container');
  let label;
  if(type === 'companies') label = 'Şirket';
  else label = 'Ürün';
  document.getElementById('sync-title').innerText = label + ' Güncelleme Logu';
  out.textContent = 'Çalıştırılıyor...';
  box.classList.remove('d-none');
  fetch('run_sync.php?type=' + type)
    .then(r => r.text())
    .then(text => {
      out.textContent = text;
      out.scrollTop = out.scrollHeight;
      setTimeout(()=>location.reload(),1000);
    })
    .catch(err => {
      out.textContent = 'Hata: ' + err;
    });
}
</script>
</body>
</html>
