<?php
// urun_fiyat_log_revert_list.php
include "fonk.php";
oturumkontrol();
$start = $_SESSION['revert_start'] ?? date('Y-m-d\T00:00');
$end   = $_SESSION['revert_end']   ?? date('Y-m-d\TH:i');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Fiyat Geri Alma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
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
                <h3 class="mb-3">Fiyat Değişikliklerini Geri Alma</h3>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="start_dt" class="form-label">Başlangıç</label>
                        <input type="datetime-local" id="start_dt" class="form-control" value="<?php echo htmlspecialchars($start); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_dt" class="form-label">Bitiş</label>
                        <input type="datetime-local" id="end_dt" class="form-control" value="<?php echo htmlspecialchars($end); ?>">
                    </div>
                    <div class="col-md-4 align-self-end">
                        <button id="listChanges" class="btn btn-primary">Listele</button>
                    </div>
                </div>
                <table id="revertTable" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" title="Tümünü Seç"></th>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th>Kullanıcı</th>
                            <th>Güncelleme Tarihi</th>
                            <th>Yurt İçi Fiyatlar</th>
                            <th>İhracat Fiyatlar</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button id="revertSelected" class="btn btn-warning mt-2">Seçilenleri Geri Al</button>

                <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Geri Alma Sonucu</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="resultModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'menuler/footer.php'; ?>
    </div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
$(function(){
    function priceInfo(oldVal, newVal, currentVal){
        var html = '';
        if(oldVal && newVal){
            html += '<span class="badge bg-danger me-1">'+oldVal+'</span>';
            html += '<i class="mdi mdi-arrow-right-bold"></i>';
            html += '<span class="badge bg-primary ms-1 me-1">'+newVal+'</span>';
        } else {
            if(oldVal){ html += '<span class="badge bg-danger me-1">Eski: '+oldVal+'</span>'; }
            if(newVal){ html += '<span class="badge bg-primary me-1">Yeni: '+newVal+'</span>'; }
        }
        if(currentVal){
            var cls = (newVal && currentVal == newVal) ? 'bg-success' : 'bg-warning text-dark';
            html += '<span class="badge '+cls+'">Anlık: '+currentVal+'</span>';
        }
        return html || '-';
    }

    var table = $('#revertTable').DataTable({
        ordering: false,
        paging: false,
        searching: true,
        info: false
    });

    $('#listChanges').on('click', function(){
        var start = $('#start_dt').val();
        var end = $('#end_dt').val();
        if(!start || !end){ alert('Tarih aralığı gerekli'); return; }
        $.getJSON('urun_fiyat_log_changes.php', {start: start, end: end}, function(resp){
            if(resp.status === 'success'){
                table.clear();
                $('#selectAll').prop('checked', false);
                resp.data.forEach(function(r){
                    var chk = '<input type="checkbox" class="sel" data-domestic="'+(r.domestic_log_id||'')+'" data-export="'+(r.export_log_id||'')+'">';
                    var domHtml = priceInfo(r.eski_domestic, r.yeni_domestic, r.current_domestic);
                    var expHtml = priceInfo(r.eski_export, r.yeni_export, r.current_export);
                    table.row.add([
                        chk,
                        r.stokkodu,
                        r.stokadi,
                        r.adsoyad || '',
                        r.guncelleme_tarihi || '',
                        domHtml,
                        expHtml
                    ]);
                });
                table.draw();
            } else {
                alert('Hata: ' + resp.error);
            }
        });
    });

    $('#selectAll').on('change', function(){
        $('#revertTable input.sel').prop('checked', this.checked);
    });

    if($('#start_dt').val() && $('#end_dt').val()){
        $('#listChanges').trigger('click');
    }

    $('#revertSelected').on('click', function(){
        var ids = [];
        $('#revertTable input.sel:checked').each(function(){
            var d = $(this).data('domestic');
            var e = $(this).data('export');
            if(d) ids.push(d);
            if(e) ids.push(e);
        });
        if(ids.length === 0){ alert('Lütfen kayıt seçin'); return; }
        function showResult(resp, cb){
            var platformNames = {
                mysql: 'Satış Web Veritabanı',
                logo_gempa: 'Logo Gempa',
                logo_gemas: 'Logo Gemaş',
                web: 'Gemaş Web'
            };
            var html = '';
            if(resp.message){ html += '<p>'+resp.message+'</p>'; }
            html += '<ul>';
            $.each(resp.platforms || {}, function(key, val){
                $.each(['domestic','export'], function(_, type){
                    var entry = val[type];
                    if(!entry || entry.error === 'No change'){ return; }
                    var status;
                    if(entry.success === true){ status = '<span class="text-success">Başarılı</span>'; }
                    else if(entry.success === false){ status = '<span class="text-danger">Hata: '+entry.error+'</span>'; }
                    else { status = '<span class="text-muted">Atlandı: '+entry.error+'</span>'; }
                    html += '<li>'+platformNames[key]+' - '+(type === 'domestic' ? 'Yurt İçi' : 'İhracat')+': '+status+'</li>';
                });
            });
            html += '</ul>';
            $('#resultModalBody').html(html);
            var modalEl = document.getElementById('resultModal');
            var modal = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('hidden.bs.modal', function handler(){
                modalEl.removeEventListener('hidden.bs.modal', handler);
                cb();
            });
            modal.show();
        }

        function next(idx){
            if(idx >= ids.length){
                alert('İşlem tamamlandı');
                return;
            }
            $.post('urun_fiyat_log_revert.php', {id: ids[idx]}, function(resp){
                if(resp.status === 'error'){
                    alert('Hata: ' + resp.error);
                    return;
                }
                showResult(resp, function(){ next(idx+1); });
            }, 'json').fail(function(){
                alert('Sunucu hatası');
            });
        }
        next(0);
    });
});
</script>
</body>
</html>
