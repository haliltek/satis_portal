<?php
// Composer autoload dosyasını dahil ediyoruz
require __DIR__ . '/vendor/autoload.php';

try {
    // Tüm banka döviz kurlarını çekiyoruz
    $rates = (new \Ahmeti\BankExchangeRates\Service)->get();
    // Sadece "EUR/TRY" verisini alıyoruz
    $euroRate = $rates['EUR/TRY'] ?? null;
} catch (\Exception $exception) {
    $error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Euro Kuru (EUR/TRY)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 20px;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Euro Kuru (EUR/TRY)</h1>
        <?php if (isset($error)) : ?>
            <p><strong>Hata:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif ($euroRate === null) : ?>
            <p>Euro kuru bilgisi bulunamadı.</p>
        <?php else: ?>
            <?php foreach ($euroRate as $rate): ?>
                <table>
                    <tr>
                        <th>Banka</th>
                        <td><?php echo htmlspecialchars($rate['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Sembol</th>
                        <td><?php echo htmlspecialchars($rate['symbol'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Alış</th>
                        <td><?php echo htmlspecialchars($rate['buy'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Satış</th>
                        <td><?php echo htmlspecialchars($rate['sell'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Zaman</th>
                        <td><?php echo htmlspecialchars($rate['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Açıklama</th>
                        <td><?php echo htmlspecialchars($rate['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
