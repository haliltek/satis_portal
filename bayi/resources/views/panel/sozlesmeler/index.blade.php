
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Sözleşmeler Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Sözleşme İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Sözleşmeler</a></li>

                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->




            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left">Sözleşmeler Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Sözleşmeyi seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Sözleşme Adı</th>
                                    <th>İşlem</th>

                                </tr>
                                </thead>

                                <tbody>
                                @foreach($sozlesme as $soz)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$soz->sozlesme_adi}}</td>
                                        <td>
                                            <a href="{{url('panel/sozlesmeduzenle/')}}/{{$soz->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>


                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->

    </div>
    <!-- end main content-->




@endsection



