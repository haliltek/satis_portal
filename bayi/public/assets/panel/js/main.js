$(function(){
    var fiyatid = 2;

    $('.addfiyat').click(function(){

        $('.ftable').append('<div class="col-lg-8"><label for="fiyat' + fiyatid + '" class="left col-lg-2 col-form-label p-t-15 fiyatitem' + fiyatid + '">Fiyat ' + fiyatid + '</label><div class="left col-lg-8 fiyatitem' + fiyatid + '"><input class="form-control m-t-10" id="fiyat' + fiyatid + '" placeholder="' + fiyatid + '. Fiyat adı"/></div><div class="col-lg-1 m-t-10 left"><button type="button" fiyatclass="fiyatitem' + fiyatid + '" class="delfiyat btn"><i class="fa fa-times"></i></button></div></div>');


        $('.delfiyat').click(function(){
            var fiyatclass = $(this).attr("fiyatclass");
            $('.' + fiyatclass).remove();
            $(this).parent().remove();
        });
        fiyatid++;
    });
    $('a[href="#finish"]').hide();
    $('.changestat').click(function(){
       var durum = $(this).attr('durum');
       var adres = $(this).attr('adres');
       var id = '#'+ $(this).attr('id');
       $.get('/panel/adresdurum',{durum:durum,adres:adres},function(data){
            if(data=='1') {
                $(id).removeClass();
                $(id).addClass('btn btn-success changestat');
                $(id).text('Aktif');
                //notice('Adres onaylandı!');
            }
            else {
                $(id).removeClass();
                $(id).addClass('btn btn-danger changestat');
                $(id).text('Pasif');
                //warn('Adres kaldırıldı!');
            }
       });
    });


    $('.odemedetay').click(function(){
        var oid = $(this).attr('oid');

        $.getJSON('/panel/odemedetay/'+oid,function(data){
            $('.odemedetay-modal').fadeIn();
            $.each(data,function(index,val){
                $('#gonderen b').text(val.gonderen);
                $('#odenen b').text(formatMyMoney(val.odenen) + '₺');
                $('#bayi b').text(val.name);
                $('#sip-tutar b').text(formatMyMoney(val.geneltoplam) + '₺');
                $('#odemeonay').attr('oid',val.id);
            })
        });
        $('#close-odemedetay').click(function(){
            $('.odemedetay-modal').fadeOut();
        });
        $('#odemeonay').click(function(){
            var oid = $(this).attr('oid');
            $.get('/panel/havaleonay',{oid:oid},function(veri){
                if(veri == '1') {
                    $('.odemedetay-modal').fadeOut();
                }
            });
        })

    });

    $('.feedMessage').click(function(){
        $('.odemedetay-modal').fadeOut();
        var fid = $(this).attr('fid');
        var name = $(this).attr('name');
        $('.feedMessage-name').text(' ');
        $('.feedMessage-area').text(' ');
        $.get('/panel/feedmessage/'+fid,function(data){
            $('.odemedetay-modal').fadeIn(function(){
                $('.feedMessage-area').text(data);
                $('.feedMessage-name').text(name);
            });

        });
        $('#close-odemedetay').click(function(){
            $('.odemedetay-modal').fadeOut();
        });
    });


    $('.noticestat').hover(function(){
        var id = $(this).attr('id');
        $.get('/panel/changenoticestat',{id:id},function(data) {
            if(data == '1') {
                setTimeout(function(){
                    $('.noticestat[id='+id+']').remove();
                },10000);

            }
        });
    });



    $('.copybtn').click(function(){
        $('#acarno').val($('#ureticino').val());
    });
    $('.pmakbuz').click(function(){
        print();
    });
    /*
    var oemid = 2;

    $('.addoem').click(function(){

        $('.oem').append('<label for="oem' + oemid + '" class="col-lg-3 col-form-label p-t-15 oemitem' + oemid + '">Oem No ' + oemid + '</label><div class="col-lg-8 oemitem' + oemid + '"><input class="form-control m-t-10" id="oem' + oemid + '" /></div><div class="col-lg-1 m-t-10 "><button type="button" oemclass="oemitem' + oemid + '" class="deloem btn"><i class="fa fa-times"></i></button></div>');


        $('.deloem').click(function(){
            var oemclass = $(this).attr("oemclass");
            $('.' + oemclass).remove();
            $(this).parent().remove();
        });
        oemid++;
    });
    */
    $('.addoem').on('click',function(){
        $('#oemlist').append('<option value="' + $('#oem').val() +'">' + $('#oem').val() +'</option>');
        $('#oem').val('');
    });
    $('.deloem').on('click',function(){
        $('#oemlist option:selected').remove();
    });
    $('.addot').click(function(){
        var tanimad = $('#tanimad').val();
        var tanimval = $('#tanimval').val();
        $('#otlist').append('<option value="[' + tanimad +']' + tanimval +'">[' + tanimad +']' + tanimval +'</option>');
        $('#tanimad, #tanimval').val('');
    });
    $('.delot').on('click',function(){
        $('#otlist option:selected').remove();
    });
    $('.step1 .step-box ul li').click(function(){
        $(this).siblings('li').attr('class','');
        $(this).attr('class','active');
        $('.step2,.step3,.step4,.savemodel').hide();
        $('.step2,.step3,.step4').find('li').attr('class','');
        $('.step2').fadeIn();
    });
    $('.step2 .step-box ul li').click(function(){
        $(this).siblings('li').attr('class','');
        $(this).attr('class','active');
        $('.step3,.step4,.savemodel').hide();
        $('.step3,.step4').find('li').attr('class','');
        $('.step3').fadeIn();
    });
    $('.step3 .step-box ul li').click(function(){
        $(this).siblings('li').attr('class','');
        $(this).attr('class','active');
        $('.step4,.savemodel').hide();
        $('.step4').find('li').attr('class','');
        $('.step4').fadeIn();
    });
    $('.step4 .step-box ul li').click(function(){
        var cls = $(this).attr('class');

        if(cls != 'active') {
            $(this).attr('class','active');
            $('.yillar').append('<div class="mbox" id="s'+$(this).attr('val')+'">'+$(this).attr('val')+',</div>');
        }
        else {
            var getid = $(this).attr('val');
            $(this).attr('class','');
            $('#s'+getid).remove();
        }
        //$(this).siblings('li').attr('class','');
        //$(this).attr('class','active');
        $('.savemodel').fadeIn();
    });
    $('.savemodel').on('click',function(){
        $('.model-result').append('<li>' + $('.step1 li.active').text() + ' - ' + $('.step2 li.active').text() + ' - ' + $('.step3 li.active').text() + ' - ' + $('.step4 li.active').text() + '</li>');

    });

    /*
    $('.addot').click(function(){
        $('.tanim').append('<div class="form-group row"><div class="col-lg-4"><input id="fiyat" name="fiyat" type="text" class="form-control" placeholder="Tanım adı"></div><div class="col-lg-8"><input id="fiyat" name="fiyat" type="text" class="form-control" placeholder="Tanım içeriği"></div></div>');
    });
    */
    function readURL(input) {
        if (input.files && input.files[0]) {
            var adet = $('input[type=file]').get(0).files.length;
            for(i=0; i < adet; i++) {
                var reader = new FileReader();

                reader.onload = function(e) {

                    $('#imagearea').append('<div class="pimage"><img src="' + e.target.result + '" id=""/><button class="remimg" type="button" resid="res' + i +'"><i class="fa fa-times" ></i></button></div>');

                    $('.remimg').click(function(){
                        $(this).parent().remove();
                    });
                }

                reader.readAsDataURL(input.files[i]);

            }

        }

    }

    $("#res").change(function() {
        readURL(this);
    });
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

    $('#addcart').click(function(){

    });
    $('.closecartmodal').click(function(){
        $('.cartmodal').fadeOut();
    });

    $('.year li').click(function(){

    });
    $('.passwordbox').click(function(){
       $('.editpassword').fadeIn();
       var id = $(this).attr('bid');
       $('.changepassword').attr('bid',id);
    });
    $('.changepassword').click(function(){
        var password = $('#password').val();
        var password2 = $('#password2').val();
        var id = $(this).attr('bid');
        if(password == password2) {
            $.get('/panel/editpass', {id:id,password:password},function(data) {
                if(data=='1') {
                    $('.passwordresult').html('Parola güncellendi.');
                    setTimeout(function(){
                        $('.editpassword').fadeOut();
                    },2500);
                }
            });
        }
        else {
            $('.passwordresult').html('Hata! Paralolar eşleşmiyor.');
        }


    });
    $('.closepasswordbox').click(function() {
        $('.editpassword').fadeOut();
    });
    setTimeout(function(){
        $('.fa-car-alt').attr('class','fa fa-car-alt');
        $('.fa-edit').attr('class','fa fa-edit');
        $('.fa-times').attr('class','fa fa-times');

    },10000);

    $('.kargodurum').click(function(){
        $('.kargodurum-modal').remove();
        //$('.kargodurum-modal').fadeIn();
        var kid = $(this).attr('kid');
        var kad = $(this).attr('kad');
        var sid = $(this).attr('sid');
        $(this).parent().append('<div class="kargodurum-modal"><div class="col-lg-12 m-b-10 fleft"><label>Kargo Durum</label><select class="form-control kdurum"></select></div><div class="col-lg-12 p-0 fleft m-t-20 kargo-alt"><div class="col-lg-6 fleft"><label>Gönderilen Kargo</label><select class="form-control kargo-firma"></select></div><div class="col-lg-6 fleft"><label>Kargo Takip No</label><input type="text" class="form-control" id="kargo-takip"></div></div><div class="kargoresult col-lg-6 fleft" style="padding-top:15px;"></div><div class="col-lg-6 fright m-b-10 m-t-20"><button class="btn btn-success fright savekargo" bid="">Kaydet</button><button class="btn btn-danger fright m-r-5 closekargo-modal">Kapat</button></div></div>');
        $('.kdurum').append('<option value="'+ $(this).attr('kno') +'" selected="selected">Seçili : '+ $(this).attr('kd') +'</option>');
        $('.kdurum').append('<option value="0">Beklemede</option><option value="1">Hazırlanıyor</option><option value="2">Kargoya Verildi</option>');

        $('.kdurum').change(function(){
            if($('.kdurum').val() == '2'){
                $('.kargo-alt').show();
                $('.kargo-firma').append('<option value="'+ kid +'">Seçili : '+ kad +'</option>')
                $.getJSON('kargolist/',function(data){
                    $.each(data,function(key,val){
                        $('.kargo-firma').append('<option value="'+ val.id +'">'+ val.name +'</option>');
                    });
                });
            }
        });
        $('.savekargo').click(function(){
            var kargofirma = $('.kargo-firma').val();
            var kargotakip = $('#kargo-takip').val();
            var kargodurum = $('.kdurum').val();
            $.get('kargodurum/',{kargofirma:kargofirma,kargodurum:kargodurum,kargotakip:kargotakip,sid:sid},function(ksave){
               //$('.kargoresult').html(ksave);
               if(ksave == '1') {
                   var mainClassId = '#k'+sid;
                   $('.kargoresult').html('Kargo durum güncellendi');
                   if(kargodurum=='1') { var text = 'Hazırlanıyor'; $(mainClassId).css('color','#fd971a'); }
                   else if(kargodurum=='2') { var text = 'Kargoya Verildi'; $(mainClassId).css('color','#4ead05'); }
                   else { var text = 'Beklemede'; $(mainClassId).css('color','#666');}
                   $(mainClassId).text(text)
                   setTimeout(function(){
                       $('.kargodurum-modal').remove();
                   },1000)
               }
               else {
                   $('.kargoresult').html('Hata!');
               }
            });
        });

        $('.closekargo-modal').click(function(){
            $('.kargodurum-modal').remove();
        })
    });

    $('.kargodurum2').click(function(){
        $('.kargodurum-modal').remove();
        //$('.kargodurum-modal').fadeIn();
        var kid = $(this).attr('kid');
        var kad = $(this).attr('kad');
        var sid = $(this).attr('sid');
        $(this).parent().append('<div class="kargodurum-modal"><div class="col-lg-12 m-b-10 fleft"><label>Kargo Durum</label><select class="form-control kdurum"></select></div><div class="col-lg-12 p-0 fleft m-t-20 kargo-alt"><div class="col-lg-6 fleft"><label>Gönderilen Kargo</label><select class="form-control kargo-firma"></select></div><div class="col-lg-6 fleft"><label>Kargo Takip No</label><input type="text" class="form-control" id="kargo-takip" value=""></div></div><div class="kargoresult col-lg-6 fleft" style="padding-top:15px;"></div><div class="col-lg-6 fright m-b-10 m-t-20"><button class="btn btn-danger fright m-r-5 closekargo-modal">Kapat</button></div></div>');
        $('.kdurum').append('<option value="'+ $(this).attr('kno') +'" selected="selected">Kargoya verildi</option>');
        $('.kargo-alt').show();
        $('.kargo-firma').append('<option value="'+ kid +'">Seçili : '+ kad +'</option>')
        $.getJSON('kargobilgi/',{sid:sid},function(data){
            $.each(data,function(key,val){
                $('#kargo-takip').val(val.kargotakip);
            });
        });





        $('.closekargo-modal').click(function(){
            $('.kargodurum-modal').remove();
        })
    });

    $('.durum').click(function(){
        $('.durum-modal').remove();
        var sid = $(this).attr('sid');
        var tutar = $(this).attr('tutar');
        $(this).parent().append('<div class="durum-modal"><div class="col-lg-12 m-b-10 fleft"><label>Sipariş Durumu</label><select class="form-control sdurum"></select></div><div class="col-lg-12 p-0 fleft m-t-20 siparis-alt"><div class="col-lg-6 fleft"><label>Ödeme Kanalı</label><select class="form-control odeme"></select></div><div class="col-lg-6 fleft"><label>Ödenen Tutar</label><input type="text" class="form-control" id="odenen"></div></div><div class="durumresult col-lg-6 fleft" style="padding-top:15px;"></div><div class="col-lg-6 fright m-b-10 m-t-20"><button class="btn btn-success fright savedurum" bid="">Kaydet</button><button class="btn btn-danger fright m-r-5 closedurum-modal">Kapat</button></div></div>');
        $('.sdurum').append('<option value="'+ $(this).attr('durum') +'" >Seçili : '+ $(this).attr('durumad') +'</option>');
        $('.sdurum').append('<option value="2">Ödeme Yapıldı</option>');

        $('.sdurum').change(function(){
            if($('.sdurum').val()=='2') {
                $('.siparis-alt').fadeIn();
                $.getJSON('odemetip/',function(veri){
                    $.each(veri,function(key,val){
                        $('.odeme').append('<option value="'+ val.id +'">'+ val.odeme_adi +'</option>')
                    });
                });
                $('#odenen').val(formatMyMoney(tutar));
            }
        });
        $('.savedurum').click(function(){
            var odeme = $('.odeme').val();
            var odenen = $('#odenen').val();
            var mainStatus = '#s'+sid;
            var siparisdurum = $('.sdurum').val();
            $.get('durumkaydet/',{sid:sid,odeme:odeme,odenen:odenen,siparisdurum:siparisdurum},function(veri){
                if(veri == '1') {
                    $('.durumresult').html('Ödeme Bilgisi Güncellendi');
                    if(siparisdurum=='2') { var text = 'Ödeme Yapıldı'; $(mainStatus).css('color','#4ead05'); }
                    $(mainStatus).text(text);
                    setTimeout(function(){
                        $('.durum-modal').remove();
                    },1000)
                }
                else { $('.durumresult').html('Hata!'); }
            });
        });


        $('.closedurum-modal').click(function(){
            $('.durum-modal').remove();
        })
    });

    $('.tedarikcimodal').click(function(){
        $('.tedarikmodal').fadeIn();
    });
    $('.close-tedarikmodal').click(function(){
        $('.tedarikmodal').fadeOut();
    });
    $('.tedarikciekle').click(function(){
        var tedarikci = $('.tedarikci').val();
        $.get('/panel/tedarikciekle',{tedarikci:tedarikci},function(data){
            if(data == '1') {
                $('.tedarikresult').text('Tedarikçi eklendi..');
                setTimeout(function(){
                    $('.tedarikmodal').fadeOut();
                },1000);
            }
        });
    });

});
function addcart(id) {
    $('.cartmodal').fadeIn();
    $('.markamodelekle').attr('urun',id);
    $.get('urunmarkamodel2/'+id,{},function(data){
        $.each(data,function(key,val){
            $('.model-result').append('<li style="padding:0px 0px 0px 10px; margin-right:15px;" class="marks-'+ val.id +' btn btn-secondary btn-sm">'+ val.marka_adi +' - '+ val.model_adi +' - '+ val.motor_adi +' - '+ val.yil +' <i id="'+ val.id +'" class="markamodelsil mdi-delete-sweep-outline btn btn-danger btn-sm" style="margin:-1px; margin-left:5px;"></i></li>');
        });
    });
}

