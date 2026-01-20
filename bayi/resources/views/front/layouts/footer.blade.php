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
<!-- end main content-->

</div>
<!-- END layout-wrapper -->

</div>
<!-- end container-fluid -->

<!-- Right Sidebar -->
<div class="right-bar">
    <div data-simplebar class="h-100">
        <div class="rightbar-title px-3 py-4">
            <a href="javascript:void(0);" class="right-bar-toggle float-right">
                <i class="mdi mdi-close noti-icon"></i>
            </a>
            <h5 class="m-0">Ayarlar</h5>
        </div>

        <!-- Settings -->
        <hr class="mt-0" />
        <h6 class="text-center mb-0">Mod Seçiniz</h6>

        <div class="p-4">
            <div class="mb-2">
                <img src="{{ asset('assets/front/assets/images/layouts/layout-1.jpg') }}" class="img-fluid img-thumbnail" alt="">
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input theme-choice" id="light-mode-switch" checked />
                <label class="custom-control-label" for="light-mode-switch">Aydınlık Mod</label>
            </div>

            <div class="mb-2">
                <img src="{{ asset('assets/front/assets/images/layouts/layout-2.jpg') }}" class="img-fluid img-thumbnail" alt="">
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input theme-choice" id="dark-mode-switch" data-bsStyle="assets/css/bootstrap-dark.min.css" data-appStyle="assets/css/app-dark.min.css" />
                <label class="custom-control-label" for="dark-mode-switch">Karanlık Mod</label>
            </div>


        </div>

    </div>
    <!-- end slimscroll-menu-->
</div>
<!-- /Right-bar -->
<div class="dark-screen"></div>
<div class="f-bar">
    <div class="f-image-area">
        <div class="inner" style="position:relative;">
            <div class="close-f-image">
                <i class="fa fa-times "></i>
            </div>
            <div class="f-fullscreen">
                <i class="fa fa-expand"></i>
            </div>

            <div class="image" fs="0">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">

                    </ol>
                    <div class="carousel-inner f-image" role="listbox">

                    </div>
                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="f-menu-bar">
        <div class="f-menu-top">
            <div class="inner">
                <div class="f-arrow" stat="1">
                    <i class="fa fa-arrow-circle-down" aria-hidden="true"></i> <span>Kapat</span>
                </div>
            </div>
        </div>
        <div class="f-menu">
            <div class="inner">
                <div class="f-image-box fleft">
                    <img class="f-resim" src="" />
                </div>
                <div class="f-infobox">
                    <div class="col-lg-6 fleft f-kod">Ürün Kodu : <b></b></div>
                    <div class="col-lg-6 fleft f-stok">Stok : <b></b></div>
                    <div class="col-lg-12 fleft f-ad"></div>
                </div>
                <div class="op">
                    <button class="btn btn-success fright">Sepete Ekle</button>
                    <div class="f-number-box">
                        <div class="col-lg-3 fleft p-0">
                            <button class="btn btn-light adet-eksi" style="border-radius: 0px; width: 35px; margin-left: -4px; z-index: 9; position: relative;">-</button>
                        </div>
                        <div class="col-lg-6 fleft p-0">
                            <input class="form-control f-adet f-input" type="number" min="1"  style="border-radius:0px; background-color: #f6f6f6; border:0px;" value="1"/>
                        </div>
                        <div class="col-lg-3 fleft p-0">
                            <button class="btn btn-light adet-arti" style="border-radius:0px; width:35px; margin-left:-1px;">+</button>
                        </div>


                    </div>
                    <button class="btn btn-info m-r-20 f-esdeger-button" >Kampanya Paket</button>
                </div>
                <div class="f-mn">
                    <li class="fiyat" style="background-color:#bf0606; margin-right:25px">Ürün Fiyatı<br><b class="f-fiyat" fiyat=""></b></li>
                    <li style="position:relative; cursor:pointer;" class="obt" durum="0">Ürün<br>Özellikleri
                    <div class="f-ozellik">
                        <div class="o-row f-oem">
                            <h5>Kampanya</h5>
                            <p></p>
                        </div>
                        <div class="o-row f-oz">
                            <h5>Ürün Özellikleri</h5>
                            <p></p>
                        </div>
                    </div>
                    </li>
                    <li>Uyumlu<br>Modeller</li>

                </div>
            </div>
        </div>
    </div>
