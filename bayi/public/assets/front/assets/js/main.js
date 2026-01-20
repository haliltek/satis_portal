$(function(){
    var baseUrl = window.BASE_URL || '';
    $.get(baseUrl + '/contract',{},function(cont){
       if(cont != '1'){
           $('.contract-modal').fadeIn();
           getcontract(11);
           $('.access-contract').show();
           $('.closecontract').hide();

           $.get(baseUrl + '/adresdurum',{},function(data){
               if(data != '1'){
                    $('.adresmodal-outer').fadeIn();
               }
           });
       }
    });
    $('.parolaguncelle').on('click',function(){
            var oldpass = $('#oldpass').val();
            var newpass = $('#newpass').val();
            var newre = $('#newpassr').val();
        $.get('parolaguncelle/',{oldpass:oldpass,newpass:newpass,newre:newre},function(veri){
            if(veri == '1') {
                notice('Parolanız güncellendi');
            }
            else if(veri == '2') { warn('Hatalı eski parola'); }
            else {
                warn('Hata!');
            }
        });
    });

    $('.sepetadet').change(function(){
       var urunid = $(this).attr('u-id');
       var adet = $(this).val();
       var baseUrl = window.BASE_URL || '';

       $.get(baseUrl + '/upbasket',{urunid:urunid,adet:adet},function(data) {
           if(data=='2') { warn('Adet 1 den küçük olamaz'); }
           else if(data=='0') { warn('Güncelleme hatası!'); }
           else {
               notice('Sepetiniz güncellendi');
               $.each(data,function(index,val){
                   // Kampanya paket ise gösterilecek adeti kullan, değilse normal adeti
                   var hesaplamaAdet = (val.kampanya_tipi && val.gosterilen_adet) ? val.gosterilen_adet : val.adet;
                   var fiyat = Number(val.fiyat * hesaplamaAdet);
                   $('#fiyat'+urunid).text(formatMyMoney(fiyat)+'₺');
               });
               $.getJSON(baseUrl + '/basketprice',function(veri){
                   $.each(veri,function(index,value){
                        // Ödeme tipine göre ek iskonto ekle
                        if (typeof calculateCartTotals === 'function') {
                            calculateCartTotals();
                        } else {
                            // Fallback: Normal gösterim
                            $('#t-toplam').text(value.toplam+' €');
                            $('#t-indirim').text(value.indirim+' €');
                            $('#t-aratoplam').text(value.aratoplam+' €');
                            $('#t-kdv').text(value.kdv+' €');
                            $('#t-geneltoplam').text(value.geneltoplam+' €');
                        }
                   });
               })
           }
       });
    });


    var path = window.location.pathname;
    var urlControl = path.indexOf('sepetonay');
    if(urlControl != -1) {  
        // Sepetonay sayfasında bu AJAX çağrısını yapma
        return;
    }
    else {
        var baseUrl = window.BASE_URL || '';
        $.get(baseUrl + '/sipcontrol',{},function(sip){
            if(sip != '0') {
                var siparisLink = baseUrl + '/siparisler';
                $('body').append('<a href="' + siparisLink + '"><div class="siparis-bilgi"><div class="sipadet">'+ sip +'</div><div class="siptext">Siparişi Tamamla</div></div></a>');
            }
        }).fail(function(xhr, status, error) {
            console.error('sipcontrol AJAX hatası:', error);
        });
    }
    //pos
    $('#odeme-yap').click(function(){
       //$('.pos-modal').fadeIn();
       var baseUrl = window.BASE_URL || '';
       var fiyat = $('.odenecek-tutar').val();
       var sid = $('.sipid').val();
       var sipnot = $('.siparis-not').val();
       $.get(baseUrl + '/sipnot/',{sipnot:sipnot,sid:sid},function(veri) {
           if(veri==1) {
               notice('Sipariş bilgileri kayıt edildi');
               $.get(baseUrl + '/pos/',{fiyat:fiyat,sid:sid},function(data) {
                   $('.pos-modal').html(data);
               });
           }
           else { warn('hata!');}

       });

    });


    $('.posgetir').click(function(){
        var baseUrl = window.BASE_URL || '';
        $('.odeme-bekleyen').html('');
           $('.paymodal').fadeIn();
           $.getJSON(baseUrl + '/odenmemis',{},function(data){
              $.each(data,function(index,add){
                  $('.odeme-bekleyen').append('<tr><td>'+ add.sip_id +'</td><td>'+ add.tarih +'</td><td>'+ shortprice(add.geneltoplam) +'₺</td><td>Ödeme bekliyor</td><td><button class="btn btn-primary" sipid="'+add.sip_id+'" onclick="gopay('+add.sip_id+')" tutar="'+add.geneltoplam+'" id="sip'+add.sip_id+'">Ödeme Yap</button></td></tr>');
              });
           });
           $('.paymodal-close').click(function(){
               $('.paymodal').fadeOut();
           });
           $('.odemeyap').click(function(){
               var fiyat = $('.odemetutar').val();
               $.get(baseUrl + '/pos',{fiyat:fiyat},function(veri) {
                   $('.pos-area').html(veri);
                   $('.paymodal').fadeOut();
               });
           });
    });

    $('.editorder').click(function(){
       var sid = $(this).attr('sid');
       $('.sepet-icerik').append('<div class="editordermodal"><div class="card"><div class="card-body" style="box-shadow: 0px 0px 8px #999;"><h5 style="m-b-20">Sipariş Düzenle</h5><div class="col-lg-5 fleft p-l-0"><label>Ödeme tipi</label><select class="form-control" id="e-odemetip"></select></div><div class="col-lg-5 fleft p-r-0"><label>Kargo</label><select class="form-control" id="e-kargo"></select></div><div class="col-lg-2 fleft p-r-0"><button class="btn btn-success fright saveneworder" style="margin-top:29px;">Kaydet</button></div><div class="col-lg-12 fleft m-t-20 listegetir p-0"></div><div class="col-lg-12 fleft m-t-20 p-0"><button class="btn btn-danger fright m-r-5 close-ordereditmodal">Kapat</button></div></div></div></div>')
       $('.listegetir').append('<table id="datatable-buttons2" class="table table-striped table-bordered dt-responsive nowrap ordereditlist" style="border-collapse: collapse; border-spacing: 0; width: 100%;"><thead><tr height="50"><th width="10%" >Acar No</th><th width="40%">Ürün Adı</th><th width="10%">Adet</th><th width="12%">Tutar</th><th width="12%">Toplam</th></tr></thead><tbody></tbody></table>');
        $.getJSON('siparisbilgi/',{sid:sid},function(data){
            $.each(data,function(index,val){
                $('#e-odemetip').append('<option value="'+val.odeme+'" selected>Seçili : '+val.odeme_adi+'</option>');
                $('#e-kargo').append('<option value="'+val.kargo+'" selected>Seçili : '+val.name+'</option>');

            });
       });
       $.getJSON('/panel/kargolist2/',{sid:sid},function(data){
            $.each(data,function(index,val){
                $('#e-kargo').append('<option value="'+val.id+'">'+val.name+'</option>');
            });
       });
       $.getJSON('/panel/odemetip',{sid:sid},function(data){
            $.each(data,function(index,val){
                $('#e-odemetip').append('<option value="'+val.id+'">'+val.odeme_adi+'</option>');
            });
       });
        $.getJSON('detail/'+sid,function(veri){
            veri.forEach(function(ekle){
                var total = Number(ekle.adet) * Number(ekle.tutar);
                var indirim = Number(ekle.siptutar) / 100 * Number(ekle.sipiskonto);
                $('.ordereditlist tbody').append('<tr><td>'+ekle.urun_kodu+'</td><td>'+ekle.urun_adi+'</td><td><input type="number" class="form-control sepetadet0" u-id="'+ ekle.s_urun_id +'" sipid="'+ ekle.sipid +'" min="1" value="'+ekle.adet+'" /></td><td id="tutar'+ ekle.s_urun_id +'">'+formatMyMoney(ekle.tutar)+'₺</td><td id="toplam'+ ekle.s_urun_id +'">'+ formatMyMoney(ekle.tutar) +'₺</td></tr>');
            });
            $('.sepetadet0').change(function(){
                var urunid = $(this).attr('u-id');
                var adet = $(this).val();


                if(adet=='0') { warn('Adet 1 den küçük olamaz'); }
                else {
                    notice('Sepetiniz güncellendi');
                    $.getJSON('siparisurun/',{urunid:urunid,adet:adet,sid:sid},function(sepet){
                        $.each(sepet,function(index,pr) {
                           $('#tutar'+urunid).text(formatMyMoney(pr.tutar)+'₺');
                           $('#toplam'+urunid).text(formatMyMoney(pr.genel_toplam)+'₺');
                        });
                    });
                }

            });
        });
       $('.close-ordereditmodal').on('click',function(){
           $('.editordermodal').remove();
           window.location.reload();
       });


       $('.saveneworder').click(function(){
          var kargo = $('#e-kargo').val();
          var odeme = $('#e-odemetip').val();
          $.get('updateorder',{kargo:kargo,odeme:odeme,sid:sid},function(veri){
              if(veri == '1') {
                  notice('Siparişiniz güncellendi');
                  setTimeout(function(){
                      $('.editordermodal').remove();
                  },1000)
              }
              else { warn('hata!') }
          });
       });
    });

    $('.close-adresmodal, .close-adresmodal2').click(function(){
        $('.adresmodal-outer').fadeOut();
    });

    //endpos
    $('.s-body h4').append('<i class="fa fa-chevron-up"></i>');
    function imagepreview(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#prevlogo').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#up-logo").change(function() {
        imagepreview(this);
    });
    $('.s-body').find('.row').slideUp(0);
    setTimeout(function(){
        $('.s-body').find('.row').slideDown(1200);
    },300)

    $('.s-body h4').click(function(){
        $(this).parent().find('.row').animate({height:'toggle'});
        var dh = $(this).parent().height();
        if(dh < '40') {
            $(this).children('i').attr('class','fa fa-chevron-up');
        }
        else {
            $(this).children('i').attr('class','fa fa-chevron-down');
        }
    });
    /*$('.s-body h4').click(function(){
        var dh = $(this).parent().height();
        if(dh == '15') {
            // $(this).parent().css({'height':'auto'});
            $(this).parent().animate({maxHeight:1000},1500);
            $(this).children('i').attr('class','fa fa-chevron-up');
        }
        else {
            // $(this).parent().css({'height':'55px'});
            $(this).parent().animate({maxHeight:55},1000);
            $(this).children('i').attr('class','fa fa-chevron-down');
        }

    });*/
    // tab
    var tabid = $('.tab[class="tab active"]').attr('v-tab');
    $('.tab-c').hide();
    $('#' + tabid).fadeIn();
    $('.tab').click(function(){
        var tid = $(this).attr('v-tab');
        $('.tab').attr('class','tab');
        $(this).attr('class','tab active');
        $('.tab-c').hide();
        $('#' + tid).fadeIn();

        // tab kontrol
        if ($('#tab3').is(':visible')) {
            // veri getir
        }
    });
    // end tab
    $('.sepet').hover(
        function(){
            $('.sepet-detay').fadeIn();
        },
        function(){
            setTimeout(function(){
                $('.sepet-detay').fadeOut();
            },500);
        });

    $('.pos').click(function(){
        $('.cardmodal').fadeIn();
    });
    $('.c-pos-modal').click(function(){
        $('.cardmodal').fadeOut();
    });

    $('.siparis').click(function(){
        $('.siparismodal').fadeIn();
    });
    $('.c-siparis-modal').click(function(){
        $('.siparismodal').fadeOut();
    });

    $('.show-basket').click(function(){
        //$('.list-table .s-body .row').slideUp(300);
        $('.sepet-icerik').fadeIn();
    })
    // sepet kargo işlem
    $('.kargo-sec').click(function(){
        $('.kargo-modal').fadeIn();
    });
    $('.kurye-sec').click(function(){
        $('.kurye-modal').fadeIn();
    });
    $('.kargo-ekle').click(function(){
        $('.kargo').val($(this).attr('content'));
        $('.kargo2').val($(this).attr('ad'));
        $('.kargo-modal').fadeOut();
    });
    $('.kurye-ekle').click(function(){
        $('.kargo').val('Seçilen : Kurye - ' + $(this).attr('content'));
        $('.kurye-modal').fadeOut();
    });
    $('.c-k-modal').click(function(){
        $('.k-modal').fadeOut();
    });


    $('.payorder').click(function(){
        if($('#odeme_tipi').val() == 1) {
            $('.cardmodal').fadeIn();
        }
    });
    // pos
    $('#cardnumber').on('keyup keypress',function(){
        $(this).val($(this).val().replace(/[A-Z\.]/i,''));
        $('.credit-card .number').text($(this).val());
        var number = $(this).val();
        if(number.length == 4) { $(this).val(number + ' '); }
        else if(number.length == 9) { $(this).val(number + ' '); }
        else if(number.length == 14) { $(this).val(number + ' '); }
    });

    $('#cardname').keyup(function(){
        $('.credit-card .name').text($(this).val());
    });
    $('#fdate,#ldate').change(function(){
        $('.credit-card .exp').text($('#fdate').val() + '/' + $('#ldate').val());
    });

    // adres ekle
    $('.adresekle').click(function(){
        var adresad = $('#adresad').val();
        var adres = $('#adres').val();
        var sehir = $('#sehir').val();
        var ilce = $('#ilce').val();
        var telefon = $('#telefon').val();
        if(adresad,adres,sehir,ilce != '') {
            var baseUrl = window.BASE_URL || '';
            $.get(baseUrl + '/adreskaydet',{adresad:adresad,adres:adres,sehir:sehir,ilce:ilce,telefon:telefon},function(data) {
                notice(data);
                $.getJSON(baseUrl + '/adreslist',function(veri){
                    $('.adresler').html('<h6>Kayıtlı Adreslerim</h6>');
                    veri.forEach(function(ekle,index){
                        if(ekle.durum != '1') { var durum = '<div style="color:#ff6114; margin-top:10px">Onay Bekliyor</div>'; }
                        else { var durum = '<div style="color:#45cb85; margin-top:10px">Onaylandı</div>'; }
                        $('.adresler').append('<div class="address-blok"><div class="address-title">' + ekle.baslik +' <i onclick="adresil(' + ekle.adres_id + ')" class="fa fa-trash fright deladress" aid="' + ekle.adres_id + '">Sil</i><i class="fa fa-edit fright editadress" style="margin-right:5px;" >Düzenle</i></div><div class="address-text">' + ekle.adres +' ' + ekle.ilce +' ' + ekle.il +'<br>' + ekle.tel +'</div><div class="onay-durum">'+durum+'</div></div>');
                    });
                });
                $('#adresad,#adres,#sehir,#ilce,#telefon').val('');
            });
        }
        else {
            warn('Tüm alanları doldurun!');
        }


    });
    var baseUrl = window.BASE_URL || '';
    $.getJSON(baseUrl + '/adreslist',function(veri){
        veri.forEach(function(ekle){
            if(ekle.durum != '1') { var durum = '<div style="color:#ff6114; margin-top:10px">Onay Bekliyor</div>'; }
            else { var durum = '<div style="color:#45cb85; margin-top:10px">Onaylandı</div>'; }
            $('.adresler').append('<div class="address-blok"><div class="address-title">' + ekle.baslik +' <i onclick="adresil(' + ekle.adres_id + ')" class="fa fa-trash fright deladress" aid="' + ekle.adres_id + '"></i><i class="fa fa-edit fright editadress" style="margin-right:5px;" ></i></div><div class="address-text">' + ekle.adres +' ' + ekle.ilce +' ' + ekle.il +'<br>' + ekle.tel +'</div><div class="onay-durum">'+durum+'</div></div>');
        });
    });



    $('.pmakbuz').click(function(){
        print();
    });
    $('#oem').keyup(function(){
        if($(this).val() != '') {
            $('#acar_no').prop('disabled',true);
        }
    });
    $('#oem').blur(function(){
        if($(this).val() == '') {
            $('#acar_no').prop('disabled',false);
        }
        else {
            warn('Oem No ve Acar No alanları aynı anda girilemez!');
        }
    });

    $('#acar_no').keyup(function(){
        if($(this).val() != '') {
            $('#oem').prop('disabled',true);
        }
    });
    $('#acar_no').blur(function(){
        if($(this).val() == '') {
            $('#oem').prop('disabled',false);
        }
        else {
            warn('Oem No ve Acar No alanları aynı anda girilemez!');
        }
    });
    $('.showdetail').click(function(){
        $('.siparis-modal').fadeIn();
       var pid = $(this).attr('pid');
       var route = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/detail/'+pid;
       $('.siparis-modal h5 b').text('Sipariş #'+ pid);

        $('.siparis-modal table tbody').html('');
        $('#siparis-toplam').text('0,00₺');
        $('#kdv').text('0,00₺');
        $('#brut').text('0,00₺');
        $('#iskonto-tutar').text('0%');
        $('#indirim').text('0,00₺');
        $('#ara-toplam').text('0,00₺');
        
        $.getJSON(route,function(veri){
            if (veri && veri.length > 0) {
                veri.forEach(function(ekle){
                    var total = Number(ekle.adet) * Number(ekle.tutar);
                    var indirim = Number(ekle.siptutar) / 100 * Number(ekle.sipiskonto);
                    $('.siparis-modal .siparisurunliste tbody').append('<tr><td>'+ekle.urun_kodu+'</td><td>'+ekle.urun_adi+'</td><td>'+ekle.adet+'</td><td>'+formatMyMoney(ekle.tutar)+'₺</td><td>%'+ekle.iskonto+'</td><td>'+formatMyMoney(ekle.kdv)+'₺</td><td>'+ formatMyMoney((Number(ekle.adet) * Number(ekle.tutar) - Number(indirim) + Number(ekle.kdv))) +'₺</td></tr>');
                    var geneltoplam = ekle.geneltoplam;
                    $('#siparis-toplam').text(formatMyMoney(geneltoplam)+'₺');
                    //$('#siparis-toplam').benjaminjs();
                    $('#kdv').text(formatMyMoney(ekle.sipkdv)+'₺');
                    $('#brut').text(formatMyMoney(ekle.siptutar)+'₺');
                    $('#iskonto-tutar').text(ekle.sipiskonto+'%');


                    $('#indirim').text(formatMyMoney(indirim)+'₺');
                    $('#ara-toplam').text(formatMyMoney(Number(ekle.siptutar) - Number(indirim))+'₺');
                });
            } else {
                $('.siparis-modal .siparisurunliste tbody').append('<tr><td colspan="7" style="text-align:center;">Ürün bulunamadı</td></tr>');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Sipariş detayı yüklenemedi:', textStatus, errorThrown);
            $('.siparis-modal .siparisurunliste tbody').append('<tr><td colspan="7" style="text-align:center;">Sipariş detayı yüklenemedi</td></tr>');
        });
        $('.closedetail').click(function(){
            $('.siparis-modal').fadeOut();
        });
        $('.detaylink').attr('href', (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/siparis/'+ pid);
    });
    $('.closedetail').click(function(){
        $('.siparis-modal').fadeOut();
    });

    $('.sirket-guncelle').click(function(){
        var tel = $('#tel').val();
        var gsm = $('#gsm').val();
        var adres = $('#adres').val();
        var sirketadres = $('#sirket-adres').val();
        var il = $('#il').val();
        var ilce = $('#ilce').val();
        var hesapad = $('#hesap-ad').val();
        var banka = $('#banka').val();
        var sube = $('#sube').val();
        var iban = $('#iban').val();
        $.get('adrestalep',{tel:tel,gsm:gsm,adres:adres,sirketadres:sirketadres,il:il,ilce:ilce,hesapad:hesapad,banka:banka,sube:sube,iban:iban},function(data){
           if(data=='1') {
               notice('Adres değişiklik talebiniz alındı');
            }
        });

    });
    // feedback form
    $('.feedClick').click(function(){
        var form = $('.feedback');
        form.animate({
           marginRight:'0%'
        });
        var closeFeed = $('.close-feedback');
        closeFeed.click(function(){
            form.animate({
                marginRight:'-43%'
            });
        });
    });
    $('.send-feedback').click(function(){
        var form = $('.feedback');
        var success = 'Mesajınız gönderildi.';
        var error = 'Hata! mesaj gönderilemedi';
        var isim = $('#f-isim').val();
        var secenek = $('#f-secenek').val();
        var konu = $('#f-konu').val();
        var mesaj = $('#f-mesaj').val();

       $.get('send-feedback',{isim:isim,secenek:secenek,konu:konu,mesaj:mesaj},function(data){
           if(data == 1){
               notice(success);
               form.animate({
                   marginRight:'-43%'
               });
           }
           else { warn(error); }
       });

    });

});
function notice(text) {
    var noticeBox = $('.notice-box');
    $('.footer').append('<div class="notice-box access-notice"><i class="fa fa-bell" style="margin-right:10px;"></i>' + text + '</div>');
    $('.notice-box').fadeIn(800);
    setTimeout(function(){
        $('.notice-box').fadeOut(800);
        setTimeout(function(){
            $('.notice-box').remove();
        },1000);
    },3000);
}
function warn(text) {
    var noticeBox = $('.notice-box');
    $('.footer').append('<div class="notice-box warn-notice"><i class="fa fa-exclamation-triangle" style="margin-right:10px;"></i>' + text + ' <button type="button" class="close fright"><span aria-hidden="true">×</span></button></div>');
    $('.notice-box').fadeIn(800);
    setTimeout(function(){
        $('.notice-box').fadeOut(800);
        setTimeout(function(){
            $('.notice-box').remove();
        },1000);
    },3000);
}
function notice2(text) {
    $('.footer').append('<div class="notice-box2 access-notice"><i class="fa fa-bell" style="margin-right:10px;"></i>' + text + '</div>');
    $('.notice-box2').fadeIn(800);
    setTimeout(function(){
        $('.notice-box2').fadeOut(800);
        setTimeout(function(){
            $('.notice-box2').remove();
        },1000);
    },3000);
}
function warn2(text) {
    $('.footer').append('<div class="notice-box2 warn-notice"><i class="fa fa-exclamation-triangle" style="margin-right:10px;"></i>' + text + ' <button type="button" class="close fright"><span aria-hidden="true">×</span></button></div>');
    $('.notice-box2').fadeIn(800);
    setTimeout(function(){
        $('.notice-box2').fadeOut(800);
        setTimeout(function(){
            $('.notice-box2').remove();
        },1000);
    },3000);
}
function adresil(adresid) {
    var buton = $('.deladress[aid="'+adresid+'"]');
    $.get('adressil/', {adresid:adresid},function(data) {
        if(data == 1) {
            notice('Adres silindi.');
            $(buton).parent().parent().remove();
        }
        else { warn('Adres silme hatası!'); }
    });
}


function showdetail(id){

    $('.siparis-modal').fadeIn();
    var route = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/detail/'+id;
    $('.siparis-modal h5 b').text('Sipariş #'+ id);

    $('.siparis-modal table tbody').html('');
    $('#siparis-toplam').text('0,00₺');
    $('#kdv').text('0,00₺');
    $('#brut').text('0,00₺');
    $('#iskonto-tutar').text('0%');
    $('#indirim').text('0,00₺');
    $('#ara-toplam').text('0,00₺');
    
    $.getJSON(route,function(veri){
        if (veri && veri.length > 0) {
            $.each(veri,function(index,ekle){
                var total = Number(ekle.adet) * Number(ekle.tutar);
                $('.siparis-modal .siparisurunliste tbody').append('<tr><td>'+ekle.urun_kodu+'</td><td>'+ekle.urun_adi+'</td><td>'+ekle.adet+'</td><td>'+ekle.tutar+'₺</td><td>%'+ekle.iskonto+'</td><td>'+ shortprice(ekle.kdv) +'₺</td><td>'+ shortprice(Number(ekle.adet) * Number(ekle.tutar) + Number(ekle.kdv)) +'₺</td></tr>');
                var geneltoplam = Number(ekle.geneltoplam);
                geneltoplam = geneltoplam.toFixed(2);
                $('#siparis-toplam').text(geneltoplam +'₺');
                var kdv = Number(ekle.sipkdv);
                $('#kdv').text(kdv.toFixed(2) +'₺');
                $('#brut').text(shortprice(ekle.siptutar) +'₺');
                $('#iskonto-tutar').text(ekle.sipiskonto+'%');

                var indirim = Number(ekle.siptutar) / 100 * Number(ekle.sipiskonto);
                $('#indirim').text(shortprice(indirim) +'₺');
                $('#ara-toplam').text(shortprice(Number(ekle.siptutar) - Number(indirim))+'₺');
            });
        } else {
            $('.siparis-modal .siparisurunliste tbody').append('<tr><td colspan="7" style="text-align:center;">Ürün bulunamadı</td></tr>');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Sipariş detayı yüklenemedi:', textStatus, errorThrown);
        $('.siparis-modal .siparisurunliste tbody').append('<tr><td colspan="7" style="text-align:center;">Sipariş detayı yüklenemedi</td></tr>');
    });

    $('.detaylink').attr('href','/siparis/'+ pid);
};
function shortprice(price) {
    var newPrice = Number(price);
    newPrice = newPrice.toFixed(2);
    return newPrice;
}

function productimage(id) {
    $('.image-modal').remove();
    var kid = '#k'+id;
    var baseUrl = window.BASE_URL || '';
    
    // Önce ürünün stok kodunu al
    $.getJSON(baseUrl + '/productdetail/' + id, function(productData) {
        var val = Array.isArray(productData) ? productData[0] : productData;
        var stokkodu = val.urun_kodu || '';
        var imageUrl = '';
        
        if (stokkodu) {
            // Stok kodunun ilk 2 karakterini al
            var ilkIkiKarakter = stokkodu.substring(0, 2);
            // Dinamik görsel URL'ini oluştur
            imageUrl = 'https://gemas.com.tr/public/uploads/images/malzeme/' + ilkIkiKarakter + '/' + stokkodu + '.jpg';
        } else {
            // Stok kodu yoksa varsayılan görseli kullan
            imageUrl = baseUrl + '/assets/front/assets/images/unnamed.png';
        }
        
        // Modal oluştur
        $(kid).parent().append('<div class="image-modal"><img src="' + imageUrl + '" onerror="this.src=\'' + baseUrl + '/assets/front/assets/images/unnamed.png\';" /><i class="fa fa-times-circle imagemodal-close" onclick="destroyproductimage()"></i></div>');
        if($(window).width() < '768') {
            $('.image-modal').css({'left':'0','width':'100%'});
        }
    }).fail(function() {
        // Ürün detayı alınamazsa varsayılan görseli göster
        var imageUrl = baseUrl + '/assets/front/assets/images/unnamed.png';
        $(kid).parent().append('<div class="image-modal"><img src="' + imageUrl + '" /><i class="fa fa-times-circle imagemodal-close" onclick="destroyproductimage()"></i></div>');
        if($(window).width() < '768') {
            $('.image-modal').css({'left':'0','width':'100%'});
        }
    });

}
function destroyproductimage() {
    $('.image-modal').remove();
}
function siparisSil(sid) {
    $.get('siparissil/',{sid:sid},function(veri){
        if(veri=='1') {
            $('.'+sid).remove();
            notice('Siparişiniz silindi.');
            window.location.replace("siparisler");
        }
        else {
            warn('Hata!')
        }
    });
}

function gopay(sid) {
    var fiyat = $('#sip'+sid).attr('tutar');
    $.get('pos/',{fiyat:fiyat,sid:sid},function(data) {
        $('.pos-area').html(data);
        $('.paymodal').fadeOut();
    });
}


function odemebildir(sid) {

    $('.bildirim-modal').fadeIn();
    $.get('siparisfiyat',{sid:sid},function(veri){
        $('.odenen-miktar').val(formatMyMoney(veri));
    });

    $('.close-bildirimmodal').click(function(){
        $('.bildirim-modal').fadeOut();
    });
    $('.odemeKaydet').click(function(){
        var hesap = $('.hesapbilgi').val();
        var odenen = $('.odenen-miktar').val();
        var gonderen = $('.gonderen').val();
        if(hesap=='') {
            warn('Hesap bilgisi boş bırakılamaz');
        }
        else if(odenen=='') {
            warn('Ödeme miktarını giriniz');
        }
        else if(gonderen=='') {
            warn('Gönderen bilgisini giriniz');
        }
        else {
            $.get('odemebildir',{hesap:hesap,odenen:odenen,gonderen:gonderen,sid:sid},function(data){
                if(data==1){
                    notice('Ödeme bildirimi gönderildi');
                    $('.bildirim-modal').fadeOut();
                }
            });
        }

    });
}
function getcontract(num) {
    var baseUrl = window.BASE_URL || '';
    $.get(baseUrl + '/getcontract/' + num, function(data){
        $('.contract-text').html(data);
        //$('.backcontract').show();
    });
}

// Sözleşme butonları için event listener'lar (sayfa yüklendiğinde bir kez tanımlanmalı)
$(document).ready(function() {
    // Back contract butonu
    $(document).on('click', '.backcontract', function(){
        $('.contract-text').html('<div class="contract-box fleft" onclick="getcontract(11)"><p><b>Mesafeli Satış Sözleşmesi</b></p><p>Sözleşmeyi Oku</p></div><div class="contract-box fleft" onclick="getcontract(41)"><p><b>Gizlilik ve Güvenlik</b></p><p>Sözleşmeyi Oku</p></div><div class="contract-box fleft" onclick="getcontract(1)"><p><b>Üyelik Sözleşmesi</b></p><p>Sözleşmeyi Oku</p></div><div class="contract-box fleft" onclick="getcontract(51)"><p><b>KVKK Aydınlatma Metni</b></p><p>Sözleşmeyi Oku</p></div>');
        $(this).hide();
    });
    
    // Access contract butonu (Sözleşmeleri Onayla)
    $(document).on('click', '.access-contract', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var baseUrl = window.BASE_URL || '';
        var $button = $(this);
        var originalText = $button.text();
        
        // Butonu devre dışı bırak ve loading göster
        $button.prop('disabled', true).text('Onaylanıyor...');
        
        console.log('verifycontract çağrılıyor:', baseUrl + '/verifycontract');
        
        $.ajax({
            url: baseUrl + '/verifycontract',
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(response){
                console.log('verifycontract success:', response);
                
                if(response && response.status == 1){
                    alert('Tüm sözleşmeler onaylandı.');
                    // Modal'ı kapat
                    $('.contract-modal').fadeOut(300, function(){
                        $(this).hide();
                    });
                    // Sayfayı yenile
                    setTimeout(function(){
                        location.reload();
                    }, 500);
                } else {
                    alert('Sözleşme onaylanırken bir hata oluştu: ' + (response ? (response.message || 'Bilinmeyen hata') : 'Yanıt alınamadı'));
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('verifycontract error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                console.error('Response Type:', xhr.getResponseHeader('Content-Type'));
                
                // JSON parse hatası olabilir, text response'u kontrol et
                try {
                    var contentType = xhr.getResponseHeader('Content-Type') || '';
                    var responseText = xhr.responseText.trim();
                    
                    console.log('Content-Type:', contentType);
                    console.log('Response Text:', responseText);
                    
                    // Eğer JSON değilse, text olarak kontrol et
                    if(contentType.indexOf('application/json') === -1) {
                        if(responseText == '1' || responseText == '{"status":1}'){
                            alert('Tüm sözleşmeler onaylandı.');
                            $('.contract-modal').fadeOut(300, function(){
                                $(this).hide();
                            });
                            setTimeout(function(){
                                location.reload();
                            }, 500);
                            return;
                        }
                    }
                    
                    // JSON parse dene
                    var jsonResponse = JSON.parse(responseText);
                    if(jsonResponse && jsonResponse.status == 1){
                        alert('Tüm sözleşmeler onaylandı.');
                        $('.contract-modal').fadeOut(300, function(){
                            $(this).hide();
                        });
                        setTimeout(function(){
                            location.reload();
                        }, 500);
                        return;
                    }
                    
                    alert('Sözleşme onaylanırken bir hata oluştu: ' + (jsonResponse ? jsonResponse.message : responseText));
                    $button.prop('disabled', false).text(originalText);
                } catch(parseError) {
                    console.error('Parse error:', parseError);
                    alert('Sözleşme onaylanırken bir hata oluştu: ' + error + ' (Status: ' + xhr.status + ')');
                    $button.prop('disabled', false).text(originalText);
                }
            }
        });
    });
    
    // Close contract butonu
    $(document).on('click', '.closecontract', function(){
        $('.contract-modal').hide();
    });
});

$('.class').slideDown(2000);


function formatMyMoney(price) {

    var currency_symbol = "₺"

    var formattedOutput = new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2,
    });

    return formattedOutput.format(price).replace(currency_symbol, '')
}
function formatMyMoney2(price) {

    var currency_symbol = "₺"

    var formattedOutput = new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2,
    });

    return formattedOutput.format(price).replace(currency_symbol, '')
}
function opencontract(){
    $.get('contract',{},function(cont){
        if(cont == '1'){
            $('.contract-modal').fadeIn();
            getcontract(11);
            $('.access-contract').hide();
            $('.closecontract').show();
        }
    });



};

