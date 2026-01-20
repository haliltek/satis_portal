@extends('front.layouts.apps')
@section('title', "{$siteadi}")
@section('content')

    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">






            </div>
            <!-- end page title -->




            <div class="row fatura m-t-30">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Sipariş Detay </h4>
                            <div class="row">
                                <div class="col-lg-12">

                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr height="50">
                                            <th width="10%">No</th>
                                            <th width="10%">Sipariş Tarihi</th>
                                            <th width="40%">Ürün Adı</th>
                                            <th width="10%">Adet</th>
                                            <th width="12%">Birim Fiyatı</th>
                                            <th width="8%">İskonto</th>
                                            <th width="10%">Kdv</th>
                                            <th width="12%">Fiyat</th>

                                        </tr>
                                        </thead>

                                        <tbody>
                                        @php($brut = '0')
                                        @foreach($detay as $show)
                                        <tr>
                                            <td>{{$show->urun_kodu}}</td>
                                            <td>{{dateformat($show->tarih)}}</td>
                                            <td>{{$show->urun_adi}}</td>
                                            <td>{{$show->adet}}</td>
                                            <td>@money($show->tutar * $show->adet)₺</td>
                                            <td>%{{$show->iskonto}}</td>
                                            <td>@money($show->kdv)₺</td>
                                            <td>@money($show->tutar * $show->adet)₺</td>
                                        </tr>
                                            @php($brut += $show->tutar * $show->adet )
                                        @endforeach
                                        <tr style="border:0px;">
                                            <td colspan="6" align="right">
                                                <p>Brüt Toplam :</p>
                                                <p>İndirim(%10) :</p>
                                                <p>Ara Toplam :</p>
                                                <p>Kdv(%18) :</p>
                                                <p><b>Sipariş Toplamı :</b></p>
                                            </td>
                                            <td colspan="2">
                                                <p>@money($brut)₺</p>
                                                <p>@money($brut / 100 * $show->iskonto)₺</p>
                                                <p>@money($brut - $show->tutar / 100 * $show->iskonto)₺</p>
                                                <p>@money($brut / 100 * 18)₺</p>
                                                <p><b>@money($show->geneltoplam)₺</b></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
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
