<?php
include "fonk.php";
oturumkontrol();
require_once __DIR__ . '/classes/DatabaseManager.php';
require_once __DIR__ . '/vendor/autoload.php';

use Proje\DatabaseManager;
use PhpOffice\PhpSpreadsheet\IOFactory;

$dbConfig = [
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'user' => env('DB_USER'),
    'pass' => env('DB_PASS'),
    'name' => env('DB_NAME'),
];

$dbManager = new DatabaseManager($dbConfig);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($tmp);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $count = 0;
            foreach ($rows as $idx => $row) {
                if ($idx === 1) { // header
                    continue;
                }
                $companyId = intval($row['A'] ?? 0);
                $cariCode  = trim($row['B'] ?? '') ?: null;
                $username  = trim($row['C'] ?? '');
                $email     = trim($row['D'] ?? '');
                $password  = trim($row['E'] ?? '');
                $role      = trim($row['F'] ?? 'dealer');
                if ($companyId && $username && $email && $password) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $dbManager->createB2bUser(
                        companyId: $companyId,
                        cariCode: $cariCode,
                        username: $username,
                        email: $email,
                        password: $hash,
                        status: 0,
                        role: $role ?: 'dealer'
                    );
                    $count++;
                }
            }
            $message = '<div class="alert alert-success">' . $count . ' kayıt eklendi.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Dosya okunamadı: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Dosya yüklenemedi.</div>';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Toplu Bayi Yükle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
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
                                <h4 class="card-title mb-4">Toplu Bayi Yükle</h4>
                                <p class="text-muted">
                                    Dosya sütunları sırasıyla <strong>company_id</strong>, <strong>cari_code</strong>,
                                    <strong>username</strong>, <strong>email</strong>, <strong>password</strong> ve
                                    <strong>role</strong> olmalıdır. Örnek bir dosyayı
                                    <a href="samples/dealer_bulk_template.csv" download>buradan</a>
                                    indirebilirsiniz.
                                </p>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Excel/CSV Dosyası</label>
                                        <input type="file" name="file" class="form-control" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">Yükle</button>
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
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
