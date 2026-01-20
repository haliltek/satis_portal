<?php
// functions.php

/**
 * Veritabanından en güncel access token'ı alır.
 *
 * @param PDO $pdo Veritabanı bağlantısı
 * @return array Token verisi
 * @throws Exception Token bulunamaz veya süresi dolmuşsa
 */
function getLatestToken(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT * FROM api_tokens ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        throw new Exception("Geçerli bir access token bulunamadı. Lütfen token alın.");
    }

    return $tokenData;
}

/**
 * Access token'ın süresinin dolup dolmadığını kontrol eder.
 *
 * @param array $tokenData Token verisi
 * @return bool Süresi dolmuşsa true, değilse false
 */
function isTokenExpired(array $tokenData): bool
{
    return strtotime($tokenData['expires_at']) < time();
}

/**
 * Kullanıcı girdilerini temizler.
 *
 * @param string $data
 * @return string Temizlenmiş veri
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>
