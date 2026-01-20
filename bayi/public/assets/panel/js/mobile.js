$(window).resize(function(){
    if($(window).width() < '768') {
        //alert('mobile size');
        //* filtre
        $('#filtrele').parent().parent().css({'overflow-y':'scroll'});
        $('.fatura .col-lg-12').css({'overflow-y':'scroll'});
        $('.urun-liste').css({'overflow-y':'scroll'});
        $('.urunliste').css({'width':'1170px'});
        $('.page-content').css({'padding':'15px 0px 60px 0px'});
        $('.page-title').css({'width':'100%'});

   }
    if($(window).width() > '769') {

    }
});
$(document).ready(function(){
    $(window).trigger('resize');
});
