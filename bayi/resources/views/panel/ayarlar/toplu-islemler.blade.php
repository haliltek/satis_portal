@extends('panel.layouts.app')

@section('title', 'B2B Admin Panel - Site Ayarları')

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
                        <h4 class="page-title mb-0 font-size-18">Toplu İşlemler</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ayar</a></li>
                                <li class="breadcrumb-item active">Toplu İşlemler</li>
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
                                <h4 class="card-title mb-4 s-body-title">Toplu indirim/zam uygula </h4>


                                    <div class="row">

                                        <div class="form-group col-lg-3 fleft">
                                            <label for="name">Oran(%)</label>
                                            <input type="text" id="name" name="title" value="" class="form-control islem-oran" placeholder="" />
                                        </div>
                                        <div class="form-group col-lg-3 fleft">
                                            <label for="name">Tür</label>
                                            <select class="form-control islem-tur">
                                                <option value="1">İndirim</option>
                                                <option value="0">Zam</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-1">

                                            <button class="btn btn-success topluindirim" style="margin-top:28px;">Uygula</button>
                                        </div>
                                    </div>

                                </div>



                            </div>
                        </div>
                    </div>


                <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Tedarikçiye göre indirim/zam uygula </h4>
                                <div class="row">
                                    <div class="form-group col-lg-3 fleft">
                                        <label for="name">Tedarikçi</label>
                                        <select class="form-control islem2-tedarikci">
                                            <option>Narin</option>
                                            <option>Yıldız</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-lg-3 fleft">
                                        <label for="name">Oran(%)</label>
                                        <input type="text" id="name" name="title" value="" class="form-control islem2-oran" placeholder="" />
                                    </div>
                                    <div class="form-group col-lg-3 fleft">
                                        <label for="name">Tür</label>
                                        <select class="form-control islem2-tur">
                                            <option>İndirim</option>
                                            <option>Zam</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-lg-1">

                                        <button class="btn btn-success ttopluindirim" style="margin-top:28px;">Uygula</button>
                                    </div>
                                </div>

                            </div>



                        </div>
                    </div>
                </div>

                <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Kategoriye göre indirim/zam uygula </h4>
                            <div class="row">
                                <div class="form-group col-lg-3 fleft">
                                    <label for="name">Kategori</label>
                                    <select class="form-control islem3-kategori">
                                        @foreach($kategoriler as $kategori)
                                        <option value="{{$kategori->id}}">{{$kategori->kategori_adi}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 fleft">
                                    <label for="name">Oran(%)</label>
                                    <input type="text" id="name" name="title" value="" class="form-control islem3-oran" placeholder="" />
                                </div>
                                <div class="form-group col-lg-3 fleft">
                                    <label for="name">Tür</label>
                                    <select class="form-control islem3-tur">
                                        <option>İndirim</option>
                                        <option>Zam</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-1">
                                    <button class="btn btn-success ktopluindirim" style="margin-top:28px;">Uygula</button>
                                </div>
                            </div>

                        </div>



                    </div>
                </div>
            </div>

                <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body s-body">
                            <h4 class="card-title mb-4 s-body-title">Aktif / Pasif durumu </h4>
                            <div class="row">
                                <div class="form-group col-lg-3 fleft">
                                    <div class="col-lg-12 p-0">
                                        <label for="name">Fiyat olmayan ürünler</label>
                                    </div>

                                    <button class="btn btn-success fiyatdurum" stat="1">Aktif</button>
                                    <button class="btn btn-danger fiyatdurum" stat="0">Pasif</button>
                                </div>

                                <div class="form-group col-lg-3 fleft">
                                    <div class="col-lg-12 p-0">
                                        <label for="name">Stok olmayan ürünler</label>
                                    </div>

                                    <button class="btn btn-success stokdurum" stat="1">Aktif</button>
                                    <button class="btn btn-danger stokdurum" stat="0">Pasif</button>
                                </div>

                                <div class="form-group col-lg-3 fleft">
                                    <div class="col-lg-12 p-0">
                                        <label for="name">Tüm ürünler</label>
                                    </div>

                                    <button class="btn btn-success urundurum" stat="1">Aktif</button>
                                    <button class="btn btn-danger urundurum" stat="0">Pasif</button>
                                </div>
                            </div>

                        </div>



                    </div>
                </div>
            </div>

                <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body ">
                            <div class="col-lg-4 fleft">
                                <h4 class="card-title mb-4 ">Tüm ürünlere stok ekle </h4>
                                <div class="row">
                                    <div class="form-group col-lg-8 fleft">
                                        <div class="col-lg-12 p-0">
                                            <label for="name">Eklenecek stok adedi</label>
                                        </div>
                                        <input type="text" class="form-control stok" />
                                    </div>
                                    <div class="form-group col-lg-3">
                                        <button class="btn btn-success stokekle" style="margin-top:28px;">Uygula</button>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-5 fleft p-0">
                                <h4 class="card-title mb-4">Kategoriye göre stok ekle </h4>
                                <div class="row">
                                    <div class="form-group col-lg-5 fleft">
                                        <div class="col-lg-12 p-0">
                                            <label for="name">Eklenecek stok adedi</label>
                                        </div>
                                        <input type="text" class="form-control kstok" />
                                    </div>
                                    <div class="form-group col-lg-5 fleft">
                                        <label for="name">Kategori</label>
                                        <select class="form-control">
                                            @foreach($kategoriler as $kategori)
                                                <option value="{{$kategori->id}}">{{$kategori->kategori_adi}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-lg-2">
                                        <button class="btn btn-success kstokekle" style="margin-top:28px;">Uygula</button>
                                    </div>

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
@endsection
