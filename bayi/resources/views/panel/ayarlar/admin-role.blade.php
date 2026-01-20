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
                        <h4 class="page-title mb-0 font-size-18">Roller</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                                <li class="breadcrumb-item active">Roller</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Yönetici Yetkileri</h4>
                            <p></p>



                            <div class="table-rep-plugin">
                                <div class="table mb-0" data-pattern="priority-columns">
                                    <table id="datatable-buttons2" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th data-priority="1">Yetki</th>
                                            <th data-priority="2">Durum</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($roller as $rol)
                                            <tr>
                                                <td>{{$rol->yetki}}</td>
                                                <td>
                                                    @if($rol->durum == 1)
                                                    <button class="btn btn-success yetki" onclick="rol({{$rol->yid}})" durum="1" yid="{{$rol->yetki_id}}" id="y{{$rol->yid}}" find="{{$rol->yid}}" user="{{$rol->uye}}">Aktif</button>
                                                    @else
                                                    <button class="btn btn-danger yetki" onclick="rol({{$rol->yid}})" durum="0" yid="{{$rol->yetki_id}}" id="y{{$rol->yid}}" find="{{$rol->yid}}" user="{{$rol->uye}}">Pasif</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
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
