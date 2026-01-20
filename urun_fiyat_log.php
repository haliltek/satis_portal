<?php
// urun_fiyat_log.php
include "fonk.php";
oturumkontrol();

// DataTables isteği ana sayfaya yönlendirildiğinde JSON döndür
if (isset($_GET['datatable']) || isset($_POST['datatable'])) {
    require __DIR__ . '/urun_fiyat_log_datatable.php';
    exit;
}

// aynı yetkiye sahip olmayanlar erişemesin
$departName = $yoneticisorgula["bolum"] ?? '';
$depRes = mysqli_query($db, "SELECT id FROM departmanlar WHERE departman='$departName'");
$depRow = mysqli_fetch_array($depRes);
$depId = $depRow["id"] ?? 0;
$yetkiRes = mysqli_query($db, "SELECT urunler FROM yetkiler WHERE departmanid='$depId'");
$yetkiRow = mysqli_fetch_array($yetkiRes);
if (($yetkiRow['urunler'] ?? 'Hayir') != 'Evet') {
    header('Location: anasayfa.php');
    exit;
}

// Kullanıcı listesini al
$yoneticiList = [];
$result = mysqli_query($db, "SELECT yonetici_id, adsoyad FROM yonetici ORDER BY adsoyad");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $yoneticiList[] = $row;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Ürün Fiyat Logları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" />
    <style>
        #logTable td.name-cell,
        #logTable th.name-cell {
            width: 250px;
            white-space: normal !important;
            word-break: break-word;
        }
    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
<div id="layout-wrapper">
    <header id="page-topbar">
        <?php include 'menuler/ustmenu.php'; ?>
        <?php include 'menuler/solmenu.php'; ?>
    </header>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h2 class="mb-0 text-dark">Ürün Fiyat Güncelleme Logları</h2>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label for="user_filter" class="form-label">Kullanıcı</label>
                                        <select id="user_filter" class="form-select">
                                            <option value="">Tümü</option>
                                            <?php foreach($yoneticiList as $y): ?>
                                                <option value="<?php echo $y['yonetici_id']; ?>"><?php echo $y['adsoyad']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" id="start_date" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                        <input type="date" id="end_date" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="reverted_filter" class="form-label">Durum</label>
                                        <select id="reverted_filter" class="form-select">
                                            <option value="">Tümü</option>
                                            <option value="0">Aktif</option>
                                            <option value="1">Geri Alınmış</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Hızlı Tarihler</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm quick-date" data-range="today">Bugün</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm quick-date" data-range="yesterday">Dün</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm quick-date" data-range="7">Son 7 Gün</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm quick-date" data-range="30">Son 30 Gün</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm quick-date" data-range="all">Tüm Zamanlar</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex justify-content-end align-items-end">
                                        <div class="d-flex gap-2">
                                            <button id="fillNamesBtn" class="btn btn-secondary">Eksik Stok Adlarını Doldur</button>
                                            <a href="urun_fiyat_log_revert_list.php" class="btn btn-warning">Toplu Geri Alma</a>
                                        </div>
                                    </div>
                                </div>
                                <table id="logTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Stok Kodu</th>
                                            <th>Ürün Adı</th>
                                            <th>Kullanıcı</th>
                                            <th>Güncelleme Tarihi</th>
                                            <th>Yurt İçi Fiyatlar</th>
                                            <th>İhracat Fiyatlar</th>
                                            <th>Fiyat Farkı</th>
                                            <th>Güncel Fiyat (Yurt İçi)</th>
                                            <th>Güncel Fark (Yurt İçi)</th>
                                            <th>Güncel Fiyat (İhracat)</th>
                                            <th>Güncel Fark (İhracat)</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="assets/libs/jszip/jszip.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
