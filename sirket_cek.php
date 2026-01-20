<?php
// sirket_cek.php - LOGO'dan şirket bilgilerini al, farkları göster ve onayla
include "fonk.php";
oturumkontrol();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function parseEnvFile($path){
    $vars=[];
    if(!file_exists($path)) return $vars;
    foreach(file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){
        if(strpos(trim($l),'#')===0||strpos($l,'=')===false) continue;
        [$k,$v]=explode('=',$l,2);
        $vars[trim($k)] = trim($v);
    }
    return $vars;
}

function valuesEqual($a,$b){
    if(is_numeric($a) && is_numeric($b)){
        return (float)$a == (float)$b;
    }
    return (string)$a === (string)$b;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?");
    if(!$stmt) return false;
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}
$env = parseEnvFile(__DIR__.'/.env');

$mssql_hostname      = $env['GEMPA_LOGO_HOST'] ?? 'localhost';
$mssql_dbname        = $env['GEMPA_LOGO_DB'] ?? '';
$mssql_dbname_gemas  = $env['GEMAS_LOGO_DB'] ?? '';
$mssql_username      = $env['GEMPA_LOGO_USER'] ?? '';
$mssql_password      = $env['GEMPA_LOGO_PASS'] ?? '';

$mysql_host     = $env['DB_HOST'] ?? 'localhost';
$mysql_dbname   = $env['DB_NAME'] ?? '';
$mysql_username = $env['DB_USER'] ?? 'root';
$mysql_password = $env['DB_PASS'] ?? '';

try {
    $mssql_dsn = "sqlsrv:Server=$mssql_hostname;Database=$mssql_dbname";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    $mssql_baglanti = new PDO($mssql_dsn, $mssql_username, $mssql_password, $options);

    // GEMAS veritabanı bağlantısı
    $mssql_dsn_gemas = "sqlsrv:Server=$mssql_hostname;Database=$mssql_dbname_gemas";
    $mssql_baglanti_gemas = new PDO($mssql_dsn_gemas, $mssql_username, $mssql_password, $options);
} catch (PDOException $e) {
    die('MSSQL bağlantısı başarısız: ' . htmlspecialchars($e->getMessage()));
}

