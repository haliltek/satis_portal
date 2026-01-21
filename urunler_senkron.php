<?php
include "fonk.php";
oturumkontrol();
require_once __DIR__ . '/services/LoggerService.php';
$logger = new LoggerService(__DIR__ . '/error.log');
// Hata raporlamayı etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * .env dosyasını okumak için basit bir fonksiyon.
 */
function parseEnvFile(string $path): array
{
  $vars = [];
  if (!file_exists($path)) {
    return $vars;
  }
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $vars[trim($key)] = trim($value);
  }
  return $vars;
}

$env = parseEnvFile(__DIR__ . '/.env');

// MySQL bağlantı bilgileri
$mysqlHost = $env['DB_HOST']   ?? 'localhost';
$mysqlUser = $env['DB_USER']   ?? 'root';
$mysqlPass = $env['DB_PASS']   ?? '';
$mysqlDB   = $env['DB_NAME']   ?? '';
$mysqlPort = $env['DB_PORT']   ?? '3306';

// MSSQL bağlantı bilgileri
$mssqlHost = '192.168.5.253,1433';
$mssqlUser = 'halil';
$mssqlPass = '12621262';

/**
 * Türkçe karakter düzeltme fonksiyonu.
 */
function karakterTr($text)
{
  $text   = trim($text);
  $search = ['Ð', 'Þ', 'Ý', 'Ä°', 'ãœ', 'ã‡', 'ä°', 'þ', 'Ì', 'ð', 'Ý', 'ý'];
  $replace = ['Ğ', 'Ş', 'ı', 'İ', 'Ü', 'Ç', 'İ', 'ş', 'Ü', 'ğ', 'ı', 'ı'];
  return str_replace($search, $replace, $text);
}

