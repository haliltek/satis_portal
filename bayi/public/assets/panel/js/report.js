$(function(){
    $('.doreport').click(function(){
       var month = $('.month').val();
       var year = $('.year').val();
        $('.toplam-tutar, .toplam-adet').text('0');
       $('.report-table').html('');
       $.getJSON('/panel/doreport',{month:month,year:year},function(veri){
           $.each(veri,function(index,val){
               $('.report-table').append('<tr><td>'+ val.sip_id +'</td><td>'+ val.name +'</td><td>'+ val.tarih +'</td><td>'+ val.tutar +'</td><td>'+ val.geneltoplam +'</td></tr>');
           });
           $.each(veri,function(index,value){
              $('.toplam-tutar').text(value.toplam);
              $('.toplam-adet').text(value.adet);
           });
       });
    });

    $('.monthlyreport').click(function(){
        var month = $('.month').val();
        var year = $('.year').val();
        $('.toplam-tutar, .toplam-adet').text('0');
        $('.report-table').html('');
        $.getJSON('/panel/monthlyreport',{month:month,year:year},function(veri){
            $.each(veri,function(index,val){
                $('.report-table').append('<tr><td>'+ val.sip_id +'</td><td>'+ val.name +'</td><td>'+ val.tarih +'</td><td>'+ val.tutar +'</td><td>'+ val.geneltoplam +'</td></tr>');
            });
            $.each(veri,function(index,value){
                $('.toplam-tutar').text(value.toplam);
                $('.toplam-adet').text(value.adet);
            });
        });
    });



});
