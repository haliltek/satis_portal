@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Adresler')

@section('content')



    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Bayi Adresler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Bayi Yönetimi</a></li>
                                <li class="breadcrumb-item active">Bayi Adresler</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0">
                <a class="btn btn-primary" href="{{url('panel/bayiler')}}">Tüm Bayiler</a>
                <a class="btn btn-success" href="{{url('panel/bayiekle')}}">Yeni Bayi Ekle</a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5>Bayi Adresler</h5>
                            <div class="editpassword">
                                <div class="col-lg-12">
                                    <h5>Parola Güncelle</h5>
                                </div>
                                <div class="col-lg-12 p-0">
                                    <div class="col-lg-6 fleft">
                                        <label>Yeni Şifre</label>
                                        <input type="text" class="form-control" id="password">
                                    </div>
                                    <div class="col-lg-6 fleft">
                                        <label>Yeni Şifre Tekrar</label>
                                        <input type="text" class="form-control" id="password2">
                                    </div>
                                </div>

                                <div class="passwordresult col-lg-6 fleft" style="padding-top:15px;">

                                </div>


                                <div class="col-lg-6 fright m-t-15">
                                    <button class="btn btn-success fright changepassword" bid="">Güncelle</button>
                                    <button class="btn btn-danger fright m-r-5 closepasswordbox">Kapat</button>
                                </div>

                            </div>
                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Firma</th>
                                    <th>Bayi</th>
                                    <th>Adres</th>
                                    <th>Durum</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($adresler as $adres)
                                    <tr>
                                        <td>{{$adres->firma_unvani}}</td>
                                        <td>{{$adres->name}}</td>
                                        <td>{{$adres->adres}}</td>
                                        <td>
                                            @if($adres->durum != '1')
                                                <button class="btn btn-danger changestat" durum="{{$adres->durum}}" adres="{{$adres->adres_id}}" id="s{{$adres->adres_id}}" >Pasif</button>
                                                @else
                                                <button class="btn btn-success changestat" durum="{{$adres->durum}}" adres="{{$adres->adres_id}}" id="s{{$adres->adres_id}}">Aktif</button>
                                                @endif
                                        </td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->

        </div>
        <!-- End Page-content -->

    </div>



@endsection
