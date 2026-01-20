
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - İlçe Ekle')
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
                        <h4 class="page-title mb-0 font-size-18">İlçe İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">İlçe</a></li>
                                <li class="breadcrumb-item active">İlçe Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">İlçe Ekle
                            <a class="btn btn-danger float-right" href="{{url('panel/ilceler/'.$id)}}">Geri</a>
                        </div>
                        <div class="card-body">

                            <div data-repeater-list="group-a">
                                <form action="{{url('panel/ilceeklepost')}}" method="post">
                                    @csrf
                                    <input type="hidden" name="il" value="{{$id}}">
                                    <div data-repeater-item class="row">
                                        <div class="form-group col-lg-10">
                                            <label for="name">İlçe Adı</label>
                                            <input type="text" id="ilce" name="ilce" class="form-control" placeholder="" />
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
