@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Vergi Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Vergi İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Vergi</a></li>
                                <li class="breadcrumb-item active">Vergi İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/vergiekle')}}">Yeni Vergi Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> Vergi Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Verygi yi seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Vergi Adı</th>
                                    <th>Vergi Oranı</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($vergiler as $vergi)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$vergi->name}}</td>
                                        <td>% {{$vergi->oran}}</td>
                                        <td>
                                            @if($vergi->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$vergi->id}}" durum="1" table="vergi_oranlari">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$vergi->id}}" durum="0" table="vergi_oranlari">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/vergiduzenle/')}}/{{$vergi->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/vergisil/')}}/{{$vergi->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



