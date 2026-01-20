@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Ürün Düzenle')
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
                        <h4 class="page-title mb-0 font-size-18">Ürün Ekle</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{url('panel/urunler')}}">Ürün</a></li>
                                <li class="breadcrumb-item active">Ürün Marka Model Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">Ürün Marka Model Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/urunler')}}">Geri</a>
                        </div>
                        <div class="card-body">


                            <div id="" class="">
                                <div class="urun-tab row">
                                    <ul>
                                        <a href="{{url('panel/urunduzenle/'.$id)}}"> <li style="margin-left:-27px;" class=" urun-tab-li"><i class="mdi mdi-note-text"></i> Ürün </li></a>
                                        <a href="{{url('panel/urunmarkamodel/'.$id)}}"><li class=" urun-tab-li tabaktif"><i class="mdi mdi-link"></i> Marka Model </li></a>
                                        <a href="{{url('panel/urunfiyat/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-currency-try"></i> Fiyat </li></a>
                                        <a href="{{url('panel/urunoem/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-loupe"></i> Oem </li></a>
                                        <a href="{{url('panel/uruntanim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-checkbox-marked"></i> Tanımlar </li></a>
                                        <a href="{{url('panel/urunresim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-file-image"></i> Resim </li></a>
                                    </ul>
                                </div>

                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="box-d step1">
                                                <h6>Marka Seçimi: </h6>
                                                <div class="step-box">
                                                    <ul>
                                                        @foreach($markalar as $marka)
                                                        <li class="markasec" id="{{$marka->marka_id}}">{{$marka->marka_adi}}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="box-d step2">
                                                <h6>Model Seçimi: </h6>
                                                <div class="step-box">
                                                    <ul id="modeldoldur">

                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="box-d step3">
                                                <h6>Motor Hacmi: </h6>
                                                <div class="step-box">
                                                    <ul id="motordoldur">

                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="box-d step4">
                                                <h6>Model Yılı: </h6>
                                                <div class="step-box">
                                                    <ul>
                                                        <li>Bilinmiyor</li>
                                                        @foreach($yillar as $yil)
                                                            <li class="yilsec" id="{{$yil->id}}" val="{{$yil->yil_adi}}">{{$yil->yil_adi}}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                <div class="yillar"></div>
                                            </div>

                                            <div class="box-d savemodel m-t-20">
                                                <button type="button" urun="{{$id}}" marka="0" model="0" motor="0" yil="0" class="btn btn-success markamodelekle">Ekle</button>
                                            </div>
                                        </div>

                                        <div class="model-result m-t-20" style="margin-left:15px">
                                            @foreach($markamodel as $mm)
                                            <li class="marks-{{$mm->id}} btn btn-secondary btn-sm" style="padding:0px 0px 0px 10px">{{$mm->marka_adi}} - {{$mm->model_adi}} - {{$mm->motor_adi}} - {{$mm->yil_adi}} <i id="{{$mm->id}}" class="markamodelsil mdi-delete-sweep-outline btn btn-danger btn-sm" style="margin:-1px; margin-left:5px;"></i></li>
                                            @endforeach
                                        </div>
                                    </div>

                                    <a href="{{url('panel/urunfiyat/'.$id)}}" class="btn btn-success float-right">Kaydet ve İlerle</a>
                                </fieldset>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

        </div>
        <!-- End Page-content -->
@endsection
