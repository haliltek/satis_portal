@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')


    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                @component('components.filtre', compact('markalar', 'kategoriler'))

                @endcomponent





            </div>
            <!-- end page title -->




            <div class="row sepet-icerik">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Sepetim </h4>
                            <div class="row">
                                <div class="col-lg-12">
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th width="12%">No</th>
                                            <th width="30%">Ürün Adı</th>
                                            <th width="14%">Adet</th>
                                            <th width="15%">Birim Fiyatı</th>
                                            <th width="15%">Fiyat</th>
                                            <th>İşlem</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @php
                                            $toplam = isset($toplam) ? $toplam + $show->fiyat : 0;
                                        @endphp
                                        @foreach($sepet as $show)
                                            @php
                                                // Kampanya paket ise gösterilecek adeti kullan, değilse normal adeti
                                                $gosterilenAdet = isset($show->gosterilen_adet) ? $show->gosterilen_adet : $show->adet;
                                                // Fiyat hesaplama: kampanya paket ise gösterilecek adet * birim fiyat
                                                $fiyatHesaplama = floatval($show->fiyat ?? 0) * $gosterilenAdet;
                                                $toplam += $fiyatHesaplama;
                                            @endphp

                                        <tr>
                                            <td>{{$show->urun_kodu}}
                                                @if(!empty($show->kampanya_tipi))
                                                    <br><small class="text-info">({{$show->kampanya_tipi}})</small>
                                                @endif
                                            </td>

                                            <td>{{$show->urun_adi}}</td>
                                            <td>
                                               <input type="number"  class="sepetadet" id="sepetadet" u-id="{{$show->id}}" min="1" value="{{$gosterilenAdet}}"/>
                                               @if(!empty($show->kampanya_tipi) && $show->adet != $gosterilenAdet)
                                                   <br><small class="text-muted">Toplam: {{$show->adet}} adet</small>
                                               @endif
                                            </td>
                                            <td id="birim{{$show->id}}">{{number_format(floatval($show->fiyat ?? 0), 2, ',', '.')}} €</td>
                                            <td id="fiyat{{$show->id}}">{{number_format($fiyatHesaplama, 2, ',', '.')}} €</td>
                                            <td><button class="btn btn-danger delbasket-item" sid="{{$show->id}}"><i class="fa fa-trash" ></i> Sil</button></td>
                                        </tr>

                                        @endforeach
                                        <tr style="border:0px;">
                                            <td colspan="5" align="right">
                                                <p>Brüt Toplam :</p>
                                                <p>İndirim(%<b><span id="iskonto-yuzde">{{$iskonto}}</span></b>) :</p>
                                                <p>Ara Toplam :</p>
                                                <p id="pesin-iskonto-label" style="display:none;">Peşin Ödeme İskontosu (%10) :</p>
                                                <p id="pesin-aratoplam-label" style="display:none;">Ara Toplam (Peşin) :</p>
                                                <p>Kdv(%20) :</p>
                                                <p><b>Sepet Toplamı :</b></p>
                                            </td>
                                            <td colspan="2">
                                                <p id="t-toplam">@money($toplam) €</p>
                                                @php($iskonto_oran = $toplam / 100 * $iskonto)
                                                @php($ara_toplam = $toplam - $iskonto_oran)
                                                @php($pesin_iskonto = 0)
                                                @php($pesin_ara_toplam = $ara_toplam)
                                                @php($kdv = $pesin_ara_toplam / 100 * 20)
                                                @php($genel_toplam = $pesin_ara_toplam + $kdv)
                                                <p id="t-indirim">@money2($iskonto_oran) €</p>
                                                <p id="t-aratoplam">@money2($ara_toplam) €</p>
                                                <p id="t-pesin-iskonto" style="display:none;">@money2($pesin_iskonto) €</p>
                                                <p id="t-pesin-aratoplam" style="display:none;">@money2($pesin_ara_toplam) €</p>
                                                <p id="t-kdv">@money2($kdv) €</p>
                                                <p id="t-geneltoplam"><b>@money2($genel_toplam) €</b></p>
                                                <input type="hidden" id="base-iskonto" value="{{$iskonto}}">
                                                <input type="hidden" id="base-toplam" value="{{$toplam}}">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-lg-12 m-t-20">
                                    <div class="col-lg-4 fleft p-0">
                                        <input type="hidden" class="kargo" value="1">
                                        <input type="hidden" class="bakiye_limit" value="{{$bakiye_limit}}">
                                        <input type="hidden" class="genel_toplam" value="{{$genel_toplam}}">
                                    </div>
                                    <div class="col-lg-4 fleft p-0">
                                        <select class="form-control" id="odeme_tipi">
                                            <option value="0">Ödeme Tipi Seçiniz</option>
                                            @if($odeme_tipi->count() > 0)
                                                @foreach($odeme_tipi as $show)
                                                <option value="{{$show->id}}">{{$show->odeme_adi}}</option>
                                                @endforeach
                                            @else
                                                <option value="1">Havale/EFT</option>
                                                <option value="2">Açık Hesap</option>
                                                <option value="3">Nakit</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-lg-4 fright">
                                        @if($genel_toplam == '0')
                                            @php($butondurum = 'disabled')
                                        @else
                                            @php($butondurum = '')
                                        @endif
                                        @if($adres != '')
                                        <button class="btn btn-success waves-effect waves-light sepetonay" id="sepetonay-btn" {{$butondurum}}><i class="bx bx-check-double font-size-16 align-middle mr-2"></i>Siparişi Onayla</button>
                                         @else
                                        <a href="{{ url('/ayarlar') }}"><button class="btn btn-success"><i class="bx bx-check-double font-size-16 align-middle mr-2"></i>Adres Ekle</button></a>
                                            @endif
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
