<?php
// public/get_token.php

// Hata raporlamasını etkinleştir
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturum başlatma
session_start();

// Güvenlik Kontrolü (Oturum Açmış mı?)
include "../fonk.php";
oturumkontrol();

// Veritabanı bağlantı bilgilerini dahil et
include "../include/vt2.php";

// Autoload ve yapılandırmayı dahil et
require_once __DIR__ . '/../vendor/autoload.php';

use Proje\TokenManager;
use Proje\RestClient;

/**
 * Access Token'ı alır, veritabanına kaydeder ve döndürür.
 *
 * @param PDO $pdo Veritabanı bağlantısı
 * @param TokenManager $tokenManager TokenManager sınıfının bir örneği
 * @return array Token verisi
 * @throws Exception Hata oluşursa
 */
function fetchAndStoreAccessToken(PDO $pdo, TokenManager $tokenManager): array
{
    // Token alma işlemi
    $accessTokenData = $tokenManager->getAccessToken();

    // Token bilgilerini veritabanına kaydetme
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens 
            (access_token, token_type, expires_in, refresh_token, client_id, username, firm_no, issued_at, expires_at) 
        VALUES 
            (:access_token, :token_type, :expires_in, :refresh_token, :client_id, :username, :firm_no, :issued_at, :expires_at)
        ON DUPLICATE KEY UPDATE 
            access_token = :access_token,
            token_type = :token_type,
            expires_in = :expires_in,
            refresh_token = :refresh_token,
            client_id = :client_id,
            username = :username,
            firm_no = :firm_no,
            issued_at = :issued_at,
            expires_at = :expires_at
    ");

    $issuedAt = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$accessTokenData['expires_in']} seconds"));

    $stmt->execute([
        ':access_token'   => $accessTokenData['access_token'],
        ':token_type'     => $accessTokenData['token_type'] ?? '',
        ':expires_in'     => $accessTokenData['expires_in'] ?? 3600,
        ':refresh_token'  => $accessTokenData['refresh_token'] ?? '',
        ':client_id'      => $accessTokenData['as:client_id'] ?? '',
        ':username'       => $accessTokenData['userName'] ?? '',
        ':firm_no'        => $accessTokenData['firmNo'] ?? '',
        ':issued_at'      => $issuedAt,
        ':expires_at'     => $expiresAt
    ]);

    return $accessTokenData;
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
    header("Location: get_token.php");
    exit;
}

// TokenManager ve RestClient sınıflarını oluşturma
$config = require_once __DIR__ . '/../config/config.php';
$tokenManager = new TokenManager($config);
$restClient = new RestClient($tokenManager, $config);

// REST API çağrısı yapma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['call_logo_api'])) {
    try {
        // Token alma ve veritabanına kaydetme
        $accessTokenData = fetchAndStoreAccessToken($pdo, $tokenManager);
        $accessToken = $accessTokenData['access_token'] ?? '';

        if ($accessToken) {
            $_SESSION['status'] = [
                'message' => "Access Token başarıyla alındı ve veritabanına kaydedildi.",
                'class' => 'alert-success'
            ];
        } else {
            throw new Exception("Access Token alınamadı.");
        }
    } catch (Exception $e) {
        $_SESSION['status'] = [
            'message' => "Hata: " . htmlspecialchars($e->getMessage()),
            'class' => 'alert-danger'
        ];
    }

    // POST-Redirect-GET yöntemi
    header("Location: get_token.php");
    exit;
}

// En güncel token'ı al
try {
    $stmt = $pdo->prepare("SELECT * FROM api_tokens ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['status'] = [
        'message' => "Hata: " . htmlspecialchars($e->getMessage()),
        'class' => 'alert-danger'
    ];
    $tokenData = null;
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <title>Token Yönetimi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap Css -->
    <link href="assets_login/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets_login/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 15px;
        }

        .btn-lg {
            padding: 15px;
            font-size: 1.2rem;
        }

        textarea {
            resize: none;
        }
    </style>
</head>

<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg">
                        <div class="card-body p-4">
                            <div class="text-center mt-2 mb-4">
                                <h4 class="text-primary">Token Yönetimi</h4>
                                <p class="text-muted">Logo REST API'den Access Token Alınması</p>
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

                                <!-- Token Alma Butonu -->
                                <form method="post" action="get_token.php">
                                    <div class="d-grid">
                                        <button type="submit" name="call_logo_api" class="btn btn-success btn-lg">Access Token Al</button>
                                    </div>
                                </form>

                                <!-- Token Bilgileri -->
                                <?php if ($tokenData): ?>
                                    <hr>
                                    <h5 class="mt-4">Alınan Token Bilgileri</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered mt-3">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Access Token</th>
                                                    <td><textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($tokenData['access_token']); ?></textarea></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Token Tipi</th>
                                                    <td><?php echo htmlspecialchars($tokenData['token_type']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Geçerlilik Süresi</th>
                                                    <td><?php echo htmlspecialchars($tokenData['expires_in']); ?> saniye</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Refresh Token</th>
                                                    <td><?php echo htmlspecialchars($tokenData['refresh_token']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Client ID</th>
                                                    <td><?php echo htmlspecialchars($tokenData['client_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Kullanıcı Adı</th>
                                                    <td><?php echo htmlspecialchars($tokenData['username']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Firma No</th>
                                                    <td><?php echo htmlspecialchars($tokenData['firm_no']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Token Alınma Zamanı</th>
                                                    <td><?php echo htmlspecialchars($tokenData['issued_at']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Token Geçerlilik Bitiş Zamanı</th>
                                                    <td><?php echo htmlspecialchars($tokenData['expires_at']); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <!-- İleriye Dönük İşlemler -->
                                <?php if ($tokenData && strtotime($tokenData['expires_at']) > time()): ?>
                                    <hr>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="get_items.php" class="btn btn-primary">Malzeme Kartlarını Görüntüle</a>
                                        <a href="anasayfa.php" class="btn btn-secondary">Geri Dön</a>
                                    </div>
                                <?php endif; ?>
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
