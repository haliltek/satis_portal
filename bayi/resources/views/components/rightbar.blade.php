
<!-- Right Sidebar -->
<div class="right-bar">
    <div data-simplebar class="h-100">
        <div class="rightbar-title px-3 py-4" style="border-bottom: 1px solid #444c54;">
            <a href="javascript:void(0);" class="right-bar-toggle float-right">
                <i class="mdi mdi-close noti-icon"></i>
            </a>
            <h5 class="m-0">Ayarlar</h5>
        </div>

        <ul class="ayarlarli">

            @if(rol('5') == '1')
            <a href="{{url('panel/ayarlar')}}"><li><i class="mdi mdi-settings"></i> Site Ayaları</li></a>
            <a href="{{url('panel/feedback')}}"><li><i class="mdi mdi-message"></i> Mesajlar</li></a>
            <a href="{{url('panel/odemeyontemleri')}}"><li><i class="mdi mdi-credit-card"></i> Ödeme Ayarları</li></a>
            <a href="{{url('panel/parabirimleri')}}"><li><i class="mdi mdi-coin"></i> Para Birimi Ayarları</li></a>
            <a href="{{url('panel/fiyatayarlari')}}"><li><i class="mdi mdi-currency-try"></i> Fiyat Ayarları</li></a>
            <a href="{{url('panel/vergiayarlari')}}"><li><i class="mdi mdi-numeric"></i> Vergi Ayarlar</li></a>
            <a href="{{url('panel/birimler')}}"><li><i class="mdi mdi-altimeter"></i> Birim Ayarları</li></a>
            <a href="{{url('panel/kargolar')}}"><li><i class="mdi mdi-compass"></i> Kargo Ayarları</li></a>
            @endif
            @if(rol('6')=='1')
            <a href="{{url('panel/bankalar')}}"><li><i class="mdi mdi-bank"></i> Banka Ayarları</li></a>
            <a href="{{url('panel/iller')}}"><li><i class="mdi mdi-map-marker-multiple"></i> İl İlçe Ayarları</li></a>
            <a href="{{url('panel/sozlesmeler')}}"><li><i class="mdi mdi-content-copy"></i> Sözleşme Ayarları</li></a>
            <a href=""><li><i class="mdi mdi-ticket-account"></i> Üye Destek Paneli</li></a>
            @endif
                @if(rol('10')=='1')
            <a href="{{url('panel/tedarikciler')}}"><li><i class="mdi mdi-ticket-account"></i> Tedarikçiler</li></a>
                @endif
                @if(rol('9')=='1')
            <a href="{{url('panel/islemler')}}"><li><i class="mdi mdi-ticket-account"></i> Toplu İşlemler</li></a>
                    @endif
            <a href="{{url('panel/updatelog')}}"><li><i class="mdi mdi-info"></i> Güncelleme Kayıtları</li></a>
        </ul>




    </div>
    <!-- end slimscroll-menu-->
</div>
<!-- /Right-bar -->
