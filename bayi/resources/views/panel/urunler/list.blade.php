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

            <div class="row">
                <div class="col-lg-12">


                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Tüm Ürünleri Listelediniz</h4>
                            <p class="card-title-desc">İşlem yapmak istediğiniz ürünü seçiniz.</p>


                            <div class="table-rep-plugin">
                                <div class="table mb-0" data-pattern="priority-columns">
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th data-priority="1">No</th>
                                            <th data-priority="2">Ürün</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($urunler as $urun)
                                            <tr>
                                                <td>{{$urun->urun_kodu}}</td>
                                                <td>{{$urun->urun_adi}}</td>
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
