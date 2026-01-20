@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="row"></div>
            <div class="row">
                <div class="card m-t-30">
                    <div class="card-body s-body">
                        <h4 class="card-title mb-4 s-body-title">Kredi Kartı Ödeme Formu</h4>
                        <div class="col-lg-12">
                            {!! $paymentinput !!}
                            <div id="iyzipay-checkout-form" class="responsive"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
