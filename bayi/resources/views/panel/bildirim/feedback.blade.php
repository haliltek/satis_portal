@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Mesajlar')

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
                        <h4 class="page-title mb-0 font-size-18">Feedback Form</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Bildirim</a></li>
                                <li class="breadcrumb-item active">Feedback</li>
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
                                        <h5 class="p-0 fleft feedMessage-name">Mesaj</h5>
                                        <i class="fa fa-times" id="close-odemedetay" style="cursor:pointer; font-size:20px; color:#f00; float:right; margin-top:2px;"></i>
                                    </div>
                                </div>
                                <div class="col-lg-12 fleft feedMessage-area">

                                </div>

                            </div>


                            <table id="datatable" class="table table-striped table-bordered dt-responsive nowrap" >
                                <thead>
                                <tr>
                                    <th data-priority="1">İsim</th>
                                    <th data-priority="1">İletişim Kanalı</th>
                                    <th data-priority="1">Konu</th>
                                    <th data-priority="1">Mesaj</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($feed as $show)

                                    <tr>
                                        <td><div >{{$show->isim}}</div></td>
                                        <td>{{$show->secenek}}</td>
                                        <td>{{$show->konu}}</td>
                                        <td><i class="mdi mdi-clipboard-text-multiple feedMessage" style="cursor:pointer; margin: -6px 0 -7px 0; font-size:23px; float:left;" fid="{{$show->id}}" name="{{$show->isim}}" title="Mesajı Görüntüle"></i></td>
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
