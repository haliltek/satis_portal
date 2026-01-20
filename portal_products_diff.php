<?php
include "fonk.php";
oturumkontrol();
require_once __DIR__ . '/services/LoggerService.php';
$logger = new LoggerService(__DIR__ . '/error.log');

function valuesEqual($a, $b) {
    if (is_numeric($a) && is_numeric($b)) {
        return (float)$a == (float)$b;
    }
    return (string)$a === (string)$b;
}

$config = require __DIR__ . '/config/config.php';
$mysqlHost = $config['db']['host'];
$mysqlUser = $config['db']['user'];
$mysqlPass = $config['db']['pass'];
$mysqlDB   = $config['db']['name'];
$mysqlPort = $config['db']['port'] ?? '3306';

$portalHost = $_ENV['GEMAS_WEB_HOST'];
$portalUser = $_ENV['GEMAS_WEB_USER'];
$portalPass = $_ENV['GEMAS_WEB_PASS'];
$portalDb   = $_ENV['GEMAS_WEB_DB'];
$portalPort = $_ENV['GEMAS_WEB_PORT'] ?? '3306';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $mysql = new PDO("mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDB};charset=utf8", $mysqlUser, $mysqlPass, $options);
} catch (PDOException $e) {
    die("MySQL bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

try {
    $portal = new PDO("mysql:host={$portalHost};port={$portalPort};dbname={$portalDb};charset=utf8", $portalUser, $portalPass, $options);
} catch (PDOException $e) {
    die("Portal DB bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

$portalCols = $portal->query('SHOW COLUMNS FROM portal_urunler')->fetchAll(PDO::FETCH_COLUMN);
$portalInsertCols = array_filter($portalCols, fn($c) => $c !== 'urun_id');
$portalUpdateCols = array_filter($portalInsertCols, fn($c) => !in_array($c, ['stokkodu','durum','last_updated']));

$placeholdersPortal = implode(',', array_fill(0, count($portalInsertCols), '?'));
$insertPortalSql = 'INSERT INTO portal_urunler (' . implode(',', $portalInsertCols) . ') VALUES (' . $placeholdersPortal . ')';
$insertPortalStmt = $portal->prepare($insertPortalSql);
$updatePortalSql = 'UPDATE portal_urunler SET ' . implode(',', array_map(fn($c)=>"$c=?", $portalUpdateCols)) . ' WHERE stokkodu=?';
$updatePortalStmt = $portal->prepare($updatePortalSql);
$checkPortalStmt = $portal->prepare('SELECT 1 FROM portal_urunler WHERE stokkodu=? LIMIT 1');

if (isset($_GET['do']) && in_array($_GET['do'], ['insert','update'])) {
    header('Content-Type: application/json; charset=utf-8');
    $type = $_GET['do'];
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['stokkodu'])) {
        echo json_encode(['success'=>false,'error'=>'Veri eksik']);
        exit;
    }
    $code = $data['stokkodu'];
    try {
        $checkPortalStmt->execute([$code]);
        $exists = (bool)$checkPortalStmt->fetchColumn();
        if ($type === 'insert' && !$exists) {
            $values = [];
            foreach ($portalInsertCols as $col) {
                if ($col === 'durum') $values[] = 0;
                elseif ($col === 'last_updated') $values[] = null;
                else $values[] = $data[$col] ?? null;
            }
            $insertPortalStmt->execute($values);
        } elseif ($type === 'update' && $exists) {
            $values = [];
            foreach ($portalUpdateCols as $col) {
                $values[] = $data[$col] ?? null;
            }
            $values[] = $code;
            $updatePortalStmt->execute($values);
        } elseif ($type === 'insert' && $exists) {
            echo json_encode(['success'=>false,'error'=>'Kayıt mevcut']);
            exit;
        } else { // update but not exists
            $values = [];
            foreach ($portalInsertCols as $col) {
                if ($col === 'durum') $values[] = 0;
                elseif ($col === 'last_updated') $values[] = null;
                else $values[] = $data[$col] ?? null;
            }
            $insertPortalStmt->execute($values);
        }
        $logger->log("{$type} success for {$code}");
        echo json_encode(['success'=>true]);
    } catch (PDOException $e) {
        $logger->log("{$type} error for {$code}: " . $e->getMessage(), 'ERROR');
        echo json_encode(['success'=>false,'error'=>'DB error']);
    }
    exit;
}

$localRows = $mysql->query('SELECT * FROM urunler')->fetchAll(PDO::FETCH_ASSOC);
$portalRows = $portal->query('SELECT * FROM portal_urunler')->fetchAll(PDO::FETCH_ASSOC);
$portalMap = [];
foreach ($portalRows as $r) {
    $portalMap[$r['stokkodu']] = $r;
}

$newRows = [];
$updateRows = [];
$diffs = [];
foreach ($localRows as $row) {
    $code = $row['stokkodu'];
    if (!isset($portalMap[$code])) {
        $newRows[] = $row;
    } else {
        $remote = $portalMap[$code];
        $diff = [];
        foreach (['fiyat','satinalma_fiyat','export_fiyat'] as $col) {
            $localVal = $row[$col] ?? null;
            $remoteVal = $remote[$col] ?? null;
            if (!valuesEqual($localVal, $remoteVal)) {
                $diff[$col] = ['portal'=>$remoteVal,'local'=>$localVal];
            }
        }
        if ($diff) {
            $updateRows[] = $row;
            $diffs[$code] = $diff;
        }
    }
}

$jsonNew = json_encode($newRows, JSON_UNESCAPED_UNICODE);
$jsonUpdate = json_encode($updateRows, JSON_UNESCAPED_UNICODE);
$jsonDiffs = json_encode($diffs, JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Portal Ürün Farkları</title>
  <link href="assets/css/app.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
  <style>
    body { background:#f8f9fa; }
    #sync-btn { position:fixed; bottom:20px; right:20px; z-index:1000; }
    .icon { margin-left:8px; }
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
  <div class="card-header"><h4 class="card-title mb-0">🆕 Portalda Olmayanlar (<span id="new-count"></span>)</h4></div>
  <div class="card-body table-responsive">
    <table id="new-table" class="table table-bordered table-striped table-hover">
      <thead class="table-light">
        <tr><th>İkon</th><th>Stok Kodu</th><th>Stok Adı</th><th>Fiyat</th><th>Export Fiyat</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h4 class="card-title mb-0">✏ Fiyatı Farklı Olanlar (<span id="update-count"></span>)</h4></div>
  <div class="card-body table-responsive">
    <table id="update-table" class="table table-bordered table-striped table-hover">
      <thead class="table-light">
        <tr><th>İkon</th><th>Stok Kodu</th><th>Stok Adı</th><th>Fiyat</th><th>Export Fiyat</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div id="diff-card" class="card mb-4">
  <div class="card-header"><h4 class="card-title mb-0">⚠️ Fark Detayları</h4></div>
  <div class="card-body p-0">
    <div class="accordion" id="diff-accordion"></div>
  </div>
</div>

<button id="sync-btn" class="btn btn-primary">🔄 Farkları Onayla ve Güncelle</button>
<script>
const newRows = <?= $jsonNew ?>;
const updateRows = <?= $jsonUpdate ?>;
const diffs = <?= $jsonDiffs ?>;

function escHtml(str){
  return String(str).replace(/&/g,'&amp;')
    .replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

document.getElementById('new-count').textContent = newRows.length;
document.getElementById('update-count').textContent = updateRows.length;

const newBody = document.querySelector('#new-table tbody');
const updBody = document.querySelector('#update-table tbody');
const diffContainer = document.getElementById('diff-accordion');

if(newRows.length===0){
  newBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Kayit yok</td></tr>';
}
if(updateRows.length===0){
  updBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Kayit yok</td></tr>';
}

newRows.forEach((r,i)=>{
  const tr=document.createElement('tr');
  tr.innerHTML=`<td class="icon" data-index="${i}">—</td><td>${escHtml(r.stokkodu)}</td><td>${escHtml(r.stokadi)}</td><td>${escHtml(r.fiyat)}</td><td>${escHtml(r.export_fiyat)}</td>`;
  newBody.appendChild(tr);
});

updateRows.forEach((r,i)=>{
  const tr=document.createElement('tr');
  tr.innerHTML=`<td class="icon" data-index="${i}">—</td><td>${escHtml(r.stokkodu)}</td><td>${escHtml(r.stokadi)}</td><td>${escHtml(r.fiyat)}</td><td>${escHtml(r.export_fiyat)}</td>`;
  updBody.appendChild(tr);
});

let idx=0;
for(const code in diffs){
  const item=document.createElement('div');
  item.className='accordion-item';
  item.innerHTML=`<h2 class="accordion-header" id="h${idx}">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c${idx}" aria-expanded="false" aria-controls="c${idx}">${escHtml(code)}</button>
  </h2>
  <div id="c${idx}" class="accordion-collapse collapse" aria-labelledby="h${idx}" data-bs-parent="#diff-accordion">
    <div class="accordion-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light"><tr><th>Kolon</th><th>Portal</th><th>Local</th></tr></thead>
          <tbody>
            ${Object.entries(diffs[code]).map(([k,v])=>`<tr><td>${escHtml(k)}</td><td class="text-primary">${escHtml(v.portal)}</td><td class="text-danger">${escHtml(v.local)}</td></tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>
  </div>`;
  diffContainer.appendChild(item);
  idx++;
}

function setIcon(table, index, status){
  const td=document.querySelector(`#${table}-table .icon[data-index="${index}"]`);
  if(td) td.textContent = status==='loading' ? '⏳' : (status==='done' ? '✓' : '✗');
}

async function processRow(type, data, index){
  setIcon(type==='insert'?'new':'update', index, 'loading');
  try{
    const res = await fetch('?do='+type,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const j = await res.json();
    if(j.success){ setIcon(type==='insert'?'new':'update', index, 'done'); }
    else setIcon(type==='insert'?'new':'update', index, 'error');
  }catch(e){ setIcon(type==='insert'?'new':'update', index, 'error'); }
}

document.getElementById('sync-btn').addEventListener('click', async function(){
  const btn = this;
  btn.disabled = true;
  for(let i=0;i<newRows.length;i++) await processRow('insert', newRows[i], i);
  for(let i=0;i<updateRows.length;i++) await processRow('update', updateRows[i], i);
  btn.textContent = '✅ Tamamlandı';
});
</script>
        </div>
      </div>
      <?php include "menuler/footer.php"; ?>
    </div>
  </div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function(){
  $('#new-table').DataTable({pageLength:100,language:{url:'assets/libs/datatables.net/i18n/tr.json'},destroy:true});
  $('#update-table').DataTable({pageLength:100,language:{url:'assets/libs/datatables.net/i18n/tr.json'},destroy:true});
});
</script>
</body>
</html>
