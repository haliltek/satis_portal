
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Seri Düzenle')
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
                        <h4 class="page-title mb-0 font-size-18">Seri İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Seri</a></li>
                                <li class="breadcrumb-item active">Seri Düzenle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Seri Düzenle
                            <a class="btn btn-danger float-right" href="{{url('panel/seriler')}}">Geri</a>
                        </div>
                        <div class="card-body">

                            <div data-repeater-list="group-a">
                                <form action="{{url('panel/seriduzenlepost')}}/{{$id}}" method="post">
                                    @csrf
                                    <div data-repeater-item class="row">
                                        <div class="form-group col-lg-3">
                                            <label for="name">Marka</label>
                                            <select id="markasec" name="marka" class="form-control" required>

                                                @foreach($markalar as $marka)
                                                    @if($markaid==$marka->marka_id)
                                                    <option value="{{$marka->marka_id}}" selected>{{$marka->marka_adi}}</option>
                                                    @else
                                                     <option value="{{$marka->marka_id}}">{{$marka->marka_adi}}</option>
                                                    @endif
                                                    @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3">
                                            <label for="name">Model</label>
                                            <select id="modeller" name="model" class="form-control" required>
                                                 <option value="{{$modelid}}">{{$modeladi}}</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-4">
                                            <label for="name">Seri Adı</label>
                                            <input type="text" id="seri" name="seri" class="form-control" value="{{$motoradi}}" placeholder=""  required>
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
