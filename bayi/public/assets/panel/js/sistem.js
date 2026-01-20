// Seri İşlemlerinde Marka seçilince modeli getirme
$(function(){
    var table= '/panel/urunlist';

    $('.urunliste').DataTable( {
        "destroy": true,
        "ajax": table

    });

});

$('#markasec').change(function (){
   var marka = $(this).val();

   $('#modeller').empty();
   $.getJSON('/panel/modelgetir', {marka:marka}, function (data){

       $.each(data, function(key, value) {
           $("#modeller").append('<option value="'+value.id+'">'+value.model_adi+'</option>');
       });
   });
});

$('#katsec').change(function (){
    var kat = $(this).val();

    $('#altkat').hide();
    $('#altsec').empty();
    $.getJSON('/panel/altgetir', {kat:kat}, function (data){
        var sonuc = data;
        if (sonuc !=0) {
            $('#altkat').show();
            $("#altsec").append('<option value="0">Alt Kategori Seç</option>');
            $.each(data, function(key, value) {
                $("#altsec").append('<option value="'+value.id+'">'+value.kategori_adi+'</option>');
            });
        }
    });
});

$('#altsec').change(function (){
    var kat = $(this).val();
    $('#altkat2').hide();
    $('#altsec2').empty();
    $.getJSON('/panel/altgetir', {kat:kat}, function (data){
        var sonuc = data;
        if (sonuc !=0) {
            $('#altkat2').show();
            $("#altsec2").append('<option value="0">Alt Kategori Seç</option>');
            $.each(data, function(key, value) {
                $("#altsec2").append('<option value="'+value.id+'">'+value.kategori_adi+'</option>');
            });
        }
    });
});

$(".altekle").change(function (){
   var id = $(this).val();
   $("#altid").val(id);
})

$(".durumdegis").click(function (){
   var durum = $(this).attr('durum');
   var durum = Number(durum);
   var table = $(this).attr('table');
   var id= $(this).attr('id');

    if(durum==1){
        $(this).removeClass('btn-danger');
        $(this).addClass('btn-success');
        $(this).text('Aktif');
        $(this).attr('durum', 0);
    }else{
        $(this).removeClass('btn-success');
        $(this).addClass('btn-danger');
        $(this).text('Pasif');
        $(this).attr('durum', 1);
    }
   $.get('/panel/durumdegis', {durum:durum, table:table, id:id}, function (data){

   });

});

$(".kurguncelle").click(function (){
    $.get('/panel/birimtopluguncelle', function (data){
        alert("Kurlar TCMB kurları ile Güncellendi");
        $('.page-content').append('<meta http-equiv="refresh" content="1">');
    });
});

$('.birimkaydet').click(function (){
   var id = $(this).attr('id');
   var kur = $("#birim-"+id).val();
   $.get('/panel/birimkaydet', {id:id, kur:kur}, function (data){
       alert("Kur Güncellendi");
   });
});

$(".kapakyap").click(function (){
   var urun = $(this).attr('urun');
   var rid = $(this).attr('rid');
   var durum = $(this).attr('durum');

   if(durum==1){

   }else{

   }

   $.get('/panel/kapakyap', { rid:rid, urun:urun}, function (data){


   });

});

$(".tanimekle").click(function (){
    var name = $("#tanimad").val();
    var tanim = $("#tanimval").val();
    var id = $(this).attr('urun');

    $.get('/panel/uruntanimekle', {name:name, tanim:tanim, id:id}, function (data){

        $.getJSON('/panel/uruntanimcek', {id:id}, function (data){
            $("#otlist").empty();
            $.each(data, function(key, value) {
                $("#otlist").append('<option class="tanimsil" value="'+value.id+'">['+value.tanimadi+']'+value.tanim_deger+'</option>');
            });
            $(".tanimsil").click(function (){
                var id= $(this).val();
                $('.delot').attr('tanim', id);
            });
        });
    });
});


$(".tanimsil").click(function (){
    var id= $(this).val();
    $('.delot').attr('tanim', id);
});

$(".delot").click(function (){
   var id= $('.delot').attr('tanim');
   $.get('/panel/tanimsil', {id:id}, function (data){
   });
});



$(".oemekle").click(function (){
    var oem = $("#oem").val();
    var id = $(this).attr('urun');

    $.get('/panel/urunoemekle', {oem:oem, id:id}, function (data){

        $.getJSON('/panel/urunoemcek', {id:id}, function (data){
            $("#oemlist").empty();
            $.each(data, function(key, value) {
                $("#oemlist").append('<option class="oemsil" value="'+value.id+'">'+value.oem+'</option>');
            });
            $(".oemsil").click(function (){
                var id= $(this).val();
                $('.deloem').attr('oem', id);
            });
        });
    });
});

