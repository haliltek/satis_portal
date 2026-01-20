@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Adminler Ekle')

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
                        <h4 class="page-title mb-0 font-size-18">Admin Düzenle</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Admin Yönetimi</a></li>
                                <li class="breadcrumb-item active">Admin Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->



            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-header">Admin Düzenle ({{$name}})
                            <a class="btn btn-danger float-right" href="{{url('panel/adminler')}}">Geri</a>
                        </div>
                        <div class="card-body">
                            <form autocomplete="off" method="post" action="{{url('panel/adminduzenlepost')}}">
                                @csrf
                                <input type="hidden" name="uye" value="{{$uyeid}}">
                                <div class="form-group">
                                    <label>Ad Soyad</label>
                                    <input type="text" name="ad" class="form-control" value="{{$name}}" required autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label>E-Mail</label>
                                    <input type="email" name="email" value="{{$email}}" class="form-control" required autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label>Şifre</label>
                                    <input type="password" minlength="8" name="sifre" class="form-control" required autocomplete="false">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">Kaydet</button>
                                </div>
                            </form>
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
