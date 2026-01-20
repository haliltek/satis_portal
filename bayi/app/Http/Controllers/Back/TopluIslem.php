<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class TopluIslem extends Controller
{
    public function topluindirim(Request $request) {
        $oran = $request->oran;
        $tur = $request->tur;
        $urunler = DB::table('urunler')->get();
        $count = count($urunler);
        $key = '1';
        foreach($urunler as $urun) {
            $urunid = $urun->id;
            $findprice = DB::table('urun_fiyatlari')->where('urun',$urunid)->first();
            $queryRate = $findprice->fiyat / 100 * $oran;
            if($tur == '1') {
                $newPrice = $findprice->fiyat - $queryRate;
            }
            else {
                $newPrice = $findprice->fiyat + $queryRate;
            }
            $upPrice = DB::table('urun_fiyatlari')->where('urun',$urunid)->update([ 'fiyat' => $newPrice ]);
            if($upPrice){
                if($key == $count) {
                    echo 'işlem başarılı';
                }
                else { $key++; }
            }
        }
    }

    public function ttopluindirim(Request $request) {
        $oran = $request->oran;
        $tur = $request->tur;
        $tedarikci = $request->tedarikci;
        $urunler = DB::table('urunler')->where('tedarikci',$tedarikci)->get();
        $count = count($urunler);
        $key = '1';
        foreach($urunler as $urun) {
            $urunid = $urun->id;
            $findprice = DB::table('urun_fiyatlari')->where('urun',$urunid)->first();
            $queryRate = $findprice->fiyat / 100 * $oran;
            if($tur == '1') {
                $newPrice = $findprice->fiyat - $queryRate;
            }
            else {
                $newPrice = $findprice->fiyat + $queryRate;
            }
            $upPrice = DB::table('urun_fiyatlari')->where('urun',$urunid)->update([ 'fiyat' => $newPrice ]);
            if($upPrice){
                if($key == $count) {
                    echo 'işlem başarılı';
                }
                else { $key++; }
            }
        }
    }

    public function ktopluindirim(Request $request) {
        $oran = $request->oran;
        $tur = $request->tur;
        $kategori = $request->kategori;
        $urunler = DB::table('urunler')->where('ust_kat',$kategori)->get();
        $count = count($urunler);
        $key = '1';
        foreach($urunler as $urun) {
            $urunid = $urun->id;
            $findprice = DB::table('urun_fiyatlari')->where('urun',$urunid)->first();
            $queryRate = $findprice->fiyat / 100 * $oran;
            if($tur == '1') {
                $newPrice = $findprice->fiyat - $queryRate;
            }
            else {
                $newPrice = $findprice->fiyat + $queryRate;
            }
            $upPrice = DB::table('urun_fiyatlari')->where('urun',$urunid)->update([ 'fiyat' => $newPrice ]);
            if($upPrice){
                if($key == $count) {
                    echo 'İşlem başarılı';
                }
                else { $key++; }
            }
        }
    }

    public function fiyatdurum(Request $request){
        $stat = $request->stat;
        $priceQuery = DB::table('urunler')
            ->leftJoin('urun_fiyatlari', 'urun_fiyatlari.urun','=','urunler.id')
            ->where('urun_fiyatlari.fiyat','<','1')
            ->update(['urunler.durum' => $stat]);
        if($priceQuery){
            echo 'İşleminiz tamamlandı';
        }
        else { echo 'Hata!'; }
    }

    public function stokdurum(Request $request){
        $stat = $request->stat;
        $stockQuery = DB::table('urunler')
            ->where('stok','<',1)
            ->update([ 'durum' => $stat ]);
        if($stockQuery){
            echo 'İşleminiz tamamlandı';
        }
        else { echo 'Hata!'; }
    }

    public function urundurum(Request $request){
        $productQuery = DB::table('urunler')
            ->update([ 'durum' => $request->stat ]);
        if($productQuery){
            echo 'İşleminiz tamamlandı';
        }
        else { echo 'Hata!'; }
    }

    public function stokekle(Request $request){

    }
    public function kstokekle(Request $request){

    }

    public function updatelog(){
        $fh = fopen(public_path().'/logs/update.log','r');
        return view('panel.ayarlar.log',compact('fh'));
    }
}
