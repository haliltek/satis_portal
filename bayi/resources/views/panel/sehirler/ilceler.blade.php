@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - İlçeler Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">İlçe İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">İlçe</a></li>
                                <li class="breadcrumb-item active">İlçe İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/ilceekle/'.$id)}}">Yeni İlçe Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> İlçe Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz ilçe seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>İlçe Adı</th>
                                    <th>İlçeler</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($ilceler as $ilce)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$ilce->ilce_adi}}</td>
                                        <td>
                                            @if($ilce->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$ilce->id}}" durum="1" table="ilceler">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$ilce->id}}" durum="0" table="ilceler">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/ilceduzenle/')}}/{{$ilce->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/ilcesil/')}}/{{$ilce->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



