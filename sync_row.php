<?php
// Tek satırlık şirket senkronizasyonu
ini_set('max_execution_time',0);
header('Content-Type: application/json');
session_start();

// .env dosyasını oku
function parseEnvFile($path) {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $vars[trim($key)] = trim($value);
    }
    return $vars;
}

$env = parseEnvFile(__DIR__ . '/.env');
$mysql_host = $env['DB_HOST'] ?? 'localhost';
$mysql_dbname = $env['DB_NAME'] ?? 'b2bgemascom_teklif';
$mysql_username = $env['DB_USER'] ?? 'root';
$mysql_password = $env['DB_PASS'] ?? '';

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? null;
if (!$code) {
    http_response_code(400);
    echo json_encode(['success'=>false,'msg'=>'Kod eksik']);
    exit;
}
$dataset = $_SESSION['sync_dataset'] ?? [];
if (!isset($dataset[$code])) {
    http_response_code(404);
    echo json_encode(['success'=>false,'msg'=>'Kayıt bulunamadı']);
    exit;
}
$row = $dataset[$code];

if (isset($row['s_country_code'])) {
    $row['s_country_code'] = substr((string)$row['s_country_code'], 0, 5);
}

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $pdo = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $check = $pdo->prepare('SELECT sirket_id FROM sirket WHERE s_arp_code = ? OR internal_reference = ? FOR UPDATE');
    $update = $pdo->prepare('UPDATE sirket SET internal_reference=?, s_adi=?, s_adresi=?, s_il=?, s_ilce=?, s_country_code=?, s_country=?, s_telefonu=?, mail=?, acikhesap=?, payplan_code=?, payplan_def=?, trading_grp=?, logo_company_code=? WHERE s_arp_code=? OR internal_reference=?');
    $insert = $pdo->prepare('INSERT INTO sirket (internal_reference,s_adi,s_arp_code,s_adresi,s_il,s_ilce,s_country_code,s_country,s_telefonu,s_vno,s_vd,yetkili,mail,mailsifre,smtp,port,kategori,acikhesap,logo_company_code,payplan_code,payplan_def,trading_grp) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $pdo->beginTransaction();
    $check->execute([$code, $row['internal_reference']]);
    if ($check->fetchColumn()) {
        $update->execute([
            $row['internal_reference'],
            $row['s_adi'],
            $row['s_adresi'],
            $row['s_il'],
            $row['s_ilce'],
            $row['s_country_code'],
            $row['s_country'],
            $row['s_telefonu'],
            $row['mail'],
            $row['acikhesap'],
            $row['payplan_code'],
            $row['payplan_def'],
            $row['trading_grp'],
            $row['logo_company_code'],
            $code,
            $row['internal_reference']
        ]);
    } else {
        $insert->execute([
            $row['internal_reference'],
            $row['s_adi'],
            $code,
            $row['s_adresi'],
            $row['s_il'],
            $row['s_ilce'],
            $row['s_country_code'],
            $row['s_country'],
            $row['s_telefonu'],
            $row['s_vno'],
            $row['s_vd'],
            $row['yetkili'],
            $row['mail'],
            $row['mailsifre'],
            $row['smtp'],
            $row['port'],
            $row['kategori'],
            $row['acikhesap'],
            $row['logo_company_code'],
            $row['payplan_code'],
            $row['payplan_def'],
            $row['trading_grp']
        ]);
    }
    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
