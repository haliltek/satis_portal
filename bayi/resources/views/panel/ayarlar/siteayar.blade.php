@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Site Ayarları')

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
                    <h4 class="page-title mb-0 font-size-18">Ayarlar</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Ayar</a></li>
                            <li class="breadcrumb-item active">Genel Ayarlar</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        <form method="post" action="{{url('panel/ayarduzenle/1')}}" enctype="multipart/form-data">
            @csrf
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body s-body">
                        <h4 class="card-title mb-4 s-body-title">Temel Ayarlar </h4>


                        <div>
                            <div class="row">


                                <div class="form-group col-lg-12">
                                    <label for="name">Başlık</label>
                                    <input type="text" id="name" name="title" value="{{$siteadi}}" class="form-control" placeholder="" />
                                </div>


                            </div>

                        </div>



                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body s-body">
                        <h4 class="card-title mb-4 s-body-title">Firma Bilgileri </h4>


                        <div>
                            <div class="row">
                                <div class="form-group col-lg-6">
                                    <label for="name">Firma Adı</label>
                                    <input type="text" id="name" name="firmaadi" value="{{$firma_adi}}" class="form-control" placeholder="" />
                                </div>

                                <div class="form-group col-lg-6">
                                    <label for="name">Ünvan</label>
                                    <input type="text" id="name" name="unvan" value="{{$unvan}}" class="form-control" placeholder="" />
                                </div>

                                <div class="form-group col-lg-2">
                                    <label for="name">Telefon</label>
                                    <input type="text" id="name" name="telefon" value="{{$site_tel}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-2">
                                    <label for="name">Fax</label>
                                    <input type="text" id="name" name="fax" value="{{$fax}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-2">
                                    <label for="name">Gsm</label>
                                    <input type="text" id="name" name="gsm" value="{{$gsm}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-3">
                                    <label for="name">Site Mail</label>
                                    <input type="text" id="name" name="sitemail" value="{{$site_mail}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-3">
                                    <label for="name">Sipariş Mail</label>
                                    <input type="text" id="name" name="sipmail" value="{{$simail}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-12">
                                    <label for="name">Adres</label>
                                    <textarea class="form-control" name="adres">{{$adres}}</textarea>
                                </div>
                                <div class="form-group col-lg-4">
                                    <label for="name">Vergi Dairesi</label>
                                    <input type="text" id="name" name="vergi_d" value="{{$vergi_d}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-4">
                                    <label for="name">Vergi No</label>
                                    <input type="text" id="name" name="vergi_no" value="{{$vergi_no}}" class="form-control" placeholder="" />
                                </div>
                                <div class="form-group col-lg-4">
                                    <label for="name">Mersis No</label>
                                    <input type="text" id="name" name="mersis_no" value="{{$mersis_no}}" class="form-control" placeholder="" />
                                </div>
                            </div>

                        </div>



                    </div>
                </div>
            </div>
        </div>
        <!-- end row -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body s-body">

                        <h4 class="card-title mb-4 s-body-title">Seo Ayarları </h4>
                        <div class="row">
                            <div class="form-group col-lg-6">
                                <label for="name">Description</label>
                                <input type="text" id="name" name="desc" value="{{$desc}}" class="form-control" placeholder="" />
                            </div>
                            <div class="form-group col-lg-6">
                                <label for="name">Keyword</label>
                                <input type="text" id="name" name="keyw" value="{{$keyw}}" class="form-control" placeholder="" />
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
            <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body s-body">

                        <h4 class="card-title mb-4 s-body-title">Kurumsal </h4>
                        <div class="row">
                            <div class="col-lg-12">
                                <textarea id="elm1" name="kurumsal">{{$kurumsal}}</textarea>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>



        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body s-body">
                        <h4 class="card-title mb-4 s-body-title">Logo </h4>
                        <div class="row">
                            <div class="col-lg-4 left">
                                <label for="up-logo">Logo Yükle</label>
                                <div class="col-lg-12 p-l-0">
                                    <input type="file" name="image" class="logo-up-file" id="up-logo">
                                </div>
                            </div>

                            <div class="col-lg-4 left">
                                <label for="name">Logo Önizleme</label>
                                <div class="logo-preview">

                                    <img id="prevlogo" src="{{ asset('public/uploads/ayarlar/') }}/{{$logo}}" />
                                </div>
                            </div>
                            <input type="hidden" name="resim" value="{{$logo}}">
                            <div class="col-lg-12 left p-l-0 m-t-20 ftr-button">
                                <button class="btn btn-danger">Kapat</button>
                                <button class="btn btn-primary" type="submit">Kaydet</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end row -->
        </form>
    </div>
    <!-- End Page-content -->
@endsection
