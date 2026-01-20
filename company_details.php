<?php
// company_details.php - show company information
require_once "fonk.php";
use Proje\DatabaseManager;
oturumkontrol();

$userType = $_SESSION['user_type'] ?? '';
$dealerCompanyId = $_SESSION['dealer_company_id'] ?? 0;

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$company = null;
if ($id > 0) {
    $stmt = $db->prepare('SELECT * FROM sirket WHERE sirket_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $company = $stmt->get_result()->fetch_assoc();
        }
        $stmt->close();
    }
} elseif ($code !== '') {
    $stmt = $db->prepare('SELECT * FROM sirket WHERE s_arp_code = ?');
    if ($stmt) {
        $stmt->bind_param('s', $code);
        if ($stmt->execute()) {
            $company = $stmt->get_result()->fetch_assoc();
        }
        $stmt->close();
    }
}
if ($userType === 'Bayi') {
    if (!$dealerCompanyId || !$company || (int)($company['sirket_id'] ?? 0) !== $dealerCompanyId) {
        $company = null;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Şirket Detayları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="assets/css/icons.min.css" rel="stylesheet"/>
    <link href="assets/css/app.min.css" rel="stylesheet"/>
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
                    <h4 class="mb-4">Şirket Detayları</h4>
                    <?php if (!$company): ?>
                    <div class="alert alert-warning">Şirket bilgisi bulunamadı.</div>
                    <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tr><th>Ünvan</th><td><?= htmlspecialchars($company['s_adi'] ?? '') ?></td></tr>
                                <tr><th>ARP Kodu</th><td><?= htmlspecialchars($company['s_arp_code'] ?? '') ?></td></tr>
                                <tr><th>Şirket Türü</th><td><?= htmlspecialchars($company['s_turu'] ?? '') ?></td></tr>
                                <tr><th>Yetkili</th><td><?= htmlspecialchars($company['yetkili'] ?? '') ?></td></tr>
                                <tr><th>Vergi Dairesi</th><td><?= htmlspecialchars($company['s_vd'] ?? '') ?></td></tr>
                                <tr><th>Vergi No</th><td><?= htmlspecialchars($company['s_vno'] ?? '') ?></td></tr>
                                <tr><th>Adres</th><td><?= htmlspecialchars($company['s_adresi'] ?? '') ?></td></tr>
                                <tr><th>İl / İlçe</th><td><?= htmlspecialchars($company['s_il'] ?? '') ?> / <?= htmlspecialchars($company['s_ilce'] ?? '') ?></td></tr>
                                <tr><th>Ülke Kodu</th><td><?= htmlspecialchars($company['s_country_code'] ?? '') ?></td></tr>
                                <tr><th>Ülke</th><td><?= htmlspecialchars($company['s_country'] ?? '') ?></td></tr>
                                <tr><th>Telefon</th><td><?= htmlspecialchars($company['s_telefonu'] ?? '') ?></td></tr>
                                <tr><th>Mail</th><td><?= htmlspecialchars($company['mail'] ?? '') ?></td></tr>
                                <tr><th>Kategori</th><td><?= htmlspecialchars($company['kategori'] ?? '') ?></td></tr>
                                <tr><th>Logo Company</th><td><?= htmlspecialchars($company['logo_company_code'] ?? '') ?></td></tr>
                                <tr><th>Açık Hesap</th><td><?= htmlspecialchars($company['acikhesap'] ?? '') ?></td></tr>
                                <tr><th>Ödeme Planı</th><td><?= htmlspecialchars(trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''))) ?></td></tr>
                                <tr><th>Ticari Grup</th><td><?= htmlspecialchars($company['trading_grp'] ?? '') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <?php
                        global $dbManager;
                        $orders = $dbManager->getOrdersForCompany((int)$company['sirket_id'], 50);
                    ?>
                    <?php if ($orders): ?>
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Teklif/Siparişler</h5></div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Teklif No</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                        <th class="text-end">Genel Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td><a href="offer_detail.php?te=<?= (int)$o['id'] ?>&sta=Sipariş" target="_blank"><?= htmlspecialchars($o['teklifkodu']) ?></a></td>
                                        <td><?= htmlspecialchars($o['tekliftarihi']) ?></td>
                                        <td><?= htmlspecialchars($o['durum']) ?></td>
                                        <td class="text-end">
                                            <?= number_format((float)$o['geneltoplam'], 2, ',', '.') ?>
                                        </td>
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
    <div class="rightbar-overlay"></div>
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
