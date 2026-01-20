<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title> @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="B2B - Kontrol Merkezi" name="description" />
    <meta content="" name="author" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->

    <link rel="shortcut icon" href="{{ asset('assets/panel/images/favicon.ico') }}">
    <link href="{{ asset('assets/front/assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Lightbox css -->
    <link href="{{ asset('assets/front/assets/libs/magnific-popup/magnific-popup.css') }}" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/front/assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/front/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/front/assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/front/assets/css/page.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <link href="{{ asset('assets/front/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/front/assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/front/assets/libs/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/front/assets/libs/summernote/summernote-bs4.min.css') }}" rel="stylesheet" type="text/css" />
</head>





<body data-layout="horizontal" data-topbar="dark">
@if(Auth::user()->role == 'Bayi')
    <div class="container-fluid">
        <!-- Begin page -->
        <div id="layout-wrapper">

            @include('.front.layouts.header')

            @yield('content')

            @include('.front.layouts.footer')

        </div>
    </div>
@else
    <script>window.location = "/panel";</script>
@endif

<!-- JAVASCRIPT -->
<script src="{{ asset('assets/front/assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/form-advanced.init.js') }}"></script>
<!-- form repeater js -->
<script src="https://use.fontawesome.com/ae02977ad2.js"></script>

<!-- form repeater init -->

<script src="{{ asset('assets/front/assets/libs/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/pages/form-editor.init.js') }}"></script>
<!-- Magnific Popup-->
<script src="{{ asset('assets/front/assets/libs/magnific-popup/jquery.magnific-popup.min.js') }}"></script>

<!-- Tour init js-->
<script src="{{ asset('assets/front/assets/js/pages/lightbox.init.js') }}"></script>

<!-- App js - CSS yolu sorunları nedeniyle geçici olarak devre dışı -->
<!-- app.js CSS yollarını bozuyor, bu yüzden sadece gerekli fonksiyonları kullanıyoruz -->
<script>
// app.js'den sadece gerekli fonksiyonları kopyala (Waves.init, metisMenu, vb.)
(function() {
    // Waves.init için
    if (typeof Waves !== 'undefined') {
        Waves.init();
    }
    
    // MetisMenu için
    if (typeof jQuery !== 'undefined' && jQuery.fn.metisMenu) {
        jQuery('#side-menu').metisMenu();
    }
    
    // Fullscreen için
    if (typeof jQuery !== 'undefined') {
        jQuery('[data-toggle="fullscreen"]').on('click', function(e) {
            e.preventDefault();
            jQuery('body').toggleClass('fullscreen-enable');
            // Fullscreen API kullan
            if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            } else {
                if (document.cancelFullScreen) {
                    document.cancelFullScreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitCancelFullScreen) {
                    document.webkitCancelFullScreen();
                }
            }
        });
    }
})();
</script>
<!-- app.js'i yükleme - CSS yolu sorunları nedeniyle -->
<!-- <script src="{{ asset('assets/front/assets/js/app.js') }}"></script> -->
<script src="{{ asset('assets/front/assets/js/main.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/mobile.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/anasayfa_tab.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/ajax.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/sepet.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/bar.js') }}"></script>


<script src="{{ asset('assets/front/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ asset('assets/front/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/pages/datatables.init.js') }}"></script>



<script src="{{ asset('assets/front/assets/libs/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

<!-- Base URL for AJAX calls -->
<script>
    window.BASE_URL = '{{ url("/") }}';
</script>

<script src="{{ asset('assets/front/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('assets/front/assets/js/pages/sweet-alerts.init.js') }}"></script>

</body>

</html>
