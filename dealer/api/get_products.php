<?php
// Bayi Ürünleri API (DataTables için)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Giriş kontrolü
if (!isset($_SESSION['yonetici_id']) || ($_SESSION['user_type'] ?? '') !== 'Bayi') {
    echo json_encode(['error' => 'Yetkisiz erişim']);
    exit;
}

include "../../include/vt.php";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

// DataTables parametreleri
$draw = (int)($_POST['draw'] ?? 1);
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 25);
$searchValue = $_POST['search']['value'] ?? '';
$orderColumnIndex = (int)($_POST['order'][0]['column'] ?? 2); // Varsayılan: Ürün Adı (index 2)
$orderDirection = $_POST['order'][0]['dir'] ?? 'asc';

// Ek filtreler
$searchTerm = $_POST['search_term'] ?? '';
$category = $_POST['category'] ?? '';
$brand = $_POST['brand'] ?? '';

// Sütun adları (frontend'deki sıraya göre: Stok Kodu, Resim, Ürün Adı, Kategori, Marka, Birim, Fiyat, Stok, İşlem)
// Resim kolonu sıralanamaz, bu yüzden index'i atlıyoruz
$columns = [
    0 => 'stokkodu',      // Stok Kodu
    1 => null,             // Resim (sıralanamaz)
    2 => 'stokadi',        // Ürün Adı
    3 => 'kat1',           // Kategori
    4 => 'marka',           // Marka
    5 => 'olcubirimi',      // Birim
    6 => 'fiyat',          // Fiyat
    7 => 'miktar'          // Stok
];
$orderColumn = $columns[$orderColumnIndex] ?? 'stokadi';

// WHERE koşulları
$where = ['1=1'];
$params = [];
$types = '';

// Sadece yurtiçi ürünler (fiyat alanı dolu olanlar)
$where[] = "fiyat IS NOT NULL AND fiyat != '' AND fiyat != '0'";

// Arama filtresi
if ($searchValue !== '') {
    $where[] = "(stokkodu LIKE ? OR stokadi LIKE ? OR marka LIKE ?)";
    $searchParam = '%' . $searchValue . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Ek arama terimi
if ($searchTerm !== '') {
    $where[] = "(stokkodu LIKE ? OR stokadi LIKE ?)";
    $searchParam2 = '%' . $searchTerm . '%';
    $params[] = $searchParam2;
    $params[] = $searchParam2;
    $types .= 'ss';
}

// Kategori filtresi (kat1 kullanıyoruz)
if ($category !== '') {
    $where[] = "kat1 = ?";
    $params[] = $category;
    $types .= 's';
}

// Marka filtresi
if ($brand !== '') {
    $where[] = "marka = ?";
    $params[] = $brand;
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

// Toplam kayıt sayısı (filtresiz)
$totalQuery = "SELECT COUNT(*) as total FROM urunler WHERE fiyat IS NOT NULL AND fiyat != '' AND fiyat != '0'";
$totalResult = $db->query($totalQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];

// Filtrelenmiş kayıt sayısı
$filteredQuery = "SELECT COUNT(*) as total FROM urunler WHERE $whereClause";
if (count($params) > 0) {
    $stmt = $db->prepare($filteredQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $filteredRecords = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $filteredRecords = $db->query($filteredQuery)->fetch_assoc()['total'];
}

// Veri çekme (kategori kolonunu kat1 olarak alıyoruz)
// Eğer sıralama kolonu null ise (Resim kolonu), varsayılan olarak stokadi kullan
if (empty($orderColumn) || $orderColumn === null) {
    $orderColumn = 'stokadi';
}

// Güvenlik: Sadece izin verilen kolon adlarını kullan
$allowedColumns = ['stokkodu', 'stokadi', 'kat1', 'marka', 'olcubirimi', 'fiyat', 'miktar'];
if (!in_array($orderColumn, $allowedColumns)) {
    $orderColumn = 'stokadi';
}

// ORDER BY yönünü kontrol et
$orderDirection = strtoupper($orderDirection) === 'DESC' ? 'DESC' : 'ASC';

$dataQuery = "SELECT stokkodu, stokadi, COALESCE(kat1, '') as kategori, COALESCE(marka, '') as marka, 
              COALESCE(olcubirimi, 'Adet') as birim, fiyat, COALESCE(miktar, 0) as stok 
              FROM urunler 
              WHERE $whereClause 
              ORDER BY `$orderColumn` $orderDirection 
              LIMIT ? OFFSET ?";

// Görsel URL oluşturma fonksiyonu
function getProductImageUrl($stokkodu) {
    if (empty($stokkodu)) {
        return 'assets/front/assets/images/unnamed.png'; // Varsayılan görsel
    }
    
    // Stok kodunun ilk 2 karakterini al (klasör adı için)
    $ilkIkiKarakter = substr($stokkodu, 0, 2);
    
    // Base URL - Sadece domain, path olmamalı
    $hostBase = 'https://gemas.com.tr'; // Sabit olarak gemas.com.tr kullan
    
    // Görsel URL'ini oluştur: /public/uploads/images/malzeme/{ilk_2_karakter}/{stok_kodu}.jpg
    $imagePath = '/public/uploads/images/malzeme/' . $ilkIkiKarakter . '/' . $stokkodu . '.jpg';
    return $hostBase . $imagePath;
}

if (count($params) > 0) {
    $stmt = $db->prepare($dataQuery);
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rawData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $db->prepare($dataQuery);
    $stmt->bind_param('ii', $length, $start);
    $stmt->execute();
    $result = $stmt->get_result();
    $rawData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Görsel URL'lerini ekle
$data = [];
foreach ($rawData as $row) {
    $row['image_url'] = getProductImageUrl($row['stokkodu']);
    $data[] = $row;
}

$db->close();

// Yanıt
$response = [
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $data
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

