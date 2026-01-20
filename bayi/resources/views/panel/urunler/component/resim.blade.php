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
                                <li class="breadcrumb-item active">Ürün Resimleri</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">Ürün Resim Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/urunler')}}">Geri</a>
                        </div>
                        <div class="card-body">


                            <div id="" class="">
                                <div class="urun-tab row">
                                    <ul>
                                        <a href="{{url('panel/urunduzenle/'.$id)}}"> <li style="margin-left:-27px;" class=" urun-tab-li "><i class="mdi mdi-note-text"></i> Ürün </li></a>
                                        <a href="{{url('panel/urunmarkamodel/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-link"></i> Marka Model </li></a>
                                        <a href="{{url('panel/urunfiyat/'.$id)}}"><li class=" urun-tab-li "><i class="mdi mdi-currency-try"></i> Fiyat </li></a>
                                        <a href="{{url('panel/urunoem/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-loupe"></i> Oem </li></a>
                                        <a href="{{url('panel/uruntanim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-checkbox-marked"></i> Tanımlar </li></a>
                                        <a href="{{url('panel/urunresim/'.$id)}}"><li class=" urun-tab-li tabaktif"><i class="mdi mdi-file-image"></i> Resim </li></a>
                                    </ul>
                                </div>


                                <fieldset>
                                    <div class="form-group row">

                                        <form  id="file-upload-form" class="uploader row" action="{{url('panel/urunresimekle')}}" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="urunid" value="{{$id}}">
                                            <div class="col-md-12">
                                                <label for="otlist" class="col-lg-9 col-form-label" style="text-align:left;">Ürün Görselleri</label>
                                                <input type="file" onchange="yuklen(this)" name="image[]" id="image" multiple="multiple" class="form-control" style="padding: 26px 0 10px 20px; height: 80px;">
                                            </div>



                                    </div>

                                    <div id="eklenecekresimler"></div>

                                    <div class="form-group row" style="margin-left:20px;">
                                        <label for="otlist" class="col-lg-9 col-form-label" style="text-align:left;">Yüklenen Görseller</label>
                                        <div class="row">
                                            @foreach($urunresim as $urs)
                                                <div id="urresim-{{$urs->id}}" class="col-md-2" style="height:133px; border:2px solid #3b5de7;margin-left:5px !important;">
                                                    <div class="resimsil" resimid="{{$urs->id}}"><i class="mdi mdi-delete-circle"></i></div>
                                                    <img  style="overflow: hidden;" width="100%;" class=" kapakyap" durum="@if($urs->kapak==1) 1 @else 0 @endif" rid="{{$urs->id}}" urun="{{$urs->urun}}" src="{{ asset('uploads/urunler/') }}/{{$urs->resim}}">
                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                    <button type="submit"  class="btn btn-success float-right">Kaydet ve Bitir</button>
                                    </form>
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
