
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Markalar Ekle')
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
                        <h4 class="page-title mb-0 font-size-18">Marka İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Marka</a></li>
                                <li class="breadcrumb-item active">Marka Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Marka Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/markalar')}}">Geri</a>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title mb-4">Araç Marka Ekle</h4>


                            <div data-repeater-list="group-a">
                                <form method="post" action="{{url('panel/markaduzenlepost')}}/{{$id}}">
                                    @csrf
                                    @foreach($markalar as $marka)
                                    <input type="hidden" name="id" id="id" value="{{$marka->marka_id}}">
                                <div data-repeater-item class="row">
                                    <div class="form-group col-lg-10">
                                        <label for="name">Marka Adı</label>

                                        <input type="text" id="marka" name="marka" value="{{$marka->marka_adi}}" class="form-control" placeholder="" />

                                    </div>
                                    @endforeach
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
