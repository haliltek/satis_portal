@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Bankalar Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Banka İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Banka</a></li>
                                <li class="breadcrumb-item active">Banka İşlemleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/bankaekle')}}">Yeni Banka Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left"> Bankalar Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Banka yı seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Banka Adı</th>
                                    <th>Hesap Sahibi</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($bankalar as $banka)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$banka->banka_adi}}</td>
                                        <td>{{$banka->unvan}}</td>
                                        <td>
                                            @if($banka->durum==0)
                                                <button class="btn btn-danger durumdegis" id="{{$banka->id}}" durum="1" table="bankalar">Pasif</button>
                                            @else
                                                <button class="btn btn-success durumdegis" id="{{$banka->id}}" durum="0" table="bankalar">Aktif</button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{url('panel/bankaduzenle/')}}/{{$banka->id}}" alt="Düzenle"><i class="bx bx-edit"></i></a>
                                            <a href="{{url('panel/bankasil/')}}/{{$banka->id}}" alt="Sil" class="alert"><i class="bx bx-x"></i></a></td>
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



