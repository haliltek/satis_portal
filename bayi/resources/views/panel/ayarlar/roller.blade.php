@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Adminler Listesi')

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
                        <h4 class="page-title mb-0 font-size-18">Tüm Adminler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Admin Yönetimi</a></li>
                                <li class="breadcrumb-item active">Tüm Adminler</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0">
                <a class="btn btn-primary" href="{{url('panel/adminler')}}">Adminler</a>
                <a class="btn btn-success" href="{{url('panel/adminekle')}}">Yeni Admin Ekle</a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <a class="btn btn-success" href="{{url('panel/adminekle')}}">Yeni Admin Ekle</a>
                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Ad Soyad</th>
                                    <th>E-Mail</th>
                                    <th>Eklenme Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($adminler as $admin)
                                    <tr>
                                        <td><a href="{{url('panel/adminduzenle/')}}/{{$admin->id}}">{{$admin->name}}</a></td>
                                        <td>{{$admin->email}}</td>
                                        <td>{{$admin->created_at}}</td>
                                        <td>
                                            <a href="{{url('panel/rol/')}}/{{$admin->id}}" alt="Düzenle"><i class="fa fa-edit"></i></a>
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
