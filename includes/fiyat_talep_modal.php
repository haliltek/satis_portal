<!-- Fiyat Talebi Modal -->
<div class="modal fade" id="fiyatTalepModal" tabindex="-1" aria-labelledby="fiyatTalepModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="fiyatTalepModalLabel">
                    <i class="mdi mdi-alert-circle me-2"></i>Fiyat Talebi Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="font-size: 12px;">
                    <i class="mdi mdi-information"></i> 
                    Bu ürün için fiyat talebi oluşturuyorsunuz. Talebiniz yöneticiye iletilecektir.
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Ürün Bilgileri</label>
                    <div class="card bg-light">
                        <div class="card-body p-2" style="font-size: 12px;">
                            <div><strong>Stok Kodu:</strong> <span id="talepStokKodu"></span></div>
                            <div><strong>Ürün Adı:</strong> <span id="talepStokAdi"></span></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="talepNotu" class="form-label fw-bold">
                        Talep Notunuz <span class="text-danger">*</span>
                    </label>
                    <textarea 
                        class="form-control" 
                        id="talepNotu" 
                        rows="4" 
                        placeholder="Lütfen fiyat talebinizle ilgili detayları yazınız (miktar, aciliyet, özel koşullar vb.)"
                        required></textarea>
                    <div class="invalid-feedback" id="talepNotuError"></div>
                </div>
                
                <input type="hidden" id="talepUrunId">
                <input type="hidden" id="talepStokKoduHidden">
                <input type="hidden" id="talepStokAdiHidden">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning" id="fiyatTalepGonder">
                    <i class="mdi mdi-send me-1"></i>Talep Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fiyat Talebi Modal İşlemleri
$(document).ready(function() {
    // Popover'ları başlat
    initializePopovers();
});

// Popover başlatma fonksiyonu (dinamik elementler için de kullanılabilir)
window.initializePopovers = function() {
    $('[data-bs-toggle="popover"]').each(function() {
        // Zaten başlatılmışsa atla
        if ($(this).data('bs.popover')) {
            return;
        }
        new bootstrap.Popover(this);
    });
}

// Sayfa içinde yeni element eklendiğinde popover'ları güncelle
// MutationObserver ile DOM değişikliklerini izle
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
            setTimeout(function() {
                window.initializePopovers();
            }, 100);
        }
    });
});

// Tablo body'sini izle
const targetNode = document.getElementById('cartTableBody');
if (targetNode) {
    observer.observe(targetNode, { childList: true, subtree: true });
}

// Ek güvenlik: Her 2 saniyede bir kontrol et
setInterval(function() {
    window.initializePopovers();
}, 2000);

// Fiyatı Yok yazısına tıklama - Event delegation kullan
$(document).on('click', '.fiyat-yok-text', function() {
    var urunId = $(this).data('urun-id');
    var stokKodu = $(this).data('stokkodu');
    var stokAdi = $(this).data('stokadi');
    
    // Popover'ı kapat
    var popoverInstance = bootstrap.Popover.getInstance(this);
    if (popoverInstance) {
        popoverInstance.hide();
    }
    
    // Modal'ı aç
    $('#talepUrunId').val(urunId);
    $('#talepStokKoduHidden').val(stokKodu);
    $('#talepStokAdiHidden').val(stokAdi);
    $('#talepStokKodu').text(stokKodu);
    $('#talepStokAdi').text(stokAdi);
    $('#talepNotu').val('');
    $('#talepNotuError').text('');
    
    var modal = new bootstrap.Modal(document.getElementById('fiyatTalepModal'));
    modal.show();
});

// Eski buton için de destek (geriye dönük uyumluluk)
$(document).on('click', '.fiyat-talep-btn', function() {
    var urunId = $(this).data('urun-id');
    var stokKodu = $(this).data('stokkodu');
    var stokAdi = $(this).data('stokadi');
    
    $('#talepUrunId').val(urunId);
    $('#talepStokKoduHidden').val(stokKodu);
    $('#talepStokAdiHidden').val(stokAdi);
    $('#talepStokKodu').text(stokKodu);
    $('#talepStokAdi').text(stokAdi);
    $('#talepNotu').val('');
    $('#talepNotuError').text('');
    
    var modal = new bootstrap.Modal(document.getElementById('fiyatTalepModal'));
    modal.show();
});

$('#fiyatTalepGonder').on('click', function() {
    var $btn = $(this);
    var urunId = $('#talepUrunId').val();
    var stokKodu = $('#talepStokKoduHidden').val();
    var stokAdi = $('#talepStokAdiHidden').val();
    var talepNotu = $('#talepNotu').val().trim();
    
    // Validasyon
    if (!talepNotu) {
        $('#talepNotu').addClass('is-invalid');
        $('#talepNotuError').text('Lütfen talep notunuzu yazınız.');
        return;
    }
    
    $('#talepNotu').removeClass('is-invalid');
    $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Gönderiliyor...');
    
    $.ajax({
        url: 'api/fiyat_talebi_olustur.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            urun_id: urunId,
            stokkodu: stokKodu,
            stokadi: stokAdi,
            talep_notu: talepNotu
        }),
        success: function(response) {
            if (response.success) {
                var message = '✅ ' + response.message;
                if (response.mail_sent === false && response.mail_error) {
                    message += '\n\n⚠️ Not: Mail gönderilemedi (' + response.mail_error + ')';
                }
                alert(message);
                bootstrap.Modal.getInstance(document.getElementById('fiyatTalepModal')).hide();
                $('#talepNotu').val('');
                
                // UI Güncellemesi: "Fiyatı Yok" yazısını değiştir
                var urunId = $('#talepUrunId').val();
                
                // İlgili elementi bul (data-urun-id ile)
                var $element = $('.fiyat-yok-text[data-urun-id="' + urunId + '"]');
                
                if ($element.length) {
                    // Popover'ı yok et
                    var popover = bootstrap.Popover.getInstance($element[0]);
                    if (popover) {
                        popover.dispose();
                    }
                    
                    // İçeriği ve stili değiştir
                    $element.removeClass('fiyat-yok-text')
                            .css({
                                'color': '#ff9800', 
                                'font-style': 'italic', 
                                'cursor': 'default', 
                                'text-decoration': 'none',
                                'font-size': '10px',
                                'white-space': 'nowrap',
                                'font-weight': 'bold',
                                'letter-spacing': '-0.3px'
                            })
                            .removeAttr('data-bs-toggle')
                            .text('Güncelleme Bekliyor');
                            
                    // Tablo hücresini bul ve güncelle (eğer td içinde span varsa)
                    // (Opsiyonel: Eğer tüm hücre içeriğini değiştirmek istersek)
                }
                
            } else {
                alert('⚠️ ' + response.message);
            }
        },
        error: function(xhr) {
            var errorMsg = 'Bir hata oluştu.';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch(e) {}
            alert('❌ ' + errorMsg);
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="mdi mdi-send me-1"></i>Talep Gönder');
        }
    });
});
</script>
