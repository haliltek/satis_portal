@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Ürün Düzenle')
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
                        <h4 class="page-title mb-0 font-size-18">Ürün Ekle</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{url('panel/urunler')}}">Ürün</a></li>
                                <li class="breadcrumb-item active">Ürün Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">Ürün Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/urunler')}}">Geri</a>
                        </div>
                        <div class="card-body">


                            <div id="" class="">
                                <div class="urun-tab row">
                                  <ul>
                                      <a href="{{url('panel/urunduzenle/'.$id)}}"> <li style="margin-left:-27px;" class=" urun-tab-li tabaktif"><i class="mdi mdi-note-text"></i> Ürün </li></a>
                                      <a href="{{url('panel/urunmarkamodel/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-link"></i> Marka Model </li></a>
                                      <a href="{{url('panel/urunfiyat/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-currency-try"></i> Fiyat </li></a>
                                      <a href="{{url('panel/urunoem/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-loupe"></i> Oem </li></a>
                                      <a href="{{url('panel/uruntanim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-checkbox-marked"></i> Tanımlar </li></a>
                                      <a href="{{url('panel/urunresim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-file-image"></i> Resim </li></a>
                                  </ul>
                                </div>

                                <fieldset>
                                    <form method="post" action="{{url('panel/urunduzenlepost/'.$id)}}">
                                        @csrf

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group row">
                                                    <label for="txtTelephoneBilling" class="col-lg-1 col-form-label">Kategori</label>
                                                    <div class="col-lg-3">
                                                        <select id="katsec" name="ust" class="form-control" >
                                                            <option value="0">Kategori Seçiniz</option>
                                                            @foreach($kategoriler as $kategori)
                                                                <option value="{{$kategori->id}}" @if($ustkat==$kategori->id)selected @endif>{{$kategori->kategori_adi}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-3" id="altkat" style="display: none">
                                                        <select id="altsec" name="alt" class="form-control altekle" >

                                                        </select>
                                                    </div>


                                                    <div class="col-lg-3" id="altkat2" style="display: none">
                                                        <select id="altsec2" name="alt" class="form-control altekle" >

                                                        </select>
                                                    </div>

                                                    <input type="hidden" id="altid" name="altid" value="{{$altkat}}">

                                                </div>
                                            </div>

                                        </div>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtFirstNameBilling" class="col-lg-2 col-form-label">Ürün adı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtFirstNameBilling" name="urunadi" value="{{$urunadi}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtLastNameBilling" class="col-lg-2 col-form-label">Üretici Adı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtLastNameBilling" name="ureticiadi" value="{{$uretici_ad}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCompanyBilling" class="col-lg-2 col-form-label">Üretici No</label>
                                                <div class="col-lg-9">
                                                    <select id="ureticino" name="ureticino" class="form-control">
                                                        <option value="{{$ureticino}}">{{$uretici_ad}}</option>
                                                        @foreach($tedarikci as $add)
                                                            <option value="{{$add->id}}">{{$add->tedarikci}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCompanyBilling" class="col-lg-2 col-form-label">Stok</label>
                                                <div class="col-lg-9">
                                                    <input id="ureticino" name="stok" type="text" value="{{$stok}}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-2 col-form-label">No</label>
                                                <div class="col-lg-8">
                                                    <input id="acarno" name="urunno" type="text" value="{{$urunkodu}}" class="form-control">
                                                </div>
                                                <div class="col-lg-1">
                                                    <button type="button" class="btn btn-primary copybtn" title="Üretici No Kopyala"><i class="fa fa-copy" ></i></button>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-2 col-form-label">Temin Süresi</label>
                                                <div class="col-lg-8">
                                                    <input id="acarno" name="termin" type="text" value="{{$termin}}" class="form-control">
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-2 col-form-label">Stoksuz Satış</label>
                                                <div class="col-lg-8">
                                                    <select name="stoksuz" class="form-control">
                                                        <option value="0" @if($stokalt==0) selected @endif>Hayır</option>
                                                        <option value="1" @if($stokalt==1) selected @endif>Evet</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-2 col-form-label">Vergi</label>
                                                <div class="col-lg-8">
                                                    <select name="vergi" class="form-control">

                                                        @foreach($vergiler as $vergis)
                                                            <option value="{{$vergis->id}}" @if($vergi==$vergis->id) selected @endif>{{$vergis->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-2 col-form-label">Birim</label>
                                                <div class="col-lg-8">
                                                    <select name="birim" class="form-control">

                                                        @foreach($birimler as $birim)
                                                            <option value="{{$birim->id}}" @if($birimi==$birim->id) selected @endif>{{$birim->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>





                                    <div class="row" style="margin-bottom:20px;">
                                        <div class="col-lg-12">

                                            <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">Açıklama</label>
                                            <textarea id="elm1" name="aciklama">{{$aciklama}}</textarea>


                                        </div>
                                        <!-- end col -->
                                    </div>
                                        <button class="btn btn-success float-right" type="submit">Kaydet ve İlerle</button>
                                    </form>
                                </fieldset>


                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

        </div>
        <!-- End Page-content -->
@endsection
