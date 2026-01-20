@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')

    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12" class="header-search">
                    <div class="card" style="background-color:#5e6577;">
                        <div class="card-body">
                            <div class="col-lg-6 fleft">
                                <div class="col-lg-4 left">

                                    <select class="form-control">
                                        <option>Marka Seçiniz</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 fleft">

                                    <select class="form-control">
                                        <option>Model Seçiniz</option>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-lg-4 fleft">

                                    <select class="form-control">
                                        <option>Kategori Seçiniz</option>
                                        <option></option>
                                    </select>
                                </div>

                                <div class="col-lg-6 fleft">
                                    <label for="name"></label>
                                    <input type="text" class="form-control" placeholder="Oem No" />
                                </div>
                                <div class="col-lg-6 fleft">
                                    <label for="name"></label>
                                    <input type="text" class="form-control" placeholder="No"/>
                                </div>
                            </div>
                            <div class="col-lg-1 fleft">
                                <button class="btn btn-info" style="height:97px; width:60px;"><i class="fa fa-search"></i><br>Ara</button>
                            </div>
                            <div class="col-lg-5 p-0 d-none d-sm-block fleft header-search-banner" style="text-align: center; ">
                                <img src="{{ asset('assets/front/assets/images/b2b_banner2.jpg') }}" height="97" class="fright" style="max-width:100%;" onerror="this.style.display='none';"/>
                            </div>


                        </div>
                    </div>
                </div>





            </div>
            <!-- end page title -->




            <div class="row sepet-icerik">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Sepetim </h4>
                            <div class="row">

                                <div class="col-lg-12">
                                    <div class="col-lg-6 fleft">
                                        <h6>Adres Seçimi</h6>
                                        <div class="col-lg-12 p-l-0 form-group fleft">
                                            <label>Teslimat Adresi</label>
                                            <select class="form-control" id="teslimat">
                                                @if($adresler->count() > 0)
                                                    @foreach($adresler as $show)
                                                        <option value="{{$show->adres_id ?? ''}}">{{$show->baslik ?? ''}} ({{$show->adres ?? ''}})</option>
                                                    @endforeach
                                                @else
                                                    <option value="">Adres bulunamadı</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-lg-12 fleft p-l-0 form-group">
                                            <label>Fatura Adresi</label>
                                            <select class="form-control" id="fatura">
                                                @if($adresler->count() > 0)
                                                    @foreach($adresler as $show)
                                                        <option value="{{$show->adres_id ?? ''}}">{{$show->baslik ?? ''}} ({{$show->adres ?? ''}})</option>
                                                    @endforeach
                                                @else
                                                    <option value="">Adres bulunamadı</option>
                                                @endif
                                            </select>
                                        </div>
                                        @php($siparisData = $siparis->first() ?? null)
                                        @if(!$siparisData)
                                            <div class="col-lg-12 alert alert-warning">
                                                <p>Sipariş bulunamadı. Lütfen tekrar deneyin.</p>
                                            </div>
                                        @endif
                                        @if($siparisData && ($siparisData->odeme == '61' || $siparisData->odeme == 61))
                                        <div class="col-lg-12 fleft p-l-0 form-group">
                                            <label>Bakiye Bırak</label>
                                            <input type="text" class="form-control bakiye-birak" />
                                        </div>
                                        @endif
                                        @if($siparisData)
                                        <form method="post" action="{{ url('/pos/' . ($siparisData->token ?? '')) }}">
                                        <div class="col-lg-10 fleft p-0 ">
                                            @foreach($siparis as $show)
                                                <div class="col-lg-12 p-0">
                                                    <b>Sipariş Toplamı :</b> @money($show->geneltoplam ?? 0) €
                                                </div>
                                                <input type="hidden" class="toplam-tutar" value="{{$show->geneltoplam ?? 0}}"/>
                                            @endforeach
                                            <div class="col-lg-12 p-0 m-t-5" style="display:none;">
                                                <b>Kalan Bakiye : <span class="kalan-bakiye"></span></b> €
                                            </div>
                                            <input type="hidden" value="{{$bakiyelimit}}" class="bakiye-limit" />
                                            <div class="col-lg-12 p-0 m-t-5 kalan-bakiye-box" style="display:none;">
                                                <b>Kalan Bakiye Limiti  : <span class="kalan-bakiye-limit"></span></b> €
                                            </div>
                                            <div class="col-lg-12 p-0 m-t-5 odenecek">
                                                <strong>Ödenecek Tutar :</strong> <b>@money($siparisData->geneltoplam ?? 0)</b> €
                                            </div>
                                            <input type="hidden" class="odenecek-tutar" value="{{$siparisData->geneltoplam ?? 0}}"/>
                                            <input type="hidden" class="sipid" value="{{$siparisData->sip_id ?? ''}}"/>
                                            <input type="hidden" class="tip" value="{{$siparisData->odeme ?? ''}}"/>
                                        </div>
                                        </form>
                                        @endif
                                        <div class="col-lg-12 fleft m-t-30 p-0">
                                            <a href="{{ url('/ayarlar') }}"><button class="btn btn-info">Adres Ekle</button></a>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 fleft">
                                        <div style="margin-left:50px;" class="fleft">
                                        @if($siparisData && ($siparisData->odeme == '21' || $siparisData->odeme == 21))
                                                <div class="col-lg-12 fleft m-b-20">
                                                    <h6>Hesap Seç</h6>
                                                    <p>Ödeme yapacağınız <b>bankayı</b> seçiniz.</p>
                                                </div>
                                            @php($rid = '0')
                                            @foreach($bankalar as $banka)
                                                <div class="col-lg-12">
                                                    <input type="radio" name="banka" id="rb{{$rid}}" value="{{$banka->banka_adi ?? ''}}"/>
                                                    <label for="rb{{$rid}}"><b>{{$banka->banka_adi ?? ''}} - {{$banka->unvan ?? ''}}</b></label>
                                                    <p>{{$banka->hesapno ?? ''}} - {{$banka->sube ?? ''}} {{$banka->iban ?? ''}}</p>
                                                </div>
                                                @php($rid++)
                                            @endforeach
                                        @endif
                                        </div>
                                        <div class="col-lg-10 fleft" style="margin-left:50px; margin-top:20px;">
                                            <label>Sipariş Notu</label>
                                            <textarea class="form-control siparis-not"></textarea>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-lg-12 m-t-20">


                                    <div class="col-lg-2 fright">
                                        @if($siparisData && ($siparisData->odeme == '61' || $siparisData->odeme == 61))
                                            <button type="submit" class="btn btn-success fright m-t-20" id="odeme-yap">Ödeme Yap</button>
                                        @else
                                        <button type="button" class="btn btn-success fright m-t-20" id="siparis-tamamla">Siparişi Tamamla</button>
                                        @endif
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-modal">

        </div>
        <!-- end row -->

    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <p><script>document.write(new Date().getFullYear())</script> © {{baslik()}}</p>
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-right d-none d-sm-block">
                        B2B Satış Sistemi
                    </div>
                </div>
            </div>
        </div>
    </footer>
    </div>










@endsection
