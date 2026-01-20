
$(function(){
   var baseUrl = window.BASE_URL || '';
   $('.delbasket-item').click(function(){
      var sepetid = $(this).attr('sid');
      $.get(baseUrl + '/sepetsil',{sepetid:sepetid},function(data) {
          if(data == 1) {
              notice('<meta http-equiv="refresh" content="2">');
              notice('Ürün sepetten çıkartıldı.');
          }
        else {
            warn('Hata!');
          }
      });
   });
   /*$('.delbasketitem').click(function(){
      var sepetid = $(this).attr('sid');
      $.get('/sepetsil',{sepetid:sepetid},function(data) {
          if(data == 1) {
              notice('Ürün sepetten çıkartıldı.');
              $('.delbasketitem[sid="'+sepetid+'"]').parent().parent().remove();
          }
          else {
              warn('Hata!');
          }
      });
   });*/


    getbasket();


    $('.sepetibosalt').click(function(){
       sepetibosalt();
       getbasket();
    });

    $('#siparis-tamamla').click(function(){
        var teslimat = $('#teslimat').val() || '';
        var fatura = $('#fatura').val() || '';
        var sipid = $('.sipid').val();
        var tip = $('.tip').val() || '';
        var baseUrl = window.BASE_URL || '';
        var button = $(this);
        
        // Butonu devre dışı bırak ve loading göster
        button.prop('disabled', true).html('<i class="bx bx-loader bx-spin font-size-16 align-middle mr-2"></i> İşleniyor...');
        
        $.ajax({
            url: baseUrl + '/siparistamamla',
            type: 'GET',
            data: {
                sipid: sipid,
                teslimat: teslimat,
                fatura: fatura,
                tip: tip
            },
            dataType: 'text', // Response text olarak gelecek
            success: function(data) {
                data = data.trim();
                console.log('Sipariş tamamlama response:', data);
                if(data && data != '0' && data != '') {
                    window.location.replace(baseUrl + "/makbuz/" + data);
                } else {
                    alert('Sipariş tamamlanamadı. Lütfen tekrar deneyin.');
                    button.prop('disabled', false).html('<i class="bx bx-check-double font-size-16 align-middle mr-2"></i> Siparişi Tamamla');
                }
            },
            error: function(xhr, status, error) {
                console.error('Sipariş tamamlanamadı:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                alert('Sipariş tamamlanırken bir hata oluştu: ' + (xhr.responseText || error));
                button.prop('disabled', false).html('<i class="bx bx-check-double font-size-16 align-middle mr-2"></i> Siparişi Tamamla');
            }
        });
    });

});

function getbasket() {
    var baseUrl = window.BASE_URL || '';
    $.getJSON(baseUrl + '/sepetlist',function(sepetdata){
        $('.sepetliste').text('');
        var total = 0;
        if (sepetdata && sepetdata.length > 0) {
            sepetdata.forEach(function(add){
                if (add && add.urun_adi) {
                    $('.sepetliste').append('<li><div class="urun">'+ add.urun_adi +'</div><div class="adet">'+ add.adet +'</div><div class="fiyat">'+ add.fiyat +'₺</div><div class="islem"><i class="fa fa-times delbasketitem" onclick="delbasketitem('+ add.id +')" sid="'+ add.id +'"></i></div></li>');
                    total = add.toplam ? parseFloat(add.toplam.replace(/\./g, '').replace(',', '.')) : total;
                }
            });
            // Son elemanın toplamını göster
            if (sepetdata.length > 0 && sepetdata[sepetdata.length - 1].toplam) {
                $('.sepettoplam').text(sepetdata[sepetdata.length - 1].toplam + '₺');
            }
        }
        
        var sepetcount = sepetdata && sepetdata.length > 0 ? sepetdata.length : 0;
        $('.sepetCount').text(sepetcount);
    }).fail(function(xhr, status, error) {
        console.error('Sepet listesi alınamadı:', error);
        $('.sepetliste').text('<li>Sepet yüklenemedi</li>');
    });
}
function sepetibosalt(){
    var baseUrl = window.BASE_URL || '';
    $.get(baseUrl + '/sepetibosalt',{},function(data){
        if(data>0) {
           notice('Sepetiniz Boşaltıldı')
        }
        else {
            warn('Bilinmeyen bir hata ile karşılaşıldı')}
    });
}
function delbasketitem(sepetid) {
    var baseUrl = window.BASE_URL || '';
    $.get(baseUrl + '/sepetsil',{sepetid:sepetid},function(data) {
        if(data == 1) {
            notice('Ürün sepetten çıkartıldı.');
            getbasket();
        }
        else {
            warn('Hata!');
        }
    });
}

function sepet(uid) {
    var adet = $('#a' + uid).val();
    var cls = '#a' + uid;
    if(adet < 1) {
        warn('Ürün adetini seçiniz');
    }
    else {
        var baseUrl = window.BASE_URL || '';
        $.get(baseUrl + '/sepetekle', {uid:uid,adet:adet}, function (data){
            notice(data);
            getbasket()
            $(cls).parent().parent().css('background-color','rgb(239 255 201)');
            $('.form-button[uid='+uid+']').text('Sepetten Çıkart');
            $('.form-button[uid='+uid+']').attr('onclick','sepetcikar('+uid+')');
            $('.form-button[uid='+uid+']').css('background-color','#43b3a8');
        });
    }

}
function sepet3(uid) {
    var adet = $('#sa' + uid).val();
    var cls = '#sa' + uid;
    if(adet < 1) {
        warn('Ürün adetini seçiniz');
    }
    else {
        var baseUrl = window.BASE_URL || '';
        $.get(baseUrl + '/sepetekle', {uid:uid,adet:adet}, function (data){
            notice(data);
            getbasket()
            $(cls).parent().parent().css('background-color','rgb(239 255 201)');
            $('.form-button[uid='+uid+']').text('Sepetten Çıkart');
            $('.form-button[uid='+uid+']').attr('onclick','sepetcikar('+uid+')');
            $('.form-button[uid='+uid+']').css('background-color','#43b3a8');
        });
    }

}
function sepetcikar(uid) {
    var cls = '#a' + uid;
    var baseUrl = window.BASE_URL || '';
    $.get(baseUrl + '/sepetsil2',{uid:uid},function(data) {
        if(data == 1) {
            notice('Ürün sepetten çıkartıldı.');
            getbasket();
            $(cls).parent().parent().css('background','none');
            $('.form-button[uid='+uid+']').text('Sepete Ekle');
            $('.form-button[uid='+uid+']').attr('onclick','sepet('+uid+')');
            $('.form-button[uid='+uid+']').css('background-color','#45cb85');
        }
        else {
            warn('Hata!');
        }
    });
}
function sepet2(uid) {
    adet = $('#b' + uid).val();
    if(adet < 1) {
        warn('Ürün adetini seçiniz');
    }
    else {
        var baseUrl = window.BASE_URL || '';
        $.get(baseUrl + '/sepetekle', {uid:uid,adet:adet}, function (data){
            notice(data);
            getbasket()
        });
    }

}
function sepet3(uid) {
    adet = $('#f' + uid).val();
    if(adet < 1) {
        warn2('Ürün adetini seçiniz');
    }
    else {
        var baseUrl = window.BASE_URL || '';
        $.get(baseUrl + '/sepetekle', {uid:uid,adet:adet}, function (data){
            notice2(data);
            getbasket()
        });
    }

}
// Ödeme tipi seçildiğinde butonu aktif hale getir
$('#odeme_tipi').change(function(){
    var odeme_tipi = $(this).val();
    var geneltoplam = Number($('.genel_toplam').val());
    var bakiyelimit = Number($('.bakiye_limit').val());
    var buton = $('#sepetonay-btn');
    
    if(odeme_tipi == '0' || odeme_tipi == '') {
        buton.prop('disabled', true);
        return;
    }
    
    // Sepet toplamı 0 ise buton disabled kalmalı
    if(geneltoplam == 0) {
        buton.prop('disabled', true);
        return;
    }
    
    // Açık hesap kontrolü (odeme_tipi == 2 veya 51)
    if(odeme_tipi == '2' || odeme_tipi == '51'){
        if(geneltoplam > bakiyelimit) {
            buton.prop('disabled', true);
            warn('Sepet tutarı bakiye limitinizin üstünde');
            return;
        }
    }
    
    // Tüm kontroller geçtiyse butonu aktif et
    buton.prop('disabled', false);
});

// Sayfa yüklendiğinde buton durumunu kontrol et
$(document).ready(function(){
    $('#odeme_tipi').trigger('change');
});

$('.sepetonay').click(function(){
    var kargo = $('.kargo').val() || '1'; // Varsayılan kargo ID
    var geneltoplam = Number($('.genel_toplam').val());
    var bakiyelimit = $('.bakiye_limit').val();
    var odeme_tipi = $('#odeme_tipi').val();
    
    if(odeme_tipi == '0' || odeme_tipi == '') {
        warn('Ödeme tipi seçiniz');
        return false;
    }
    
    // Açık hesap kontrolü (odeme_tipi == 2 veya 51)
    if(odeme_tipi == '2' || odeme_tipi == '51'){
        if(geneltoplam > bakiyelimit) {
            warn('Sepet tutarı bakiye limitinizin üstünde');
            return false;
        }
    }
    
    var baseUrl = window.BASE_URL || '';
    
    // Butonu disabled yap
    var buton = $(this);
    buton.prop('disabled', true).text('İşleniyor...');
    
    $.ajax({
        url: baseUrl + '/siparisonay',
        method: 'GET',
        data: {kargo:kargo,odeme_tipi:odeme_tipi},
        dataType: 'json',
        success: function(response) {
            if(response.success && response.siparis_id) {
                window.location.replace(baseUrl + "/sepetonay/" + response.siparis_id);
            } else {
                warn(response.error || 'Sipariş oluşturulamadı');
                buton.prop('disabled', false).html('<i class="bx bx-check-double font-size-16 align-middle mr-2"></i>Siparişi Onayla');
            }
        },
        error: function(xhr, status, error) {
            var errorMsg = 'Bir hata oluştu';
            if(xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            }
            warn(errorMsg);
            buton.prop('disabled', false).html('<i class="bx bx-check-double font-size-16 align-middle mr-2"></i>Siparişi Onayla');
        }
    });

});
$('.bakiye-birak').keyup(function(){
    var toplam = Number($('.toplam-tutar').val());
    var bakiye = Number($('.bakiye-birak').val());
    var bakiyelimit = Number($('.bakiye-limit').val());
    if(bakiye < toplam) {
        var sonuc = 0;
        sonuc = toplam-bakiye;
        if(bakiye == '0') {
            $('.odenecek b').text(toplam);
        }
        else {
            $('.odenecek b').text(sonuc);
            $('.kalan-bakiye').text(bakiye);
            $('.odenecek-tutar').val(sonuc);
            $('.kalan-bakiye-limit').text(bakiyelimit-bakiye);
            $('.kalan-bakiye-box').show();
        }
    }
    else {
        warn('Bırakılacak bakiye toplam tutardan büyük olamaz!');
    }


});

// Kampanya paket için sepete ekleme fonksiyonu
function sepetKampanya(uid, kampanyaTipi) {
    var paketAdet = $('#a' + uid).val();
    var cls = '#a' + uid;
    
    if(paketAdet < 1) {
        warn('Paket adetini seçiniz');
        return;
    }
    
    // Kampanya tipini parse et (10+1 gibi)
    var matches = kampanyaTipi.match(/(\d+)\+(\d+)/);
    var alinanAdet = matches ? parseInt(matches[1]) : 10;
    var hediyeAdet = matches ? parseInt(matches[2]) : 1;
    var toplamAdet = paketAdet * (alinanAdet + hediyeAdet);
    var gosterilecekAdet = paketAdet * alinanAdet;
    
    var baseUrl = window.BASE_URL || '';
    
    // CSRF token'ı al
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (!csrfToken) {
        // Alternatif olarak jQuery cookie'den al (eğer varsa)
        csrfToken = $.cookie('XSRF-TOKEN') || '';
    }
    
    console.log('CSRF Token:', csrfToken ? 'Found' : 'Not found');
    
    $.ajax({
        url: baseUrl + '/sepetekleKampanya',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            uid: uid,
            adet: paketAdet,
            kampanya_tipi: kampanyaTipi,
            _token: csrfToken
        },
        success: function(data) {
            console.log('SepetekleKampanya success:', data);
            
            var message = 'Kampanya Paket Sepete Eklendi';
            if(data && data.message) {
                message = data.message;
            } else if(typeof data === 'string') {
                message = data;
            } else {
                message = 'Kampanya Paket Sepete Eklendi (Toplam: ' + toplamAdet + ' adet, Gösterilecek: ' + gosterilecekAdet + ' adet)';
            }
            
            notice(message);
            getbasket();
            $(cls).parent().parent().css('background-color','rgb(239 255 201)');
            $('.form-button[uid='+uid+']').text('Sepetten Çıkart');
            $('.form-button[uid='+uid+']').attr('onclick','sepetcikar('+uid+')');
            $('.form-button[uid='+uid+']').css('background-color','#43b3a8');
        },
        error: function(xhr, status, error) {
            console.error('=== SepetekleKampanya AJAX Error ===');
            console.error('Status:', xhr.status);
            console.error('Status Text:', xhr.statusText);
            console.error('Response Text:', xhr.responseText);
            console.error('Error:', error);
            
            var errorMsg = 'Kampanya paket sepete eklenirken bir hata oluştu.';
            
            if(xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            } else if(xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.error) {
                        errorMsg = response.error;
                    }
                } catch(e) {
                    // JSON parse edilemezse response text'i göster
                    if(xhr.status === 401) {
                        errorMsg = 'Giriş yapmanız gerekiyor. Lütfen sayfayı yenileyin.';
                    } else if(xhr.status === 404) {
                        errorMsg = 'Sayfa bulunamadı. Lütfen sayfayı yenileyin.';
                    } else if(xhr.status === 500) {
                        errorMsg = 'Sunucu hatası oluştu. Lütfen tekrar deneyin.';
                    } else {
                        errorMsg = 'Hata: ' + (xhr.responseText || error);
                    }
                }
            }
            
            warn(errorMsg);
        }
    });
}

