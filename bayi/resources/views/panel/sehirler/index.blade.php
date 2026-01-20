@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - İller Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">İl İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">İl</a></li>
                                <li class="breadcrumb-item active">İl İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/ilekle')}}">Yeni İl Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> İl Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz ili seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Şehir Adı</th>
                                    <th>İlçeler</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($iller as $il)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$il->il_adi}}</td>
                                        <td>
                                            <a href="{{url('panel/ilceler/'.$il->id)}}" class="btn btn-info">İlçeler</a>
                                        </td>
                                        <td>
                                            @if($il->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$il->id}}" durum="1" table="iller">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$il->id}}" durum="0" table="iller">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/ilduzenle/')}}/{{$il->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/ilsil/')}}/{{$il->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



