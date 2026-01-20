<?php
// public/get_items.php

// Hata raporlamasını etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturum başlatma
session_start();

// Güvenlik Kontrolü (Oturum Açmış mı?)
include "../fonk.php";
oturumkontrol();

// Veritabanı bağlantı bilgilerini dahil et
include "../include/vt2.php";

// Fonksiyonları dahil et
include "../include/functions.php"; // Tekrar kullanılabilir fonksiyonlar

// Autoload ve yapılandırmayı dahil et
require_once __DIR__ . '/../vendor/autoload.php';

use Proje\TokenManager;
use Proje\RestClient;

/**
 * Geçerli ve geçerli bir access token'ı alır.
 *
 * @param PDO $pdo Veritabanı bağlantısı
 * @return array Token verisi
 * @throws Exception Token bulunamaz veya süresi dolmuşsa
 */
function getValidAccessToken(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT * FROM api_tokens ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        throw new Exception("Geçerli bir access token bulunamadı. Lütfen token alın.");
    }

    // Token süresini kontrol et
    if (isTokenExpired($tokenData)) {
        throw new Exception("Access token süresi doldu. Lütfen yeni token alın.");
    }

    return $tokenData;
}

// Veritabanına bağlantı oluşturma
try {
    $pdo = new PDO("mysql:host=$db_server;dbname=$db_name;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['status'] = [
        'message' => "Veritabanı bağlantısı başarısız: " . htmlspecialchars($e->getMessage()),
        'class' => 'alert-danger'
    ];
    header("Location: get_items.php");
    exit;
}

// TokenManager ve RestClient sınıflarını oluşturma
$config = require_once __DIR__ . '/../config/config.php';
$tokenManager = new TokenManager($config);
$restClient = new RestClient($tokenManager, $config);

// REST API çağrısı yapma
$apiResponse = null;
$apiError = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Geçerli ve geçerli bir access token al
        $tokenData = getValidAccessToken($pdo);
        $accessToken = $tokenData['access_token'];

        // API çağrısı yap
        $apiResponse = $restClient->get('items'); // Örneğin: 'items' endpoint'i
    } catch (Exception $e) {
        $apiError = $e->getMessage();
        $_SESSION['status'] = [
            'message' => "Hata: " . htmlspecialchars($apiError),
            'class' => 'alert-danger'
        ];
    }
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title>Malzeme Kartları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap Css -->
    <link href="assets_login/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets_login/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f1f3f5;
        }

        .card {
            border-radius: 15px;
        }

        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg">
                        <div class="card-body p-4">
                            <div class="text-center mt-2 mb-4">
                                <h4 class="text-primary">Malzeme Kartları Listesi</h4>
                                <p class="text-muted">Logo REST API üzerinden alınan malzeme kartları</p>
                            </div>
                            <div class="p-2">
                                <!-- Durum Mesajları -->
                                <?php if (isset($_SESSION['status'])): ?>
                                    <div class="alert <?php echo $_SESSION['status']['class']; ?> alert-dismissible fade show" role="alert">
                                        <?php echo $_SESSION['status']['message']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php unset($_SESSION['status']); ?>
                                <?php endif; ?>

                                <!-- API Yanıtları -->
                                <?php if ($apiResponse): ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>API Başarılı!</strong> Malzeme kartları başarıyla alındı.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered mt-3">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Adı</th>
                                                    <th>Açıklama</th>
                                                    <th>Fiyat</th>
                                                    <!-- Diğer gerekli sütunları ekleyin -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($apiResponse['items'] as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($item['price'] ?? ''); ?></td>
                                                        <!-- Diğer sütunlar -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif ($apiError): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <strong>Hata:</strong> <?php echo htmlspecialchars($apiError); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info" role="alert">
                                        Henüz malzeme kartları görüntülenmedi. Token alarak işlemi başlatabilirsiniz.
                                    </div>
                                <?php endif; ?>

                                <!-- İleriye Dönük İşlemler -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="get_token.php" class="btn btn-primary">Token Yönetimi</a>
                                    <a href="anasayfa.php" class="btn btn-secondary">Geri Dön</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JAVASCRIPT -->
        <script src="assets_login/libs/jquery/jquery.min.js"></script>
        <script src="assets_login/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets_login/js/app.js"></script>
    </div>
</body>

</html>
