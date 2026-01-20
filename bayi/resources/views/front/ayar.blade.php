@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')

    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row"></div>
            <div class="row m-t-30">





                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <div class="tab-box">
                            <div class="tab active" v-tab="tab1">
                                Adres Ayarları
                            </div>
                            <div class="tab" v-tab="tab2">
                                Bayi Bilgilerim
                            </div>
                            <div class="tab" v-tab="tab3">
                                Şirket Bilgilerim
                            </div>

                        </div>

                        <div class="page-title-right">
                            <div class="custom-control custom-switch mb-2 fleft" dir="ltr" style="margin-right:30px;">

                            </div>
                            <ol class="breadcrumb m-0" style="display:none;">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Tab</a></li>
                                <li class="breadcrumb-item active">Arama Sonucu</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row list-table">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">

                            <h4 class="card-title mb-4 s-body-title"> </h4>


                            <div>
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="form-group col-lg-12 tab-c p-0" id="tab1">
                                            <div class="col-lg-8 fleft p-l-0">

                                                <div class="col-md-3 p-l-0 fleft">
                                                    <label>Adres Adı</label>
                                                    <input type="text" class="form-control" id="adresad"/>
                                                </div>
                                                <div class="col-md-7 p-l-0 fleft">
                                                    <label>Adres</label>
                                                    <input type="text" class="form-control" id="adres"/>
                                                </div>

                                                <div class="col-md-3 p-l-0 fleft m-t-20">
                                                    <label>Şehir</label>
                                                    <input type="text" class="form-control" id="sehir"/>
                                                </div>
                                                <div class="col-md-3 p-l-0 fleft m-t-20">
                                                    <label>İlçe</label>
                                                    <input type="text" class="form-control" id="ilce"/>
                                                </div>
                                                <div class="col-md-4 p-l-0 fleft m-t-20">
                                                    <label>Telefon</label>
                                                    <input type="text" class="form-control" id="telefon"/>
                                                </div>
                                                <div class="col-lg-8 fleft m-t-20 p-0">
                                                    <button class="btn btn-success adresekle">Kaydet</button>
                                                    <div class="col-lg-8 fright m-t-10 p-0 adres-sonuc">

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 fleft adresler">
                                                <h6>Kayıtlı Adreslerim</h6>

                                            </div>



                                        </div>

                                        @if($user)
                                        <div class="form-group col-lg-12 tab-c p-0" id="tab2">
                                            <div class="col-lg-12 fleft m-b-10">
                                                <h5>Parola İşlemleri</h5>
                                            </div>
                                            <div class="col-lg-12 fleft form-group p-0">
                                                <div class="col-lg-3 fleft">
                                                    <label>Eski Şifre</label>
                                                    <input type="password" class="form-control" value="" id="eski" />
                                                </div>
                                                <div class="col-lg-3 fleft">
                                                    <label>Yeni Şifre</label>
                                                    <input type="password" class="form-control" value="" id="yeni" />
                                                </div>
                                                <div class="col-lg-3 fleft">
                                                    <label>Yeni Şifre Tekrar</label>
                                                    <input type="password" class="form-control" value="" id="yeni-tekrar" />
                                                </div>
                                                <div class="col-lg-2 fleft">
                                                    <button class="btn btn-success m-t-30 parolaguncelle">Güncelle</button>
                                                </div>
                                            </div>
                                            <div class="col-lg-8 fleft p-0 m-t-30">
                                                <div class="col-lg-12 fleft m-b-10">
                                                    <h5>Bayi Bilgileri</h5>
                                                </div>
                                                @if($cariCode)
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Cari Kodu</div>
                                                    <div class="col-lg-4 fleft"><strong>{{$cariCode}}</strong></div>
                                                </div>
                                                @endif
                                                @if($sirketBilgileri && isset($sirketBilgileri->s_adi))
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Şirket Adı</div>
                                                    <div class="col-lg-9 fleft"><strong>{{$sirketBilgileri->s_adi}}</strong></div>
                                                </div>
                                                @endif
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Bayi İskonto Oranı</div>
                                                    <div class="col-lg-4 fleft">%{{$user->iskonto ?? 0}}</div>
                                                </div>
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Bayi Durumu</div>
                                                    <div class="col-lg-4 fleft">
                                                        @if(isset($user->durum) && $user->durum == 1)
                                                            {{'Aktif'}}
                                                        @else
                                                            {{'Pasif'}}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Bakiye</div>
                                                    <div class="col-lg-4 fleft">@money($user->bakiye ?? 0)₺</div>
                                                </div>
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Bayi Açık Hesap Limiti</div>
                                                    <div class="col-lg-4 fleft">{{$user->acik_hesap_limit ?? 0}}₺</div>
                                                </div>
                                                @if($sirketBilgileri)
                                                @php
                                                    $acikhesapRaw = $sirketBilgileri->acikhesap ?? '0';
                                                    $acikhesapNormalized = str_replace([','], '', $acikhesapRaw);
                                                    $acikhesap = floatval($acikhesapNormalized);
                                                @endphp
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Açık Hesap Bakiye</div>
                                                    <div class="col-lg-4 fleft"><strong class="text-primary">{{ number_format($acikhesap, 2, ',', '.') }}₺</strong></div>
                                                </div>
                                                @if($sirketBilgileri->payplan_code || $sirketBilgileri->payplan_def)
                                                <div class="col-lg-12 p-0 fleft form-group">
                                                    <div class="col-lg-3 fleft">Ödeme Planı</div>
                                                    <div class="col-lg-4 fleft">
                                                        <strong>
                                                            {{ $sirketBilgileri->payplan_code ? $sirketBilgileri->payplan_code . ' - ' : '' }}
                                                            {{ $sirketBilgileri->payplan_def ?? '' }}
                                                        </strong>
                                                    </div>
                                                </div>
                                                @endif
                                                @endif
                                            </div>
                                        </div>
                                        @endif


                                        @if($user)
                                        <div class="form-group col-lg-12 tab-c p-0" id="tab3">

                                            <div class="col-lg-6 p-l-0 fleft">
                                                <div class="col-lg-12 fleft m-b-10">
                                                    <h5>Firma Bilgileri</h5>
                                                </div>

                                                <div class="col-lg-12 form-group fleft">
                                                    <label>Firma Ünvanı</label>
                                                    <input type="text" class="form-control" value="{{$user->firma_unvani ?? ''}}" id="unvan" disabled/>
                                                </div>
                                                <div class="col-lg-12 form-group p-l-0 fleft p-r-0">
                                                    <div class="col-lg-6 fleft">
                                                        <label>Firma Sahibi</label>
                                                        <input type="text" class="form-control" value="{{$user->firma_sahibi ?? ''}}" id="firma-sahip" disabled/>
                                                    </div>
                                                    <div class="col-lg-6 fleft">
                                                        <label>Firma Yetkili</label>
                                                        <input type="text" class="form-control" value="{{$user->yetkili_ad_soyad ?? ''}}" id="firma-yetkili" disabled/>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 form-group p-l-0 fleft p-r-0">
                                                    <div class="col-lg-6 fleft">
                                                        <label>Telefon</label>
                                                        <input type="text" class="form-control" value="{{$user->sirket_telefonu ?? ''}}" id="tel" />
                                                    </div>
                                                    <div class="col-lg-6 fleft">
                                                        <label>Gsm</label>
                                                        <input type="text" class="form-control" value="{{$user->cep_telefonu ?? ''}}" id="gsm" />
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 form-group p-l-0 fleft p-r-0">
                                                    <div class="col-lg-6 fleft">
                                                        <label>Vergi Dairesi</label>
                                                        <input type="text" class="form-control" value="{{$user->vd ?? ''}}" id="vd" disabled/>
                                                    </div>
                                                    <div class="col-lg-6 fleft">
                                                        <label>Vergi No</label>
                                                        <input type="text" class="form-control" value="{{$user->vno ?? ''}}" id="vno" disabled/>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 form-group fleft">
                                                    <label>Şirket Adres</label>
                                                    <input type="text" class="form-control" value="{{$user->sirket_adres ?? ''}}" id="sirket-adres" />
                                                </div>
                                                <div class="col-lg-8 fleft">
                                                    <button class="btn btn-success sirket-guncelle">Güncelle</button>
                                                    <div class="col-lg-8 fright m-t-10 p-0 adres-sonuc">

                                                    </div>
                                                </div>
                                            </div>



                                            <div class="col-lg-6 fleft">
                                                <div class="col-lg-12 fleft m-b-10">
                                                    <h5>Adres Bilgileri</h5>
                                                </div>
                                                <div class="col-lg-12 form-group p-l-0 fleft">
                                                    <div class="col-lg-6 fleft">
                                                        <label>Şehir</label>
                                                        <input type="text" class="form-control" value="{{$user->sehir ?? ''}}" id="il" />
                                                    </div>
                                                    <div class="col-lg-6 fleft">
                                                        <label>İlçe</label>
                                                        <input type="text" class="form-control" value="{{$user->ilce ?? ''}}" id="ilce" />
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 form-group fleft">
                                                    <label>Adres</label>
                                                    <input type="text" class="form-control" value="{{$user->adres ?? ''}}" id="adres" />
                                                </div>

                                            </div>
                                            <div class="col-lg-6 fleft">
                                                <div class="col-lg-12 fleft m-b-10 m-t-10">
                                                    <h5>Banka/Hesap Bilgileri</h5>
                                                </div>
                                                <div class="col-lg-12 form-group fleft">
                                                    <label>Hesap Adı</label>
                                                    <input type="text" class="form-control" value="{{$user->bankahesapadi ?? ''}}" id="hesap-ad" />
                                                </div>
                                                <div class="col-lg-12 form-group p-l-0 fleft">
                                                    <div class="col-lg-6 fleft">
                                                        <label>Banka</label>
                                                        <input type="text" class="form-control" value="{{$user->banka ?? ''}}" id="banka" />
                                                    </div>
                                                    <div class="col-lg-6 fleft">
                                                        <label>Şube</label>
                                                        <input type="text" class="form-control" value="{{$user->sube ?? ''}}" id="sube" />
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 form-group fleft">
                                                    <label>IBAN</label>
                                                    <input type="text" class="form-control" value="{{$user->iban ?? ''}}" id="iban" />
                                                </div>

                                            </div>

                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-lg-12 left p-l-0 m-t-20 ftr-button" style="display:none;">
                                        <button class="btn btn-danger">Kapat</button>
                                        <button class="btn btn-success">Kaydet</button>
                                    </div>


                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>





            <!-- end row -->

        </div>
        <!-- End Page-content -->

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <p><script>document.write(new Date().getFullYear())</script> © b2b salter</p>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-right d-none d-sm-block">
                            B2B Satış Sistemi <a href="https://www.salter-group.com" target="_blank">Salter-Group</a> Tarafından Yapılmıştır.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>


@endsection