try {
    $mysql_dsn = "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4";
    $mysql_baglanti = new PDO($mysql_dsn, $mysql_username, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('MySQL bağlantısı başarısız: ' . htmlspecialchars($e->getMessage()));
}

function map_logo_row(array $row): array {
    return [
        'internal_reference' => $row['LOGICALREF'] ?? null,
        's_adi'            => $row['DEFINITION_'] ?? null,
        's_arp_code'       => $row['CODE'] ?? null,
        's_adresi'         => trim(($row['ADDR1'] ?? '') . ' ' . ($row['ADDR2'] ?? '')) ?: null,
        's_il'             => $row['CITY'] ?? null,
        's_ilce'           => null,
        's_country_code'   => isset($row['COUNTRY_CODE']) ? substr((string)$row['COUNTRY_CODE'], 0, 5) : null,
        's_country'        => $row['COUNTRY'] ?? null,
        'trading_grp'      => $row['TRADING_GRP'] ?? null,
        's_telefonu'       => $row['TELNRS1'] ?? null,
        's_vno'            => null,
        's_vd'             => null,
        'yetkili'          => null,
        'mail'             => $row['EMAILADDR'] ?? null,
        'mailsifre'        => null,
        'smtp'             => null,
        'port'             => null,
        'kategori'         => null,
        'acikhesap'        => $row['BAKIYE'] ?? 0,
        'payplan_code'     => $row['PAYPLAN_CODE'] ?? null,
        'payplan_def'      => $row['PAYPLAN_DEF'] ?? null,
        'logo_company_code'=> $row['CODE'] ?? null
    ];
}

$countryCol = columnExists($mssql_baglanti, 'LG_566_CLCARD', 'COUNTRY_CODE')
    ? 'COUNTRY_CODE'
    : (columnExists($mssql_baglanti, 'LG_566_CLCARD', 'COUNTRYCODE') ? 'COUNTRYCODE' : null);
$countryField  = $countryCol ? "CL.$countryCol AS COUNTRY_CODE," : "'' AS COUNTRY_CODE,";
$tradingCol = null;
foreach (['TRADINGGRP','TRADING_GRP'] as $c) {
    if (columnExists($mssql_baglanti, 'LG_566_CLCARD', $c)) { $tradingCol = $c; break; }
}
$tradingField = $tradingCol ? "CL.$tradingCol AS TRADING_GRP," : "'' AS TRADING_GRP,";

// GEMPA tarafındaki şirketler için sorgu
$mssql_sql_gempa = "
    SELECT
        CL.LOGICALREF,
        CL.CODE,
        CL.DEFINITION_,
        CL.ADDR1,
        CL.ADDR2,
        CL.CITY,
        {$countryField}
        CL.COUNTRY,
        {$tradingField}
        CL.TELNRS1,
        CL.EMAILADDR,
        PP.CODE        AS PAYPLAN_CODE,
        PP.DEFINITION_ AS PAYPLAN_DEF,
        BAL.BAKIYE
    FROM LG_566_CLCARD CL
    LEFT JOIN LG_566_PAYPLANS PP ON CL.PAYMENTREF = PP.LOGICALREF
    LEFT JOIN (
        SELECT CLIENTREF,
               SUM(CASE WHEN SIGN = 0 THEN AMOUNT ELSE -AMOUNT END) AS BAKIYE
        FROM LG_566_01_CLFLINE
        WHERE CANCELLED = 0
        GROUP BY CLIENTREF
    ) BAL ON BAL.CLIENTREF = CL.LOGICALREF";

// GEMAS tarafındaki şirketler için sorgu (525 prefixi)
$mssql_sql_gemas = str_replace('LG_566', 'LG_526', $mssql_sql_gempa);

$gempa_codes = [];
$gemas_codes = [];

// MySQL'deki mevcut şirketler (yalnızca gerekli alanlar)
$mysql_map = [];
try {
    $stmtMysql = $mysql_baglanti->query('SELECT s_arp_code,s_adi,s_adresi,s_il,s_ilce,s_country_code,s_country,s_telefonu,s_vno,s_vd,yetkili,mail,mailsifre,smtp,port,kategori,acikhesap,logo_company_code,payplan_code,payplan_def,trading_grp,internal_reference FROM sirket');
    while ($row = $stmtMysql->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($row['s_arp_code'])) {
            continue;
        }
        $mysql_map[$row['s_arp_code']] = $row;
    }
} catch (PDOException $e) {
    die('MySQL sorgusu başarısız: ' . htmlspecialchars($e->getMessage()));
}

// LOGO (Gempa) tarafını işle
$new_records = [];
$update_records = [];
try {
    $stmtGempa = $mssql_baglanti->prepare($mssql_sql_gempa);
    $stmtGempa->execute();
    while ($row = $stmtGempa->fetch(PDO::FETCH_ASSOC)) {
        $code = $row['CODE'] ?? null;
        if (!$code) {
            continue;
        }
        $gempa_codes[$code] = $row['LOGICALREF'] ?? null;

        $mapped = map_logo_row($row);
        if (empty($mapped['s_adi'])) {
            continue; // boş isim varsa atla
        }

        if (!isset($mysql_map[$code])) {
            $new_records[$code] = $mapped;
            continue;
        }

        $existing = $mysql_map[$code];
        $diff = [];
        foreach ($mapped as $k => $v) {
            if ($k === 's_arp_code' || $k === 'logo_company_code') {
                continue;
            }
            $oldVal = $existing[$k] ?? null;
            if (valuesEqual($oldVal, $v)) {
                continue;
            }
            $diff[$k] = ['old' => $oldVal, 'new' => $v];
        }
        if ($diff) {
            $update_records[$code] = ['data' => $mapped, 'diff' => $diff];
        }
    }
} catch (PDOException $e) {
    die('LOGO sorgusu başarısız: ' . htmlspecialchars($e->getMessage()));
}

if (!$gempa_codes) {
    die('MSSQL\'den veri alınamadı.');
}

