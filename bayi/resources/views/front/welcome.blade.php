@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">

                @component('components.filtre', compact('markalar', 'kategoriler'))

                @endcomponent
                <div class="adresmodal-outer">

                    <div class="adresmodal">
                        <div class="close-adresmodal"><i class="fa fa-times"></i></div>
                        <div class="col-lg-12 align-center fleft m-t-20">
                            <h4 style="color:#f00; width:100%; text-align:center" class="fleft m-t-10">Kayıtlı adres bulunamadı!</h4>
                            <h5 style="color:#000; width:100%; text-align:center" class="fleft m-t-10">Fatura/Teslimat adres bilgilerini ekleyin.</h5>
                        </div>
                        <div class="col-lg-12 m-t-30 fleft" style="text-align: center;">
                            <button class="btn btn-info close-adresmodal2"><b>Daha Sonra Ekle</b></button>
                            <a href="{{ url('/ayarlar') }}"><button class="btn btn-success" style="border-color:#1cb565;"><b>Adres Ekle</b></button></a>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <div class="tab-box">
                            <div class="tab active" v-tab="tab1" table="yeniurun">
                                Yeni Ürünler
                            </div>
                            <div class="tab" v-tab="tab2" table="anasayfa_urun">
                                Arama Sonucu
                            </div>
                            <div class="tab" v-tab="tab3" table="kampanya" id="kampanya">
                                <span class="outlet-icon">🔥</span> Outlet Ürünler
                            </div>

                            <div class="tab" v-tab="tab4" >
                                Kampanya Paket
                            </div>
                            <div class="tab" v-tab="tab5">
                                Satın Aldıklarım
                            </div>

                        </div>

                        <div class="page-title-right">
                            <div class="custom-control custom-switch mb-2 fleft" dir="ltr" style="margin-right:30px; display:none">
                                <input type="checkbox" class="custom-control-input" id="customSwitch1" checked="">
                                <label class="custom-control-label" for="customSwitch1">Stoktakiler</label>
                            </div>
                            <div class="custom-control custom-switch mb-2 fleft text-area12" dir="ltr" style="position:absolute; margin-top:-33px; color: #ff0100;">
                                Peşin alımlarda + %10 iskonto hakkınız bulunmaktadır.
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
            <div class="product-detailmodal">
                <div class="main-cont" style="padding:15px;">
                    <h5 class="col-lg-12"> </h5>
                    <i class="fa fa-times closeicon close-detailmodal"></i>
                    <div class="col-lg-7 p-0 fleft m-t-5">
                        <div class="product-image">
                            <img id="d-resim" src="" />
                        </div>
                        <div class="col-lg-12 fleft m-b-20 m-t-20">
                            <label>Uyumlu Modeller</label>
                            <div class="col-lg-12 p-0" id="d-marka"></div>
                        </div>
                        <div class="col-lg-12 fleft m-b-20 m-t-20">
                            <label>Açıklama</label>
                            <div class="col-lg-12 p-0" id="d-aciklama"></div>
                        </div>
                    </div>
                    <div class="col-lg-5 fleft m-t-5 r-panel">
                        <h6>Ürün Özellikleri</h6>
                        <div class="col-lg-12 form-group fleft p-l-0">
                            <div class="col-lg-5 fleft p-l-0 bold">Fiyat</div>
                            <div class="col-lg-7 fleft" id="d-fiyat"></div>
                        </div>
                        <div class="col-lg-12 form-group fleft p-l-0">
                            <div class="col-lg-5 fleft p-l-0 bold">Stok Adedi</div>
                            <div class="col-lg-7 fleft" id="d-stok"></div>
                        </div>
                        <div class="col-lg-12 form-group fleft p-l-0">
                            <div class="col-lg-5 fleft p-l-0 bold" id="d-tanimad"></div>
                            <div class="col-lg-7 fleft" id="d-tanimdeger"></div>
                        </div>
                        <div class="col-lg-12 form-group fleft p-l-0">
                            <div class="col-lg-5 fleft p-l-0 bold">Kampanya</div>
                            <div class="col-lg-7 fleft" id="d-oem"></div>
                        </div>
                        <div class="col-lg-12 fleft m-t-20">
                            <div class="col-lg-12 fright m-b-10">
                                <button class="fright btn btn-primary esdeger-button" id="">Kampanya Paket</button>
                            </div>
                            <div class="col-lg-12 fright">
                                <div class="col-lg-7 fleft p-0">
                                    <input type="number" class="form-control fright d-input" id="" value="0" min="0"/>
                                </div>
                                <div class="col-lg-5 fleft p-r-0 ">
                                    <button class="btn btn-success fright ">Sepete Ekle</button>
                                </div>

                            </div>

                            <div class="col-lg-1 fright" style="display:none;">
                                <button class="btn btn-danger close-detailmodal">Kapat</button>
                            </div>
                        </div>
                    </div>


                </div>
            </div>


            <div class="row list-table">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">

                            <h4 class="card-title mb-4 s-body-title">Ürün Sonuçları</h4>


                            <div>
                                <div class="row">

                                    <div class="col-lg-12">

                                        <div class="form-group col-lg-12 tab-c p-0" id="tab1">
                                            <table id="yeniurun" class="yeniurun table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>

                                                    <th width="15%">No</th>
                                                    <th width="40%">Ürün Adı</th>
                                                    <th width="10%">Fiyat</th>
                                                    <th width="15%">Kampanya</th>
                                                    <th width="10%">Adet</th>
                                                    <th width="15%">İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <tr>

                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="form-group col-lg-12 tab-c p-0" id="tab2">
                                            <table id="filtrele" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>

                                                    <th width="15%">No</th>
                                                    <th width="35%">Ürün Adı</th>
                                                    <th width="10%">Fiyat</th>
                                                    <th width="15%">Kampanya</th>
                                                    <th width="10%">Adet</th>
                                                    <th width="15%">İşlem</th>

                                                </tr>
                                                </thead>

                                                <tbody>

                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="form-group col-lg-12 tab-c p-0" id="tab3">
                                            <table id="kampanya2" class="kampanya table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>
                                                    <th width="15%">No</th>
                                                    <th width="40%">Ürün Adı</th>
                                                    <th width="10%">Fiyat</th>
                                                    <th width="15%">Kampanya</th>
                                                    <th width="10%">Adet</th>
                                                    <th width="15%">İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <tr>

                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="form-group col-lg-12 tab-c p-0" id="tab4">
                                            <table id="esdeger2" class="esdeger table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>

                                                    <th width="15%">No</th>
                                                    <th width="35%">Ürün Adı</th>
                                                    <th width="10%">Fiyat</th>
                                                    <th width="15%">Kampanya</th>
                                                    <th width="10%">Adet</th>
                                                    <th width="15%">İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <tr>

                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="form-group col-lg-12 tab-c p-0" id="tab5">
                                            <div class="siparis-modal">
                                                <h5 style="padding:15px"><b></b> Ürün Listesi <i class="fa fa-times fright closedetail"></i></h5>

                                                <table id="datatable-buttons2" class="table table-striped table-bordered dt-responsive nowrap siparisurunliste" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                    <thead>
                                                    <tr height="50">
                                                        <th width="10%">No</th>

                                                        <th width="40%">Ürün No</th>
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
                                                <div class="col-lg-5 fright m-b-30">
                                                    <div class="col-lg-12 fright">
                                                        <div class="col-lg-6 fleft align-right"><b>Brüt Toplam</b></div>
                                                        <div class="col-lg-6 fleft align-right" id="brut"></div>
                                                    </div>
                                                    <div class="col-lg-12 fright ">
                                                        <div class="col-lg-6 fleft align-right"><b>İndirim</b>(<b id="iskonto-tutar"></b>)</div>
                                                        <div class="col-lg-6 fleft align-right" id="indirim"></div>
                                                    </div>
                                                    <div class="col-lg-12 fright">
                                                        <div class="col-lg-6 fleft align-right"><b>Ara Toplam</b></div>
                                                        <div class="col-lg-6 fleft align-right" id="ara-toplam"></div>
                                                    </div>
                                                    <div class="col-lg-12 fright">
                                                        <div class="col-lg-6 fleft align-right"><b>Kdv Toplam</b></div>
                                                        <div class="col-lg-6 fleft align-right" id="kdv"></div>
                                                    </div>
                                                    <div class="col-lg-12 fright">
                                                        <div class="col-lg-6 fleft align-right"><b>Sipariş Toplamı</b></div>
                                                        <div class="col-lg-6 fleft align-right" id="siparis-toplam"></div>
                                                    </div>
                                                    <div class="col-lg-12 fright m-t-10" style="border-right:0px;">
                                                        <button class="btn btn-danger closedetail fright m-r-10">Kapat</button>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">

                                                    <a class="detaylink fright m-b-20" href="" style="display:none;"><button class="btn btn-info">Sipariş Detayına Git</button></a>

                                                </div>

                                            </div>
                                            <table id="satinaldiklarim" class="satinaldiklarim table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>

                                                    <th width="15%">No</th>
                                                    <th width="40%">Ürün Adı</th>
                                                    <th width="15%">Genel Toplam</th>
                                                    <th width="15%">Tarih</th>
                                                    <th width="15%">Adet</th>
                                                    <th width="15%">İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <tr>

                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>

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


    </div>

    <style>
        /* Outlet Ürünler sekmesi için yanıp sönen ikon animasyonu */
        .outlet-icon {
            display: inline-block;
            animation: outletBlink 1.5s infinite;
            margin-right: 5px;
            font-size: 16px;
        }
        
        @keyframes outletBlink {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.3;
                transform: scale(0.9);
            }
        }
        
        /* Aktif sekmede ikon animasyonunu durdur */
        .tab.active .outlet-icon {
            animation: none;
            opacity: 1;
        }
    </style>

@endsection
