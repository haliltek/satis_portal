<?php
// fiyat_talepleri.php
include "fonk.php";
oturumkontrol();

// Datatable isteği varsa yönlendir
if (isset($_GET['datatable']) || isset($_POST['datatable'])) {
    require __DIR__ . '/fiyat_talepleri_datatable.php';
    exit;
}

// Kullanıcı tipi kontrolü
$userType = $_SESSION['user_type'] ?? '';
$yonetici_id = $_SESSION['yonetici_id'] ?? 0;

// Yönetici veya Personel olmalı
if (!in_array($userType, ['Yönetici', 'Personel'])) {
    header('Location: anasayfa.php');
    exit;
}

$isYonetici = ($userType === 'Yönetici');

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Bekleyen Fiyat Talepleri</title>
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
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-warning text-dark">
                                <h4 class="mb-0">
                                    <?php if ($isYonetici): ?>
                                        Bekleyen Fiyat Talepleri (Tüm Talepler)
                                    <?php else: ?>
                                        Fiyat Taleplerim
                                    <?php endif; ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select id="status_filter" class="form-select">
                                            <option value="beklemede" selected>Beklemede</option>
                                            <option value="onaylandi">Onaylandı</option>
                                            <option value="reddedildi">Reddedildi</option>
                                            <option value="">Tümü</option>
                                        </select>
                                    </div>
                                </div>
                                <table id="requestTable" class="table table-bordered dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Stok Kodu</th>
                                            <th>Ürün Adı</th>
                                            <th>Talep Eden</th>
                                            <th>Tarih</th>
                                            <th>Talep Notu</th>
                                            <th>Durum</th>
                                            <th>İşlem</th>
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
    <!-- Fiyat Güncelleme Onay Modalı -->
    <div class="modal fade custom-modal" id="priceUpdateModal" tabindex="-1" role="dialog" aria-labelledby="priceUpdateModalLabel" aria-describedby="priceUpdateModalDesc" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="priceUpdateForm" method="post" novalidate>
                <input type="hidden" name="action" value="updatePriceWithMail">
                <input type="hidden" name="stok_kodu" id="modal_stok_kodu" value="">
                <input type="hidden" name="logicalref" id="modal_logicalref" value="">
                <input type="hidden" name="gempa_logicalref" id="modal_gempa_logicalref" value="">
                <input type="hidden" name="gemas_logicalref" id="modal_gemas_logicalref" value="">
                <input type="hidden" name="yeni_domestic_price" id="modal_yeni_domestic_price" value="">
                <input type="hidden" name="yeni_export_price" id="modal_yeni_export_price" value="">
                <input type="hidden" name="urun_adi" id="modal_urun_adi" value="">
                <input type="hidden" name="old_domestic_price" id="modal_old_domestic_price" value="">
                <input type="hidden" name="old_export_price" id="modal_old_export_price" value="">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="priceUpdateModalLabel"><i class="bx bx-edit me-2"></i> Fiyat Güncelleme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Ürün Bilgileri -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-6">
                                    <p><strong>Stok Kodu:</strong> <span id="modal_display_stok_kodu"></span></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Ürün Adı:</strong> <span id="modal_display_urun_adi"></span></p>
                                </div>
                            </div>
                            <!-- Fiyat Giriş Alanları -->
                             <div class="row mt-3">
                                <div class="col-6">
                                    <label class="form-label">Yurtiçi Fiyatı</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">Eski</span>
                                        <input type="text" class="form-control" id="modal_display_old_domestic" readonly>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-success text-white">Yeni</span>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="input_new_domestic" placeholder="Yeni Fiyat">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">İhracat Fiyatı</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">Eski</span>
                                        <input type="text" class="form-control" id="modal_display_old_export" readonly>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-success text-white">Yeni</span>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="input_new_export" placeholder="Yeni Fiyat">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vazgeç & Güncelle Butonları -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Vazgeç</button>
                            <button type="submit" class="btn btn-success">Güncelle</button>
                        </div>

                        <!-- Mail Gönderimi Seçeneği -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="sendMailCheckbox" name="send_mail" value="1" checked>
                            <label class="form-check-label" for="sendMailCheckbox">
                                Güncelleme sonrası mail gönderilsin mi?
                            </label>
                        </div>

                        <!-- Mail Yönetim Paneli -->
                        <div id="mailListContainer" class="card mb-0" style="display: none;">
                            <div class="card-header">
                                <h6 class="mb-0">Mail Gönderilecek Adresler</h6>
                            </div>
                            <div class="card-body" id="mailListContent">
                                <!-- AJAX ile mail listesi yüklenecek -->
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script>
var isYonetici = <?php echo $isYonetici ? 'true' : 'false'; ?>;
var currentUserId = <?php echo $yonetici_id; ?>;

