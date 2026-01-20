@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel')

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
                        <h4 class="page-title mb-0 font-size-18">B2B Kontrol Merkezi</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Kontrol Merkezi</a></li>
                                <li class="breadcrumb-item active">Merhaba {{ Auth::user()->name }}</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
           @php
    $siparis_toplam = 0; // Sayısal değer olarak başlat
    $siparis_adet = 0; // Sayısal değer olarak başlat
@endphp
@if($siparis->isNotEmpty())
    @foreach($siparis as $show)
        @php
            $siparis_toplam += $show->tutar;
        @endphp
    @endforeach
    @php
        $siparis_adet = count($siparis); // Döngü dışında bir kez hesapla
    @endphp
@endif
            <div class="row">
                <div class="col-xl-12">
                    <div class="fleft col-lg-3 p-l-0">
                        <div class="card">
                            <div class="card-body">
                                <div class="media">
                                    <div class="avatar-sm font-size-20 mr-3">
                                            <span class="avatar-title bg-soft-primary text-primary rounded">
                                                    <i class="mdi mdi-tag-plus-outline"></i>
                                                </span>
                                    </div>
                                    <div class="media-body">
                                        <div class="font-size-16 mt-2">Toplam Satılan Ürün / ₺</div>
                                    </div>
                                </div>
                                <h4 class="mt-4">{{$siparis_toplam}} ₺</h4>

                            </div>
                        </div>

                    </div>
                    <div class="fleft col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="media">
                                    <div class="avatar-sm font-size-20 mr-3">
                                            <span class="avatar-title bg-soft-primary text-primary rounded">
                                                    <i class="mdi mdi-account-multiple-outline"></i>
                                                </span>
                                    </div>
                                    <div class="media-body">
                                        <div class="font-size-16 mt-2">Toplam Satılan Ürün / Ad</div>

                                    </div>
                                </div>
                                <h4 class="mt-4">{{$siparis_adet}} Adet</h4>

                            </div>
                        </div>

                    </div>
                    <div class="fleft col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="media">
                                    <div class="avatar-sm font-size-20 mr-3">
                                            <span class="avatar-title bg-soft-primary text-primary rounded">
                                                    <i class="mdi mdi-account-multiple-outline"></i>
                                                </span>
                                    </div>
                                    <div class="media-body">
                                        <div class="font-size-16 mt-2">Bayiler</div>

                                    </div>
                                </div>
                                <h4 class="mt-4">{{$bayi}} Bayi</h4>

                            </div>
                        </div>

                    </div>
                    <div class="col-lg-3 fleft p-r-0">
                        <div class="card bg-primary">
                            <div class="card-body">
                                <div class="text-white-50">
                                    <h5 class="text-white">Türkiye'nin En Gelişmiş<br> B2B Sistemi</h5>
                                    <div>
                                        <a href="#" class="btn btn-outline-success btn-sm">İncele</a>
                                    </div>
                                </div>
                                <div class="row justify-content-end">
                                    <div class="col-8">
                                        <div class="mt-4">
                                            <img src="assets/images/widget-img.png" alt="" class="img-fluid mx-auto d-block">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>




            </div>
            <!-- end row -->


            <!-- end row -->


            <!-- end row -->

            <div class="row">


                <div class="col-lg-8 fleft">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Son Siparişler <a class="btn btn-info fright" href="panel/siparisler" style="margin-top:-8px;">Tüm Siparişler</a></h4>

                            <div class="table-responsive">
                                <table class="table table-centered">
                                    <thead>
                                    <tr>
                                        <th data-priority="1">Sipariş</th>
                                        <th data-priority="2">Tarih</th>
                                        <th data-priority="3">Bayi</th>
                                        <th data-priority="4">Ödeme</th>
                                        <th data-priority="5">Kargo</th>
                                        <th data-priority="6">Sipariş Tutarı</th>
                                        <th data-priority="7">Durum</th>
                                        <th data-priority="8">Kargo Durum</th>
                                        <th data-priority="9">İşlem</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($siparisler as $siparis)
                                        @if($siparis->durum == '1')
                                            @php
                                                $durum = 'Ödeme Bekliyor'; $color = '#fd971a';
                                            @endphp
                                        @elseif($siparis->durum == '2')
                                            @php
                                                $durum = 'Ödeme Yapıldı';
                                                $color = '#4ead05';
                                            @endphp
                                        @else
                                            @php
                                                $durum = 'Tamamlanmadı'; $color='#666';
                                            @endphp
                                        @endif
                                        @if($siparis->kargo_durum == '1')
                                            @php($kargo_durum = 'Hazırlanıyor')
                                            @php($color2 = '#fd971a')
                                        @elseif($siparis->kargo_durum == '2')
                                            @php($kargo_durum = 'Kargoya Verildi')
                                            @php($color2 = '#4ead05')
                                        @else
                                            @php($kargo_durum = 'Beklemede')
                                            @php($color2 = '#666')
                                        @endif
                                        <tr>
                                            <td>{{$siparis->sip_id}}</td>
                                            <td>{{$siparis->tarih}}</td>
                                            <td>{{$siparis->bayi}}</td>
                                            <td>{{$siparis->odeme_adi}}</td>
                                            <td>{{$siparis->kargo_adi}}</td>
                                            <td>@money($siparis->geneltoplam)₺</td>
                                            <td><span style="color:{{$color}};" durum="{{$siparis->durum}}" durumad="{{$durum}}"  sid="{{$siparis->sip_id}}" id="s{{$siparis->sip_id}}" tutar="{{$siparis->geneltoplam}}">{{$durum}}</span></td>
                                            <td><span style="color:{{$color2}};"  sid="{{$siparis->sip_id}}" kid="{{$siparis->kargo_adi}}" kad="{{$siparis->name}}" kno="{{$siparis->kargo_durum}}" kd="{{$kargo_durum}}" id="k{{$siparis->sip_id}}">{{$kargo_durum ?? "Null"}}</span></td>
                                            <td><a href="/panel/siparis/{{$siparis->sip_id}}" title="İncele" class="m-r-5"><i class="fa fa-eye"></i></a></td>
                                        </tr>
                                    @endforeach



                                    </tbody>
                                </table>
                            </div>


                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fleft">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Havale/Eft Bildirimleri <a class="btn btn-info fright" href="panel/havalebildirim" style="margin-top:-8px;">Tümünü Gör</a></h4>
                            <div class="table-responsive">
                                <div class="odemedetay-modal">
                                    <div class="card">
                                        <div class="card-body" style="padding-top:10px; padding-bottom:10px;">
                                            <h5 class="p-0 fleft">Ödeme Detay</h5>
                                            <i class="fa fa-times" id="close-odemedetay" style="cursor:pointer; font-size:20px; color:#f00; float:right; margin-top:2px;"></i>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 fleft">
                                        <div class="col-lg-12 fleft form-group" id="gonderen">Gönderen : <b></b> </div>
                                        <div class="col-lg-12 fleft form-group" id="bayi">Bayi : <b></b></div>
                                        <div class="col-lg-6 fleft form-group" id="sip-tutar">Sipariş Tutar : <b></b></div>
                                        <div class="col-lg-6 fleft form-group" id="odenen">Ödenen : <b></b></div>
                                    </div>
                                    <div class="col-lg-12 fleft ">
                                        <button class="btn btn-success fright" id="odemeonay" oid="">Onayla</button>
                                    </div>
                                </div>

                                <table class="table table-centered">
                                    <thead>
                                    <tr>
                                        <th data-priority="1">Uye</th>
                                        <th data-priority="1">Ödenen</th>
                                        <th data-priority="1">Sipariş</th>
                                        <th data-priority="1">İncele</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($havale as $show)
                                            @if($show->onay < '1')
                                                @php($color='#fd971a')
                                            @else
                                                @php($color='#4ead05')
                                                @endif
                                        <tr>
                                            <td><div style="color:{{$color}}">{{$show->name}}</div></td>
                                            <td><div class="align-right"> @money($show->odenen)₺</div></td>
                                            <td>{{$show->sipid}}</td>
                                            <td><i class="fa fa-eye odemedetay" style="cursor:pointer" oid="{{$show->id}}"></i></td>
                                        </tr>
                                        @endforeach



                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- End Page-content -->


    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    </div>
    <!-- end container-fluid -->



@endsection
