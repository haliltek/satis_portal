
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Vergi Ekle')
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
                        <h4 class="page-title mb-0 font-size-18">Vergi İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Vergi Ayarları</a></li>
                                <li class="breadcrumb-item active">Vergi Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Vergi Ekle
                            <a class="btn btn-danger float-right" href="{{url('panel/vergiayarlari')}}">Geri</a>
                        </div>
                        <div class="card-body">

                            <div data-repeater-list="group-a">
                                <form action="{{url('panel/vergiduzenlepost')}}/{{$id}}" method="post">
                                    @csrf
                                    <div data-repeater-item class="row">
                                        <div class="form-group col-lg-3">
                                            <label for="name">Vergi Adı</label>
                                            <input type="text" id="name" name="name" value="{{$name}}" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-3">
                                            <label for="name">Vergi Oranı</label>
                                            <input type="number" id="name" value="{{$oran}}" name="oran" class="form-control" placeholder="" />
                                        </div>
                                        <div class="col-lg-2 align-self-center">
                                            <input data-repeater-create type="submit" class="btn btn-primary btn-block m-t-10" value="Ekle" />
                                        </div>
                                    </div>
                                </form>
                            </div>



                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->


        </div>
        <!-- End Page-content -->

@endsection
