<?php
require_once "fonk.php";
oturumkontrol();

if (($_SESSION['user_type'] ?? '') !== 'Bayi') {
    header('Location: anasayfa.php');
    exit;
}

global $dbManager;
$companyId = (int)($_SESSION['dealer_company_id'] ?? 0);
$cariCode = $_SESSION['dealer_cari_code'] ?? null; // Bayi cari kodu (ör: 120.01.E04)
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$q      = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'id';
$dir    = $_GET['dir'] ?? 'DESC';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$orders = [];
$statuses = [];
$total = 0;
if ($companyId > 0) {
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $rows = $dbManager->listOrdersForCompany($companyId, 1000, 0, $status ?: null, $q, $sort, $dir, $cariCode);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders.csv"');
        echo "Teklif No,Telefon,Tarih,Durum,Genel Toplam\n";
        foreach ($rows as $r) {
            $amt = (float)$r['eurotutar'];
            $cur = '€';
            if ($amt <= 0) {
                $amt = (float)$r['dolartutar'];
                $cur = $amt > 0 ? '$' : '₺';
                if ($cur === '₺') {
                    $amt = (float)$r['tltutar'];
                }
            }
            echo '"'.implode('","', [
                $r['teklifkodu'],
                $r['projeadi'],
                $r['tekliftarihi'],
                $r['durum'],
                number_format($amt, 2, ',', '.') . ' ' . $cur
            ]).'"\n';
        }
        exit;
    }
    $total = $dbManager->countOrdersForCompany($companyId, $status ?: null, $q, $cariCode);
    $orders = $dbManager->listOrdersForCompany($companyId, $limit, $offset, $status ?: null, $q, $sort, $dir, $cariCode);
    $statuses = $dbManager->getAllOrderStatuses();
}

function getStatusBadgeClass(string $status): string
{
    switch ($status) {
        case 'Sipariş Ödeme Alındı / Tamamlandı':
            return 'badge-status-success';
        case 'Teklif Reddedildi':
        case 'Sipariş İptal Edildi':
            return 'badge-status-danger';
        case 'Teklif Oluşturuldu / Gönderilecek':
        case 'Teklif Gönderildi / Onay Bekleniyor':
        case 'Sipariş Onay Bekliyor':
        case 'Teklif Onay Bekleniyor':
        case 'Teklife Revize Talep Edildi / İnceleme Bekliyor':
        case 'Teklif Revize Edildi / Onay Bekleniyor':
            return 'badge-status-warning';
        case 'Sipariş Onaylandı / Logoya Aktarım Bekliyor':
        case 'Sipariş Logoya Aktarıldı / Ödemesi Bekleniyor':
        case 'Sipariş Ödemesi Bekleniyor':
            return 'badge-status-info';
        default:
            return 'badge-status-secondary';
    }
}