// Kampanya paket fiyat güncelleme fonksiyonu
function updateKampanyaFiyat(uid, paketAdet, birimFiyat, alinanAdet, hediyeAdet) {
    if(paketAdet < 1) {
        paketAdet = 1;
    }
    
    // Toplam fiyat = paket adeti * birim fiyat * alınan adet (hediye dahil değil)
    var toplamFiyat = paketAdet * birimFiyat * alinanAdet;
    
    // Formatla
    var formattedPrice = toplamFiyat.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    // Güncelle - € ile göster
    $('#kampanya-toplam-' + uid).text(formattedPrice + ' €');
}

// Sepet toplamlarını hesapla (ödeme tipine göre)
function calculateCartTotals() {
    var baseToplam = parseFloat($('#base-toplam').val() || 0);
    var baseIskonto = parseFloat($('#base-iskonto').val() || 0);
    var odemeTipi = $('#odeme_tipi').val();
    var odemeTipiText = $('#odeme_tipi option:selected').text().toLowerCase();
    
    // Nakit veya Havale/EFT seçildiyse peşin ödeme iskontosu uygula
    var pesinIskontoUygula = false;
    if (odemeTipi != '0' && (odemeTipiText.indexOf('nakit') !== -1 || odemeTipiText.indexOf('havale') !== -1 || odemeTipiText.indexOf('eft') !== -1)) {
        pesinIskontoUygula = true;
    }
    
    // Bayi iskontosu
    var iskontoOran = baseToplam / 100 * baseIskonto;
    
    // İlk ara toplam (bayi iskontosu sonrası)
    var araToplam1 = baseToplam - iskontoOran;
    
    // Peşin ödeme iskontosu (%10) - sadece nakit/havale seçildiyse
    var pesinIskonto = 0;
    var pesinAraToplam = araToplam1;
    if (pesinIskontoUygula) {
        pesinIskonto = araToplam1 / 100 * 10;
        pesinAraToplam = araToplam1 - pesinIskonto;
    }
    
    // KDV (%20) - peşin ara toplam üzerinden hesaplanır
    var kdv = pesinAraToplam / 100 * 20;
    
    // Genel toplam
    var genelToplam = pesinAraToplam + kdv;
    
    // Formatla ve göster
    function formatMoney(value) {
        return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Bayi iskontosu gösterimi
    $('#iskonto-yuzde').text(baseIskonto.toFixed(2));
    $('#t-indirim').text(formatMoney(iskontoOran) + ' €');
    $('#t-aratoplam').text(formatMoney(araToplam1) + ' €');
    
    // Peşin ödeme iskontosu gösterimi (sadece nakit/havale seçildiyse)
    if (pesinIskontoUygula) {
        $('#pesin-iskonto-label').show();
        $('#pesin-aratoplam-label').show();
        $('#t-pesin-iskonto').show().text(formatMoney(pesinIskonto) + ' €');
        $('#t-pesin-aratoplam').show().text(formatMoney(pesinAraToplam) + ' €');
    } else {
        $('#pesin-iskonto-label').hide();
        $('#pesin-aratoplam-label').hide();
        $('#t-pesin-iskonto').hide();
        $('#t-pesin-aratoplam').hide();
    }
    
    // KDV ve genel toplam
    $('#t-kdv').text(formatMoney(kdv) + ' €');
    $('#t-geneltoplam').html('<b>' + formatMoney(genelToplam) + ' €</b>');
    
    // Hidden input'u güncelle
    $('.genel_toplam').val(genelToplam);
}

// Ödeme tipi değiştiğinde fiyatları yeniden hesapla
$(document).ready(function() {
    $('#odeme_tipi').on('change', function() {
        calculateCartTotals();
    });
    
    // Sayfa yüklendiğinde de hesapla
    if ($('#base-toplam').length > 0) {
        calculateCartTotals();
    }
});
