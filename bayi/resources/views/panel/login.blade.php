@extends('panel.layouts.login')

@section('title', 'B2B Admin Login')

@section('content')

    <div class="home-btn d-none d-sm-block">
        <a href="{{ url('panel') }}" class="text-dark"><i class="fas fa-home h2"></i></a>
    </div>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="bg-login text-center">
                            <div class="bg-login-overlay"></div>
                            <div class="position-relative">
                                <h5 class="text-white font-size-20">{{baslik()}}</h5>
                                <p class="text-white-50 mb-0">Giriş yapmak için bilgilerinizi giriniz</p>
                                <a href="" class="logo logo-admin mt-4">

                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="p-2">
                                <form method="post" class="form-horizontal" action="{{route('panel.login.post')}}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="username">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" name="email" id="username" placeholder="Kullanıcı Adınız">
                                    </div>

                                    <div class="form-group">
                                        <label for="userpassword">Şifre</label>
                                        <input type="password" class="form-control" name="password" id="userpassword" placeholder="Şifreniz">
                                    </div>

                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="customControlInline">
                                        <label class="custom-control-label" for="customControlInline">Beni Hatırla</label>
                                    </div>

                                    <div class="mt-3">
                                        <button class="btn btn-primary btn-block waves-effect waves-light" type="submit" style="background-color: #a61b25; border-color: #9e1e22;">Giriş Yap</button>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <a href="sifremi-unuttum.html" class="text-muted"><i class="mdi mdi-lock mr-1"></i> Şifremi Unuttum?</a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p><script>document.write(new Date().getFullYear())</script> © {{baslik()}}</p>
                       
                    </div>

                </div>
            </div>
        </div>
    </div>
<style>
    .bg-login-overlay {

        background: linear-gradient(to right, #de0624, #b91818) !important;
    }
</style>

@endsection
