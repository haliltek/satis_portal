<div class="topnav">
    <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

    @foreach($rol1 as $rol_1)
        @php($durum1=$rol_1->durum)
    @endforeach
    @foreach($rol2 as $rol_2)
        @php($durum2=$rol_2->durum)
    @endforeach
    @foreach($rol3 as $rol_3)
        @php($durum3=$rol_3->durum)
    @endforeach
    @foreach($rol4 as $rol_4)
        @php($durum4=$rol_4->durum)
    @endforeach
        @foreach($rol7 as $rol_7)
            @php($durum7=$rol_7->durum)
        @endforeach
        @foreach($rol8 as $rol_8)
            @php($durum8=$rol_8->durum)
        @endforeach
        <div class="collapse navbar-collapse" id="topnav-menu-content">
            <ul class="navbar-nav">


                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle arrow-none"  id="topnav-components" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Üyeler<div class="arrow-down"></div>
                    </a>

                    <div class="dropdown-menu" aria-labelledby="topnav-pages">
                        @if($durum3=='1')
                        <a href="{{url('panel/bayiler')}}" class="dropdown-item">Bayiler</a>
                        <a href="{{url('panel/adreslist')}}" class="dropdown-item">Bayi Adresler</a>
                        <a href="{{url('panel/bayiekle')}}" class="dropdown-item"> Bayi Ekle</a>
                            @else
                        @endif
                        @if($durum4=='1')
                        <a href="{{url('panel/adminler')}}" class="dropdown-item">Adminler</a>
                        <a href="{{url('panel/adminekle')}}" class="dropdown-item"> Admin Ekle</a>
                        @endif
                        @if($durum8=='1')
                            <a href="{{url('panel/roller')}}" class="dropdown-item">Admin Yetkiler</a>
                        @endif
                    </div>
                </li>
                @if($durum2=='1')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-pages" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Siparişler<div class="arrow-down"></div>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="topnav-pages">

                        <a href="{{url('panel/siparisler')}}" class="dropdown-item">Tüm Siparişler</a>
                        <a href="{{url('panel/onaybekleyen')}}" class="dropdown-item">Onay Bekleyen Siparişler</a>
                        <a href="{{url('panel/kargolanan-siparisler')}}" class="dropdown-item">Kargolanan Siparişler</a>
                        <!--<a href="#" class="dropdown-item">İptal Edilen Siparişler</a>-->
                        <a href="{{url('panel/tamamlanan-siparisler')}}" class="dropdown-item">Tamamlanan Siparişler</a>
                    </div>
                </li>
                @endif
                @if($durum1=='1')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-urunyonetimi" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Ürün Yönetimi <div class="arrow-down"></div>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="topnav-urunyonetimi">

                        <div class="dropdown">
                            <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-urunler" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Ürünler <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-urunler">
                                <a href="{{url('panel/urunler')}}" class="dropdown-item">Tüm Ürünler</a>
                                <a href="{{url('panel/urunekle')}}" class="dropdown-item">Yeni Ürün Ekle</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-kategoriler" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Kategoriler <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-kategoriler">
                                <a href="{{url('panel/kategoriler')}}" class="dropdown-item">Tüm Kategoriler</a>
                                <a href="{{url('panel/kategoriekle')}}" class="dropdown-item">Yeni Kategori Ekle</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-markamodel" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Marka & Model<div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-markamodel">
                                <a href="{{url('panel/markalar')}}" class="dropdown-item">Marka İşlemleri</a>
                                <a href="{{url('panel/modeller')}}" class="dropdown-item">Model İşlemleri</a>
                                <a href="{{url('panel/seriler')}}" class="dropdown-item">Seri İşlemleri</a>
                                <a href="{{url('panel/yillar')}}" class="dropdown-item">Yıl İşlemleri</a>
                            </div>
                        </div>
                    </div>
                </li>
                @endif
                @if($durum7=='1')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-pages" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Raporlar<div class="arrow-down"></div>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="topnav-pages">


                        <a href="/panel/siparisrapor" class="dropdown-item">Sipariş Raporları</a><!--
                        <a href="#" class="dropdown-item">Bayi Raporları</a>
                        <a href="#" class="dropdown-item">Stok Hareket Raporları</a>
                        <a href="#" class="dropdown-item">Ürün Raporları</a>-->
                    </div>
                </li>
                @endif
            </ul>
        </div>

        <!-- Menü Yanı Arama -->

    </nav>
</div>