<script>
$(document).ready(function(){
    function priceText(oldVal, newVal){
        var o = oldVal ? oldVal : '-';
        var n = newVal ? newVal : '-';
        return o + ' → ' + n;
    }

    var origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
    var dataUrl = origin + window.location.pathname;

    var table = $('#logTable').DataTable({
        serverSide: true,
        processing: true,
        pageLength: 50,
        lengthMenu: [[50, 100, 200, -1], [50, 100, 200, 'Tümü']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'csv', exportOptions: { columns: ':visible' } },
            { extend: 'excel', exportOptions: { columns: ':visible' } },
            { extend: 'colvis', text: 'Sütunlar' }
        ],
        ajax: {
            url: dataUrl,
            type: 'POST',
            data: function(d){
                d.datatable = 1;
                d.user = $('#user_filter').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.reverted = $('#reverted_filter').val();
            }
        },
        columnDefs: [
            { targets: [8,9,10,11], visible: false },
            { targets: 2, width: '250px' }
        ],
        columns: [
            { data: 'log_id', title: 'ID' },
            { data: 'stokkodu', title: 'Stok Kodu' },
            { data: 'stokadi', title: 'Ürün Adı', className: 'name-cell' },
            { data: 'adsoyad', title: 'Kullanıcı' },
            { data: 'guncelleme_tarihi', title: 'Güncelleme Tarihi' },
            { data: 'onceki_domestic', orderable: false, render: function(data, type, row){ return priceText(row.onceki_domestic, row.yeni_domestic); } },
            { data: 'onceki_export', orderable: false, render: function(data, type, row){ return priceText(row.onceki_export, row.yeni_export); } },
            { data: 'fiyat_farki', render: function(data){
                var diff = parseFloat(data);
                if (isNaN(diff)) return '-';
                var cls = diff >= 0 ? 'text-success' : 'text-danger';
                return '<span class="' + cls + '">' + diff.toFixed(2) + '</span>';
            } },
            { data: 'guncel_fiyat_domestic', render: function(data){
                var val = parseFloat(data);
                if (isNaN(val)) return '-';
                return val.toFixed(2);
            } },
            { data: 'guncel_fark_domestic', render: function(data){
                var diff = parseFloat(data);
                if (isNaN(diff)) return '-';
                var cls = diff >= 0 ? 'text-success' : 'text-danger';
                return '<span class="' + cls + '">' + diff.toFixed(2) + '</span>';
            } },
            { data: 'guncel_fiyat_export', render: function(data){
                var val = parseFloat(data);
                if (isNaN(val)) return '-';
                return val.toFixed(2);
            } },
            { data: 'guncel_fark_export', render: function(data){
                var diff = parseFloat(data);
                if (isNaN(diff)) return '-';
                var cls = diff >= 0 ? 'text-success' : 'text-danger';
                return '<span class="' + cls + '">' + diff.toFixed(2) + '</span>';
            } },
            { data: 'reverted', render: function(data){ return data == 1 ? 'Geri Alındı' : 'Aktif'; } }
        ],
        order: [[4,'desc']],
        language: { url: 'assets/libs/datatables.net/i18n/tr.json' }
    });

    $('#user_filter, #start_date, #end_date, #reverted_filter').on('change', function(){
        table.draw();
    });

    $('.quick-date').on('click', function(){
        var range = $(this).data('range');
        if(range === 'all') {
            $('#start_date').val('');
            $('#end_date').val('');
        } else {
            var end = new Date();
            var start = new Date(end);
            if(range === 'today') {
                // start and end already set to today
            } else if(range === 'yesterday') {
                start.setDate(end.getDate() - 1);
                end = new Date(start);
            } else {
                var days = parseInt(range, 10) || 0;
                start.setDate(end.getDate() - (days - 1));
            }
            var format = function(d){ return d.toISOString().split('T')[0]; };
            $('#start_date').val(format(start));
            $('#end_date').val(format(end));
        }
        table.draw();
    });

    $('#fillNamesBtn').on('click', function(){
        $.post('fill_log_names.php', function(resp){
            if (resp.status === 'success') {
                alert('Güncellenen kayıt: ' + resp.updated);
                table.ajax.reload();
            } else {
                alert('Hata: ' + resp.error);
            }
        }, 'json');
    });

});
</script>
</body>
</html>