function delorder(sid) {

    Swal.fire({
        title:"Silmek istediğinize emin misiniz?",
        text:"Bu işlem geri alınamaz!",
        type:"warning",
        showCancelButton:!0,
        confirmButtonColor:"#34c38f",
        cancelButtonColor:"#f46a6a",
        cancelButtonText:"kapat",
        confirmButtonText:"Evet, sil!",
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.get('siparissil/',{sid:sid},function(veri){
                    if(veri=='1') {
                        $('.'+sid).remove();
                        notice('Siparişiniz silindi.');
                        Swal.fire({
                            title: 'Silindi',
                            text: "Siparişiniz silindi.",
                            type: 'success',
                            showCancelButton: false,
                            showCloseButton: false,
                            showConfirmButton: false
                        });
                        setTimeout(function(){
                            $('.swal2-container').remove();
                        },1200);
                    }
                    else {
                        warn('Hata!')
                    }
                });


            })

        },
    })

}
function oemistek(){
    $('body').append('<div class="darkscreen"><div class="oem-istek-form "><div class="card"><div class="col-lg-12 fleft form-group"><label>Oem numarasını giriniz</label></div><div class="col-lg-12 fleft"><input type="input" class="form-control oemno0" /></div><div class="col-lg-2 fright"><button class="btn btn-danger oem-istek-kapat" >Kapat</button></div><div class="col-lg-2 fright"><button class="btn btn-success oem-istek-kayit">Gönder</button></div></div></div></div>');
    $('.darkscreen').fadeIn();
    $('.oem-istek-kapat').click(function(){
        $('.darkscreen').remove();
    });
    $('.oem-istek-kayit').click(function(){
        var oemno = $('.oemno0').val();
        $.get('oemistek/',{oemno:oemno},function(data){
           if(data==1) {
               notice('Oem talebi alındı');
               $('.darkscreen').remove();
           }
        });
    });
}
