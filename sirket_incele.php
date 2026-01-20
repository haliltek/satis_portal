<?php
$hostname = '195.175.85.186';
$dbname = 'GEMPA2024';
$username = 'halil';
$password = '12621262';

try {
    // PDO bağlantısı kuruluyor
    $dsn = "sqlsrv:Server=$hostname;Database=$dbname";
    $baglanti = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8 // UTF-8 encoding kullanımı
    ]);
    echo "MSSQL bağlantısı başarılı!<br>";
} catch (PDOException $e) {
    die("MSSQL bağlantısı başarısız: " . htmlspecialchars($e->getMessage()));
}

// SQL sorgusu
$sql = "
    SELECT 
        CL.CODE,
        CL.DEFINITION_,
        CL.ADDR1,
        CL.ADDR2,
        CL.CITY,
        CL.TELNRS1,
        (
            SELECT ISNULL(SUM(
                CASE 
                    WHEN CLF.SIGN = 0 THEN CLF.AMOUNT 
                    ELSE -CLF.AMOUNT 
                END
            ), 0)
            FROM LG_564_01_CLFLINE CLF 
            WHERE CLF.CLIENTREF = CL.LOGICALREF 
              AND CLF.CANCELLED = 0
        ) AS BAKIYE
    FROM 
        LG_564_CLCARD CL
";

try {
    $stmt = $baglanti->prepare($sql);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "MSSQL'den veri çekme başarılı!<br>";
} catch (PDOException $e) {
    die("Sorgu hatası: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Veri Tablosu</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #ddd;
        }
    </style>
</head>

<body>
    <h1>Müşteri Verileri</h1>
    <?php if (count($results) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Açıklama</th>
                    <th>Adres 1</th>
                    <th>Adres 2</th>
                    <th>Şehir</th>
                    <th>Telefon</th>
                    <th>Bakiye</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['CODE']); ?></td>
                        <td><?php echo htmlspecialchars($row['DEFINITION_']); ?></td>
                        <td><?php echo htmlspecialchars($row['ADDR1']); ?></td>
                        <td><?php echo htmlspecialchars($row['ADDR2']); ?></td>
                        <td><?php echo htmlspecialchars($row['CITY']); ?></td>
                        <td><?php echo htmlspecialchars($row['TELNRS1']); ?></td>
                        <td><?php echo number_format($row['BAKIYE'], 2); ?> TL</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Veri bulunamadı.</p>
    <?php endif; ?>
</body>

</html>