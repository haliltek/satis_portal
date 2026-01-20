@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')

    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row"></div>
            <div class="row m-t-30">





                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <div class="tab-box">
                            <div class="tab active" v-tab="tab1">
                                Sipariş Bilgileri
                            </div>
                            <div class="tab" v-tab="tab2">
                                Banka Hesapları
                            </div>
                            <div class="tab" v-tab="tab3">
                                Son Ödemeler
                            </div>

                        </div>

                        <div class="page-title-right">
                            <div class="custom-control custom-switch mb-2 fleft" dir="ltr" style="margin-right:30px;">

                            </div>
                            <ol class="breadcrumb m-0" style="display:none;">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Tab</a></li>
                                <li class="breadcrumb-item active">Arama Sonucu</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row list-table">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">

                            <h4 class="card-title mb-4 s-body-title"> </h4>


                            <div>
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="form-group col-lg-12 tab-c p-0" id="tab1">

                                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>
                                                    <th width="12%">Sipariş No</th>
                                                    <th width="14%">Ödeme</th>
                                                    <th width="15%">Kargo</th>
                                                    <th width="15%">Kdv</th>
                                                    <th width="15%">Sipariş Tutarı</th>
                                                    <th width="15%">Bakiye</th>
                                                    <th>İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>

                                                @foreach($siparisler as $show)



                                                    <tr>
                                                        <td>{{$show->sip_id}}</td>
                                                        <td>{{$show->odeme_adi}}</td>
                                                        <td>{{$show->name}}</td>
                                                        <td><div class="align-right"> @money($show->kdv) €</div></td>
                                                        <td><div class="align-right">@money($show->geneltoplam) €</div></td>
                                                        <td><div class="align-right">@money($show->bakiye) €</div></td>
                                                        <td>
                                                            <a href="siparis/{{$show->sip_id}}" title="İncele" ><button class="btn btn-info" class="m-r-5"><i class="fa fa-eye"></i> İncele</button></a>
                                                            <a href="makbuz/{{$show->sip_id}}" title="Makbuz"><button class="btn btn-primary" class="m-r-5"><i class="fa fa-sticky-note"></i> Makbuz</button></a>
                                                        </td>
                                                    </tr>

                                                @endforeach

                                                </tbody>
                                            </table>


                                        </div>


                                        <div class="form-group col-lg-12 tab-c p-0" id="tab2">
                                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>
                                                    <th width="25%">Ünvan</th>
                                                    <th width="25%">Banka Adı</th>
                                                    <th width="10%">Şube</th>
                                                    <th width="15%">Hesap No</th>
                                                    <th width="25%">IBAN</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                @foreach($bankalar as $banka)

                                                <tr>
                                                    <td>{{$banka->unvan}}</td>
                                                    <td>{{$banka->banka_adi}}</td>
                                                    <td>{{$banka->sube}}</td>
                                                    <td>{{$banka->hesapno}}</td>
                                                    <td>{{$banka->iban}}</td>
                                                </tr>

                                                @endforeach
                                                </tbody>
                                            </table>

                                        </div>



                                    </div>

                                    <div class="col-lg-12 left p-l-0 m-t-20 ftr-button" style="display:none;">
                                        <button class="btn btn-danger">Kapat</button>
                                        <button class="btn btn-success">Kaydet</button>
                                        </div>

                                        <div class="form-group col-lg-12 tab-c p-0" id="tab3">
                                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                <tr>
                                                    <th width="15%">Makbuz No</th>
                                                    <th width="15%">Tarih</th>
                                                    <th width="20%">Ödeme Tipi</th>
                                                    <th width="20%">Tutar</th>
                                                    <th width="30%">İşlem</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                @if(!empty($sonOdemeler) && count($sonOdemeler) > 0)
                                                    @foreach($sonOdemeler as $odeme)
                                                        <tr>
                                                            <td>{{ isset($odeme->sid) ? $odeme->sid : '-' }}</td>
                                                            <td>{{ isset($odeme->tarih_formatted) ? $odeme->tarih_formatted : (isset($odeme->tarih) ? $odeme->tarih : '-') }}</td>
                                                            <td>{{ isset($odeme->odeme_adi) ? $odeme->odeme_adi : '-' }}</td>
                                                            <td><div class="align-right">@money2(isset($odeme->tutar) ? floatval($odeme->tutar) : 0)</div></td>
                                                            <td>
                                                                <button class="btn btn-primary" onclick="window.print()" title="Yazdır"><i class="fa fa-print"></i> Yazdır</button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="5" class="text-center">Ödeme kaydı bulunamadı.</td>
                                                    </tr>
                                                @endif
                                                </tbody>
                                            </table>

                                        </div>


                                    </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>





            <!-- end row -->

        </div>
        <!-- End Page-content -->

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <p><script>document.write(new Date().getFullYear())</script> © b2b salter</p>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-right d-none d-sm-block">
                            B2B Satış Sistemi <a href="https://www.salter-group.com" target="_blank">Salter-Group</a> Tarafından Yapılmıştır.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>


@endsection