function buildSortLink(string $column, string $label, string $currentSort, string $currentDir, array $params): string
{
    $dir = 'ASC';
    if ($currentSort === $column && $currentDir === 'ASC') {
        $dir = 'DESC';
    }
    $params['sort'] = $column;
    $params['dir'] = $dir;
    return '<a href="?' . http_build_query($params) . '" class="text-decoration-none">' . $label . '</a>';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Siparişlerim</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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
            <h4 class="mb-2">Siparişlerim</h4>
            <form class="row row-cols-lg-auto g-2 align-items-end mb-3" method="get" aria-label="Sipariş filtreleme formu">
                <div class="col-12">
                    <label for="status" class="visually-hidden">Durum</label>
                    <select id="status" name="status" class="form-select form-select-sm" aria-label="Duruma göre filtrele">
                        <option value="">Tümü</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= htmlspecialchars($s['durum']) ?>" <?= $status===$s['durum']?'selected':'' ?>><?= htmlspecialchars($s['durum']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="q" class="visually-hidden">Ara</label>
                    <input id="q" name="q" type="search" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="Teklif No veya Telefon ile ara" aria-label="Teklif No veya Telefon ile ara">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
                    <a href="?export=csv&amp;<?= http_build_query(['status'=>$status,'q'=>$q]) ?>" class="btn btn-sm btn-secondary" aria-label="CSV aktar">CSV</a>
                </div>
            </form>
            <div class="mb-2 small text-muted">Sayfa başına <?= $limit ?> kayıt, toplam <?= $total ?> kayıt</div>
            <?php if (!$orders): ?>
            <div class="alert alert-info">Henüz sipariş oluşturmadınız.</div>
            <a href="siparis-olustur.php" class="btn btn-primary btn-sm">Yeni Sipariş</a>
            <?php else: ?>
            <form method="post" id="bulkForm">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover align-middle dealer-orders-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col"><input type="checkbox" id="checkAll" aria-label="Tümünü seç"></th>
                            <th scope="col"><?= buildSortLink('teklifkodu','Teklif No',$sort,$dir,$_GET) ?></th>
                            <th scope="col"><?= buildSortLink('projeadi','Telefon Numarası',$sort,$dir,$_GET) ?></th>
                            <th scope="col"><?= buildSortLink('tekliftarihi','Tarih',$sort,$dir,$_GET) ?></th>
                            <th scope="col"><?= buildSortLink('durum','Durum',$sort,$dir,$_GET) ?></th>
                            <th scope="col" class="text-end"><?= buildSortLink('geneltoplam','Genel Toplam',$sort,$dir,$_GET) ?></th>
                            <th scope="col" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="<?= (int)$o['id'] ?>" aria-label="Seç"></td>
                            <td><?= htmlspecialchars($o['teklifkodu']) ?></td>
                            <td><?= htmlspecialchars($o['projeadi']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($o['tekliftarihi'])) ?></td>
                            <td><span class="badge <?= getStatusBadgeClass($o['durum']) ?>" title="<?= htmlspecialchars($o['durum']) ?>"><?= htmlspecialchars($o['durum']) ?></span></td>
                            <?php
                                $amt = (float)$o['eurotutar'];
                                $curCode = 'EUR';
                                if ($amt <= 0) {
                                    $amt = (float)$o['dolartutar'];
                                    $curCode = $amt > 0 ? 'USD' : 'TRY';
                                    if ($curCode === 'TRY') {
                                        $amt = (float)$o['tltutar'];
                                    }
                                }
                                $curSymbol = $curCode === 'TRY' ? '₺' : ($curCode === 'USD' ? '$' : '€');
                            ?>
                            <td class="text-end">
                                <span class="order-total" data-value="<?= number_format($amt,2,'.','') ?>" data-currency="<?= $curCode ?>">
                                    <?= number_format($amt, 2, ',', '.') ?> <?= $curSymbol ?>
                                </span>
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" aria-label="Görüntüle" title="Görüntüle" target="_blank" href="offer_detail.php?te=<?= (int)$o['id'] ?>&sta=Sipariş"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="btn-group" role="group" aria-label="Toplu işlemler">
                    <button type="submit" formaction="siparisupdate.php" formmethod="post" class="btn btn-sm btn-danger" disabled id="bulkCancel">İptal Et</button>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p=1;$p<=max(1,ceil($total/$limit));$p++): ?>
                            <li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include "menuler/footer.php"; ?>
</div>
</div>
<div class="rightbar-overlay"></div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var $$=function(sel){return Array.from(document.querySelectorAll(sel));};
    var checkAll=document.getElementById('checkAll');
    function updateBulk(){
        var btn=document.getElementById('bulkCancel');
        if(btn){btn.disabled=!document.querySelector('input[name="selected[]"]:checked');}
    }
    if(checkAll){
        checkAll.addEventListener('change',function(){
            $$('input[name="selected[]"]').forEach(function(cb){cb.checked=checkAll.checked;});
            updateBulk();
        });
        $$('input[name="selected[]"]').forEach(function(cb){cb.addEventListener('change',updateBulk);});
    }
    document.querySelectorAll('.order-total').forEach(function(el){
        var v=parseFloat(el.dataset.value);var cur=el.dataset.currency;
        try{
            var t=new Intl.NumberFormat('tr-TR',{style:'currency',currency:cur}).format(v);
            el.textContent=t;el.setAttribute('aria-label','Genel Toplam: '+t);
        }catch(e){}
    });
});
</script>
</body>
</html>
