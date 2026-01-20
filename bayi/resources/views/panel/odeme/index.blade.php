@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Ödeme Yöntemleri Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Ödeme Yöntemleri </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ödeme </a></li>
                                <li class="breadcrumb-item active">Ödeme Yöntemleri </li>
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

                            <h4 class="card-title w-75 left"> Ödeme Yöntemleri Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Ödeme Yönteminiz seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Yöntem Adı</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($yontem as $ynt)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$ynt->odeme_adi}}</td>
                                        <td>
                                            @if($ynt->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$ynt->id}}" durum="1" table="odeme_yontemleri">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$ynt->id}}" durum="0" table="odeme_yontemleri">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ynt->id==61)
                                            <a href="{{url('panel/iyzico/')}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            @endif
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



