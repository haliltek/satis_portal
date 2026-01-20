
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - İyzico Düzenle')
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
                        <h4 class="page-title mb-0 font-size-18">Ödeme İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ödeme</a></li>
                                <li class="breadcrumb-item active">İyzico Ayarları</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">İyzico Ayarları
                            <a class="btn btn-danger float-right" href="{{url('panel/odemeyontemleri')}}">Geri</a>
                        </div>
                        <div class="card-body">

                            <div data-repeater-list="group-a">
                                <form action="{{url('panel/iyzicoduzenle')}}/61" method="post">
                                    @csrf
                                    <div data-repeater-item class="row">
                                        <div class="form-group col-lg-3">
                                            <label for="name">Secret ID</label>
                                            <input type="text" id="marka" name="secret" value="{{$secret_id}}" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-3">
                                            <label for="name">Secret Key</label>
                                            <input type="text" id="marka" name="key"  value="{{$secret_key}}" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-4">
                                            <label for="name">Back Url</label>
                                            <input type="text" id="marka" name="back" value="{{$back_url}}" class="form-control" placeholder="" />
                                        </div>
                                        <div class="col-lg-1 align-self-center">
                                            <input data-repeater-create type="submit" class="btn btn-primary btn-block m-t-10" value="Kaydet" />
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