// GEMAS tarafını işle (525 prefix)
try {
    $stmtGemas = $mssql_baglanti_gemas->prepare($mssql_sql_gemas);
    $stmtGemas->execute();
    while ($row = $stmtGemas->fetch(PDO::FETCH_ASSOC)) {
        $code = $row['CODE'] ?? null;
        if (!$code) {
            continue;
        }
        $gemas_codes[$code] = $row['LOGICALREF'] ?? null;
    }
} catch (PDOException $e) {
    die('GEMAS sorgusu başarısız: ' . htmlspecialchars($e->getMessage()));
}

$missing_list = [];
$all_codes = array_unique(array_merge(array_keys($gempa_codes), array_keys($gemas_codes), array_keys($mysql_map)));
foreach ($all_codes as $c) {
    $gempa = $gempa_codes[$c] ?? null;
    $gemas = $gemas_codes[$c] ?? null;
    $web   = isset($mysql_map[$c]);
    if (!$gempa || !$gemas || !$web) {
        $missing_list[$c] = [
            'gempa' => $gempa,
            'gemas' => $gemas,
            'web'   => $web,
        ];
    }
}

$dataset_arr = $new_records;
foreach ($update_records as $code => $info) {
    $dataset_arr[$code] = $info['data'];
}
$_SESSION['sync_dataset'] = $dataset_arr;
$total_count = count($dataset_arr);

