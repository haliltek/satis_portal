<?php
// musteri-search.php
require_once "fonk.php"; // Veritabanı bağlantısı ve oturum fonksiyonları gibi gerekli dosyalar
oturumkontrol();
header('Content-Type: application/json; charset=utf-8');

// Gelen parametreleri al
$q = $_GET['q'] ?? '';
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$limit = 10;                   // Her sayfada 10 kayıt göster
$offset = ($page - 1) * $limit; // Kaçıncı kayıttan başlayacağımızı hesapla

// Pazar tipini belirle: Önce GET parametresinden, sonra session'dan, sonra yönetici satış tipinden
$pazarTipi = 'yurtici';
if (isset($_GET['pazar_tipi'])) {
    $pazarTipi = ($_GET['pazar_tipi'] === 'yurtdisi') ? 'yurtdisi' : 'yurtici';
} elseif (!empty($_SESSION['pazar_tipi'])) {
    $pazarTipi = $_SESSION['pazar_tipi'];
} else {
    // Eski yöntem: Yönetici satış tipinden kontrol et
    $yonetici_id = $_SESSION['yonetici_id'] ?? 0;
    if ($yonetici_id) {
        $stmtType = $db->prepare("SELECT satis_tipi FROM yonetici WHERE yonetici_id = ?");
        $stmtType->bind_param("i", $yonetici_id);
        $stmtType->execute();
        $salesRow = $stmtType->get_result()->fetch_assoc();
        $salesType = strtolower($salesRow['satis_tipi'] ?? '');
        $stmtType->close();
        if (strpos($salesType, 'dışı') !== false) {
            $pazarTipi = 'yurtdisi';
        }
    }
}

// Pazar tipine göre filtreleme koşulu
// Yurtdışı seçiliyse sadece is_export = 1 olan müşterileri göster
// Yurtiçi seçiliyse is_export = 0 veya NULL olan müşterileri göster
$cond = ($pazarTipi === 'yurtdisi')
    ? "is_export = 1"
    : "(is_export = 0 OR is_export IS NULL)";

// 1) Sorgu: 10 adet şirket getir
// FULLTEXT arama kullan (çok daha hızlı)
if (!empty($q) && strlen($q) >= 2) {
    // FULLTEXT arama - çok hızlı
    $stmt = $db->prepare("
        SELECT sirket_id,
               s_arp_code,
               s_adi
        FROM sirket
        WHERE MATCH(s_adi) AGAINST(? IN BOOLEAN MODE)
          AND $cond
        ORDER BY s_arp_code ASC
        LIMIT ?, ?;
    ");
    $searchTerm = $q . '*'; // Wildcard ekle (başlangıç araması için)
    $stmt->bind_param("sii", $searchTerm, $offset, $limit);
} else {
    // Boş arama - sadece filtre uygula
    $stmt = $db->prepare("
        SELECT sirket_id,
               s_arp_code,
               s_adi
        FROM sirket
        WHERE $cond
        ORDER BY s_arp_code ASC
        LIMIT ?, ?;
    ");
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    // Select2, gelen JSON'da 'id' ve 'text' alanlarını bekler
    $items[] = [
        'id'   => $row['sirket_id'],
        'text' => $row['s_arp_code'] . ' - ' . $row['s_adi']
    ];
}
$stmt->close();

// 2) Toplam kayıt sayısını bul (daha fazla kayıt var mı kontrolü için)
if (!empty($q) && strlen($q) >= 2) {
    $stmt2 = $db->prepare("
        SELECT COUNT(*) AS total
        FROM sirket
        WHERE MATCH(s_adi) AGAINST(? IN BOOLEAN MODE)
          AND $cond
    ");
    $searchTerm = $q . '*';
    $stmt2->bind_param("s", $searchTerm);
} else {
    $stmt2 = $db->prepare("
        SELECT COUNT(*) AS total
        FROM sirket
        WHERE $cond
    ");
}
$stmt2->execute();
$totalRow = $stmt2->get_result()->fetch_assoc();
$total = $totalRow['total'] ?? 0;
$stmt2->close();

// Şu anki sayfanın son kayıt indeksine ulaştıysak, daha fazla var mı?
$more = ($offset + $limit) < $total;

// 3) JSON olarak sonuçları döndür
echo json_encode([
    'results' => $items,
    'pagination' => [
        'more' => $more
    ]
]);
