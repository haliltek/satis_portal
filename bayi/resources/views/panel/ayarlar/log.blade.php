@extends('panel.layouts.app')

@section('title', 'Güncelleme kayıtları')

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
                        <h4 class="page-title mb-0 font-size-18">Log</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Log</a></li>
                                <li class="breadcrumb-item active">Güncelleme logları</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Güncelleme logları </h4>
                            <div class="log-area" style="float:left; width:100%; height:500px; background-color:#f5f5f5; overflow-y:scroll; margin-top:10px;">
                                @while($line = fgets($fh))
                                    {{$line}}<br>
                                @endwhile
                            </div>

                        </div>



                    </div>
                </div>
            </div>




        </div>

        <!-- end row -->

    </div>
    <!-- End Page-content -->
@endsection
