<?php
include "fonk.php";
oturumkontrol();
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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = intval($_POST['company_id'] ?? 0);
    $cariCode  = trim($_POST['cari_code'] ?? '') ?: null;
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if ($companyId && $username && $email && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $dbManager->createB2bUser(
            companyId: $companyId,
            cariCode: $cariCode,
            username: $username,
            email: $email,
            password: $hash,
            status: 0, // pending
            role: 'dealer'
        );
        $message = '<div class="alert alert-success">Kaydınız alınmıştır. Onay bekleniyor.</div>';
    } else {
        $message = '<div class="alert alert-danger">Lütfen tüm alanları doldurunuz.</div>';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Bayi Kayıt Formu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <?php echo $message; ?>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Bayi Kayıt Formu</h4>
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Şirket</label>
                                        <select id="company_id" name="company_id" class="form-control" style="width:100%" required></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Cari Kodu</label>
                                        <input type="text" name="cari_code" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kullanıcı Adı</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Şifre (en az 6 karakter)</label>
                                        <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Şifre (Tekrar)</label>
                                        <input type="password" id="password_confirm" class="form-control" minlength="6" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">Kaydol</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "menuler/footer.php"; ?>
    </div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
$(function() {
    $('#company_id').select2({
        placeholder: 'Şirket seçiniz',
        allowClear: true,
        ajax: {
            url: 'musteri-search.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination.more }
                };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        var code = e.params.data.text.split(' - ')[0];
        $('input[name="cari_code"]').val(code);
    }).on('select2:clear', function () {
        $('input[name="cari_code"]').val('');
    });

    $('form').on('submit', function(e){
        if ($('#password').val() !== $('#password_confirm').val()) {
            e.preventDefault();
            alert('Şifreler eşleşmiyor');
        }
    });
});
</script>
</body>
</html>
