<?php
session_start();

require 'config/config.php';
require 'vendor/autoload.php'; // Composer autoload (Guzzle, Dotenv, vs.)
require 'classes/TokenManager.php';

// Oturum kontrolü
if (!isset($_SESSION['yonetici_id'])) {
    header("Location: index.php");
    exit();
}

// Loglama fonksiyonu
function writeLog($message) {
    $logFile = __DIR__ . "/log.txt";
    $currentDateTime = date("Y-m-d H:i:s");
    $formattedMessage = "[$currentDateTime] " . $message . "\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Offset ve limit değerlerini alalım (varsayılan: offset=0, limit=10)
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
writeLog("list-items.php: offset=$offset, limit=$limit");

// Config ayarlarını alalım
$config = require 'config/config.php';

// TokenManager'ı başlatıp access token'ı alalım
$tokenManager = new \Proje\TokenManager($config);
try {
    $tokenData = $tokenManager->getAccessToken();
    $accessToken = $tokenData['access_token'];
} catch (Exception $e) {
    writeLog("Token hatası: " . $e->getMessage());
    die("Token alınırken hata oluştu: " . $e->getMessage());
}

// GuzzleHttp Client'ı, API base URL ile oluşturuyoruz
$client = new \GuzzleHttp\Client([
    'base_uri' => $config['apiBaseUrl'], // Örneğin: http://192.168.5.252:32001/api/v1/
]);

// REST API'ye GET isteği gönderelim
try {
    $response = $client->request('GET', 'items', [
        'query' => [
            'offset' => $offset,
            'limit'  => $limit,
        ],
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json',
        ],
    ]);

    $body = $response->getBody()->getContents();
    writeLog("API Yanıtı: " . $body);

    $data = json_decode($body, true);
    if ($data === null) {
        writeLog("JSON decode hatası: " . $body);
        die("JSON decode hatası oluştu.");
    }
} catch (\GuzzleHttp\Exception\RequestException $e) {
    writeLog("API İstek Hatası: " . $e->getMessage());
    die("API isteği sırasında hata oluştu: " . $e->getMessage());
}

// API yanıtındaki veriyi kontrol edelim
$items = isset($data['items']) ? $data['items'] : [];
$totalCount = isset($data['count']) ? (int)$data['count'] : 0;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Ürün Listesi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <button onclick="history.back()" class="btn btn-outline-light me-2">
                <i class="bi bi-arrow-left-circle"></i> Geri
            </button>
            <a class="navbar-brand" href="#">Ürün Listesi</a>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <div class="container my-5">
        <h4 class="text-primary mb-3">Ürünler</h4>
        
        <!-- Ürünler Tablosu -->
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Kod</th>
                            <th>Adı</th>
                            <th>Açıklama</th>
                            <th>Fiyat</th>
                            <th>VAT</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['INTERNAL_REFERENCE'] ?? $item['id']) ?></td>
                                <td><?= htmlspecialchars($item['CODE'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['NAME'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['DESCRIPTION'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['SORD_AMOUNT_TOLERANCE'] ?? $item['price'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['VAT'] ?? '') ?></td>
                                <td>
                                    <a href="item-detail.php?id=<?= htmlspecialchars($item['INTERNAL_REFERENCE'] ?? $item['id']) ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-info-circle"></i> Detay
                                    </a>
                                    <a href="item-edit.php?id=<?= htmlspecialchars($item['INTERNAL_REFERENCE'] ?? $item['id']) ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i> Düzenle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Sayfa navigasyonu">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($offset == 0) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['offset' => 0])) ?>">
                            <i class="bi bi-chevron-bar-left"></i> İlk
                        </a>
                    </li>
                    <li class="page-item <?= ($offset <= 0) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])) ?>">
                            <i class="bi bi-chevron-left"></i> Önceki
                        </a>
                    </li>
                    <li class="page-item <?= (($offset + $limit) >= $totalCount) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['offset' => $offset + $limit])) ?>">
                            Sonraki <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Hiç ürün bulunamadı veya ürünler alınırken bir hata oluştu.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