// PDO bağlantıları
try {
  $dsnGEMPA = "sqlsrv:Server=$mssqlHost;Database=GEMPA2026";
  $optionsGEMPA = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
  if (
    extension_loaded('pdo_sqlsrv')
    && defined('PDO::SQLSRV_ATTR_ENCODING')
    && defined('PDO::SQLSRV_ENCODING_UTF8')
  ) {
    $optionsGEMPA[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
  }
  $baglantiGEMPA = new PDO($dsnGEMPA, $mssqlUser, $mssqlPass, $optionsGEMPA);
} catch (PDOException $e) {
  die("GEMPA bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

try {
  $dsnGEMAS = "sqlsrv:Server=$mssqlHost;Database=GEMAS2026";
  $optionsGEMAS = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
  if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
    $optionsGEMAS[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
  }
  $baglantiGEMAS = new PDO($dsnGEMAS, $mssqlUser, $mssqlPass, $optionsGEMAS);
} catch (PDOException $e) {
  die("GEMAS bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

try {
  $dsnMySQL = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDB};charset=utf8";
  $optionsMySQL = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ];
  $baglantiMySQL = new PDO($dsnMySQL, $mysqlUser, $mysqlPass, $optionsMySQL);
} catch (PDOException $e) {
  die("MySQL bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

// Portal (remote) MySQL connection
try {
  $portalHost = $_ENV['GEMAS_WEB_HOST'];
  $portalUser = $_ENV['GEMAS_WEB_USER'];
  $portalPass = $_ENV['GEMAS_WEB_PASS'];
  $portalDb   = $_ENV['GEMAS_WEB_DB'];
  $portalPort = $_ENV['GEMAS_WEB_PORT'] ?? '3306';
  $dsnPortal  = "mysql:host={$portalHost};port={$portalPort};dbname={$portalDb};charset=utf8";
  $baglantiPortal = new PDO($dsnPortal, $portalUser, $portalPass, $optionsMySQL);
  $portalCols = $baglantiPortal->query('SHOW COLUMNS FROM portal_urunler')->fetchAll(PDO::FETCH_COLUMN);
  $portalInsertCols = array_filter($portalCols, fn($c) => $c !== 'urun_id');
  $portalUpdateCols = array_filter($portalInsertCols, fn($c) => !in_array($c, ['stokkodu','durum','last_updated']));
  $placeholdersPortal = implode(',', array_fill(0, count($portalInsertCols), '?'));
  $insertPortalSql = 'INSERT INTO portal_urunler (' . implode(',', $portalInsertCols) . ') VALUES (' . $placeholdersPortal . ')';
  $insertPortalStmt = $baglantiPortal->prepare($insertPortalSql);
  $updatePortalSql = 'UPDATE portal_urunler SET ' . implode(',', array_map(fn($c)=>"$c=?", $portalUpdateCols)) . ' WHERE stokkodu=?';
  $updatePortalStmt = $baglantiPortal->prepare($updatePortalSql);
  $checkPortalStmt = $baglantiPortal->prepare('SELECT 1 FROM portal_urunler WHERE stokkodu=? LIMIT 1');
} catch (PDOException $e) {
} catch (PDOException $e) {
  // Portal DB hatası oluşursa, siteyi kilitleme. Sadece logla ve null yap.
  error_log("Portal DB bağlantı hatası (Atlandı): " . $e->getMessage());
  $baglantiPortal = null;
  $insertPortalStmt = null;
  $updatePortalStmt = null;
  $checkPortalStmt  = null;
}

/**
 * AJAX endpoint: tek tek INSERT veya UPDATE işlemi.
 */
if (isset($_GET['do']) && in_array($_GET['do'], ['insert', 'update'])) {
  header('Content-Type: application/json; charset=utf-8');
  $type = $_GET['do'];
  // Gelen POST verileri
  $data = json_decode(file_get_contents('php://input'), true);
  if (!$data || !isset($data['stokkodu'])) {
    $logger->log('AJAX verisi hatalı veya stokkodu eksik', 'ERROR');
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri.']);
    exit;
  }

  $stokKodu       = $data['stokkodu'];
  $stokAdi        = $data['stokadi'];
  $anaBirimKodu   = $data['ana_birim_kodu'];
  $anaBirimAdi    = $data['ana_birim_adi'];
  $miktar         = $data['miktar'];
  $fiyat          = $data['fiyat'];
  $satinalmaFiyat = $data['satinalma_fiyat'];
  $exportFiyat    = $data['export_fiyat'];
  $doviz          = $data['doviz'];
  $gempaLogical   = $data['GEMPA2026LOGICAL'];
  $gemasLogical   = $data['GEMAS2026LOGICAL'];
  $logoActive     = isset($data['logo_active']) ? (int)$data['logo_active'] : 0;

  if ($type === 'insert') {
    try {
      $stmt = $baglantiMySQL->prepare(
              "INSERT INTO urunler
              (GEMPA2026LOGICAL, GEMAS2026LOGICAL, stokkodu, stokadi, olcubirimi, miktar, fiyat,
               satinalma_fiyat, export_fiyat, doviz, logo_active, guncelleme, zaman)
              VALUES
              (:GEMPA, :GEMAS, :stokkodu, :stokadi, :olcu, :miktar, :fiyat,
               :satinalma, :exportFiyat, :doviz, :logo_active, :guncelleme, :zaman)");
      $stmt->execute([
        ':GEMPA'       => $gempaLogical,
        ':GEMAS'       => $gemasLogical,
        ':stokkodu'    => $stokKodu,
        ':stokadi'     => $stokAdi,
        ':olcu'        => $anaBirimKodu,
        ':miktar'      => $miktar,
        ':fiyat'       => $fiyat,
        ':satinalma'   => $satinalmaFiyat,
        ':exportFiyat' => $exportFiyat,
        ':doviz'       => $doviz,
        ':logo_active' => $logoActive,
        ':guncelleme'  => 1,
        ':zaman'       => date('Y-m-d H:i:s')
      ]);
      $logger->log("Insert success for $stokKodu");


      if ($baglantiPortal && $checkPortalStmt && $updatePortalStmt && $insertPortalStmt) {
        $checkPortalStmt->execute([$stokKodu]);
        if ($checkPortalStmt->fetchColumn()) {
          $valuesUp = [];
          foreach ($portalUpdateCols as $col) {
            $valuesUp[] = $data[$col] ?? null;
          }
          $valuesUp[] = $stokKodu;
          $updatePortalStmt->execute($valuesUp);
        } else {
          $valuesIns = [];
          foreach ($portalInsertCols as $col) {
            if ($col === 'durum') {
              $valuesIns[] = 0;
            } elseif ($col === 'last_updated') {
              $valuesIns[] = null;
            } else {
              $valuesIns[] = $data[$col] ?? null;
            }
          }
          $insertPortalStmt->execute($valuesIns);
        }
      }

      echo json_encode(['success' => true]);
    } catch (PDOException $e) {
      $logger->log('Insert error for ' . $stokKodu . ': ' . $e->getMessage(), 'ERROR');
      echo json_encode(['success' => false, 'error' => 'DB error']);
    }
    exit;
  }

  if ($type === 'update') {
    try {
      $stmt = $baglantiMySQL->prepare(
              "UPDATE urunler SET
                GEMPA2026LOGICAL = :GEMPA,
                GEMAS2026LOGICAL = :GEMAS,
                stokadi         = :stokadi,
                olcubirimi      = :olcu,
                miktar          = :miktar,
                fiyat           = :fiyat,
                satinalma_fiyat = :satinalma,
                export_fiyat    = :exportFiyat,
                doviz           = :doviz,
                logo_active     = :logo_active,
                guncelleme      = 1,
                zaman           = :zaman
              WHERE stokkodu = :stokkodu
          ");
      $stmt->execute([
        ':GEMPA'       => $gempaLogical,
        ':GEMAS'       => $gemasLogical,
        ':stokadi'     => $stokAdi,
        ':olcu'        => $anaBirimKodu,
        ':miktar'      => $miktar,
        ':fiyat'       => $fiyat,
        ':satinalma'   => $satinalmaFiyat,
        ':exportFiyat' => $exportFiyat,
        ':doviz'       => $doviz,
        ':logo_active' => $logoActive,
        ':zaman'       => date('Y-m-d H:i:s'),
        ':stokkodu'    => $stokKodu
      ]);
      $logger->log("Update success for $stokKodu");


      if ($baglantiPortal && $checkPortalStmt && $updatePortalStmt && $insertPortalStmt) {
        $checkPortalStmt->execute([$stokKodu]);
        if ($checkPortalStmt->fetchColumn()) {
          $valuesUp = [];
          foreach ($portalUpdateCols as $col) {
            $valuesUp[] = $data[$col] ?? null;
          }
          $valuesUp[] = $stokKodu;
          $updatePortalStmt->execute($valuesUp);
        } else {
          $valuesIns = [];
          foreach ($portalInsertCols as $col) {
            if ($col === 'durum') {
              $valuesIns[] = 0;
            } elseif ($col === 'last_updated') {
              $valuesIns[] = null;
            } else {
              $valuesIns[] = $data[$col] ?? null;
            }
          }
          $insertPortalStmt->execute($valuesIns);
        }
      }

      echo json_encode(['success' => true]);
    } catch (PDOException $e) {
      $logger->log('Update error for ' . $stokKodu . ': ' . $e->getMessage(), 'ERROR');
      echo json_encode(['success' => false, 'error' => 'DB error']);
    }
    exit;
  }
}

// ————————— Verileri toplayalım —————————

// MSSQL’den veri çekimi
$sql = "
    SELECT DISTINCT
        I.LOGICALREF           AS LOGICALREF,
        I.CODE                 AS CODE,
        I.NAME                 AS NAME,
        USL.CODE               AS ANA_BIRIM_KODU,
        USL.NAME               AS ANA_BIRIM_ADI,
        ISNULL(
            (SELECT TOP 1 ONHAND
             FROM LV_566_01_GNTOTST
             WHERE INVENNO = 0
               AND STOCKREF = I.LOGICALREF),
            0
        )                      AS MIKTAR,
        (CASE 
            WHEN P.CURRENCY = 20 THEN 'EUR'
            WHEN P.CURRENCY = 1  THEN 'USD'
            WHEN P.CURRENCY = 160 THEN 'TL'
            ELSE 'BOS'
        END)                    AS DOVIZ,
        ISNULL(P.PRICE, 0)     AS FIYAT,
        ISNULL(P_SATINALMA.PRICE, 0) AS SATINALMA_FIYAT,
        ISNULL(P_EXPORT.PRICE, 0)    AS EXPORT_FIYAT,
        G.LOGICALREF           AS GEMAS_LOGICAL,
        I.ACTIVE               AS ITEMS_ACTIVE
    FROM LG_566_ITEMS I

    LEFT JOIN LG_566_UNITSETL USL 
        ON USL.UNITSETREF = I.UNITSETREF
       AND USL.MAINUNIT   = 1

    LEFT JOIN LG_566_PRCLIST P 
        ON P.CARDREF = I.LOGICALREF 
        AND P.PTYPE   = 2
        AND (P.CYPHCODE IS NULL OR P.CYPHCODE <> 'EXPORT')
        AND GETDATE() BETWEEN P.BEGDATE AND P.ENDDATE

    LEFT JOIN LG_566_PRCLIST P_SATINALMA 
        ON P_SATINALMA.CARDREF = I.LOGICALREF 
       AND P_SATINALMA.PTYPE   = 1
       AND GETDATE() BETWEEN P_SATINALMA.BEGDATE AND P_SATINALMA.ENDDATE

    LEFT JOIN LG_566_PRCLIST P_EXPORT 
        ON P_EXPORT.CARDREF = I.LOGICALREF 
       AND P_EXPORT.PTYPE   = 2
       AND P_EXPORT.CYPHCODE = 'EXPORT'

    LEFT JOIN [GEMAS2026].[dbo].[LG_526_ITEMS] G
        ON G.CODE = I.CODE

    ORDER BY I.LOGICALREF;
";
$sorgu = $baglantiGEMPA->query($sql);
$rows  = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// RX filtresi + gruplama → “non-duplicate”
$filtered = [];
foreach ($rows as $row) {
  $code = strtoupper($row['CODE']);
  $name = strtoupper($row['NAME']);
  $rxPatterns = ['RXMEDİAPHARMA', 'RX 20', 'RXWEB', 'RX PROG', 'RXETİKET', 'RX EYS', 'RXMPWEB'];
  $skip = false;
  foreach ($rxPatterns as $pat) {
    if (strpos($code, $pat) !== false || strpos($name, $pat) !== false) {
      $skip = true;
      break;
    }
  }
  if ($skip) continue;
  $filtered[] = $row;
}

$grouped = [];
foreach ($filtered as $row) {
  $logicalRef = (int)$row['LOGICALREF'];
  $grouped[$logicalRef][] = $row;
}

$nonDuplicates = [];
foreach ($grouped as $logicalRef => $items) {
  $allowed = [];
  $excluded = [];
  $hasRX = false;
  foreach ($items as $item) {
    if (
      stripos($item["NAME"], 'RXMEDİAPHARMA') !== false ||
      stripos($item["CODE"], 'RX 20') !== false ||
      stripos($item["CODE"], 'RXWEB') !== false ||
      stripos($item["CODE"], 'RX PROG') !== false ||
      stripos($item["CODE"], 'RXETİKET') !== false ||
      stripos($item["CODE"], 'RX EYS') !== false ||
      stripos($item["CODE"], 'RXMPWEB') !== false
    ) {
      $hasRX = true;
      break;
    }
  }
  if ($hasRX) {
    $excluded = $items;
  } else {
    $hasEUR = false;
    foreach ($items as $item) {
      if ($item["DOVIZ"] === "EUR") {
        $hasEUR = true;
        break;
      }
    }
    foreach ($items as $item) {
      if ($hasEUR && $item["DOVIZ"] === "TL") {
        $excluded[] = $item;
      } else {
        $allowed[] = $item;
      }
    }
    $fiyatSet = [];
    foreach ($items as $item) {
      $fmt = number_format((float)$item["FIYAT"], 2, '.', '');
      $fiyatSet[$fmt] = true;
    }
    if (count($fiyatSet) > 1) {
      $allowed = [];
      $excluded = $items;
    }
  }
  if (!empty($allowed)) {
    $selected = reset($allowed);
  } else {
    $selected = reset($items);
  }
  $nonDuplicates[$logicalRef] = $selected;
}

// Mevcut “urunler” tablosundaki veriler
$existingUrunler = [];
$stmtExisting = $baglantiMySQL->query("
    SELECT 
        GEMPA2026LOGICAL,
        GEMAS2026LOGICAL,
        stokkodu,
        stokadi,
        olcubirimi AS ana_birim_kodu,
        miktar,
        fiyat,
        satinalma_fiyat,
        export_fiyat,
        doviz,
        logo_active
    FROM urunler
");
while ($row = $stmtExisting->fetch(PDO::FETCH_ASSOC)) {
  $existingUrunler[$row['stokkodu']] = $row;
}

// “newRows” ve “updateRows” dizilerini oluştur
$newRows    = [];
$updateRows = [];
$diffs      = [];

foreach ($nonDuplicates as $logicalRef => $row) {
  $gempaLogical   = (int)$row['LOGICALREF'];
  $gemasLogical   = isset($row['GEMAS_LOGICAL']) ? (int)$row['GEMAS_LOGICAL'] : 0;
  $stokKodu       = $row['CODE'];
  $rawStokAdi     = karakterTr($row['NAME']);
  $stokAdi        = $rawStokAdi;
  $anaBirimKodu   = $row['ANA_BIRIM_KODU']   ?? '';
  $anaBirimAdi    = $row['ANA_BIRIM_ADI']    ?? '';
  $miktar         = (int)$row['MIKTAR'];
  $fiyat          = number_format((float)$row['FIYAT'], 2, '.', '');
  $satinalmaFiyat = number_format((float)$row['SATINALMA_FIYAT'], 2, '.', '');
  $exportFiyat    = number_format((float)$row['EXPORT_FIYAT'], 2, '.', '');
  $doviz          = $row['DOVIZ'];
  $rowActive = isset($row['ITEMS_ACTIVE']) ? (int)$row['ITEMS_ACTIVE'] : 0;

  if (!isset($existingUrunler[$stokKodu])) {
    // Yeni kayıt
    $newRows[] = [
      'GEMPA2026LOGICAL' => $gempaLogical,
      'GEMAS2026LOGICAL' => $gemasLogical,
      'stokkodu'         => $stokKodu,
      'stokadi'          => $stokAdi,
      'ana_birim_kodu'   => $anaBirimKodu,
      'ana_birim_adi'    => $anaBirimAdi,
      'miktar'           => $miktar,
      'fiyat'            => $fiyat,
      'satinalma_fiyat'  => $satinalmaFiyat,
      'export_fiyat'     => $exportFiyat,
      'doviz'            => $doviz,
      'logo_active'      => $rowActive
    ];
  } else {
    // Güncelleme kontrolü
    $mevcut = $existingUrunler[$stokKodu];
    $farkVar = false;
    $farklar = [];

    $alanlar = [
      'GEMPA2026LOGICAL' => $gempaLogical,
      'GEMAS2026LOGICAL' => $gemasLogical,
      'stokadi'          => $rawStokAdi,
      'ana_birim_kodu'   => $anaBirimKodu,
      'miktar'           => $miktar,
      'fiyat'            => $fiyat,
      'satinalma_fiyat'  => $satinalmaFiyat,
      'export_fiyat'     => $exportFiyat,
      'doviz'            => $doviz,
      'logo_active'      => $rowActive
    ];

    foreach ($alanlar as $sutun => $yeniVal) {
      $mysqlVal = $mevcut[$sutun] ?? null;
      if ((is_numeric($mysqlVal) || is_numeric($yeniVal))
        ? ((float)$mysqlVal !== (float)$yeniVal)
        : ((string)$mysqlVal !== (string)$yeniVal)
      ) {
        $farkVar = true;
        $farklar[$sutun] = [
          'mysql' => ($mysqlVal === null ? 'NULL' : (string)$mysqlVal),
          'yeni'  => ($yeniVal === null  ? 'NULL' : (string)$yeniVal)
        ];
      }
    }

    if ($farkVar) {
      $updateRows[] = [
        'GEMPA2026LOGICAL' => $gempaLogical,
        'GEMAS2026LOGICAL' => $gemasLogical,
        'stokkodu'         => $stokKodu,
        'stokadi'          => $stokAdi,
        'ana_birim_kodu'   => $anaBirimKodu,
        'ana_birim_adi'    => $anaBirimAdi,
        'miktar'           => $miktar,
        'fiyat'            => $fiyat,
        'satinalma_fiyat'  => $satinalmaFiyat,
        'export_fiyat'     => $exportFiyat,
        'doviz'            => $doviz,
        'logo_active'  => $rowActive
      ];
      $diffs[$stokKodu] = $farklar;
    }
  }
}

// JSON olarak embed et
$jsonNew    = json_encode($newRows, JSON_UNESCAPED_UNICODE);
$jsonUpdate = json_encode($updateRows, JSON_UNESCAPED_UNICODE);
$jsonDiffs  = json_encode($diffs,   JSON_UNESCAPED_UNICODE);

?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8" />
  <title><?php echo $sistemayar["title"]; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
  <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
  <link rel="shortcut icon" href="assets/images/favicon.ico">
  <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" />
  <link href="assets/css/icons.min.css" rel="stylesheet" />
  <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" />
  <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
  <link href="assets/css/custom.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
    }

    #sync-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }

    .icon {
      margin-left: 8px;
    }

    .status-icon span[title="Kullanımda"] {
      color: green;
    }

    .status-icon span[title="Kullanım Dışı"] {
      color: red;
    }
  </style>
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
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="card-title mb-0">🆕 Yeni Gelenler (<span id="new-count"></span>)</h4>
    </div>
    <div class="card-body table-responsive">
      <table id="new-table" class="table table-bordered table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th>İkon</th>
            <th>GEMPA2026LOGICAL</th>
            <th>GEMAS2026LOGICAL</th>
            <th>Stok Kodu</th>
            <th>Stok Adı</th>
            <th>Ana Birim Kodu</th>
            <th>Ana Birim Adı</th>
            <th>Miktar</th>
            <th>Satış Fiyatı</th>
            <th>Satınalma Fiyatı</th>
            <th>Export Fiyatı</th>
            <th>Döviz</th>
          </tr>
        </thead>
        <tbody>
          <!-- Satırlar JS ile eklenecek -->
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <h4 class="card-title mb-0">✏ Güncelleme Gerekenler (<span id="update-count"></span>)</h4>
    </div>
    <div class="card-body table-responsive">
      <table id="update-table" class="table table-bordered table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th>İkon</th>
            <th>GEMPA2026LOGICAL</th>
            <th>GEMAS2026LOGICAL</th>
            <th>Stok Kodu</th>
            <th>Stok Adı</th>
            <th>Ana Birim Kodu</th>
            <th>Ana Birim Adı</th>
            <th>Miktar</th>
            <th>Satış Fiyatı</th>
            <th>Satınalma Fiyatı</th>
            <th>Export Fiyatı</th>
            <th>Döviz</th>
            <th>Kullanım Durumu</th>
          </tr>
        </thead>
        <tbody>
          <!-- Satırlar JS ile eklenecek -->
        </tbody>
      </table>
    </div>
  </div>

  <div id="diff-card" class="card mb-4">
    <div class="card-header">
      <h4 class="card-title mb-0">⚠️ Fark Detayları</h4>
    </div>
    <div class="card-body p-0">
      <div class="accordion" id="diff-accordion">
        <!-- JS ile eklenecek -->
      </div>
    </div>
  </div>

  <div id="empty-message" class="alert alert-success text-center d-none my-4">
    Tüm ürünler güncel, senkronize edilecek fark bulunamadı.
  </div>

  <button id="sync-btn" class="btn btn-primary position-fixed bottom-0 end-0 m-4">🔄 Farkları Onayla ve Güncelle</button>

  <script>
    // PHP'den gelen JSON dizileri
    const newRows = <?= $jsonNew    ?>;
    const updateRows = <?= $jsonUpdate ?>;
    const diffs = <?= $jsonDiffs  ?>;

    document.getElementById('new-count').textContent = newRows.length;
    document.getElementById('update-count').textContent = updateRows.length;

    // Tablo ve container referansları
    const newTableBody = document.querySelector('#new-table tbody');
    const updateTableBody = document.querySelector('#update-table tbody');
  const diffContainer = document.getElementById('diff-accordion');
  const diffCard = document.getElementById('diff-card');
  const syncBtn = document.getElementById('sync-btn');
  const emptyMsg = document.getElementById('empty-message');

  if (newRows.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="12" class="text-center text-muted">Yeni ürün bulunamadı.</td>`;
    newTableBody.appendChild(tr);
  }

  if (updateRows.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="13" class="text-center text-muted">Güncelleme gerektiren ürün bulunamadı.</td>`;
    updateTableBody.appendChild(tr);
  }

  if (newRows.length === 0 && updateRows.length === 0) {
    emptyMsg.classList.remove('d-none');
    syncBtn.style.display = 'none';
  }

    // Yeni gelenleri tabloya ekle
    newRows.forEach((row, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="icon" data-index="${idx}">—</td>
        <td>${row.GEMPA2026LOGICAL}</td>
        <td>${row.GEMAS2026LOGICAL}</td>
        <td>${row.stokkodu}</td>
        <td>${row.stokadi}</td>
        <td>${row.ana_birim_kodu}</td>
        <td>${row.ana_birim_adi}</td>
        <td>${row.miktar}</td>
        <td>${row.fiyat}</td>
        <td>${row.satinalma_fiyat}</td>
        <td>${row.export_fiyat}</td>
        <td>${row.doviz}</td>
      `;
      newTableBody.appendChild(tr);
    });

    // Güncelleme gerekenleri tabloya ekle
    updateRows.forEach((row, idx) => {
      // logo_active === 0 → kullanımda (✔️), 1 → kullanım dışı (❌)
      const icon = row.logo_active === 0 ?
        '<span title="Kullanımda">✔️</span>' :
        '<span title="Kullanım Dışı">❌</span>';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="icon" data-index="${idx}">—</td>
        <td>${row.GEMPA2026LOGICAL}</td>
        <td>${row.GEMAS2026LOGICAL}</td>
        <td>${row.stokkodu}</td>
        <td>${row.stokadi}</td>
        <td>${row.ana_birim_kodu}</td>
        <td>${row.ana_birim_adi}</td>
        <td>${row.miktar}</td>
        <td>${row.fiyat}</td>
        <td>${row.satinalma_fiyat}</td>
        <td>${row.export_fiyat}</td>
        <td>${row.doviz}</td>
        <td class="status-icon">${icon}</td>  <!-- ◀︎ İkon hücresi -->
      `;
      updateTableBody.appendChild(tr);
    });

  if (Object.keys(diffs).length === 0) {
    diffCard.style.display = 'none';
  } else {
    let i = 0;
    for (let stokKodu in diffs) {
      const item = document.createElement('div');
      item.className = 'accordion-item';
      item.innerHTML = `
        <h2 class="accordion-header" id="heading${i}">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${i}" aria-expanded="false" aria-controls="collapse${i}">
            ${stokKodu}
          </button>
        </h2>
        <div id="collapse${i}" class="accordion-collapse collapse" aria-labelledby="heading${i}" data-bs-parent="#diff-accordion">
          <div class="accordion-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead class="table-light">
                  <tr><th>Kolon</th><th>MySQL (var)</th><th>Yeni (MSSQL’den)</th></tr>
                </thead>
                <tbody>
                  ${Object.entries(diffs[stokKodu]).map(([kolon, vals]) => `
                    <tr>
                      <td>${kolon}</td>
                      <td class="text-primary">${vals.mysql}</td>
                      <td class="text-danger">${vals.yeni}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>`;
      diffContainer.appendChild(item);
      i++;
    }
  }

    // Ikonları güncelleyen yardımcı fonksiyon
    function setIcon(tableType, index, status) {
      // tableType: 'new' veya 'update'
      // status: 'loading' veya 'done'
      let selector;
      if (tableType === 'new') {
        selector = `#new-table .icon[data-index="${index}"]`;
      } else {
        selector = `#update-table .icon[data-index="${index}"]`;
      }
      const td = document.querySelector(selector);
      if (!td) return;
      td.textContent = status === 'loading' ? '⏳' : '✓';
    }

    // AJAX ile bir tek satırı işleme fonksiyonu
    async function processRow(type, data, index) {
      // type: 'insert' veya 'update'
      setIcon(type === 'insert' ? 'new' : 'update', index, 'loading');
      try {
        const response = await fetch(`?do=${type}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
          setIcon(type === 'insert' ? 'new' : 'update', index, 'done');
        } else {
          td = document.querySelector(
            `${type==='insert' ? '#new-table' : '#update-table'} .icon[data-index="${index}"]`
          );
          td.textContent = '✗';
        }
      } catch (e) {
        td = document.querySelector(
          `${type==='insert' ? '#new-table' : '#update-table'} .icon[data-index="${index}"]`
        );
        td.textContent = '✗';
      }
    }

    // “Farkları Onayla ve Güncelle” butonuna tıklandığında
    syncBtn.addEventListener('click', async () => {
      syncBtn.disabled = true;
      // Yeni kayıtları ekle
      for (let i = 0; i < newRows.length; i++) {
        await processRow('insert', newRows[i], i);
      }
      // Güncelleme gerekenleri güncelle
      for (let i = 0; i < updateRows.length; i++) {
        await processRow('update', updateRows[i], i);
      }
      syncBtn.textContent = '✅ Tüm İşlemler Tamamlandı';
    });

    $(document).ready(function () {
      $('#new-table').DataTable({
        pageLength: 100,
        destroy: true,
        language: { url: 'assets/libs/datatables.net/i18n/tr.json' }
      });
      $('#update-table').DataTable({
        pageLength: 100,
        destroy: true,
        language: { url: 'assets/libs/datatables.net/i18n/tr.json' }
      });
    });
  </script>
        </div> <!-- container-fluid -->
      </div> <!-- page-content -->
      <?php include "menuler/footer.php"; ?>
    </div> <!-- main-content -->
  </div> <!-- layout-wrapper -->

  <script src="assets/libs/jquery/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
</body>

</html>