$(document).ready(function(){
    var table = $('#requestTable').DataTable({
        serverSide: true,
        processing: true,
        order: [[0, 'desc']],
        ajax: {
            url: 'fiyat_talepleri.php',
            type: 'POST',
            data: function(d){
                d.datatable = 1;
                d.status = $('#status_filter').val();
            }
        },
        columns: [
            { data: 'talep_id' },
            { data: 'stokkodu' },
            { data: 'stokadi' },
            { data: 'talep_eden_adi' },
            { data: 'talep_tarihi', render: function(data){
                if(!data) return '-';
                var d = new Date(data);
                return d.toLocaleDateString('tr-TR') + ' ' + d.toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'});
            }},
            { data: 'talep_notu', render: function(data){
                if(!data) return '-';
                return data.length > 50 ? data.substring(0, 50) + '...' : data;
            }},
            { data: 'durum', render: function(data){
                if(data=='beklemede') return '<span class="badge bg-warning text-dark">Beklemede</span>';
                if(data=='onaylandi') return '<span class="badge bg-success">Onaylandı</span>';
                if(data=='reddedildi') return '<span class="badge bg-danger">Reddedildi</span>';
                return data;
            }},
            { data: 'talep_id', orderable: false, render: function(data, type, row){
                // Personel ise sadece durum göster
                if (!isYonetici) {
                    if(row.durum == 'beklemede') {
                        return '<span class="badge bg-info">İşleniyor...</span>';
                    } else if(row.durum == 'onaylandi') {
                        return '<span class="badge bg-success"><i class="bx bx-check"></i> Tamamlandı</span>';
                    } else if(row.durum == 'reddedildi') {
                        return '<span class="badge bg-danger"><i class="bx bx-x"></i> Reddedildi</span>';
                    }
                    return '-';
                }
                
                // Yönetici ise işlem butonları
                if(row.durum !== 'beklemede') return '-';
                return '<div class="d-flex gap-1">' +
                       '<button class="btn btn-sm btn-primary btn-update-price" data-talepid="'+data+'" data-stokkodu="'+row.stokkodu+'" title="Fiyat Güncelle"><i class="bx bx-edit"></i></button>' + 
                       '<button class="btn btn-sm btn-success btn-approve" data-id="'+data+'" title="Onayla"><i class="bx bx-check"></i></button>' +
                       '<button class="btn btn-sm btn-danger btn-reject" data-id="'+data+'" title="Reddet"><i class="bx bx-x"></i></button>' +
                       '</div>';
            }}
        ],
        language: { url: 'assets/libs/datatables.net/i18n/tr.json' }
    });

    $('#status_filter').on('change', function(){
        table.draw();
    });

    // Onayla Button (Basic Status Update for now)
    $('#requestTable').on('click', '.btn-approve', function(){
        var id = $(this).data('id');
        if(confirm('Bu fiyat talebini onaylıyor musunuz? (Fiyatlar güncellenecek)')) {
            $.post('fiyat_talepleri_islem.php', { action: 'approve', id: id }, function(res){
                if(res.status === 'success') {
                    table.draw();
                } else {
                    alert('Hata: ' + res.message);
                }
            }, 'json');
        }
    });

    // Fiyat Güncelle Butonu
    $('#requestTable').on('click', '.btn-update-price', function(){
        var stokKodu = $(this).data('stokkodu');
        
        // 1. Ürün bilgilerini çek
        $.post('fiyat_talepleri_islem.php', { action: 'getProductDetails', stok_kodu: stokKodu }, function(res){
            if(res.status === 'success') {
                var p = res.data;
                var oldDomestic = parseFloat(p.fiyat).toFixed(2);
                var oldExport = parseFloat(p.export_fiyat).toFixed(2);
                
                // Modal doldur
                $('#modal_stok_kodu').val(stokKodu);
                $('#modal_logicalref').val(p.LOGICALREF);
                $('#modal_gemas_logicalref').val(p.GEMAS2026LOGICAL);
                $('#modal_gempa_logicalref').val(p.GEMPA2026LOGICAL);
                $('#modal_urun_adi').val(p.stokadi);
                
                $('#modal_old_domestic_price').val(oldDomestic);
                $('#modal_old_export_price').val(oldExport);
                
                $('#modal_display_stok_kodu').text(stokKodu);
                $('#modal_display_urun_adi').text(p.stokadi);
                $('#modal_display_old_domestic').text(oldDomestic);
                $('#modal_display_old_export').text(oldExport);
                
                // Inputları temizle
                $('#input_new_domestic').val('');
                $('#input_new_export').val('');
                
                // Mail listesini yükle
                $('#sendMailCheckbox').prop('checked', true);
                $('#mailListContainer').show();
                loadMailListTo('#mailListContent');
                
                $('#priceUpdateModal').modal('show');
            } else {
                alert('Hata: ' + res.message);
            }
        }, 'json');
    });
    
    // Input değişikliklerini hidden alanlara aktar
    $('#input_new_domestic').on('input', function(){
        $('#modal_yeni_domestic_price').val($(this).val());
    });
    $('#input_new_export').on('input', function(){
        $('#modal_yeni_export_price').val($(this).val());
    });

    // Mail İşlemleri
    function loadMailListTo(containerSelector) {
        $.ajax({
            url: 'urunlerlogo.php',
            type: 'POST',
            data: { action: 'getMailList' },
            dataType: 'json',
            success: function(mails) {
                var html = '';
                if (mails.length > 0) {
                    $.each(mails, function(index, mail) {
                        html += '<div class="form-check">';
                        html += '<input class="form-check-input mail-checkbox" type="checkbox" name="selected_mail_ids[]" value="' + mail.mail_id + '" id="mail_' + mail.mail_id + '" checked>';
                        html += '<label class="form-check-label" for="mail_' + mail.mail_id + '">' + mail.email + ' (' + (mail.adsoyad ? mail.adsoyad : '') + ')</label>';
                        html += '</div>';
                    });
                } else {
                    html = '<p>Mail adresi bulunamadı.</p>';
                }
                $(containerSelector).html(html);
            }
        });
    }

    // Modal Form Gönderimi (Zengin UI - Event Delegation)
    $(document).on('submit', '#priceUpdateForm', function(e) {
        e.preventDefault();
        
        // Form verilerini al
        var formData = $(this).serializeArray();
        
        // Mail checkboxlarını ekle
        var selectedMailIds = [];
        $('.mail-checkbox:checked').each(function() {
            selectedMailIds.push($(this).val());
        });
        
        var newFormData = $.grep(formData, function(item){ 
            return item.name !== 'selected_mail_ids[]'; 
        });
        newFormData.push({name: 'selected_mail_ids', value: selectedMailIds.join(',')});
        
        $.ajax({
            url: 'urunlerlogo.php',
            type: 'POST',
            data: newFormData,
            dataType: 'json',
            beforeSend: function() {
                $('#priceUpdateModal .modal-body').append('<div id="loadingIndicator" class="alert alert-info mt-2"><i class="bx bx-loader-alt bx-spin"></i> İşlem yapılıyor, lütfen bekleyiniz...</div>');
                $('#priceUpdateModal button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                const $modal = $('#priceUpdateModal');
                const $body = $modal.find('.modal-body');
                const $footer = $modal.find('.modal-footer'); // Footer varsa

                // 1) spinner’ı temizle
                $modal.find('#loadingIndicator').remove();
                // Butonları gizle (veya footer'ı)
                $modal.find('button[type="submit"]').hide();
                $modal.find('button.btn-secondary').hide(); // Vazgeç butonu

                // 2) gövdedeki form alanlarını temizle
                $body.empty();

                // 3) ignored-error kontrolü
                const ignoredExportError = response.platforms?.logo_gemas?.export?.ignored_error;

                // 4) ikon ve başlık belirle
                let iconClass, titleText;
                if (response.status === 'success' || ignoredExportError) {
                    iconClass = 'bx bx-check-circle text-success';
                    titleText = ignoredExportError ? 'Güncelleme Kısmen Başarılı' : 'Güncelleme Başarılı';
                } else if (response.status === 'partial' || response.status === 'warning') {
                    iconClass = 'bx bx-error text-warning';
                    titleText = 'Güncelleme Kısmen Başarılı';
                } else {
                    iconClass = 'bx bx-x-circle text-danger';
                    titleText = 'Güncelleme Başarısız';
                }

                // 5) Başlık ve mesaj
                $body.append(`
                    <div class="text-center p-3">
                        <i class="${iconClass}" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">${titleText}</h5>
                        <p>${response.message}</p>
                    </div>
                `);

                // 5.1) Progress bar
                if (response.platforms) {
                    const counts = { success: 0, skip: 0, fail: 0 };
                    $.each(response.platforms, (pk, res) => {
                        ['domestic', 'export'].forEach(type => {
                            const entry = res[type];
                            const ignored = pk === 'logo_gemas' && type === 'export' && entry.ignored_error;
                            if (entry.success === true || ignored) {
                                if (entry.error !== 'No change') counts.success++;
                            } else if (entry.success === null) {
                                counts.skip++;
                            } else if (entry.success === false) {
                                counts.fail++;
                            }
                        });
                    });
                    const total = counts.success + counts.skip + counts.fail;
                    const successPct = total ? (counts.success / total) * 100 : 0;
                    const skipPct = total ? (counts.skip / total) * 100 : 0;
                    const failPct = total ? (counts.fail / total) * 100 : 0;
                    
                    $body.append(`
                        <div class="progress mb-2" style="height:18px;">
                            ${counts.success ? `<div class="progress-bar bg-success" style="width:${successPct}%"></div>` : ''}
                            ${counts.skip ? `<div class="progress-bar bg-warning text-dark" style="width:${skipPct}%"></div>` : ''}
                            ${counts.fail ? `<div class="progress-bar bg-danger" style="width:${failPct}%"></div>` : ''}
                        </div>
                        <div class="text-center mb-3 small">${counts.success} Başarılı • ${counts.skip} Atlandı • ${counts.fail} Hata</div>
                    `);
                }

                // 6) Platform detaylarını göster (tablo)
                if (response.platforms) {
                    const labels = {
                        mysql: '<i class="bx bx-data text-primary me-1"></i>Satış Web DB',
                        logo_gempa: '<i class="bx bxs-factory text-danger me-1"></i>Logo GEMPAS',
                        logo_gemas: '<i class="bx bxs-factory text-danger me-1"></i>Logo GEMAS',
                        web: '<i class="bx bx-globe text-info me-1"></i>Gemas Web/App'
                    };
                    const rows = [];
                    $.each(response.platforms, (platformKey, results) => {
                        let rowHtml = `<tr><th>${labels[platformKey]}</th>`;
                        ['domestic', 'export'].forEach(type => {
                            const entry = results[type];
                            const isIgnored = platformKey === 'logo_gemas' && type === 'export' && entry.ignored_error;
                            const isSkipped = entry.success === null;
                            const isSuccess = entry.success === true || isIgnored;
                            let badgeClass, text, iconHtml;
                            
                            if (isSkipped) {
                                badgeClass = 'badge bg-warning text-dark';
                                iconHtml = '<i class="bx bx-fast-forward me-1"></i>';
                                text = 'Atlandı';
                            } else if (isSuccess) {
                                const noChange = entry.error === 'No change';
                                badgeClass = noChange ? 'badge bg-secondary' : 'badge bg-success';
                                iconHtml = noChange ? '<i class="bx bx-minus-circle me-1"></i>' : '<i class="bx bx-check-circle me-1"></i>';
                                text = noChange ? 'Değişmedi' : 'Başarılı';
                            } else {
                                badgeClass = 'badge bg-danger';
                                iconHtml = '<i class="bx bx-x-circle me-1"></i>';
                                text = 'Hata';
                            }
                            
                            let detailMsg = '';
                            if (isSkipped || (!isSuccess && entry.error !== 'No change')) {
                                detailMsg = entry.error;
                            } else if (isIgnored) {
                                detailMsg = entry.ignored_error;
                            }
                            
                            const tooltipAttr = detailMsg ? ' title="'+detailMsg+'"' : ''; // Basit title attribute
                            rowHtml += `<td><span class="${badgeClass}"${tooltipAttr}>${iconHtml}${text}</span></td>`;
                        });
                        rowHtml += '</tr>';
                        rows.push(rowHtml);
                    });

                    $body.append(`
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr><th>Platform</th><th>Yurtiçi</th><th>İhracat</th></tr>
                            </thead>
                            <tbody>${rows.join('')}</tbody>
                        </table>
                    `);
                }

                // 7) Mail durumu
                if (response.mailTotal > 0) {
                    $body.append(`
                        <div class="alert alert-light border mt-3">
                            <h6 class="mb-1"><i class="bx bx-envelope me-1"></i>Mail Durumu</h6>
                            <p class="mb-0 small">Gönderilen: <strong>${response.mailSent}</strong> / ${response.mailTotal}</p>
                            ${response.mailFailed.length ? `<p class="mb-0 small text-danger">Hatalı: ${response.mailFailed.join(', ')}</p>` : ''}
                        </div>
                    `);
                }

                // 8) Kapat düğmesi
                $body.append(`
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Kapat</button>
                    </div>
                `);

                // Tabloyu yenile (Fiyatlar değişmiş olabilir)
                if (table) table.draw();
                
            },
            error: function(xhr) {
                $('#loadingIndicator').remove();
                $('#priceUpdateModal .modal-body').append(`
                    <div class="alert alert-danger text-center mt-3">
                        <h5>Sunucu Hatası</h5>
                        <p>${xhr.responseText}</p>
                        <button type="button" class="btn btn-secondary mt-2" data-bs-dismiss="modal">Kapat</button>
                    </div>
                `);
            }
        });
    });

    // Modal kapanınca içeriği sıfırla (Çünkü body dynamic değişiyor)
    var originalModalBody = $('#priceUpdateModal .modal-body').html();
    $('#priceUpdateModal').on('hidden.bs.modal', function () {
       $(this).find('.modal-body').html(originalModalBody);
       $('#priceUpdateForm')[0].reset();
       $('#priceUpdateModal button[type="submit"]').prop('disabled', false).show();
       $('#priceUpdateModal button.btn-secondary').show();
    });

});
</script>
</body>
</html>
