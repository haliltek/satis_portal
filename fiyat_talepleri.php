<?php
// fiyat_talepleri.php
include "fonk.php";
oturumkontrol();

// Datatable isteği varsa yönlendir
if (isset($_GET['datatable']) || isset($_POST['datatable'])) {
    require __DIR__ . '/fiyat_talepleri_datatable.php';
    exit;
}

// Sadece Yönetici
$userType = $_SESSION['user_type'] ?? '';
if ($userType !== 'Yönetici') {
    header('Location: anasayfa.php');
    exit;
}

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
                                <h4 class="mb-0">Bekleyen Fiyat Talepleri</h4>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select id="status_filter" class="form-select">
                                            <option value="Beklemede" selected>Beklemede</option>
                                            <option value="Onaylandı">Onaylandı</option>
                                            <option value="Reddedildi">Reddedildi</option>
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
                                            <th>Personel</th>
                                            <th>Tarih</th>
                                            <th>Mevcut (TL)</th>
                                            <th>Mevcut (Exp)</th>
                                            <th>Döviz</th>
                                            <th>Stok</th>
                                            <th>Aktif</th>
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
            { data: 'id' },
            { data: 'stok_kodu' },
            { data: 'urun_adi' },
            { data: 'adsoyad' },
            { data: 'tarih' },
            { data: 'mevcut_fiyat_yurtici', render: function(data){ return parseFloat(data).toFixed(2); }},
            { data: 'mevcut_fiyat_export', render: function(data){ return parseFloat(data).toFixed(2); }},
            { data: 'doviz' },
            { data: 'miktar' },
            { data: 'logo_active', render: function(data){
                return data == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>';
            }},
            { data: 'oneri_not' },
            { data: 'durum', render: function(data){
                if(data=='Beklemede') return '<span class="badge bg-warning text-dark">Beklemede</span>';
                if(data=='Onaylandı') return '<span class="badge bg-success">Onaylandı</span>';
                if(data=='Reddedildi') return '<span class="badge bg-danger">Reddedildi</span>';
                return data;
            }},
            { data: 'id', orderable: false, render: function(data, type, row){
                if(row.durum !== 'Beklemede') return '-';
                return '<div class="d-flex gap-1">' +
                       '<button class="btn btn-sm btn-primary btn-update-price" data-stokkodu="'+row.stok_kodu+'" title="Fiyat Güncelle"><i class="bx bx-edit"></i></button>' + 
                       '<button class="btn btn-sm btn-success btn-approve" data-id="'+data+'" title="Tamamlandı Olarak İşaretle"><i class="bx bx-check"></i></button>' +
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

    // Modal Form Gönderimi
    $('#priceUpdateForm').on('submit', function(e) {
        e.preventDefault();
        
        // Form verilerini al
        var formData = $(this).serializeArray();
        
        // Mail checkboxlarını ekle
        var selectedMailIds = [];
        $('.mail-checkbox:checked').each(function() {
            selectedMailIds.push($(this).val());
        });
        // formData.push({name: 'selected_mail_ids', value: selectedMailIds.join(',')});  // urunlerlogo.php bu formatı bekliyor mu emin olalım.
        // urunlerlogo JS'inde: formData.push({ name: 'selected_mail_ids', value: selectedMailIds.join(',') });
        // Evet, string olarak bekliyor.
        
        // Ama serializeArray() zaten selected_mail_ids[] olarak alabilir, onu string'e çevirip değiştirelim veya ekleyelim.
        // En temiz yöntem manuel eklemek.
        // Form'daki `action` hidden field `updatePriceWithMail` olarak ayarlı. URL `urunlerlogo.php` olmalı.
        
        // Düzeltme: formData'yı manipüle et
        // Checkboxları çıkarıp string olarak ekleyelim
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
                $('#priceUpdateModal .modal-body').append('<div id="loadingIndicator" class="alert alert-info mt-2">İşlem yapılıyor, lütfen bekleyiniz...</div>');
            },
            success: function(response) {
                $('#loadingIndicator').remove();
                if (response.status === 'success' || response.status === 'partial') {
                    alert('İşlem Başarılı: ' + response.message);
                    $('#priceUpdateModal').modal('hide');
                    table.draw();
                } else {
                    alert('Hata: ' + response.message);
                }
            },
            error: function(xhr) {
                $('#loadingIndicator').remove();
                alert('Sunucu hatası: ' + xhr.responseText);
            }
        });
    });
});
</script>
</body>
</html>