$(".oemsil").click(function (){
    var id= $(this).val();
    $('.deloem').attr('oem', id);
});
$(".deloem").click(function (){
    var id= $('.deloem').attr('oem');
    $.get('/panel/urunoemsil', {id:id}, function (data){

    });
});



$('.resimsil').click(function (){
    var id= $(this).attr('resimid');
    $('#urresim-'+id).remove();
    $.get('/panel/urunresimsil', {id:id}, function (data){

    });
});

$(".fiyatkaydet").click(function (){
   var id = $(this).attr('id');
   var urun = $('#fiyat-'+id).attr('urun');
   var fiyatid = $('#fiyat-'+id).attr('fiyatid');
   var fiyat = $('#fiyat-'+id).val();

    if(fiyatid==""){
        // Ekle
        var fiyatid=0;
    }
    $.get('/panel/urunfiyatguncelle', {urun:urun, fiyat:fiyat, fiyatid:fiyatid, ayar:id}, function (data){

    });
});

$(".markasec").click(function (){
    var marka = $(this).attr('id');
    $(".markamodelekle").attr('marka', marka);
    $("#modeldoldur").empty();
    $.getJSON('/panel/urunmodelgetir', {marka:marka}, function (data){
        $.each(data, function(key, value) {

            $("#modeldoldur").append('<li class="modelsec" id="'+value.id+'">'+value.model_adi+'</li>');
        });

        $(".modelsec").click(function (){
            var model = $(this).attr('id');
            $(".markamodelekle").attr('model', model);
            $("#motordoldur").empty();
            $('.modelsec').removeClass('active');
            $(this).addClass('active');
            $(".step3").show();
            $.getJSON('/panel/urunmotorgetir', {model:model}, function (data){

                $.each(data, function(key, value) {
                    $("#motordoldur").append('<li class="motorsec" marka="'+value.marka+'" model="'+value.model+'" id="'+value.id+'">'+value.motor_adi+'</li>');
                });

                $(".motorsec").click(function (){
                    var motor = $(this).attr('id');
                    $(".markamodelekle").attr('motor', motor);
                    $('.motorsec').removeClass('active');
                    $(this).addClass('active');
                    $(".step4").show();
                });
            });
        });
    });
});

$(".yilsec").click(function (){
   var yil = $('.yillar .mbox').text();
    $(".markamodelekle").attr('yil', yil);
});

$(".markamodelekle").click(function (){

   var urun = $(this).attr('urun');
   var marka = $(this).attr('marka');
   var model = $(this).attr('model');
   var motor = $(this).attr('motor');
   var yil = $('.yillar .mbox').text();

   $.get('/panel/urunmarkamodelekle', {urun:urun, marka:marka, model:model, motor:motor, yil:yil}, function (data){

   });
});

$('.markamodelsil').click(function (){
   var id= $(this).attr('id');
   $(".marks-"+id).remove();

    $.get('/panel/markamodelsil', {id:id}, function (data){

    });
});

function yuklen(input){
    var data = $(input)[0].files;
    $.each(data, function(index, file){
        if(/(\.|\/)(gif|jpe?g|png)$/i.test(file.type)){
            var fRead = new FileReader();
            fRead.onload = (function(file){
                return function(e) {
                    var img = $('<img width="120px;"/>').addClass('thumb').attr('src', e.target.result);
                    $('#eklenecekresimler').append(img);
                };
            })(file);
            fRead.readAsDataURL(file);
        }
    });
}
function durumdegis(id) {
    var bid = 'b'+id;
    var durum = $('#b'+id).attr('durum');
    durum = Number(durum);
    var table = 'urunler';

    if(durum==1){
        $('#'+bid).removeClass('btn-danger');
        $('#'+bid).addClass('btn-success');
        $('#'+bid).text('Aktif');
        $('#'+bid).attr('durum', 0);
    }else{
        $('#'+bid).removeClass('btn-success');
        $('#'+bid).addClass('btn-danger');
        $('#'+bid).text('Pasif');
        $('#'+bid).attr('durum', 1);
    }
    $.get('/panel/durumdegis', {durum:durum, table:table, id:id}, function (data){

    });
}

function kampdegis(id) {
    var bid = 'c'+id;
    var durum = $('#c'+id).attr('durum');
    durum = Number(durum);
    var table = 'urunler';

    if(durum==1){
        $('#'+bid).removeClass('btn-danger');
        $('#'+bid).addClass('btn-success');
        $('#'+bid).text('Aktif');
        $('#'+bid).attr('durum', 0);
    }else{
        $('#'+bid).removeClass('btn-success');
        $('#'+bid).addClass('btn-danger');
        $('#'+bid).text('Pasif');
        $('#'+bid).attr('durum', 1);
    }
    $.get('/panel/kampdegis', {durum:durum, table:table, id:id}, function (data){

    });
}
