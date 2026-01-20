// Seri İşlemlerinde Marka seçilince modeli getirme

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


