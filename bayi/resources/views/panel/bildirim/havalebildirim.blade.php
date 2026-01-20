@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Havale Bildirimler')

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
                        <h4 class="page-title mb-0 font-size-18">Havale Bildirimler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Bildirim</a></li>
                                <li class="breadcrumb-item active">Havale Bildirim</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">


                            <div class="odemedetay-modal" style="margin-left:100px;">
                                <div class="card">
                                    <div class="card-body" style="padding-top:10px; padding-bottom:10px;">
                                        <h5 class="p-0 fleft">Ödeme Detay</h5>
                                        <i class="fa fa-times" id="close-odemedetay" style="cursor:pointer; font-size:20px; color:#f00; float:right; margin-top:2px;"></i>
                                    </div>
                                </div>
                                <div class="col-lg-12 fleft">
                                    <div class="col-lg-12 fleft form-group" id="gonderen">Gönderen : <b></b> </div>
                                    <div class="col-lg-12 fleft form-group" id="bayi">Bayi : <b></b></div>
                                    <div class="col-lg-6 fleft form-group" id="sip-tutar">Sipariş Tutar : <b></b></div>
                                    <div class="col-lg-6 fleft form-group" id="odenen">Ödenen : <b></b></div>
                                </div>
                                <div class="col-lg-12 fleft ">
                                    <button class="btn btn-success fright" id="odemeonay" oid="">Onayla</button>
                                </div>
                            </div>

                            <table id="datatable" class="table table-striped table-bordered dt-responsive nowrap" >
                                <thead>
                                <tr>
                                    <th data-priority="1">Uye</th>
                                    <th data-priority="1">Ödenen</th>
                                    <th data-priority="1">Sipariş</th>
                                    <th data-priority="1">İncele</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($havale as $show)
                                    @if($show->onay < '1')
                                        @php($color='#fd971a')
                                    @else
                                        @php($color='#4ead05')
                                    @endif
                                    <tr>
                                        <td><div style="color:{{$color}}">{{$show->name}}</div></td>
                                        <td><div class="align-right"> @money($show->odenen)₺</div></td>
                                        <td>{{$show->sipid}}</td>
                                        <td><i class="fa fa-eye odemedetay" style="cursor:pointer" oid="{{$show->id}}"></i></td>
                                    </tr>
                                @endforeach



                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->

        </div>
        <!-- End Page-content -->

    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    </div>
    <!-- end container-fluid -->



@endsection
