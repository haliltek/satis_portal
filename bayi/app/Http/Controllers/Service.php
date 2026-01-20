<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class Service extends Controller
{
    public function product_list(){
        $products = DB::table('urunler')
            ->leftJoin('urun_fiyatlari','urun_fiyatlari.urun','=','urunler.id')
            ->leftJoin('urun_oem','urun_oem.urun','=','urunler.id')
            ->leftJoin('urun_tanimlari','urun_tanimlari.urun','=','urunler.id')
            ->select('urunler.urun_adi','urunler.id','urunler.urun_kodu','urunler.ust_kat','urun_fiyatlari.fiyat','urun_oem.oem','urun_tanimlari.tanimadi','urun_tanimlari.tanim_deger')
            ->paginate(500);
        return $products;
    }
    public function newlist() {
        $products = DB::table('urun_ozellikleri')
            ->paginate(500);
        return $products;

    }
    public function image_list(){
        $images = DB::table('urunler')
            ->leftJoin('urun_resimler','urun_resimler.urun','=','urunler.id')
            ->select('urunler.urun_adi','urunler.id','urunler.urun_kodu','urun_resimler.resim')
            ->paginate(50);
        return $images;
    }
    public function category_list(){
        $categories = DB::table('kategoriler')
            ->get();
        return $categories;
    }
    public function model_list(){
        $models = DB::table('urun_ozellikleri')
            ->get();
        return $models;
    }
}
