<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{ url('panel') }}" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="/uploads/ayarlar/{{logo()}}" alt="" height="35">
                                </span>
                    <span class="logo-lg">
                                    <img src="/uploads/ayarlar/{{logo()}}" alt="" height="35">
                                </span>
                </a>

                <a href="{{ url('panel') }}" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="/uploads/ayarlar/{{logo()}}" alt="" height="35">
                                </span>
                    <span class="logo-lg">
                                    <img src="/uploads/ayarlar/{{logo()}}" alt="" height="35">
                                </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light" data-toggle="collapse" data-target="#topnav-menu-content">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <x-menu />
        </div>

        <div class="d-flex">



            <div class="dropdown d-none d-lg-inline-block ml-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                    <i class="mdi mdi-fullscreen"></i> TAM EKRAN
                </button>
            </div>

            <x-bildirim />

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{asset('assets/panel/images/users/avatar-2.jpg')}}" alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ml-1">{{ Auth::user()->name }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <!-- item-->
                    <a class="dropdown-item" href="{{url('panel/adminduzenle/')}}/{{ Auth::user()->id }}"><i class="bx bx-user font-size-16 align-middle mr-1"></i> Profilim</a>

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{url('panel/cikis')}}"><i class="bx bx-power-off font-size-16 align-middle mr-1 text-danger"></i> Çıkış</a>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect">
                    <i class="mdi mdi-settings-outline"></i>
                </button>
            </div>
        </div>
    </div>
</header>
<div class="allnoticebox">
    <h5>Tüm bildirimler <i class="fa fa-times fright closeallnoticebox" id="closeallnoticebox" style="float:right; cursor:pointer;"></i> </h5>
    <div class="notices0 ">



    </div>
</div>