</div>
<!-- Right bar overlay-->
<div class="rightbar-overlay"></div>
<div class="feedClick" >
    Bize Yazın
</div>
<div class="feedback" >
    <div class="col-lg-12">
        <div class="col-lg-12 fleft m-b-10">
            <h4 style="font-weight: 600;">Bize Yazın</h4>
        </div>
        <div class="col-lg-12 fleft">
            <div class="col-lg-12 fleft form-group">
                <input type="text" class="form-control" value="{{Auth::user()->name}}" id="f-isim"/>
            </div>
            <div class="col-lg-12 fleft form-group">
                <label>Sizinle nasıl iletişime geçelim ?</label>
                <select class="form-control" id="f-secenek">
                    <option>Telefon</option>
                    <option>E-posta</option>
                    <option>SMS</option>
                </select>
            </div>
            <div class="col-lg-12 fleft form-group">
                <label>Konu</label>
                <select class="form-control" id="f-konu">
                    <option>Öneri</option>
                    <option>Şikayet</option>
                    <option>Bildirim</option>
                </select>
            </div>
            <div class="col-lg-12 fleft form-group">
                <label>Mesajınız ?</label>
                <textarea cols="3" class="form-control" id="f-mesaj"></textarea>
            </div>
            <div class="col-lg-12 fleft form-group">
                <button class="btn btn-success fright send-feedback">Gönder</button>
                <button class="btn btn-danger fright close-feedback m-r-5">Kapat</button>
            </div>
        </div>
    </div>
</div>



<div class="contract-modal">
    <div class="contract-inner">
        <div class="contract-content fleft">
            <div class="contract-box fleft" onclick="getcontract(11)">
                <p><b>Mesafeli Satış Sözleşmesi</b></p>
                <p>Sözleşmeyi Oku</p>
            </div>
            <div class="contract-box fleft" onclick="getcontract(41)">
                <p><b>Gizlilik ve Güvenlik</b></p>
                <p>Sözleşmeyi Oku</p>
            </div>
            <div class="contract-box fleft" onclick="getcontract(1)">
                <p><b>Üyelik Sözleşmesi</b></p>
                <p>Sözleşmeyi Oku</p>
            </div>
            <div class="contract-box fleft" onclick="getcontract(51)">
                <p><b>KVKK Aydınlatma Metni</b></p>
                <p>Sözleşmeyi Oku</p>
            </div>
        </div>
        <div class="contract-text">

        </div>
        <div class="col-lg-12 fleft" style="padding-top:25px;">
            <button class="btn btn-success fright m-r-5 access-contract">Sözleşmeleri Onayla</button>
            <button class="btn btn-danger fright m-r-5 backcontract" style="display:none;">Geri</button>
            <button class="btn btn-danger fright m-r-5 closecontract">Kapat</button>
        </div>
    </div>

</div>



<div class="paymodal">
    <div class="col-lg-12 p-0 paymodal-inner">
        <div class="col-lg-12 fleft m-t-20">
            <div class="col-lg-12 fleft">
                <h4>Ödeme Yap / Bakiye Yükle</h4>
            </div>
            <div class="col-lg-12 fleft m-t-20">
                <label class="col-lg-12 p-0">Ödeme tutarı giriniz</label>
                <div class="col-lg-6 p-0 fleft">
                    <input type="text" class="form-control odemetutar">
                </div>
                <div class="col-lg-2 fleft">
                    <button class="btn btn-success odemeyap">Ödeme Yap</button>
                </div>
            </div>
        </div>

        <div class="col-lg-12 fleft m-t-30">
            <div class="col-lg-12 fleft m-b-10">
                <h4>Sipariş Ödemesi Yap</h4>
            </div>
            <div class="col-lg-12 fleft">
                <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                    <tr>
                        <th width="4%">Sipariş</th>
                        <th width="12%">Tarih</th>
                        <th width="8%">Genel Toplam</th>
                        <th width="10%">Durum</th>
                        <th width="3%">İşlem</th>
                    </tr>
                    </thead>

                    <tbody class="odeme-bekleyen">

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button class="btn btn-danger paymodal-close">Kapat</button>
</div>
<div class="pos-area"></div>
