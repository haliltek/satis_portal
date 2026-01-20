@extends('front.layouts.apps')
@section('title', "{$mesaj}")
@section('content')

    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row" style="padding:20px;"></div>

            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <div class="col-lg-12 fleft">
                            <h3>{{$mesaj}}</h3>
                        </div>
                    </div>
                </div>


            </div>

@endsection
