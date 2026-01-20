
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Banka Ekle')
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
                        <h4 class="page-title mb-0 font-size-18">Banka İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Banka Ayarları</a></li>
                                <li class="breadcrumb-item active">Banka Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Banka Ekle
                            <a class="btn btn-danger float-right" href="{{url('panel/bankalar')}}">Geri</a>
                        </div>
                        <div class="card-body">

                            <div data-repeater-list="group-a">
                                <form action="{{url('panel/bankaeklepost')}}" method="post">
                                    @csrf
                                    <div data-repeater-item class="row">
                                        <div class="form-group col-lg-4">
                                            <label for="name">Banka Adı</label>
                                            <input type="text" id="name" name="name" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-4">
                                            <label for="name">Ünvan</label>
                                            <input type="text" id="name" name="unvan" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-4">
                                            <label for="name">Şube</label>
                                            <input type="text" id="name" name="sube" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-6">
                                            <label for="name">Hesap No</label>
                                            <input type="text" id="name" name="hesapno" class="form-control" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-6">
                                            <label for="name">İban No</label>
                                            <input type="text" id="name" name="iban" class="form-control" placeholder="" />
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
