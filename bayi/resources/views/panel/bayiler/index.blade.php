@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Bayiler Listesi')

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
                        <h4 class="page-title mb-0 font-size-18">Tüm Bayiler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Bayi Yönetimi</a></li>
                                <li class="breadcrumb-item active">Tüm Bayiler</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0">
                <a class="btn btn-primary" href="{{url('panel/adreslist')}}">Bayi Adresler</a>
                <a class="btn btn-success" href="{{url('panel/bayiekle')}}">Yeni Bayi Ekle</a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

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
                                    <th>Ad Soyad</th>
                                    <th>E-Mail</th>
                                    <th>Eklenme Tarihi</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($bayiler as $bayi)
                                <tr>
                                    <td><a href="{{url('panel/bayidetay/')}}/{{$bayi->id}}">{{$bayi->name}}</a></td>
                                    <td>{{$bayi->email}}</td>
                                    <td>{{$bayi->created_at}}</td>
                                    <td>
                                        @if($bayi->durum==0)
                                            <button class="btn btn-danger durumdegis" id="{{$bayi->id}}" durum="1" table="users">Pasif</button>
                                        @else
                                            <button class="btn btn-success durumdegis" id="{{$bayi->id}}" durum="0" table="users">Aktif</button>
                                        @endif
                                    </td>
                                    <td width="150">
                                        <a href="{{url('panel/bayiduzenle/')}}/{{$bayi->id}}" alt="Düzenle"><button class="btn btn-primary"><i class="fa fa-edit"></i> Düzenle</button></a>
                                        <a href="{{url('panel/bayisil/')}}/{{$bayi->id}}" alt="Sil" class=""><button class="btn btn-danger"><i class="fa fa-trash"></i> Sil</button></a>
                                        <a title="Şifre Değiştir" class="passwordbox" bid="{{$bayi->id}}"><button class="btn btn-dark"><i class="fa fa-key"></i> Şifre Değiştir</button></a>
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
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    </div>
    <!-- end container-fluid -->



@endsection
