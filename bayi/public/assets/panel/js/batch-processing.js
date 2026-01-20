$(function(){

    $('.topluindirim').click(function() {
        var oran = $('.islem-oran').val();
        var tur = $('.islem-tur').val();
        if(oran != '') {
            $('.footer').append('<div class="notice-modal"></div>');
            $('.notice-modal').css({'background-color':'#00000078','position':'fixed','z-index':'9999','top':'0','left':'0','width':'100vw','height':'100vh'});
            $('.notice-modal').append('<div class="notice-text"></div>');
            $('.notice-text').css({'background-color':'#ddd','padding':'20px','text-align':'center','margin':'20% auto 0 auto','width':'30%'});
            $('.notice-text').text('İşleminiz devam ediyor, Lütfen bekleyin...');

            $.get('/panel/topluindirim',{oran:oran,tur:tur},function(data){
                $('.notice-text').text(data);
                setTimeout(function(){
                    $('.notice-modal').remove();
                },1000)
            });
        }
        else {
            warn('Oran girin!');
        }
    });
    $('.ttopluindirim').click(function() {
        var oran = $('.islem2-oran').val();
        var tur = $('.islem2-tur').val();
        var kategori = $('.islem2-tedarikci').val();
        if(oran != '') {
            $('.footer').append('<div class="notice-modal"></div>');
            $('.notice-modal').css({'background-color':'#00000078','position':'fixed','z-index':'9999','top':'0','left':'0','width':'100vw','height':'100vh'});
            $('.notice-modal').append('<div class="notice-text"></div>');
            $('.notice-text').css({'background-color':'#ddd','padding':'20px','text-align':'center','margin':'20% auto 0 auto','width':'30%'});
            $('.notice-text').text('İşleminiz devam ediyor, Lütfen bekleyin...');

            $.get('/panel/ktopluindirim',{oran:oran,tur:tur,tedarikci:tedarikci},function(data){
                $('.notice-text').text(data);
                setTimeout(function(){
                    $('.notice-modal').remove();
                },1000)
            });
        }
        else {
            warn('Oran girin!');
        }
    });
    $('.ktopluindirim').click(function() {
        var oran = $('.islem3-oran').val();
        var tur = $('.islem3-tur').val();
        var kategori = $('.islem3-kategori').val();
        if(oran != '') {
            $('.footer').append('<div class="notice-modal"></div>');
            $('.notice-modal').css({'background-color':'#00000078','position':'fixed','z-index':'9999','top':'0','left':'0','width':'100vw','height':'100vh'});
            $('.notice-modal').append('<div class="notice-text"></div>');
            $('.notice-text').css({'background-color':'#ddd','padding':'20px','text-align':'center','margin':'20% auto 0 auto','width':'30%'});
            $('.notice-text').text('İşleminiz devam ediyor, Lütfen bekleyin...');

            $.get('/panel/ktopluindirim',{oran:oran,tur:tur,kategori:kategori},function(data){
                $('.notice-text').text(data);
                setTimeout(function(){
                    $('.notice-modal').remove();
                },1000)
            });
        }
        else {
            warn('Oran girin!');
        }
    });

    $('.fiyatdurum').click(function(){
       var stat = $(this).attr('stat');
       noticemodal('İşleminiz devam ediyor, Lütfen bekleyin...');
       $.get('/panel/fiyatdurum',{stat:stat},function(data){
           $('.notice-text').text(data);
           setTimeout(function(){
               $('.notice-modal').remove();
           },1500)
       });
    });

    $('.stokdurum').click(function(){
        var stat = $(this).attr('stat');
        noticemodal('İşleminiz devam ediyor, Lütfen bekleyin...');
        $.get('/panel/stokdurum',{stat:stat},function(data){
            $('.notice-text').text(data);
            setTimeout(function(){
                $('.notice-modal').remove();
            },1500)
        });
    });

    $('.urundurum').click(function(){
        var stat = $(this).attr('stat');
        noticemodal('İşleminiz devam ediyor, Lütfen bekleyin...');
        $.get('/panel/urundurum',{stat:stat},function(data){
            $('.notice-text').text(data);
            setTimeout(function(){
                $('.notice-modal').remove();
            },1500)
        });
    });

    $('.stokekle').click(function(){
        var stok = $('.stok').val();
        noticemodal('İşleminiz devam ediyor, Lütfen bekleyin...');
        $.get('/panel/stokekle',{stok:stok},function(data){
            $('.notice-text').text(data);
            setTimeout(function(){
                $('.notice-modal').remove();
            },1500)
        });
    });

    $('.kstokekle').click(function(){
        var stok = $('.kstok').val();
        noticemodal('İşleminiz devam ediyor, Lütfen bekleyin...');
        $.get('/panel/kstokekle',{stok:stok},function(data){
            $('.notice-text').text(data);
            setTimeout(function(){
                $('.notice-modal').remove();
            },1500)
        });
    });

});


function noticemodal(text) {
    $('.footer').append('<div class="notice-modal"></div>');
    $('.notice-modal').css({'background-color':'#00000078','position':'fixed','z-index':'9999','top':'0','left':'0','width':'100vw','height':'100vh'});
    $('.notice-modal').append('<div class="notice-text"></div>');
    $('.notice-text').css({'background-color':'#ddd','padding':'20px','text-align':'center','margin':'20% auto 0 auto','width':'30%'});
    $('.notice-text').text(text);
}
