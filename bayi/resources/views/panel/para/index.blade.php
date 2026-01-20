
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Para Birimleri Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Para Birimleri İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Para Birimleri</a></li>

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

                            <h4 class="card-title w-75 left">Para Birimleri Listesi</h4>

                            <button class="btn btn-success kurguncelle" style="margin-top:20px; margin-bottom: 20px;">Kurları Güncelle (TCMB)</button>



                                @foreach($birim as $br)
                                <div class="col-md-12 row" style="margin-top:10px;">
                                <div style="padding-top:8px; " class="col-md-1">{{$br->isim}}</div>
                                <div class="col-md-1">
                                    <input id="birim-{{$br->id}}" type="text" class="form-control" value="{{$br->kur}}">
                                </div>
                                <div class="col-md-1">
                                    <button class="btn @if($br->durum==0) btn-danger @else btn-success @endif  durumdegis" durum="@if($br->durum==0) 1 @else 0 @endif" id="{{$br->id}}" table="para_birimleri">@if($br->durum==0) Pasif @else Aktif @endif</button>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-info birimkaydet" id="{{$br->id}}">Kaydet</button>
                                </div>
                                </div>
                                @endforeach




                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->

    </div>
    <!-- end main content-->




@endsection



