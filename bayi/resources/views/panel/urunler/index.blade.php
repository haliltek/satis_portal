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
                    <h4 class="page-title mb-0 font-size-18">Tüm Ürünler</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Ürün Yönetimi</a></li>
                            <li class="breadcrumb-item active">Tüm Ürünler</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="col-lg-12 mb-2 align-right p-0 fleft">
            <a class="btn btn-success mt-1 mr-1 mb-4 fright " href="{{url('panel/urunekle')}}">
                Yeni Ürün Ekleyin
            </a>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card collapse hidden" id="aramakutusu">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Tüm Ürünlerde Arayın</h4>
                        <form class="outer-repeater">
                            <div data-repeater-list="outer-group" class="outer">
                                <div data-repeater-item class="outer">
                                    <div class="form-group row mb-4">
                                        <label for="taskname" class="col-form-label col-lg-2">Ürün </label>
                                        <div class="col-lg-10">
                                            <input id="taskname" name="taskname" type="text" class="form-control" placeholder="Oem No, Parça No, Ürün Adı...">
                                        </div>
                                    </div>

                                    <div class="form-group row mb-4">
                                        <div class="col-md-6">
                                            <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                <option value="">--Kategori Seçiniz--</option>
                                                <option value="34">Kaporta</option>
                                                <option value="06">Motor</option>
                                                <option value="35">Elektrik</option>
                                                <option value="20">Balata</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                <option value="">--Alt Kategori Seçiniz--</option>
                                                <option value="34">Kapı</option>
                                                <option value="06">Dişli</option>
                                                <option value="35">Fren Balatası</option>
                                                <option value="20">Diğer</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-4">

                                        <div class="col-md-4">
                                            <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                <option value="">--Marka Seçiniz--</option>
                                                <option value="34">BMW</option>
                                                <option value="06">Audi</option>
                                                <option value="35">Ford</option>
                                                <option value="20">Volvo</option>
                                            </select>
                                        </div>


                                        <div class="col-md-4">
                                            <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                <option value="">--Model Seçiniz--</option>
                                                <option value="34">Focus</option>
                                                <option value="06">3.16</option>
                                                <option value="35">A5</option>
                                                <option value="20">Fiesta</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                <option value="">--Yıl Seçiniz--</option>
                                                <option value="34">2020</option>
                                                <option value="06">2019</option>
                                                <option value="35">2018</option>
                                                <option value="20">2017</option>
                                            </select>
                                        </div>
                                    </div>



                                </div>
                            </div>
                        </form>
                        <div class="row justify-content-end text-right">
                            <div class="col-lg-12" style="display: none;">
                                <button type="submit" class="btn btn-primary">Ürünlerde Ara</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card">
                    <div class="card-body">

                        <h4 class="card-title">Tüm Ürünleri Listelediniz</h4>
                        <p class="card-title-desc">İşlem yapmak istediğiniz ürünü seçiniz.</p>

                        <a  class="mt-1 mr-1 mb-4" data-toggle="collapse" href="#aramakutusus" aria-expanded="false" aria-controls="aramakutusu">

                        </a>

                        <div class="table-rep-plugin urun-liste fleft" style="width:100%; overflow-x:scroll;">
                            <div class="table mb-0" data-pattern="priority-columns">
                                <table id="datatable-buttons2" class="table table-striped table-bordered dt-responsive nowrap urunliste" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                                    <thead>
                                    <tr>
                                        <th data-priority="1">Stok Kodu</th>
                                        <th data-priority="2">Resim</th>
                                        <th data-priority="3">Ürün Adı</th>
                                        <th data-priority="6">Kategori</th>
                                        <th data-priority="5">Stok</th>
                                        <th data-priority="4">Fiyat</th>
                                        <th data-priority="7">Vergi</th>
                                        <th data-priority="8">kampanya</th>
                                        <th data-priority="9">Durum</th>
                                        <th data-priority="10">Tedarikçi</th>
                                        <th data-priority="11">İşlem</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                <div class="cartmodal">
                                    <h5>Araç Marka - Model Seçimi</h5>
                                    <fieldset>
                                        <div class="row">

                                            <div class="col-md-12">
                                                <div class="box-d step1">
                                                    <h6>Marka Seçimi: </h6>
                                                    <div class="step-box">
                                                        <ul>
                                                            @foreach($markalar as $marka)
                                                                <li class="markasec" id="{{$marka->marka_id}}">{{$marka->marka_adi}}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="box-d step2">
                                                    <h6>Model Seçimi: </h6>
                                                    <div class="step-box">
                                                        <ul id="modeldoldur">

                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="box-d step3">
                                                    <h6>Motor Hacmi: </h6>
                                                    <div class="step-box">
                                                        <ul id="motordoldur">

                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="box-d step4">
                                                    <h6>Model Yılı: </h6>
                                                    <div class="step-box">
                                                        <ul class="year">
                                                            <li>Bilinmiyor</li>
                                                            @foreach($yillar as $yil)
                                                                <li class="yilsec" id="{{$yil->id}}" val="{{$yil->yil_adi}}">{{$yil->yil_adi}}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                    <div class="yillar"></div>
                                                </div>

                                                <div class="box-d savemodel m-t-20">
                                                    <button type="button" urun="" marka="0" model="0" motor="0" yil="0" class="btn btn-success markamodelekle">Ekle</button>
                                                </div>
                                            </div>

                                            <div class="model-result m-t-20" style="margin-left:15px;">

                                            </div>
                                        </div>

                                        <button class="btn btn-success float-right">Kaydet</button>
                                        <button class="btn btn-danger closecartmodal float-right" style="margin-right:5px">Kapat</button>
                                    </fieldset>
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
@endsection
