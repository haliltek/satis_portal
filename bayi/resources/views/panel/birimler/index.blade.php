@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Birim Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Birim İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Birim</a></li>
                                <li class="breadcrumb-item active">Birim İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/birimekle')}}">Yeni Birim Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> Birim Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Birimi seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Birim Adı</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($birimler as $birim)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$birim->name}}</td>
                                        <td>
                                            @if($birim->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$birim->id}}" durum="1" table="birimler">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$birim->id}}" durum="0" table="birimler">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/birimduzenle/')}}/{{$birim->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/birimsil/')}}/{{$birim->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



