
@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Bayi Detayları')
@section('content')
@foreach($user as $bayi)
    @endforeach
    <div class="main-content">

        <div class="page-content">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="page-title mb-0 font-size-18">Bayi Detay</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="tum-bayiler.html">Tüm Bayiler</a></li>
                                <li class="breadcrumb-item active">Bayi Detay</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <!-- start row -->
            <div class="row">
                <div class="col-md-12 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="profile-widgets py-3">

                                <div class="text-center">
                                    <div class="">
                                        <img src="assets/images/users/avatar-2.jpg" alt="" class="avatar-lg mx-auto img-thumbnail rounded-circle">
                                        <div class="online-circle"><i class="fas fa-circle text-success"></i></div>
                                    </div>

                                    <div class="mt-3 ">
                                        <a href="#" class="text-dark font-weight-medium font-size-16">{{$bayi->firma_unvani}}</a>
                                        <p class="text-body mt-1 mb-1">{{$bayi->name}}</p>

                                        <span class="badge badge-success">Aktif</span>
                                        <span class="badge badge-danger">%{{$bayi->iskonto}} İskonto</span>
                                    </div>



                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">İletişim Bilgileri</h5>

                            <p class="card-title-desc">
                                {{$bayi->adres}}
                            </p>

                            <div class="mt-3">
                                <p class="font-size-12 text-muted mb-1">E-Posta Adresi</p>
                                <h6 class="">{{$bayi->email}}</h6>
                            </div>

                            <div class="mt-3">
                                <p class="font-size-12 text-muted mb-1">Telefon Numarası</p>
                                <h6 class="">{{$bayi->sirket_telefonu}}</h6>
                            </div>

                            <div class="mt-3">
                                <p class="font-size-12 text-muted mb-1">Cep Teelfonu</p>
                                <h6 class="">{{$bayi->cep_telefonu}}</h6>
                            </div>

                        </div>
                    </div>

                    <div class="card" style="display:none;">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Bayilik Ürün Kategorileri</h5>
                            <p class="text-muted">Bayisi olduğu kategoriler listelenmektedir.</p>
                            <ul class="list-unstyled list-inline language-skill mb-0">
                                <li class="list-inline-item badge badge-primary"><span>Balata</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Hortum</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Direksiyon Aksamları</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Kaporta</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Kapı </span></li>
                                <li class="list-inline-item badge badge-primary"><span>Aydınlatma</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Egzoz</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Tekerlek</span></li>
                                <li class="list-inline-item badge badge-primary"><span>Kaput</span></li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="col-md-12 col-xl-9">
                    <div class="row">
                        <div class="col-md-12 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <p class="mb-2">Tamamlanan Satışlar</p>
                                            <h4 class="mb-0">{{$siparis_adet}} Adet</h4>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-right">
                                                <div>
                                                    % 18 <i class="mdi mdi-arrow-up text-success ml-1"></i>
                                                </div>
                                                <div class="progress progress-sm mt-3">
                                                    <div class="progress-bar" role="progressbar" style="width: 62%" aria-valuenow="62" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <p class="mb-2">Bekleyen Siparişler</p>
                                            <h4 class="mb-0">{{$bekleyen_siparis}}</h4>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-right">
                                                <div>
                                                    % 18 <i class="mdi mdi-arrow-up text-success ml-1"></i>
                                                </div>
                                                <div class="progress progress-sm mt-3">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <p class="mb-2">Toplam Satış / ₺</p>
                                            <h4 class="mb-0">{{$satis}} ₺</h4>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-right">
                                                <div>
                                                    % 18 <i class="mdi mdi-arrow-up text-success ml-1"></i>
                                                </div>
                                                <div class="progress progress-sm mt-3">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">

                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">

                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#revenue" role="tab">
                                        <span class="d-none d-sm-block">Aylık Satış Grafiği</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#settings" role="tab">
                                        <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                        <span class="d-none d-sm-block">Geri Bildirim Gönder</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content p-3 text-muted">

                                <div class="tab-pane active" id="revenue" role="tabpanel">
                                    <div id="revenue-chart" class="apex-charts mt-4"></div>
                                </div>
                                <div class="tab-pane" id="settings" role="tabpanel">

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="userbio">Mesajınız</label>
                                                <textarea class="form-control" id="userbio" rows="4" placeholder="Herhangi bir sorun olduğunda hemen buradan yazın..."></textarea>
                                            </div>
                                        </div>
                                        <!-- end col -->
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label for="useremail">E-Posta Adresiniz</label>
                                                <input type="email" class="form-control" id="useremail" placeholder="E-Posta Adresinizi Giriniz">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label for="userpassword">Departman</label>
                                                <div class="col-lg-9">
                                                    <select id="ddlCreditCardType" name="ddlCreditCardType" class="form-control">
                                                        <option value="">--Seçiniz--</option>
                                                        <option value="AE">Muhasebe</option>
                                                        <option value="VI">Satış</option>
                                                        <option value="MC">Teknik</option>
                                                        <option value="DI">Genel</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- end col -->
                                    </div>
                                    <div class="mt-4">
                                        <button class="btn btn-primary" type="submit">Gönder</button>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Son Alınan Ürünler</h4>

                            <div class="table-responsive">
                                <table class="table table-centered mb-0">
                                    <thead>
                                    <tr>
                                        <th scope="col">Kargo</th>
                                        <th scope="col">Alım Tarihi</th>
                                        <th scope="col">Sipariş No</th>
                                        <th scope="col">Fiyat</th>
                                        <th scope="col" colspan="2">Durum</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($siparisler as $show)

                                    <tr>
                                        <td>{{$show->name}}</td>
                                        <td>
                                            {{$show->tarih}}
                                        </td>
                                        <td>{{$show->sip_id}}</td>
                                        <td>{{$show->geneltoplam}} ₺</td>
                                        @if($show->adurum == '2')
                                            @php($durum='Ödendi')
                                            @php($class='success')
                                        @elseif($show->adurum=='1')
                                            @php($durum='Ödeme Bekliyor')
                                            @php($class='warning')
                                        @else
                                            @php($durum='Tamamlanmadı')
                                            @php($class='danger')
                                        @endif
                                        <td><span class="badge badge-soft-{{$class}} font-size-12">{{$durum}}</span></td>
                                        <td><a href="h" class="btn btn-primary btn-sm">Görüntüle</a></td>
                                    </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <ul class="pagination pagination-rounded justify-content-center mb-0">
                                    <li class="page-item">
                                        <a class="page-link" href="#">Önceki</a>
                                    </li>
                                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Sonraki</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

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
