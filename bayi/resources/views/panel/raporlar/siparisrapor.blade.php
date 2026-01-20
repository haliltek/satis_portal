@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Bayiler Listesi')

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
                        <h4 class="page-title mb-0 font-size-18">Sipariş Raporları</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Rapor Yönetimi</a></li>
                                <li class="breadcrumb-item active">Sipariş Raporları</li>
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
                            <div class="col-lg-12 fleft m-b-20">
                                <h4>Sipariş Raporları</h4>
                            </div>
                            <div class="col-lg-12 p-0">
                                <div class="col-lg-3 fleft">
                                    <div class="col-lg-12 fleft p-0">
                                        <label>Tarihe Göre Sipariş Raporları</label>
                                    </div>
                                    <div class="col-lg-3 p-l-0 fleft">
                                        <select class="form-control month">
                                            <option>1</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                            <option>6</option>
                                            <option>7</option>
                                            <option>8</option>
                                            <option>9</option>
                                            <option>10</option>
                                            <option>11</option>
                                            <option>12</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 p-0 fleft">
                                        <select class="form-control year">
                                            <option>2020</option>
                                            <option>2021</option>
                                            <option>2022</option>
                                            <option>2023</option>
                                            <option>2024</option>
                                            <option>2025</option>
                                            <option>2026</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6 p-r-0 fleft">
                                        <button class="btn btn-success doreport">Rapor Getir</button>
                                    </div>
                                </div>
                                <div class="col-lg-2 fleft">
                                    <div class="col-lg-12 fleft">
                                        <label>1 Aylık Sipariş Raporları</label>
                                    </div>
                                    <div class="col-lg-12 fleft">
                                        <button class="btn btn-success form-control">Rapor Getir</button>
                                    </div>
                                </div>
                                <div class="col-lg-2 fleft">
                                    <div class="col-lg-12 fleft">
                                        <label>3 Aylık Sipariş Raporları</label>
                                    </div>
                                    <div class="col-lg-12 fleft">
                                        <button class="btn btn-success form-control">Rapor Getir</button>
                                    </div>
                                </div>
                                <div class="col-lg-2 fleft">
                                    <div class="col-lg-12 fleft">
                                        <label>1 Yıllık Sipariş Raporları</label>
                                    </div>
                                    <div class="col-lg-12 fleft">
                                        <button class="btn btn-success form-control">Rapor Getir</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Sipariş</th>
                                    <th>Bayi</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Genel Toplam</th>
                                </tr>
                                </thead>
                                <tbody class="report-table">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-lg-12 fleft m-b-20">
                                <h4>Toplam Veriler</h4>
                            </div>
                            <div class="col-lg-12 p-0">
                                <div class="col-lg-3 fleft">
                                    <div class="col-lg-12 fleft p-0">
                                        <label>Toplam Sipariş Adedi</label>
                                    </div>
                                    <div class="col-lg-12 fleft p-0 green bold toplam-adet">
                                        data
                                    </div>
                                </div>

                                <div class="col-lg-3 fleft">
                                    <div class="col-lg-12 fleft p-0">
                                        <label>Toplam Sipariş Tutarı</label>
                                    </div>
                                    <div class="col-lg-12 fleft p-0 green bold toplam-tutar">
                                        data
                                    </div>
                                </div>

                                <div class="col-lg-3 fleft">
                                    <div class="col-lg-12 fleft p-0">
                                        <label>Sipariş Veren Bayiler</label>
                                    </div>
                                    <div class="col-lg-12 fleft p-0 green bold">
                                        data
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-lg-12 fleft m-b-20">
                                <h4>Kategori Bazlı Sipariş Raporu</h4>
                            </div>
                            <div class="col-lg-12 p-0">


                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>

        </div>
        <!-- End Page-content -->

    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    </div>
    <!-- end container-fluid -->



@endsection
