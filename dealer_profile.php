<?php
require_once "fonk.php";
oturumkontrol();

if (($_SESSION['user_type'] ?? '') !== 'Bayi') {
    header('Location: anasayfa.php');
    exit;
}

require_once __DIR__ . '/classes/DatabaseManager.php';
use Proje\DatabaseManager;

$dbConfig = [
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'user' => env('DB_USER'),
    'pass' => env('DB_PASS'),
    'name' => env('DB_NAME'),
];
$dbManager = new DatabaseManager($dbConfig);

$dealerId = (int)($_SESSION['yonetici_id'] ?? 0);
$dealer   = $dbManager->getB2bUserById($dealerId);

$company = null;
$companyId = (int)($_SESSION['dealer_company_id'] ?? 0);
if ($companyId) {
    $stmt = $db->prepare('SELECT * FROM sirket WHERE sirket_id = ?');
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$message = '';
if ($dealer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['password_confirm'] ?? '');
    if ($email === '') {
        $message = '<div class="alert alert-danger">E-posta gerekli.</div>';
    } else {
        $data = ['email' => $email];
        if ($password !== '') {
            if ($password !== $confirm || strlen($password) < 6) {
                $message = '<div class="alert alert-danger">Şifreler eşleşmiyor veya çok kısa.</div>';
            } else {
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            }
        }
        if ($message === '') {
            $dbManager->updateB2bUser($dealerId, $data);
            $message = '<div class="alert alert-success">Profil güncellendi.</div>';
            $dealer = $dbManager->getB2bUserById($dealerId);
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Profilim</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
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
            <h4 class="mb-4">Profilim</h4>
            <?= $message ?>
            <?php if ($dealer): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Hesap Bilgilerim</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($dealer['username']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dealer['email']) ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" name="password" class="form-control" minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="password_confirm" class="form-control" minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Kaydet</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($company): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Bağlı Olduğunuz Cari</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr><th>Ünvan</th><td><?= htmlspecialchars($company['s_adi']) ?></td></tr>
                        <tr><th>Cari Kodu</th><td><?= htmlspecialchars($company['s_arp_code']) ?></td></tr>
                        <tr><th>Şirket Türü</th><td><?= htmlspecialchars($company['s_turu'] ?? '') ?></td></tr>
                        <tr><th>Yetkili</th><td><?= htmlspecialchars($company['yetkili'] ?? '') ?></td></tr>
                        <tr><th>Vergi Dairesi</th><td><?= htmlspecialchars($company['s_vd'] ?? '') ?></td></tr>
                        <tr><th>Vergi No</th><td><?= htmlspecialchars($company['s_vno'] ?? '') ?></td></tr>
                        <tr><th>Adres</th><td><?= htmlspecialchars($company['s_adresi']) ?></td></tr>
                        <tr><th>İl/İlçe</th><td><?= htmlspecialchars($company['s_il'] ?? '') ?> / <?= htmlspecialchars($company['s_ilce'] ?? '') ?></td></tr>
                        <tr><th>Ülke Kodu</th><td><?= htmlspecialchars($company['s_country_code'] ?? '') ?></td></tr>
                        <tr><th>Ülke</th><td><?= htmlspecialchars($company['s_country'] ?? '') ?></td></tr>
                        <tr><th>Telefon</th><td><?= htmlspecialchars($company['s_telefonu']) ?></td></tr>
                        <tr><th>Mail</th><td><?= htmlspecialchars($company['mail']) ?></td></tr>
                        <tr><th>Kategori</th><td><?= htmlspecialchars($company['kategori'] ?? '') ?></td></tr>
                        <tr><th>Logo Company</th><td><?= htmlspecialchars($company['logo_company_code'] ?? '') ?></td></tr>
                        <tr><th>Açık Hesap</th><td><?= htmlspecialchars($company['acikhesap']) ?></td></tr>
                        <tr><th>Ödeme Planı</th><td><?= htmlspecialchars(trim(($company['payplan_code'] ?? '') . ' - ' . ($company['payplan_def'] ?? ''))) ?></td></tr>
                        <tr><th>Ticari Grup</th><td><?= htmlspecialchars($company['trading_grp'] ?? '') ?></td></tr>
                        <tr>
                            <td colspan="2" class="text-end">
                                <a href="company_details.php?id=<?= (int)$company['sirket_id'] ?>" class="text-primary">Detaylar için tıklayın</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include "menuler/footer.php"; ?>
</div>
</div>
<div class="rightbar-overlay"></div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
