@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Bayi Düzenle')

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
                        <h4 class="page-title mb-0 font-size-18">Bayi Düzenle </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{url('panel/bayiler')}}">Tüm Bayiler</a></li>
                                <li class="breadcrumb-item active">Bayi Ekle</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">

                            <form id="form-horizontal" class="form-horizontal form-wizard-wrapper" method="post" action="{{url('panel/bayiduzenlepost')}}/{{$id}}">
                                @csrf
                                <h3>Yetkili Bilgileri</h3>
                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtFirstNameBilling" class="col-lg-3 col-form-label">Adı Soyadı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtFirstNameBilling" name="name" value="{{$name}}" type="text" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtLastNameBilling" name="cep" class="col-lg-3 col-form-label">Cep Telefonu</label>
                                                <div class="col-lg-9">
                                                    <input id="txtLastNameBilling" name="cep" value="{{$cep}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCompanyBilling" class="col-lg-3 col-form-label">Şirket Telefonu</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCompanyBilling" name="sirkettel" value="{{$sirkettel}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressBilling" class="col-lg-3 col-form-label">E-Posta Adresi</label>
                                                <div class="col-lg-9">
                                                    <input id="txtEmailAddressBilling" name="email" type="mail" value="{{$mail}}" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtAddress1Billing" class="col-lg-3 col-form-label">Şirket Adresi (Merkez)</label>
                                                <div class="col-lg-9">
                                                    <textarea id="txtAddress1Billing" name="sirketadres"  rows="4" class="form-control">{{$sirketadres}}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtAddress2Billing" class="col-lg-3 col-form-label">Şirket Adresi (Bulunduğu Şube)</label>
                                                <div class="col-lg-9">
                                                    <textarea id="txtAddress2Billing" name="sirketsube"  rows="4" class="form-control">{{$sube}}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCityBilling" class="col-lg-3 col-form-label">Pozisyonu</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCityBilling" name="pozisyon" value="{{$pozisyon}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">Yetkisi</label>
                                                <div class="col-lg-9">
                                                    <input id="txtStateProvinceBilling" name="yetki" value="{{$yetki}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">Fiyat Grubu</label>
                                                <div class="col-lg-9">
                                                    <select name="fiyat" class="form-control">
                                                        @foreach($fiyat as $fiyats)
                                                        <option value="{{$fiyats->id}}" @if($fiyatgurup==$fiyats->id) selected @endif>{{$fiyats->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">Bakiye Sipariş Çalışma</label>
                                                <div class="col-lg-9">
                                                    <select name="acikhesap" class="form-control">

                                                        <option value="1" @if($acikhesap==1) selected @endif>Evet</option>
                                                        <option value="0" @if($acikhesap==0) selected @endif>Hayır</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">Açık Hesap Limiti</label>
                                                <div class="col-lg-9">
                                                    <input type="number" name="acikhesaplimit" class="form-control" value="{{$acik_hesap_limit}}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceBilling" class="col-lg-3 col-form-label">İskonto %</label>
                                                <div class="col-lg-9">
                                                    <input type="number" name="iskonto" class="form-control" value="{{$iskonto}}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </fieldset>
                                <h3>Şirket Bilgileri</h3>
                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtFirstNameShipping" class="col-lg-3 col-form-label">Şirket Ünvanı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtFirstNameShipping" name="firmaunvan" value="{{$firmaunvan}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtLastNameShipping" class="col-lg-3 col-form-label">Vergi Numarası</label>
                                                <div class="col-lg-9">
                                                    <input id="txtLastNameShipping" name="vno" value="{{$vno}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCompanyShipping" class="col-lg-3 col-form-label">Vergi Dairesi</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCompanyShipping" name="vd" value="{{$vd}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmailAddressShipping" class="col-lg-3 col-form-label">Mersis Numarası</label>
                                                <div class="col-lg-9">
                                                    <input id="txtEmailAddressShipping" name="mernis" value="{{$mernis}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCityShipping" class="col-lg-3 col-form-label">Firma Sahibi</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCityShipping" name="firmasahibi" value="{{$fsahip}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtStateProvinceShipping" class="col-lg-3 col-form-label">Muhasebe E-Posta</label>
                                                <div class="col-lg-9">
                                                    <input id="txtStateProvinceShipping" name="muhasebemail" value="{{$muhasebe_mail}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </fieldset>
                                <h3>Banka Bilgileri ve Onay</h3>
                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtNameCard" class="col-lg-3 col-form-label">Hesap Adı Bilgisi</label>
                                                <div class="col-lg-9">
                                                    <input id="txtNameCard" name="bankahesapad" value="{{$bankahesapadi}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="ddlCreditCardType" class="col-lg-3 col-form-label">Şehir</label>
                                                <div class="col-lg-9">
                                                    <select id="ddlCreditCardType" name="sehir" class="form-control">

                                                        <option value="34">İstanbul</option>
                                                        <option value="06">Ankara</option>
                                                        <option value="35">İzmir</option>
                                                        <option value="20">Edirne</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCreditCardNumber" class="col-lg-3 col-form-label">Hesap IBAN</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCreditCardNumber" name="iban" value="{{$iban}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtCardVerificationNumber" class="col-lg-3 col-form-label">Banka Adı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtCardVerificationNumber" name="bankaadi" value="{{$banka}}" type="text" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtExpirationDate" class="col-lg-3 col-form-label">Şube Adı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtExpirationDate" name="bankasube" type="text" value="{{$bsube}}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtExpirationDate" class="col-lg-3 col-form-label">Şube Adı</label>
                                                <div class="col-lg-9">
                                                    <input id="txtExpirationDate" name="hesapno" type="text" value="{{$hesapno}}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success fright">Kaydet</button>
                                </fieldset>
                                <!--<h3>Onay</h3>
                                <fieldset>
                                    <div class="p-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="customCheck1">
                                            <label class="custom-control-label" for="customCheck1">Girdiğim bilgileri kontrol ettim, kayıt olabilir.</label>
                                        </div>
                                    </div>


                                </fieldset> -->
                            </form>

                        </div>
                    </div>
                </div>
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
<script>
    $(function() {
       $('a[href="#finish"]').hide();
    });
</script>

@endsection
