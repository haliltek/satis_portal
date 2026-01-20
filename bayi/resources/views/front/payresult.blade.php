@extends('front.layouts.apps')
@section('title', "")
@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="row"></div>
            <div class="row">
                <div class="card m-t-30 col-lg-12">
                    <div class="card-body s-body">
                        <h4 class="card-title mb-4 s-body-title">Ödeme </h4>
                        <div class="col-lg-12 p-0">
                            @if($sonuc=='SUCCESS')
                                @php($result='Ödeme işleminiz gerçekleşti.')

                            @else
                                {{$result = $sonuc2}}
                            @endif
                                {{$result}}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
