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
                                <li class="breadcrumb-item active">Ürün Oem Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">Ürün Oem Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/urunler')}}">Geri</a>
                        </div>
                        <div class="card-body">


                            <div id="" class="">
                                <div class="urun-tab row">
                                    <ul>
                                        <a href="{{url('panel/urunduzenle/'.$id)}}"> <li style="margin-left:-27px;" class=" urun-tab-li "><i class="mdi mdi-note-text"></i> Ürün </li></a>
                                        <a href="{{url('panel/urunmarkamodel/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-link"></i> Marka Model </li></a>
                                        <a href="{{url('panel/urunfiyat/'.$id)}}"><li class=" urun-tab-li "><i class="mdi mdi-currency-try"></i> Fiyat </li></a>
                                        <a href="{{url('panel/urunoem/'.$id)}}"><li class=" urun-tab-li tabaktif"><i class="mdi mdi-loupe"></i> Oem </li></a>
                                        <a href="{{url('panel/uruntanim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-checkbox-marked"></i> Tanımlar </li></a>
                                        <a href="{{url('panel/urunresim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-file-image"></i> Resim </li></a>
                                    </ul>
                                </div>

                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="oem" class="col-lg-3 col-form-label">Oem No</label>
                                                <div class="col-lg-8">
                                                    <input name="oem" type="text" id="oem" class="form-control">
                                                </div>
                                                <div class="col-lg-1">
                                                    <button type="button" class="btn btn-primary oemekle" urun="{{$id}}" title="Oem Ekle"><i class="fa fa-plus"></i></button>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row oem">
                                                <label for="oemlist" class="col-lg-3 col-form-label">Eklenen Oemler</label>
                                                <div class="col-lg-8">
                                                    <select size="4" class="form-control" id="oemlist">
                                                        @foreach($oemler as $oem)
                                                            <option class="oemsil" value="{{$oem->id}}">{{$oem->oem}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-lg-1">
                                                    <button type="button" class="btn btn-primary deloem" title="Seçili Oemi Sil"><i class="fa fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>



                                    <a href="{{url('panel/uruntanim/'.$id)}}" class="btn btn-success float-right">Kaydet ve İlerle</a>

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
