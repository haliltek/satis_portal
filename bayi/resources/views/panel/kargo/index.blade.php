@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Kargo Firmaları Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Kargo İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Kargo</a></li>
                                <li class="breadcrumb-item active">Kargo İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/kargoekle')}}">Yeni Kargo Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> Kargolar Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Kargoyu seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kargo Adı</th>
                                    <th>Ücret</th>
                                    <th>Tür</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($kargolar as $kargo)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$kargo->name}}</td>
                                        <td>
                                            @if($kargo->ucret==0)
                                                Ücretsiz
                                            @else
                                                {{$kargo->ucret}} ₺
                                            @endif
                                        </td>
                                        <td>
                                            @if($kargo->tur==1)
                                                Kargo
                                            @elseif($kargo->tur==2)
                                                Uçak Kargo
                                            @elseif($kargo->tur==3)
                                                Kurye
                                            @elseif($kargo->tur==4)
                                                Ambar
                                            @elseif($kargo->tur==5)
                                                Gel Al
                                            @elseif($kargo->tur==6)
                                                Lojistik
                                            @endif
                                        </td>
                                        <td>
                                            @if($kargo->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$kargo->id}}" durum="1" table="kargolar">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$kargo->id}}" durum="0" table="kargolar">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/kargoduzenle/')}}/{{$kargo->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/kargosil/')}}/{{$kargo->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



