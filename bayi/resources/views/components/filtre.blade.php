<div class="col-12 header-search" >
    <div class="card" style="background-color:#5e6577;">
        <div class="card-body">
            <div class="col-lg-6 fleft">
                <div class="col-lg-4 left">

                    <select name="marka" id="marka" class="form-control select2">
                        <option value="0">Marka</option>
                        @foreach($markalar as $marka)
                        <option value="{{$marka->kategori_adi ?? $marka->marka ?? ''}}">{{$marka->kategori_adi ?? $marka->marka ?? 'Marka'}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 fleft">

                    <select name="model" id="model" class="form-control select2">
                        <option value="">Model</option>

                    </select>
                </div>
                <div class="col-lg-4 fleft">

                    <select name="kategori"  id="kat" class="form-control select2">
                        <option value="">Kategori</option>
                        @foreach($kategoriler as $kat)
                            <option value="{{$kat->kategori_adi ?? $kat->kat1 ?? ''}}">{{$kat->kategori_adi ?? $kat->kat1 ?? 'Kategori'}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 fleft">
                    <label for="name"></label>
                    <input id="oem" type="text" class="form-control" placeholder="Oem No" />
                </div>
                <div class="col-lg-4 fleft">
                    <label for="name"></label>
                    <input id="acar_no" type="text" class="form-control" placeholder="Acar No"/>
                </div>
                <div class="col-lg-4 fleft">
                    <label for="name"></label>
                    <input id="garama" type="text" class="form-control" placeholder="Genel Arama"/>
                </div>
            </div>
            <div class="col-lg-1 fleft main-searh-button">
                <button class="btn btn-info filtrele" style="height:97px; width:60px;"><i class="fa fa-search"></i><br>Ara</button>
            </div>
            <div class="col-lg-5 p-0 d-none d-sm-block fleft header-search-banner" style="text-align: center; ">
                <img src="{{ asset('assets/front/') }}/assets/images/b2b_banner2.jpg" height="97" class="fright" style="max-width:100%;"/>
            </div>


        </div>
    </div>
</div>

