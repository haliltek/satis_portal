@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Tedarikçiler Listesi')

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
                        <h4 class="page-title mb-0 font-size-18">Tedarikçiler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Tedarikçi Yönetimi</a></li>
                                <li class="breadcrumb-item active">Tüm Tedarikçiler</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success tedarikcimodal" style="color:#fff;">Yeni Tedarikçi Ekle</a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">


                            <div class="tedarikmodal">
                                <div class="col-lg-12">
                                    <div class="col-lg-12 m-t-20">
                                        <h4>Tedarikçi Ekle</h4>
                                    </div>
                                    <div class="col-lg-12 fleft m-b-20">
                                        <label>Tedarikçi Adı</label>
                                        <input type="text" class="form-control tedarikci" />
                                    </div>
                                    <div class="col-lg-12 fleft m-b-20">
                                        <p class="tedarikresult fleft"></p>
                                        <button class="btn btn-success fright tedarikciekle">Ekle</button>
                                        <button class="btn btn-danger fright close-tedarikmodal m-r-5">Kapat</button>
                                    </div>
                                </div>
                            </div>

                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tedarikçi Adı</th>
                                    <th>İşlemler</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($tedarikciler as $show)
                                    <tr>
                                        <td>{{$show->id}}</td>
                                        <td>{{$show->tedarikci}}</td>
                                        <td>
                                            <a href="{{url('panel/tedarikcisil/')}}/{{$show->id}}" alt="Sil" class=""><i class="fa fa-trash"></i></a>
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
