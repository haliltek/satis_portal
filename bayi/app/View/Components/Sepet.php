<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use DB;
class Sepet extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.sepet', [
            'sepet' =>$this->sepet(),
        ]);
    }


    public function sepet(){
        // Üyeyi Al
        $userid =Auth::user()->id;

        try {
            // Üyenin fiyat gurubunu al - b2b_users tablosunda fiyat_gurubu yok, varsayılan olarak 1 kullan
            $fiyat = DB::table('b2b_users')->find($userid);
            // b2b_users tablosunda fiyat_gurubu kolonu yok, varsayılan olarak 1 kullan
            $fiyatid = isset($fiyat->fiyat_gurubu) ? $fiyat->fiyat_gurubu : 1;

            // sepet tablosu yoksa boş array döndür
            if (!DB::getSchemaBuilder()->hasTable('sepet')) {
                return collect([]);
            }

            // Önce urun_id kolonunu kontrol et, yoksa id kullan
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }
            
            // urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
            try {
                // urun_fiyatlari tablosu var mı kontrol et
                if (DB::getSchemaBuilder()->hasTable('urun_fiyatlari')) {
                    $sepet = DB::table('sepet')
                        ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                        ->leftJoin('urun_fiyatlari', 'sepet.urun', '=', 'urun_fiyatlari.urun')->where('fiyat_id', $fiyatid)
                        ->select('urunler.stokadi as urun_adi', 'sepet.id', 'urun_fiyatlari.fiyat', 'sepet.adet')
                        ->where('uye', $userid)
                        ->get();
                } else {
                    // urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
                    $sepet = DB::table('sepet')
                        ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                        ->select('urunler.stokadi as urun_adi', 'sepet.id', 'urunler.fiyat', 'sepet.adet')
                        ->where('uye', $userid)
                        ->get();
                }
            } catch (\Exception $joinError) {
                \Log::warning('Sepet join hatası: ' . $joinError->getMessage());
                // Hata durumunda basit sorgu dene
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->select('urunler.stokadi as urun_adi', 'sepet.id', 'urunler.fiyat', 'sepet.adet')
                    ->where('uye', $userid)
                    ->get();
            }

            return $sepet;
        } catch (\Exception $e) {
            // Hata durumunda boş array döndür
            return collect([]);
        }
    }
}
