$(window).resize(function(){
    if($(window).width() < '700') {
        //alert('mobile size');
        //* filtre
        $('#filtrele').parent().parent().css({'overflow-y':'scroll'});
        $('.page-content').css({'padding':'15px 0px 60px 0px'});
        $('.header-search').find('.col-lg-6').css({'padding':'0px','margin-bottom':'10px'});
        $('.header-search').find('.col-lg-6 label').remove();
        $('.header-search').find('.col-lg-4').css({'padding':'0px','margin-bottom':'10px'});
        $('.header-search').find('.col-lg-4 .select2-container .select2-selection--single').css({'height':'38px'});
        //$('.header-search .header-search-banner').css({'margin-top':'20px'});
        $('.main-searh-button button').css({'width':'100%','height':'60'});
        $('.main-searh-button').css({'padding':'0'});
        $('.mobile-searh-button').hide();
        $('.select2').css('width','100%');
        //topbar
        $('.d-flex').css('width','105px');
        $('.bakiye-bilgi').removeClass('d-inline-block');
        $('.bakiye-bilgi').hide();
        $('.iskonto-bilgi').hide();
        $('.iskonto-bilgi').removeClass('d-inline-block');
        $('.topnav').css('background-color','#333');

        //tabs
        $('.page-title-box').css('width','100%');
        $('.tab').css({'padding':'13px 10px','width':'25%','background-color':'#bdbdbd','color':'#fff'});
        $('.tab.active').css({'background-color':'#fff','color':'#333'});
        $('.page-title-right').hide();
        $('.tab[v-tab="tab5"]').hide();

        //basket page
        $('.sepet-icerik .row .col-lg-12').css('overflow-y','scroll');
        $('.sepet-icerik .col-lg-2, .col-lg-3').css('padding','0');


        //modals
        $('.siparis-modal').css('width','85%');
        $('.image-modal').css('left','0');
        $('.sepet .s-title').html('<i class="fa fa-cart-plus"></i>');
        $('.navbar-brand-box').show();
        $('.header-setting-menu').css({'margin-right':'-120px','margin-top':'-5px'});

        //div

        $('.header-search .col-lg-6').css({'width':'100%'})
        $('.header-search .col-lg-4').css({'width':'100%'})
        $('.header-search .col-lg-1').css('width','100%')
        $('.header-search .col-lg-5').css('width','100%')
    }
    else if(($(window).width() < '960') && ($(window).width() > '768')) {

        /*
        $('#filtrele').parent().parent().css({'overflow-y':'scroll'});
        $('.page-content').css({'padding':'15px 0px 60px 0px'});
        $('.header-search').find('.col-lg-6').css({'padding':'0px','margin-bottom':'10px','float':'left'});
        $('.header-search').find('.col-lg-6 label').remove();
        $('.header-search').find('.col-lg-4').css({'padding':'0px','margin-bottom':'10px','width':'100%'});
        $('.header-search').find('.col-lg-6 .col-lg-6').css({'margin-bottom':'10px','width':'50%'});
        $('.header-search').find('.col-lg-4 .select2-container .select2-selection--single').css({'height':'38px'});
        //$('.header-search .header-search-banner').css({'margin-top':'20px'});

        $('.topnav').css('background-color','none');
        $('.main-searh-button button').css({'width':'100%','height':'60'});
        $('.main-searh-button').css({'padding':'0'});
        $('.mobile-searh-button').hide();
        $('.select2').css('width','100%');
        //topbar
        $('.d-flex').css('width','105px');
        $('.bakiye-bilgi').removeClass('d-inline-block');
        $('.bakiye-bilgi').hide();
        $('.topnav').css('background-color','#333');

        //tabs
        $('.page-title-box').css('width','100%');
        $('.tab-box').css('width','100%');
        $('.tab').css({'padding':'13px 10px','width':'25%','background-color':'#bdbdbd','color':'#fff'});
        $('.tab.active').css({'background-color':'#fff','color':'#333'});
        $('.page-title-right').hide();
        $('.tab[v-tab="tab5"]').hide();

        //basket page
        $('.sepet-icerik .row .col-lg-12').css('overflow-y','scroll');
        $('.sepet-icerik .col-lg-2, .col-lg-3').css('padding','0');


        //modals
        $('.siparis-modal').css('width','85%');
        $('.image-modal').css('left','0');

        $('.sepet .s-title').html('<i class="fa fa-cart-plus"></i> Sepet');
        $('.header-setting-menu').css({'margin-right':'0px','margin-top':'0px'});

         */

    }
    else if(($(window).width() < '1025') && ($(window).width() > '760')) {


        $('.topnav').css('background-color','none');
        $('.topnav').css('margin-left','0px');

        $('.mobile-searh-button').hide();
        $('.select2').css('width','100%');
        //topbar
        $('.d-flex').css('width','fit-content');
        $('.iskonto-bilgi').removeClass('d-inline-block');
        $('.iskonto-bilgi').hide();
        $('.header-search .col-lg-6').css({'width':'50%','padding-left':'0px'})
        $('.header-search .col-lg-4').css({'width':'33.33333%','padding-left':'0px'})
        $('.header-search .col-lg-1').css('width','10%')
        $('.header-search .col-lg-5').css('width','40%')


    }
    else if($(window).width() > '960') {

        $('.topnav').css({'background-color':'none','background':'none'});
        $('.sepet .s-title').html('<i class="fa fa-cart-plus"></i> Sepet');
        $('.header-setting-menu').css({'margin-right':'0px','margin-top':'0px'});
        $('.bakiye-bilgi').addClass('d-inline-block');
        $('.bakiye-bilgi').show();
        $('.d-flex').css('width','910px');
    }
    else if($(window).width() > '380') {

        $('.navbar-brand-box').show();
    }
    if($(window).width() > '769') {
        $('.mobile-search-button').hide();
    }
});
$(document).ready(function(){
    $(window).trigger('resize');
});
