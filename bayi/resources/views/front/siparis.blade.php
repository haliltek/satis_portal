@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')


    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->

            <!-- end page title -->

            <div class="row"></div>


            <div class="row sepet-icerik m-t-30">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body" style="position:relative;">
                            <h4 class="card-title mb-4 s-body-title">Siparişlerim </h4>
                            <div class="row">
                                <div class="col-lg-12" style="position:relative;">
                                    <div class="siparis-modal">
                                        <h5 style="padding:15px"><b></b> Ürün Listesi <i class="fa fa-times fright closedetail"></i></h5>

                                        <table id="datatable-buttons2" class="table table-striped table-bordered dt-responsive nowrap siparisurunliste" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                            <tr height="50">
                                                <th width="10%">No</th>

                                                <th width="40%">Ürün Adı</th>
                                                <th width="10%">Adet</th>
                                                <th width="12%">Birim Fiyatı</th>
                                                <th width="8%">İskonto</th>
                                                <th width="10%">Kdv</th>
                                                <th width="12%">Fiyat</th>

                                            </tr>
                                            </thead>

                                            <tbody>
                                            </tbody>
                                        </table>
                                        <div class="col-lg-12 fright m-b-30">
                                            <div class="col-lg-2 fleft">
                                                <div class="col-lg-12 fleft"><b>Brüt Toplam</b></div>
                                                <div class="col-lg-12 fleft" id="brut"></div>
                                            </div>
                                            <div class="col-lg-2 fleft">
                                                <div class="col-lg-12 fleft"><b>İndirim</b>(<b id="iskonto-tutar"></b>)</div>
                                                <div class="col-lg-12 fleft" id="indirim"></div>
                                            </div>
                                            <div class="col-lg-2 fleft">
                                                <div class="col-lg-12 fleft"><b>Ara Toplam</b></div>
                                                <div class="col-lg-12 fleft" id="ara-toplam"></div>
                                            </div>
                                            <div class="col-lg-2 fleft">
                                                <div class="col-lg-12 fleft"><b>Kdv Toplam</b></div>
                                                <div class="col-lg-12 fleft" id="kdv"></div>
                                            </div>
                                            <div class="col-lg-2 fleft">
                                                <div class="col-lg-12 fleft"><b>Sipariş Toplamı</b></div>
                                                <div class="col-lg-12 fleft" id="siparis-toplam"></div>
                                            </div>
                                            <div class="col-lg-2 fleft" style="border-right:0px;">
                                                <button class="btn btn-danger closedetail fright m-r-10">Kapat</button>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">

                                            <a class="detaylink fright m-b-20" href="" style="display:none;"><button class="btn btn-info">Sipariş Detayına Git</button></a>

                                        </div>

                                    </div>
                                    <div class="bildirim-modal">
                                        <div class="col-lg-12 fleft m-t-15 m-b-10">
                                            <div class="col-lg-6">
                                                <h5>Ödeme Bildirim Formu</h5>
                                            </div>

                                        </div>
                                        <div class="col-lg-12 fleft m-b-10">
                                            <div class="col-lg-6 fleft form-group">
                                                <label>Ödeme Kanalı</label>
                                                <input type="text" class="form-control odeme-kanal" value="Havale/Eft" disabled>
                                            </div>
                                            <div class="col-lg-6 fleft form-group">
                                                <label>Ödeme Yapılan Hesap</label>
                                                <select class="form-control hesapbilgi">
                                                    @foreach($hesaplar as $hesap)
                                                        <option value="{{$hesap->id}}">{{$hesap->banka_adi}}({{$hesap->sube}})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-6 fleft form-group">
                                                <label>Ödenen Tutar</label>
                                                <input type="text" class="form-control odenen-miktar" />
                                            </div>
                                            <div class="col-lg-6 fleft form-group">
                                                <label>Gönderen</label>
                                                <input type="text" class="form-control gonderen" />
                                            </div>
                                            <div class="col-lg-12 fleft m-b-15">
                                                <button class="btn btn-success fright odemeKaydet">Gönder</button>
                                                <button class="btn btn-danger fright close-bildirimmodal m-r-5">Kapat</button>
                                            </div>
                                        </div>
                                    </div>

                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th width="4%"><i class="fa fa-info"></i></th>
                                            <th width="4%">Sipariş</th>
                                            <th width="12%">Tarih</th>
                                            <th width="16%">Ödeme</th>
                                            <th width="10%">Tutar</th>
                                            <th width="10%">İskonto</th>
                                            <th width="10%">Kdv</th>
                                            <th width="8%">Genel Toplam</th>
                                            <th width="10%">Kargo Durum</th>
                                            <th width="3%">İşlem</th>
                                        </tr>
                                        </thead>

                                        <tbody>

                                        @foreach($siparisler as $show)

                                            @if($show->sdurum == '1' and $show->kargo_durum == '')
                                                @php
                                                    $durum = 'Ödeme Bekliyor'; $color = '#fd971a';
                                                    $display = 'block';
                                                    $editbutton = 'block';
                                                @endphp
                                            @elseif($show->kargo_durum == '2')
                                                @php
                                                    $durum = 'Kargoya Verildi';
                                                    $color = '#4ead05'; $display = 'none';
                                                    $editbutton = 'none';
                                                @endphp
                                            @elseif($show->kargo_durum == '1' and $show->sdurum == '2')
                                                @php
                                                    $durum = 'Hazırlanıyor';
                                                    $color = '#00a0c5'; $display = 'none';
                                                    $editbutton = 'none';
                                                @endphp
                                            @elseif($show->kargo_durum == '' and $show->sdurum == '2')
                                                @php
                                                    $durum = 'Ödeme Yapıldı';
                                                    $color = '#4ead05'; $display = 'none';
                                                    $editbutton = 'none';
                                                @endphp
                                            @elseif($show->kargo_durum == '1' and $show->sdurum == '1')
                                                @php
                                                    $durum = 'Ödeme Bekliyor'; $color = '#fd971a'; $display = 'block'; $editbutton = 'block';
                                                @endphp
                                            @else
                                                @php
                                                    $durum = 'Tamamlanmadı';
                                                    $color = '#f00';
                                                    $display = 'none';
                                                    $editbutton = 'block';
                                                @endphp
                                            @endif

                                            <tr class="{{$show->sip_id}}">
                                                <td><i class="mdi mdi-plus-box showdetail" pid="{{$show->sip_id}}"></td>
                                                <td>{{$show->sip_id}}</td>
                                                <td>{{dateformat($show->tarih)}} </td>
                                                <td>{{$show->odeme_adi}}</td>

                                                <td><div class="align-right">@money3($show->tutar) €</div></td>
                                                <td>%{{$show->iskonto}}</td>
                                                <td><div class="align-right">@money($show->kdv) €</div></td>
                                                <td><div class="align-right">{{para($show->geneltoplam)}} €</div></td>
                                                <td><span style="color:{{$color}};">{{$durum}}</span><br>{{$show->name}}</td>
                                                <td>

                                                    <a href="siparis/{{$show->sip_id}}" title="İncele" class="m-r-5 fleft" style="display:none;"><i class="fa fa-eye"></i></a>
                                                    <a class="btn btn-primary fright" href="makbuz/{{$show->sip_id}}" title="Makbuz"class="m-r-5 fleft"><i class="fa fa-sticky-note"></i> Makbuz</a>
                                                    @if($show->sdurum < '1')

                                                    <a class="btn btn-danger fright m-r-5"  title="Siparişi Sil" class="m-r-5" id="sa-warning2" onclick="delorder({{$show->sip_id}})" style="color:#fff;"><i class="fa fa-trash"></i> Sil </a>
                                                    <a class="btn btn-success fright m-r-5" href="sepetonay/{{$show->sip_id}}" title="Siparişi Tamamla" class="m-r-5 fleft"><i class="fa fa-toggle-right"></i> Siparişi Tamamla</a>
                                                    @endif
                                                    @if($show->kargo_durum == '2')
                                                    <a class="btn btn-primary fright m-r-5" target="_blank" href="{{$show->url}}{{$show->kargotakip}}"  title="Kargo Takip" class="m-r-5 fleft"><i class="fa fa-truck"></i> Kargo Takip</a>
                                                    @endif
                                                    <a class="btn btn-primary fright m-r-5" onclick="odemebildir({{$show->sip_id}})" title="Ödeme Bildir" class="m-r-5 fleft odeme-bildir" style="display:{{$display}}"><i class="fa fa-check-square-o"></i> Ödeme Bildir</a>
                                                    <button class="btn btn-primary editorder fright m-r-5" style="display:{{$editbutton}}" sid="{{$show->sip_id}}"><i class="fa fa-edit"></i></button>
                                                </td>
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
