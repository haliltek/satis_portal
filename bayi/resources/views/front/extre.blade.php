@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')


    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->

            <!-- end page title -->
            @php($siparis_toplam = '0')
            @php($siparis_adet = '0')
            @foreach($siparis as $show)
                @php($siparis_toplam += $show->tutar)
                @php($siparis_adet = count($siparis))
            @endforeach
            <div class="row ">
                <div class="col-lg-4 m-t-30 fleft">
                    <div class="card">
                        <div class="card-body">
                            <div class="media">
                                <div class="avatar-sm font-size-20 mr-4">
                                <span class="avatar-title bg-soft-primary text-primary rounded">
                                      <i class="mdi mdi-poll"></i>
                                </span>
                                </div>
                                <div class="media-body">
                                    <div class="font-size-16 mt-2">1 Aylık Sipariş Durumu</div>
                                </div>
                            </div>
                            <h6 class="mt-4 fleft m-r-20">Sipariş Adet <span>{{$siparis_adet}}</span></h6>
                            <h6 class="mt-4 fleft">Sipariş Toplam <span>{{$siparis_toplam}} €</span></h6>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4 m-t-30 fleft">
                    <div class="card">
                        <div class="card-body">
                            <div class="media">
                                <div class="avatar-sm font-size-20 mr-4">
                                <span class="avatar-title bg-soft-primary text-primary rounded">
                                      <i class="mdi mdi-poll"></i>
                                </span>
                                </div>
                                <div class="media-body">
                                    <div class="font-size-16 mt-2">3 Aylık Sipariş Durumu</div>
                                </div>
                            </div>
                            <h6 class="mt-4 fleft m-r-20">Sipariş Adet <span>{{$siparis_adet}}</span></h6>
                            <h6 class="mt-4 fleft">Sipariş Toplam <span>{{$siparis_toplam}} €</span></h6>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4 m-t-30 fleft">
                    <div class="card">
                        <div class="card-body">
                            <div class="media">
                                <div class="avatar-sm font-size-20 mr-4">
                                <span class="avatar-title bg-soft-primary text-primary rounded">
                                      <i class="mdi mdi-poll"></i>
                                </span>
                                </div>
                                <div class="media-body">
                                    <div class="font-size-16 mt-2">Siparişler Toplamı</div>
                                </div>
                            </div>
                            <h6 class="mt-4 fleft m-r-20">Sipariş Adet <span>{{$siparis_adet}}</span></h6>
                            <h6 class="mt-4 fleft">Sipariş Toplam <span>{{$siparis_toplam}} €</span></h6>

                        </div>
                    </div>
                </div>

            </div>


            <!-- Cari Bilgileri Kartı -->
            @if($sirketBilgileri || $cariCode)
            <div class="row m-t-10">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Cari Bilgileri</h4>
                            <div class="row">
                                @if($sirketBilgileri)
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Şirket Adı:</label>
                                    <p>{{ $sirketBilgileri->s_adi ?? '-' }}</p>
                                </div>
                                @endif
                                @if($cariCode)
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Cari Kodu:</label>
                                    <p>{{ $cariCode }}</p>
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Açık Hesap Bakiye:</label>
                                    <p class="text-primary" style="font-size: 18px; font-weight: bold;">
                                        @if($acikhesap > 0)
                                            {{ number_format($acikhesap, 2, ',', '.') }} ₺
                                        @else
                                            0,00 ₺
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Ödeme Planı:</label>
                                    <p>
                                        @if($payplanCode || $payplanDef)
                                            {{ $payplanCode ? $payplanCode . ' - ' : '' }}{{ $payplanDef }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($logoBakiye != 0)
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label class="font-weight-bold">Logo Bakiye:</label>
                                    <p class="text-info" style="font-size: 16px;">
                                        {{ number_format($logoBakiye, 2, ',', '.') }} ₺
                                    </p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row sepet-icerik m-t-10">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Cari Hesap Ekstresi </h4>
                            <div class="row">

                                <div class="col-lg-12">
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th width="12%">Sipariş No</th>
                                            <th width="12%">Tarih</th>
                                            <th width="14%">Ödeme Tipi</th>
                                            <th width="15%">Sipariş Tutarı</th>
                                            <th width="15%">Borç</th>
                                            <th width="15%">Alacak</th>
                                            <th width="15%">Güncel Bakiye</th>

                                        </tr>
                                        </thead>

                                        <tbody>
                                        @if(!empty($extre) && count($extre) > 0)
                                            @foreach($extre as $show)
                                                <tr>
                                                    <td>{{ isset($show->sid) ? $show->sid : '-' }}</td>
                                                    <td>{{ isset($show->tarih_formatted) ? $show->tarih_formatted : (isset($show->tarih) ? $show->tarih : '-') }}</td>
                                                    <td>{{ isset($show->odeme_adi) ? $show->odeme_adi : '-' }}</td>
                                                    <td><div style="text-align:right; float:left; min-width:100%;">@money2(isset($show->tutar) ? floatval($show->tutar) : 0)</div></td>
                                                    <td><div style="text-align:right; float:left; min-width:100%;">@money2(isset($show->borc) ? floatval($show->borc) : 0)</div></td>
                                                    <td><div style="text-align:right; float:left; min-width:100%;"><span style="color:{{ isset($show->color) ? $show->color : '#666' }}">@money2(isset($show->alacak) ? floatval($show->alacak) : 0)</span></div></td>
                                                    <td><div style="text-align:right; float:left; min-width:100%;">@money(isset($show->guncelbakiye) ? floatval($show->guncelbakiye) : 0) ₺</div></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="7" class="text-center">Ekstre kaydı bulunamadı.</td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        <!-- End Page-content -->

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <p><script>document.write(new Date().getFullYear())</script> © GEMAŞ b2b</p>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-right d-none d-sm-block">
                            B2B Satış Sistemi <a href="https://www.salter-group.com" target="_blank">GEMAŞ b2b</a> Tarafından Yapılmıştır.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

@endsection
