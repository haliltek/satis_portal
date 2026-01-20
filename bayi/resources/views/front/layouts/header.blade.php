<header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex" style="width:925px;">
                    <!-- LOGO -->
                    <div class="navbar-brand-box">
                        <a href="{{ url('/') }}" class="logo logo-dark">
                                <span class="logo-sm" style="font-size: 18px; font-weight: bold; color: #000;">
                                    GEMAŞ b2b
                                </span>
                            <span class="logo-lg" style="font-size: 20px; font-weight: bold; color: #000;">
                                    GEMAŞ b2b
                                </span>
                        </a>

                        <a href="{{ url('/') }}" class="logo logo-light">
                                <span class="logo-sm" style="font-size: 18px; font-weight: bold; color: #fff;">
                                    GEMAŞ b2b
                                </span>
                            <span class="logo-lg" style="font-size: 20px; font-weight: bold; color: #fff;">
                                    GEMAŞ b2b
                                </span>
                        </a>
                    </div>

                    <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light" data-toggle="collapse" data-target="#topnav-menu-content">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>

                    <div class="topnav" style="margin-left:130px;">
                        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

                            <div class="collapse navbar-collapse" id="topnav-menu-content">
                                <a href="{{ url('/home') }}">
                                <div class="menuicon">
                                    <div class="ico"><i class="fa fa-search"></i></div>
                                    <div class="icon-text">Ara</div>
                                </div>
                                </a>
                                <a href="{{ url('/home') }}">
                                    <div class="menuicon">
                                        <div class="ico"><i class="fa fa-th-list"></i></div>
                                        <div class="icon-text">En Yeniler</div>
                                    </div>
                                </a>
                                <a href="{{ url('/siparisler') }}">
                                <div class="menuicon">
                                    <div class="ico"><i class="fa fa-shopping-bag"></i></div>
                                    <div class="icon-text">Sipariş</div>
                                </div>
                                </a>

                                <a href="{{ url('/extre') }}">
                                <div class="menuicon">
                                    <div class="ico"><i class="fa fa-sticky-note"></i></div>
                                    <div class="icon-text">Ekstre</div>
                                </div>
                                </a>
                                <a href="{{ url('/hesap') }}">
                                <div class="menuicon">
                                    <div class="ico"><i class="fa fa-suitcase"></i></div>
                                    <div class="icon-text">Hesap</div>
                                </div>
                                </a>
                                <a href="{{ url('/sepet') }}">
                                <div class="menuicon show-basket">
                                    <div class="ico"><i class="fa fa-cart-plus"></i></div>
                                    <div class="icon-text">Sepet</div>
                                </div>
                                </a>

                                <div class="menuicon posgetir">
                                    <div class="ico"><i class="fa fa-credit-card"></i></div>
                                    <div class="icon-text">Sanal Pos</div>
                                </div>
                                <a href="{{url('cikis')}}">
                                <div class="menuicon">
                                    <div class="ico"><i class="fa fa-times-circle"></i></div>
                                    <div class="icon-text">Çıkış</div>
                                </div>
                                </a>
                            </div>


                        </nav>
                    </div>
                </div>



                <div class="dropdown d-inline-block bakiye-bilgi">
                    Hesap Bakiye
                    @php
                        $user = Auth::user();
                        $acikhesap = 0;
                        if ($user && isset($user->id)) {
                            try {
                                // Veritabanından fresh kullanıcı bilgilerini çek
                                $freshUser = \DB::table('b2b_users')->where('id', $user->id)->first();
                                
                                if ($freshUser) {
                                    // Cari code ile şirket bilgilerini çek
                                    $cariCode = trim($freshUser->cari_code ?? '');
                                    if (!empty($cariCode)) {
                                        $sirket = \DB::table('sirket')
                                            ->where('s_arp_code', $cariCode)
                                            ->orWhere('logo_company_code', $cariCode)
                                            ->first();
                                        
                                        if ($sirket && isset($sirket->acikhesap)) {
                                            // Açık hesap bakiyesini normalize et
                                            $acikhesapRaw = $sirket->acikhesap ?? '0';
                                            $acikhesapNormalized = str_replace([','], '', $acikhesapRaw);
                                            $acikhesap = floatval($acikhesapNormalized);
                                        }
                                    }
                                    
                                    // Eğer hala bulunamadıysa, company_id ile dene
                                    if ($acikhesap == 0 && isset($freshUser->company_id) && $freshUser->company_id > 0) {
                                        $sirket = \DB::table('sirket')
                                            ->where('sirket_id', $freshUser->company_id)
                                            ->first();
                                        if ($sirket && isset($sirket->acikhesap)) {
                                            $acikhesapRaw = $sirket->acikhesap ?? '0';
                                            $acikhesapNormalized = str_replace([','], '', $acikhesapRaw);
                                            $acikhesap = floatval($acikhesapNormalized);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('Header açık hesap bakiyesi sorgusu hatası: ' . $e->getMessage());
                                // Hata durumunda b2b_users tablosundaki bakiyeyi kullan
                                $acikhesap = $user->bakiye ?? 0;
                            }
                        }
                    @endphp
                    <p>@money2($acikhesap)</p>
                </div>
                <div class="dropdown d-inline-block iskonto-bilgi">
                    İskonto
                    @php
                        $userIskonto = Auth::user()->iskonto ?? 10;
                        // Eğer iskonto 0 ise varsayılan olarak %10 kullan
                        if ($userIskonto == 0) {
                            $userIskonto = 10;
                        }
                    @endphp
                    <p>%{{ number_format($userIskonto, 2, ',', '.') }}</p>
                </div>
                <!---
                <div class="dropdown d-none d-lg-inline-block ml-1" style="display:none;">
                    <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                        <i class="mdi mdi-fullscreen"></i>
                    </button>
                </div>
                -->


                <div class="dropdown d-inline-block header-setting-menu">
                    <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="rounded-circle header-profile-user" src="{{ asset('assets/front/assets/images/users/avatar-2.jpg') }}" alt="Header Avatar" onerror="this.src='{{ asset('assets/panel/images/users/avatar-1.jpg') }}'">
                        <span class="d-none d-xl-inline-block ml-1">
                            @php
                                $user = Auth::user();
                                $sirketAdi = '';
                                if ($user && isset($user->id)) {
                                    try {
                                        // Veritabanından fresh kullanıcı bilgilerini çek (session cache'i bypass et)
                                        $freshUser = \DB::table('b2b_users')->where('id', $user->id)->first();
                                        
                                        if ($freshUser) {
                                            // Önce cari_code ile sorgu yap (en güvenilir)
                                            $cariCode = trim($freshUser->cari_code ?? '');
                                            if (!empty($cariCode)) {
                                                // Önce s_arp_code ile dene
                                                $sirket = \DB::table('sirket')
                                                    ->where('s_arp_code', $cariCode)
                                                    ->first();
                                                
                                                // Bulunamadıysa logo_company_code ile dene
                                                if (!$sirket) {
                                                    $sirket = \DB::table('sirket')
                                                        ->where('logo_company_code', $cariCode)
                                                        ->first();
                                                }
                                                
                                                if ($sirket && !empty($sirket->s_adi)) {
                                                    $sirketAdi = trim($sirket->s_adi);
                                                    // company_id'yi güncelle
                                                    if (isset($sirket->sirket_id) && $sirket->sirket_id != ($freshUser->company_id ?? 0)) {
                                                        \DB::table('b2b_users')
                                                            ->where('id', $user->id)
                                                            ->update(['company_id' => $sirket->sirket_id]);
                                                    }
                                                }
                                            }
                                            
                                            // Eğer hala bulunamadıysa, company_id ile dene
                                            if (empty($sirketAdi) && isset($freshUser->company_id) && $freshUser->company_id > 0) {
                                                $sirket = \DB::table('sirket')
                                                    ->where('sirket_id', $freshUser->company_id)
                                                    ->first();
                                                if ($sirket && !empty($sirket->s_adi)) {
                                                    $sirketAdi = trim($sirket->s_adi);
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error('Header şirket adı sorgusu hatası: ' . $e->getMessage());
                                    }
                                }
                            @endphp
                            @if(!empty($sirketAdi))
                                {{ $sirketAdi }}
                            @else
                                {{ $user->username ?? $user->email ?? 'Kullanıcı' }}
                            @endif
                        </span>
                        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <!-- item-->

                        <a class="dropdown-item" href="{{ url('/ayarlar') }}"><i class="bx bx-wallet font-size-16 align-middle mr-1"></i> Hesap / Bayi Ayarları</a>
                        <a class="dropdown-item" target="_blank" href="/uploads/ariza-iade-formu.pdf"><i class="bx bx-buildings font-size-16 align-middle mr-1"></i> İade Formu</a>
                        <a class="dropdown-item " onclick="opencontract()" ><i class="bx bx-certification font-size-16 align-middle mr-1"></i> Sözleşmeler</a>
                        <a class="dropdown-item" onclick="oemistek()"><i class="bx bx-buildings font-size-16 align-middle mr-1"></i> Oem İstek Formu</a>

                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{url('cikis')}}"><i class="bx bx-power-off font-size-16 align-middle mr-1 text-danger"></i> Çıkış</a>
                    </div>
                </div>

                <div class="dropdown d-inline-block">
                    <div class="sepet">
                        <div class="s-title">
                            <i class="fa fa-cart-plus"></i> Sepet
                        </div>
                        <i class="sepetCount"></i>
                        <span class="s-arrow"><i class="fa fa-chevron-circle-down"></i></span>
                       <!-- Sepet Componenti -->
                            <x-sepet />
                        <!-- Sepet Componenti -->
                    </div>

                </div>
            </div>
    </div>
</header>
