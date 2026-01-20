@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Ürünler Listesi')
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

            <div class="col-lg-12 mb-3 align-right p-0">
                <a class="btn btn-primary" href="{{url('panel/siparisler')}}">Tüm Siparişler</a>
                <a class="btn btn-primary" href="{{url('panel/onaybekleyen')}}">Onay Bekleyen</a>
                <a class="btn btn-primary" href="{{url('panel/tamamlanan-siparisler')}}">Tamamlanan</a>
            </div>

            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Tüm Siparişleri Listelediniz</h4>
                            <p class="card-title-desc">İşlem yapmak istediğiniz siparişi seçiniz.</p>




                            <div class="table-rep-plugin">
                                <div class="table mb-0" data-pattern="priority-columns">
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
                                                <td>{{dateformat($siparis->tarih)}}</td>
                                                <td>{{$siparis->bayi}}</td>
                                                <td>{{$siparis->odeme_adi}}</td>
                                                <td>{{$siparis->name}}</td>
                                                <td>{{$siparis->geneltoplam}}₺</td>
                                                <td><span style="color:{{$color}};">{{$durum}}</span></td>
                                                <td><span style="color:{{$color2}};">{{$kargo_durum}}</span></td>
                                                <td><a href="siparis/{{$siparis->sip_id}}" title="İncele" class="m-r-5"><i class="fa fa-eye"></i></a></td>
                                            </tr>
                                        @endforeach

                                        </tbody>
                                    </table>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

        </div>
        <!-- End Page-content -->
@endsection