// Hazırlanacak JavaScript verileri
$new_data = array_values($new_records);
$update_data = [];
foreach ($update_records as $code => $info) {
    $diff_html = '<ul>';
    foreach ($info['diff'] as $field => $vals) {
        $old = htmlspecialchars($vals['old'] ?? '', ENT_QUOTES, 'UTF-8');
        $new = htmlspecialchars($vals['new'] ?? '', ENT_QUOTES, 'UTF-8');
        $f   = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
        $diff_html .= "<li>{$f}: <span class=\"text-danger fst-italic\">{$old}</span> → <span class=\"text-success fw-bold\">{$new}</span></li>";
    }
    $diff_html .= '</ul>';
    $existingRef = $mysql_map[$code]['internal_reference'] ?? null;
    $newRef = $info['data']['internal_reference'] ?? null;
    $update_data[] = [
        'code' => $code,
        'internal_reference' => $existingRef ?: $newRef,
        'diff_html' => $diff_html,
        'status' => 'Bekliyor',
        'rowClass' => ''
    ];
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Şirket Senkronizasyonu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
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
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title">Şirket Senkronizasyonu</h1>
                        <div id="sync-area">

<div class="card mb-4">
  <div class="card-header">
    <h4 class="card-title mb-0">🆕 Yeni Kayıtlar (<span><?php echo count($new_records); ?></span>)</h4>
  </div>
  <div class="card-body table-responsive">
<table id="newTable" class="table table-bordered table-striped table-hover">
<thead>
<tr><th>Kod</th><th>Internal Ref</th><th>Ad</th><th>Adres</th><th>İl</th><th>Telefon</th><th>Mail</th><th>Ödeme Planı</th></tr>
</thead>
<tbody></tbody>
</table>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h4 class="card-title mb-0">✏ Güncellenecek Kayıtlar (<span><?php echo count($update_records); ?></span>)</h4>
  </div>
  <div class="card-body">
<div class="table-responsive">
<table id="updateTable" class="table table-bordered table-striped table-hover">
<thead>
<tr><th>Kod</th><th>Internal Ref</th><th>Değişiklikler</th><th>Durum</th></tr>
</thead>
<tbody></tbody>
</table>
</div>
<button id="start-sync" class="btn btn-success position-fixed end-0 m-4" style="bottom:80px;z-index:1050;">Güncelle ve Aktar</button>
<div class="progress my-2 d-none" style="height:20px;">
    <div id="sync-progress" class="progress-bar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="<?php echo $total_count; ?>"></div>
</div>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#missingModal">Eksik Kayıtlar</button>
</div>
</div>

<div class="modal fade" id="missingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Eksik Kayıtlar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr><th>Kod</th><th>Gempa Ref</th><th>Gemas Ref</th><th>Web</th></tr>
          </thead>
          <tbody>
          <?php foreach ($missing_list as $code => $info): ?>
          <tr>
            <td><?php echo htmlspecialchars($code); ?></td>
            <td><?php echo $info['gempa'] ? htmlspecialchars($info['gempa']) : 'Yok'; ?></td>
            <td><?php echo $info['gemas'] ? htmlspecialchars($info['gemas']) : 'Yok'; ?></td>
            <td><?php echo $info['web'] ? 'Var' : 'Yok'; ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>
                    </div><!-- card-body -->
                </div><!-- card -->
            </div><!-- container-fluid -->
        </div><!-- page-content -->
        <?php include "menuler/footer.php"; ?>
    </div><!-- main-content -->
</div><!-- layout-wrapper -->
<div class="rightbar-overlay"></div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
var newData = <?php echo json_encode($new_data, JSON_UNESCAPED_UNICODE); ?>;
var updateData = <?php echo json_encode($update_data, JSON_UNESCAPED_UNICODE); ?>;
$(document).ready(function(){
    var newTable = $('#newTable').DataTable({
        data:newData,
        columns:[
            {data:'s_arp_code'},
            {data:'internal_reference'},
            {data:'s_adi'},
            {data:'s_adresi'},
            {data:'s_il'},
            {data:'s_telefonu'},
            {data:'mail'},
            {data:'payplan_def'}
        ],
        deferRender:true,
        paging:false,
        language:{url:'assets/libs/datatables.net/i18n/tr.json'}
    });
    var updateTable = $('#updateTable').DataTable({
        data:updateData,
        columns:[
            {data:'code'},
            {data:'internal_reference'},
            {data:'diff_html'},
            {data:'status'}
        ],
        columnDefs:[
            {targets:2,orderable:false},
            {targets:3,orderable:false,className:'status-cell'}
        ],
        createdRow:function(row,data){row.dataset.code=data.code; if(data.rowClass) row.classList.add(data.rowClass);},
        deferRender:true,
        paging:false,
        language:{url:'assets/libs/datatables.net/i18n/tr.json'}
    });
});

document.getElementById('start-sync').addEventListener('click',()=>{
    const table=$('#updateTable').DataTable();
    const queue=[];
    updateData.forEach((rec,idx)=>queue.push({code:rec.code,index:idx}));
    newData.forEach(rec=>queue.push({code:rec.s_arp_code,index:null}));

    const progress=document.getElementById('sync-progress');
    const progressContainer=progress.parentElement;
    // Aynı anda birden fazla kaydı işleyerek senkronizasyonu hızlandır
    // 5 adet eşzamanlı istek performans ve stabilite arasında iyi bir denge sunar
    let done=0; let idx=0; const CONCURRENCY=5;
    progressContainer.classList.remove('d-none');
    progress.style.width='0%';
    document.getElementById('start-sync').disabled=true;

    function processItem(item){
        if(item.index!==null){
            let record=updateData[item.index];
            record.status='⏳';
            table.row(item.index).data(record).draw(false);
        }
        return fetch('sync_row.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code:item.code})})
            .then(r=>r.json()).then(json=>{
                if(item.index!==null){
                    let record=updateData[item.index];
                    if(json.success){record.status='✔'; record.rowClass='table-success';}
                    else{record.status='⚠';}
                    table.row(item.index).data(record).draw(false);
                }
            }).catch(()=>{
                if(item.index!==null){
                    let record=updateData[item.index];
                    record.status='✖';
                    table.row(item.index).data(record).draw(false);
                }
            }).finally(()=>{
                done++;
                progress.style.width=((done/queue.length)*100)+'%';
            });
    }

    function next(){
        if(idx>=queue.length){
            alert('Tüm kayıtlar aktarıldı.');
            document.getElementById('start-sync').disabled=false;
            return;
        }
        const batch=[];
        for(let j=0;j<CONCURRENCY && idx<queue.length;j++,idx++){
            batch.push(processItem(queue[idx]));
        }
        Promise.all(batch).then(next);
    }
    next();
});
</script>
</body>
</html>