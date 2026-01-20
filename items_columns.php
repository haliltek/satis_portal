<?php
// items_columns.php

// Hata raporlamayı geliştirmede açın
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// VERİTABANI BAĞLANTISI
$hostname = '195.175.85.186';
$username = 'halil';
$password = '12621262';
$dbname   = 'GEMPA2026';

try {
    $dsn  = "sqlsrv:Server=$hostname;Database=$dbname";
    $db   = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);
} catch (PDOException $e) {
    die("Bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

// Sütun bilgilerini INFORMATION_SCHEMA üzerinden çekeceğiz
$sql = "
    SELECT 
        COLUMN_NAME,
        DATA_TYPE,
        COALESCE(CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION) AS MAX_LENGTH,
        IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'LG_566_ITEMS'
    ORDER BY ORDINAL_POSITION
";
$stmt    = $db->query($sql);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>LG_566_ITEMS Sütun Listesi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h3>GEMPA2026. LG_566_ITEMS Tablosu Sütunları</h3>
  <table class="table table-bordered table-striped mt-3">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Sütun Adı</th>
        <th>Veri Tipi</th>
        <th>Maks. Uzunluk/Precision</th>
        <th>NULL Olabilir</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($columns as $i => $col): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($col['COLUMN_NAME']) ?></td>
        <td><?= htmlspecialchars($col['DATA_TYPE']) ?></td>
        <td><?= htmlspecialchars($col['MAX_LENGTH']) ?></td>
        <td><?= $col['IS_NULLABLE'] ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <p class="text-muted">Buradan uygun birim sütununu (örneğin PUOMREF, UOMREF vb.) tespit edip ana sorgunuza ekleyebilirsiniz.</p>
</body>
</html>
