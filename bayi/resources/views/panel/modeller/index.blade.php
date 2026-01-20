
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Modeller Listesi')
@section('content')
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Model İşlemleri</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Modeller</a></li>

                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="col-lg-12 mb-3 align-right p-0 fleft">
                <a class="btn btn-success ekleclass" href="{{url('panel/modelekle')}}">Yeni Model Ekle</a>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title w-75 left">Araç Model Listesi</h4>
                            <p class="card-title-desc w-75 left">İşlem yapmak istediğiniz Modeli seçiniz.</p>


                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Model Adı</th>
                                    <th>Markası</th>
                                    <th>Serileri</th>
                                    <th>İşlem</th>

                                </tr>
                                </thead>

                                <tbody>
                                @foreach($modeller as $model)
                                    <tr>
                                        <td>{{$loop->index+1}}</td>
                                        <td>{{$model->model_adi}}</td>
                                        <td>{{$model->marka_adi}}</td>
                                        <td>
                                            <a href="{{url('panel/seriler')}}/{{$model->id}}" class="btn btn-warning">Seriler</a>
                                        </td>
                                        <td>
                                            <a href="{{url('panel/modelduzenle/')}}/{{$model->id}}" alt="Düzenle"><i class="fa fa-edit"></i></a>
                                            <a href="{{url('panel/modelsil/')}}/{{$model->id}}" alt="Sil" class="alert"><i class="fa fa-times"></i></a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>


                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->

    </div>
    <!-- end main content-->




@endsection



