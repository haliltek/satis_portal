@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Sipariş Detay')
@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Tüm Siparişler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Sipariş</a></li>
                                <li class="breadcrumb-item active">Tüm Siparişler</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            @foreach($ayarlar as $main)

            @endforeach
            <div class="row fatura">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Sipariş Detay </h4>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="col-lg-12 fatura-baslik">
                                        <div class="col-lg-6 fleft p-0 sol-1">
                                            <div class="fatura-logo">
                                                <h3 style="font-weight:800;">{{baslik()}}</h3>
                                                <div class="col-lg-5 p-0 m-t-10">
                                                    <div class="satir">{{$main->unvan}}</div>
                                                    <div class="satir">{{$main->gsm}} - {{$main->fax}}</div>
                                                    <div class="satir">{{$main->adres}}</div>
                                                </div>
                                            </div>
                                        </div>
                                        @foreach($users as $firma)
                                        @endforeach
                                        <div class="col-lg-6  fright sag-1" style="float:right;">
                                            <div class="col-lg-7 fright">
                                                <div class="col-lg-12 fleft">
                                                    Sayın
                                                </div>
                                                <div class="col-lg-12 fleft">
                                                    <div class="satir"><b>{{$firma->firma_unvani}} - {{$firma->yetkili_ad_soyad}}</b></div>
                                                    <div class="satir">{{$firma->sirket_telefonu}}</div>
                                                    <div class="satir">{{$firma->sirket_adres}}</div>
                                                    <div class="satir">Vergi Dairesi: {{$firma->vd}}</div>
                                                    <div class="satir">Vergi No: {{$firma->vno}}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 fleft fatura-alt">
                                            <div class="col-lg-4 fleft p-0">
                                                Vergi Dairesi : {{$main->vergi_d}}
                                            </div>
                                            <div class="col-lg-4 fleft">
                                                Vergi No : {{$main->vergi_no}}
                                            </div>
                                            <div class="col-lg-4 fleft">
                                                Mersis No : {{$main->mersis_no}}
                                            </div>
                                        </div>

                                    </div>
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr height="50">
                                            <th width="10%">No</th>
                                            <th width="10%">Sipariş Tarihi</th>
                                            <th width="40%">Ürün Adı</th>
                                            <th width="10%">Adet</th>
                                            <th width="12%">Birim Fiyatı</th>
                                            <th width="8%">İskonto</th>
                                            <th width="10%">Kdv</th>
                                            <th width="12%">Fiyat</th>

                                        </tr>
                                        </thead>

                                        <tbody>
                                        @php($brut = '0')
                                        @foreach($detay as $show)
                                            <tr>
                                                <td>{{$show->urun_kodu}}</td>
                                                <td>{{dateformat($show->tarih)}}</td>
                                                <td>{{$show->urun_adi}}</td>
                                                <td>{{$show->adet}}</td>
                                                <td>{{$show->tutar}}₺</td>
                                                <td>%{{$show->iskonto}}</td>
                                                <td>@money2($show->kdv)</td>
                                                <td>@money2($show->tutar * $show->adet)</td>
                                            </tr>
                                            @php($brut += $show->tutar * $show->adet )

                                        @endforeach
                                        @php($iskonto_tutar = $brut / 100 * $show->iskonto )
                                        @php($iskontolu = $brut - $iskonto_tutar )
                                        <tr style="border:0px;">
                                            <td colspan="6" align="right">
                                                <p>Brüt Toplam :</p>
                                                <p>İndirim(%10) :</p>
                                                <p>Ara Toplam :</p>
                                                <p>Kdv(%18) :</p>
                                                <p><b>Sipariş Toplamı :</b></p>
                                            </td>
                                            <td colspan="2">
                                                <p>@money2($brut)</p>
                                                <p>@money2($iskonto_tutar)</p>
                                                <p>@money2($iskontolu)</p>
                                                <p>@money2($iskontolu / 100 * 18)</p>
                                                <p><b>@money2($show->geneltoplam)</b></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-lg-12 fleft m-t-10">
                                    <button class="btn btn-primary pmakbuz fleft"><i class="fa fa-print m-r-5"></i>Yazdır</button>
                                    <div class="fleft" style="margin-left:40px; padding-top:10px;">Kargo Takip No : <b>{{$show->kargotakip}}</b></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        <!-- End Page-content -->
@endsection
