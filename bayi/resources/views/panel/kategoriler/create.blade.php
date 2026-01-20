@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Kategori Ekle')
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
                        <h4 class="page-title mb-0 font-size-18">Kategori Ekle</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Kategori</a></li>
                                <li class="breadcrumb-item active">Kategori Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Kategori Ekle
                            <a class="btn btn-danger float-right" href="{{url('panel/kategoriler')}}">Geri</a>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title mb-4">Kategoriler</h4>
                            <form action="{{url('panel/kategorieklepost')}}" method="post">
                                @csrf

                            <div>
                                <div class="row">
                                    <div class="col-12 p-0 row" id="katalan">
                                        <div class="form-group col-lg-3">
                                            <label for="name">Üst Kategori Seç</label>
                                            <select id="katsec" name="ust" class="form-control" >
                                                <option value="0">Üst Kategori</option>

                                                @foreach($kategoriler as $kategori)
                                                <option value="{{$kategori->id}}">{{$kategori->kategori_adi}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3" id="altkat" style="display: none;">
                                            <label for="name">Alt Kategori Seç</label>
                                            <select id="altsec" name="alt" class="form-control" >
                                                <option value="0">Alt Kategori</option>
                                            </select>
                                        </div>

                                    </div>

                                    <div class="form-group col-lg-6">
                                        <label for="name">Kategori Adı</label>
                                        <input type="text" id="name" name="kategori" class="form-control" placeholder="" />
                                    </div>
                                    <div class="col-lg-2 align-self-center m-t-5">
                                        <input data-repeater-create type="submit" class="btn btn-primary btn-block" value="Ekle" />
                                    </div>
                                </div>

                            </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->


        </div>
        <!-- End Page-content -->

@endsection
