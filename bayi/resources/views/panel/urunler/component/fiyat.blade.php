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
                                <li class="breadcrumb-item active">Ürün Fiyat Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">Ürün Fiyat Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/urunler')}}">Geri</a>
                        </div>
                        <div class="card-body">


                            <div id="" class="">
                                <div class="urun-tab row">
                                    <ul>
                                        <a href="{{url('panel/urunduzenle/'.$id)}}"> <li style="margin-left:-27px;" class=" urun-tab-li "><i class="mdi mdi-note-text"></i> Ürün </li></a>
                                        <a href="{{url('panel/urunmarkamodel/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-link"></i> Marka Model </li></a>
                                        <a href="{{url('panel/urunfiyat/'.$id)}}"><li class=" urun-tab-li tabaktif"><i class="mdi mdi-currency-try"></i> Fiyat </li></a>
                                        <a href="{{url('panel/urunoem/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-loupe"></i> Oem </li></a>
                                        <a href="{{url('panel/uruntanim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-checkbox-marked"></i> Tanımlar </li></a>
                                        <a href="{{url('panel/urunresim/'.$id)}}"><li class=" urun-tab-li"><i class="mdi mdi-file-image"></i> Resim </li></a>
                                    </ul>
                                </div>

                                <fieldset>
                                    <div class="row">


                                        @foreach($ayar as $ay)
                                            <div class="col-md-12">
                                                <div class="form-group row">
                                            @php

                                                $sor = DB::table('urun_fiyatlari')->where('urun', $id)->where('fiyat_id', $ay->id)->get();

                                                $say =0;
                                                foreach ($sor as $bas){
                                                    $say=$say+1;
                                                    $fiyat= $bas->fiyat;
                                                    $fiyatid = $bas->id;


                                                }

                                                if($say==0){
                                                        $fiyat="";
                                                        $fiyatid="0";
                                                    }

                                                echo "<label for='txtFirstNameShipping' class='col-lg-3 col-form-label'>$ay->name</label>

                                                            <div class=2col-lg-6'>
                                                                <input id='fiyat-$ay->id'  urun='$id' fiyatid='$fiyatid' name='fiyat' type='text' value='$fiyat' class='form-control'>
                                                            </div>
                                                            <div class='col-lg-3'>
                                                                <button class='btn btn-primary fiyatkaydet' id='$ay->id'><i class='mdi mdi-content-save'></i></button>
                                                </div>";

                                            @endphp
                                        </div>
                                        </div>
                                        @endforeach




                                    </div>

                                    <a href="{{url('panel/urunoem/'.$id)}}" class="btn btn-success float-right">Kaydet ve İlerle</a>


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