function rol(id){
    var rid = '#y'+id;
    var durum = $(rid).attr('durum');
    var uye = $(rid).attr('user');
    $.get('/panel/roltanim',{durum:durum,uye:uye,yid:id},function(data){
        if(data=='1') {
            $(rid).removeClass();
            $(rid).addClass('btn btn-success');
            $(rid).text('Aktif');
            $(rid).attr('durum',data)
        }
        else {
            $(rid).removeClass();
            $(rid).addClass('btn btn-danger');
            $(rid).text('Pasif');
            $(rid).attr('durum',data)
        }
    });
}
function notice(text) {
    $('.footer').append('<div class="notice-box-area" ></div>');
    $('.notice-box-area').append('<div class="notice-box access-notice"><i class="fa fa-bell" style="margin-right:10px;"></i>' + text + '</div>');
    $('.notice-box').fadeIn(800);
    setTimeout(function(){
        $('.notice-box').fadeOut(1200);
        setTimeout(function(){
            $('.notice-box').remove();
        },1400);
    },2000);
}
function warn(text) {
    $('.footer').append('<div class="notice-box-area" ></div>');
    $('.notice-box-area').append('<div class="notice-box warn-notice"><i class="fa fa-exclamation-triangle" style="margin-right:10px;"></i>' + text + ' <button type="button" class="close fright"><span aria-hidden="true">×</span></button></div>');
    $('.notice-box').fadeIn(800);
    setTimeout(function(){
        $('.notice-box').fadeOut(800);
        setTimeout(function(){
            $('.notice-box').remove();
        },2000);
    },2500);
}
function formatMyMoney(price) {

    var currency_symbol = "₺"

    var formattedOutput = new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2,
    });

    return formattedOutput.format(price).replace(currency_symbol, '')
}
function allnotice() {
    $('.allnoticebox').fadeIn();
    $('.notices0').html('Yükleniyor..');
    $.getJSON('/panel/allnotice',function(data){
        $('.notices0').html('');
        $.each(data,function(index,val){
            $('.notices0').append('<div class="fleft col-lg-12 p-0 notice-item" >\n' +
                '            <h6 class="mt-0 mb-1 notice-title">'+ val.bildirim +'</h6>\n' +
                '            <div class="font-size-12 text-muted">\n' +
                '                <p class="mb-1"><u>'+ val.firma_unvani +' </u> '+ val.mesaj +'</p>\n' +
                '                <p class="mb-0"><i class="mdi mdi-clock-outline"></i> '+ val.tarih +'</p>\n' +
                '            </div>\n' +
                '        </div>');
        });
    });
    $('#closeallnoticebox').click(function(){
        $('.allnoticebox').fadeOut();
    })
}
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
                $.get('/siparissil',{sid:sid},function(veri){
                    if(veri=='1') {
                        $('.tr'+sid).remove();
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
