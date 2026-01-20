<div class="sepet-detay">


    <div class="baslik">
        <div class="urun">Ürün adı</div>
        <div class="adet">Adet</div>
        <div class="fiyat">Fiyat</div>
    </div>
    <ul class="sepetliste">@php $toplam = 0; @endphp
        @foreach($sepet as $show)
            @php
                $fiyat = floatval($show->fiyat ?? 0);
                $toplam += $fiyat * $show->adet;
                $urunAdi = $show->stokadi ?? $show->urun_adi ?? 'Ürün Adı Yok';
            @endphp

            <li>
                <div class="urun">{{$urunAdi}}</div>
                <div class="adet">{{$show->adet}}</div>
                <div class="fiyat">{{number_format($fiyat, 2, ',', '.')}} €</div>
                <div class="islem"><i class="fa fa-times delbasketitem" sid="{{$show->id}}"></i></div>
            </li>
        @endforeach

    </ul>
    <div class="total">
        Sepet Toplamı :
        <span class="fright sepettoplam" style="margin-right:35px;">{{number_format($toplam, 2, ',', '.')}} €</span>
    </div>
    <div class="col-lg-12 buttons p-0">
        <div class="col-lg-6 p-0 fleft">
            <a class="btn btn-danger sepetibosalt">Sepeti Boşalt</a>
        </div>
        <div class="col-lg-6 p-0 fleft">
            <a href="{{ url('/sepet') }}" class="btn btn-success show-basket">Sepete Git</a>
        </div>
    </div>
</div>
