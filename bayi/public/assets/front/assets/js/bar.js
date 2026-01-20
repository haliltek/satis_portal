$(function(){






    $('.f-fullscreen').click(function(){
        var fs = $('.f-image-area .image').attr('fs');
        if(fs=='0') {
            $('.f-image-area .image').animate({'width':'80%','height':'60%'});
            $('.close-f-image, .f-fullscreen').animate({'margin-left':'81%'});
            $('.f-image-area .image').attr('fs','1');
        }
        else {
            $('.f-image-area .image').animate({'width':'450px','height':'350px'});
            $('.close-f-image, .f-fullscreen').animate({'margin-left':'470px'});
            $('.f-image-area .image').attr('fs','0');
        }

    });
    $('.close-f-image').on('click',function(){$('.f-image-area').fadeOut()});
    $('.f-image-box').on('click',function(){$('.f-image-area').fadeIn()});

    $('.adet-arti').click(function(){
       var adet = $('.f-adet').val();
       var yeniadet = Number(adet) + Number(1);
       $('.f-adet').val(yeniadet);
       var fiyat = $('.f-fiyat').attr('fiyat');
       var yenifiyat = Number(yeniadet) * Number(fiyat);
       $('.f-fiyat').text(formatMyMoney(yenifiyat)+'₺');
    });
    $('.adet-eksi').click(function(){
        var adet = $('.f-adet').val();
        if(adet > '1') {
            var yeniadet = Number(adet) - Number(1);
            $('.f-adet').val(yeniadet);
            var fiyat = $('.f-fiyat').attr('fiyat');
            var yenifiyat = Number(yeniadet) * Number(fiyat);
            $('.f-fiyat').text(formatMyMoney(yenifiyat)+'₺');
        }

    });
    $('.f-adet').change(function(){
        var adet = $(this).val();
        var fiyat = $('.f-fiyat').attr('fiyat');
        var yenifiyat = Number(adet) * Number(fiyat);
        $('.f-fiyat').text(formatMyMoney(yenifiyat)+'₺');
    });

    $('.f-arrow').click(function(){
        var stat = $(this).attr('stat');
        if(stat=='1') {
            $('.f-image-area').fadeOut();
            $('.dark-screen').fadeOut()
            setTimeout(function(){
                $('.f-menu-bar').animate({'marginBottom':'-110px'});
                $('.f-arrow i').removeClass('fa-arrow-circle-down');
                $('.f-arrow i').addClass('fa-arrow-circle-up');
                $('.f-arrow span').text('');
                $('.f-arrow').attr('stat','0');
                $('.f-ozellik').animate({'height':'0px'});
                $('.f-ozellik').css({'border':'0px','overflow-y':'hidden'});
                $(this).attr('durum','0');
            },700);
        }
        else {
            $('.f-arrow i').addClass('fa-arrow-circle-down');
            $('.f-arrow i').removeClass('fa-arrow-circle-up');
            $('.f-image-area').fadeIn();
            $('.dark-screen').fadeIn();
            $('.f-arrow').attr('stat','1');
            $('.f-arrow span').text('Kapat');
            $('.f-menu-bar').animate({'marginBottom':'0px'});
        }


    });
    $('.f-esdeger-button').click(function(){
        var id = $(this).attr('id');
        var table = 'esdeger';
        $('.tab-c').hide();
        $('#tab4').fadeIn();
        $('.tab').attr('class','tab');
        $('.tab[v-tab="tab4"]').attr('class','tab active');
        $('.esdeger').DataTable( {
            paging: true,
            "lengthChange": true,
            "pageLength":10,
            "destroy": true,
            "pagingType": "full_numbers",
            "ajax": table+'/'+id

        });
        $('.f-image-area').fadeOut();
        $('.dark-screen').fadeOut()
        setTimeout(function(){
            $('.f-menu-bar').animate({'marginBottom':'-110px'});
            $('.f-arrow i').removeClass('fa-arrow-circle-down');
            $('.f-arrow i').addClass('fa-arrow-circle-up');
            $('.f-arrow span').text('');
            $('.f-arrow').attr('stat','0')
            $('.f-ozellik').animate({'height':'0px'});
            $('.f-ozellik').css('overflow-y','hidden');
            $(this).attr('durum','0')
        },700);

    });
    $('.obt').click(function(){
       var durum = $(this).attr('durum');
       if(durum==0){
           $('.f-ozellik').animate({'height':'370px'});
           $('.f-ozellik').css({'border':'4px solid #ddd','overflow-y':'auto'});
           $(this).attr('durum','1');
       }
       else {
           $('.f-ozellik').animate({'height':'0px'});
           $('.f-ozellik').css({'border':'0px','overflow-y':'hidden'});
           $(this).attr('durum','0')
       }
    });

});
function prdetail(id) {
    var stat = $('.f-arrow').attr('stat');
    if(stat=='1') {

    }
    else {
        $('.f-arrow i').addClass('fa-arrow-circle-down');
        $('.f-arrow i').removeClass('fa-arrow-circle-up');
        $('.f-image-area').fadeIn();
        $('.dark-screen').fadeIn();
        $('.f-arrow').attr('stat','1');
        $('.f-arrow span').text('Kapat');
    }
    // Base URL'i kontrol et ve düzelt
    var baseUrl = window.BASE_URL || '';
    console.log('prdetail - window.BASE_URL:', window.BASE_URL);
    
    // Eğer baseUrl boşsa veya sadece / ise, mevcut sayfanın base URL'ini kullan
    if (!baseUrl || baseUrl === '' || baseUrl === '/') {
        // Mevcut sayfanın origin ve path'ini al
        var origin = window.location.origin;
        var pathname = window.location.pathname;
        
        // Path'ten son segment'i kaldır (örn: /home -> /)
        var pathParts = pathname.split('/').filter(function(p) { return p; });
        if (pathParts.length > 0 && pathParts[pathParts.length - 1] === 'home') {
            pathParts.pop();
        }
        
        // Base path'i oluştur
        var basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '';
        baseUrl = origin + basePath;
        
        console.log('prdetail - Calculated baseUrl from location:', baseUrl);
    }
    
    // Eğer baseUrl sonunda / varsa kaldır
    if (baseUrl.endsWith('/')) {
        baseUrl = baseUrl.slice(0, -1);
    }
    
    var route = baseUrl + '/productdetail/'+id;
    console.log('prdetail - Base URL:', baseUrl);
    console.log('prdetail - Route:', route);
    $('.f-bar').show();
    $('.dark-screen').fadeIn();
    $('.f-image-area').fadeIn()
    $('.f-bar').animate({'marginBottom':'0px'});
    $('.f-menu-bar').animate({'marginBottom':'-0px'});
    $.getJSON(route,function(data){
        // data bir obje ise direkt kullan, array ise ilk elemanı al
        var val = Array.isArray(data) ? data[0] : data;
        
        if (val && val.urun_adi) {
            $('.f-bar .f-ad').text(val.urun_adi || 'Ürün Adı Yok');
            $('.f-bar .f-kod b').text(val.urun_kodu || '');
            $('.f-bar .f-stok b').text(val.stok || '0');
            // Fiyatı € ile göster
            var fiyatStr = val.fiyat || '0';
            if (fiyatStr !== '0' && fiyatStr !== '') {
                if (String(fiyatStr).indexOf('€') === -1 && String(fiyatStr).indexOf('₺') === -1) {
                    fiyatStr += ' €';
                } else {
                    fiyatStr = String(fiyatStr).replace('₺', '€');
                }
            } else {
                fiyatStr = '0,00 €';
            }
            $('.f-bar .f-fiyat').text(fiyatStr);
            $('.f-bar .f-fiyat').attr('fiyat',val.fiyat || '0');
            $('.product-detailmodal #d-tanimad').text(val.tanimadi || '');
            $('.product-detailmodal #d-tanimdeger').text(val.tanim_deger || '');
            $('.f-bar .f-oz p').html(val.aciklama || 'Açıklama bulunamadı.');
            
            // Resim URL'ini oluştur
            var imageUrl = '';
            if (val.resim && val.resim !== '') {
                // Eğer resim tam URL ise direkt kullan, değilse baseUrl ekle
                if (val.resim.startsWith('http://') || val.resim.startsWith('https://')) {
                    imageUrl = val.resim;
                } else {
                    imageUrl = baseUrl + '/' + val.resim;
                }
            } else {
                // Resim yoksa stok kodundan dinamik URL oluştur
                if (val.urun_kodu && val.urun_kodu !== '') {
                    var ilkIkiKarakter = val.urun_kodu.substring(0, 2);
                    imageUrl = 'https://gemas.com.tr/public/uploads/images/malzeme/' + ilkIkiKarakter + '/' + val.urun_kodu + '.jpg';
                }
                // Görsel yoksa boş bırak
            }
            
            if (imageUrl && imageUrl !== '') {
                $('.f-bar .f-resim').attr('src', imageUrl).on('error', function(e) {
                    var $img = $(this);
                    // Tekrar yüklenmeyi engelle ve gizle
                    $img.off('error');
                    $img.hide();
                    // Event'i durdur
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }).show();
                
                $('.f-bar .f-resim2').attr('src', imageUrl).on('error', function(e) {
                    var $img = $(this);
                    // Tekrar yüklenmeyi engelle ve gizle
                    $img.off('error');
                    $img.hide();
                    // Event'i durdur
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }).show();
            } else {
                $('.f-bar .f-resim').hide();
                $('.f-bar .f-resim2').hide();
            }
            
            $('.f-bar .btn-success').attr('onclick','sepet3('+id+')');
            $('.f-bar .btn-success').attr('uid',id);
            $('.f-bar .f-input').attr('id','f'+id);
            $('.f-bar .f-esdeger-button').attr('id',id);
        } else {
            console.error('Ürün detayı alınamadı:', data);
            alert('Ürün detayı yüklenemedi.');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX hatası:', textStatus, errorThrown);
        alert('Ürün detayı yüklenirken bir hata oluştu.');
    });
    var baseUrl = window.BASE_URL || '';
    // Eğer baseUrl sonunda / varsa kaldır
    if (baseUrl.endsWith('/')) {
        baseUrl = baseUrl.slice(0, -1);
    }
    
    $.getJSON(baseUrl + '/urunoem/'+id,function(data){
        console.log('urunoem response:', data);
        $('.product-detailmodal #d-oem').text('');
        $('.f-bar .f-oem p').text('');
        if (Array.isArray(data) && data.length > 0) {
            $.each(data,function(index,val){
                if (val.oem) {
                    $('.f-bar .f-oem p').append(val.oem + ' ');
                }
            });
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('urunoem AJAX Error:', textStatus, errorThrown);
        console.error('Response:', jqXHR.responseText);
        console.error('URL:', baseUrl + '/urunoem/'+id);
    });
    $.getJSON(baseUrl + '/urunresim/'+id,function(res){
        $('.f-image').html('');
        if (Array.isArray(res) && res.length > 0) {
            $.each(res,function(index,val){
                var imageUrl = '';
                if (val.resim && val.resim !== '') {
                    // Eğer resim tam URL ise direkt kullan
                    if (val.resim.startsWith('http://') || val.resim.startsWith('https://')) {
                        imageUrl = val.resim;
                    } else {
                        // Eğer relative path ise baseUrl ekle
                        imageUrl = baseUrl + '/' + val.resim.replace(/^\//, '');
                    }
                }
                
                console.log('urunresim - Image URL:', imageUrl);
                
                if (imageUrl && imageUrl !== '') {
                    var imgHtml = '<img class="d-block img-fluid" src="'+imageUrl+'" onerror="this.onerror=null;this.style.display=\'none\';">';
                    
                    if(index==0) {
                        $('.f-image').append('<div class="carousel-item active">\n' + imgHtml + '\n</div>');
                    }
                    else {
                        $('.f-image').append('<div class="carousel-item">\n' + imgHtml + '\n</div>');
                    }
                }
            });
        } else {
            // Resim yoksa bir şey yapma veya boş mesaj göster
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('urunresim AJAX Error:', textStatus, errorThrown);
        console.error('Response:', jqXHR.responseText);
    });
}
