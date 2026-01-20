@extends('panel.layouts.login')
@section('title', "Giriş Yap")
@section('content')
<!--
    <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Giriş Yap') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Addresi') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Şifre') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Beni Hatırla') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Giriş Yap') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Şifremi unuttum') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
-->


<div class="account-pages my-5 pt-sm-5" style="background: #f5f7fa; min-height: 100vh; padding: 40px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card overflow-hidden" style="border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px;">
                    <div class="bg-login text-center" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); padding: 40px 20px; border-radius: 8px 8px 0 0; position: relative; overflow: hidden;">
                        <div style="position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); width: 120px; height: 120px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            @php
                                $logoPath = logo();
                                // Logo path'i düzelt - asset() helper kullan
                                if (str_starts_with($logoPath, '/')) {
                                    $logoUrl = url(str_replace('/b2b-gemas-project-main/bayi/public', '', $logoPath));
                                } else {
                                    $logoUrl = url('assets/panel/images/logo.png');
                                }
                            @endphp
                            <img src="{{ $logoUrl }}" alt="Logo" style="max-width: 80px; max-height: 80px;" onerror="this.style.display='none'; this.parentElement.innerHTML='<span style=\'font-size: 24px; color: #3498db;\'>LP</span>';">
                        </div>
                        <div class="position-relative" style="z-index: 1;">
                            <h5 class="text-white" style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">Gemaş Bayi Portal</h5>
                            <p class="text-white-50 mb-0" style="font-size: 14px; opacity: 0.9;">Giriş yapmak için bilgilerinizi giriniz</p>
                        </div>
                    </div>
                    <div class="card-body pt-5" style="padding-top: 80px !important;">
                        <div class="p-2">
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="username">E-Posta adresi</label>
                                    <input placeholder="E-posta adresiniz" id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="userpassword">Şifre</label>
                                    <input placeholder="Şifreniz" id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="custom-control-label" for="remember">
                                        {{ __('Beni Hatırla') }}
                                    </label>
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-primary btn-block waves-effect waves-light" type="submit">Giriş Yap</button>
                                </div>

                                <div class="mt-4 text-center">
                                    @if (Route::has('password.request'))
                                        <a class="text-muted" href="{{ route('password.request') }}">
                                            <i class="mdi mdi-lock mr-1"></i> {{ __('Şifremi unuttum') }}?
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="mt-5 text-center">
                    <p style="color: #6c757d; font-size: 14px;"><script>document.write(new Date().getFullYear())</script> © Gemaş b2b</p>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
