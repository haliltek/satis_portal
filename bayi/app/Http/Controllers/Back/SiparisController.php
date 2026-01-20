<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;


class SiparisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index() {
        try {
            $siparisler = DB::table('uye_siparisler')
                ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_siparisler.uye')
                ->select('uye_siparisler.*','kargolar.name','odeme_yontemleri.odeme_adi','b2b_users.username as bayi', 'b2b_users.email as bayi_email')
                ->orderBy('uye_siparisler.tarih', 'desc')
                ->orderBy('uye_siparisler.saat', 'desc')
                ->get();
            return view('panel.siparisler.index', ['siparisler'=> $siparisler]);
        } catch (\Exception $e) {
            \Log::error('SiparisController@index error: ' . $e->getMessage());
            return view('panel.siparisler.index', ['siparisler'=> collect([])]);
        }
    }
    public function onaybekleyen() {
        try {
            $siparisler = DB::table('uye_siparisler')
                ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_siparisler.uye')
                ->select('uye_siparisler.*','kargolar.name','odeme_yontemleri.odeme_adi','b2b_users.username as bayi', 'b2b_users.email as bayi_email')
                ->where('uye_siparisler.durum','1')
                ->orderBy('uye_siparisler.tarih', 'desc')
                ->orderBy('uye_siparisler.saat', 'desc')
                ->get();
            return view('panel.siparisler.onaybekleyen', ['siparisler'=> $siparisler]);
        } catch (\Exception $e) {
            \Log::error('SiparisController@onaybekleyen error: ' . $e->getMessage());
            return view('panel.siparisler.onaybekleyen', ['siparisler'=> collect([])]);
        }
    }
    public function kargolanan() {
        try {
            $siparisler = DB::table('uye_siparisler')
                ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_siparisler.uye')
                ->select('uye_siparisler.*','kargolar.name','odeme_yontemleri.odeme_adi','b2b_users.username as bayi', 'b2b_users.email as bayi_email')
                ->where('uye_siparisler.kargo_durum','2')
                ->orderBy('uye_siparisler.tarih', 'desc')
                ->orderBy('uye_siparisler.saat', 'desc')
                ->get();
            return view('panel.siparisler.kargolanan', ['siparisler'=> $siparisler]);
        } catch (\Exception $e) {
            \Log::error('SiparisController@kargolanan error: ' . $e->getMessage());
            return view('panel.siparisler.kargolanan', ['siparisler'=> collect([])]);
        }
    }
    public function tamamlanan() {
        try {
            $siparisler = DB::table('uye_siparisler')
                ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_siparisler.uye')
                ->select('uye_siparisler.*','kargolar.name','odeme_yontemleri.odeme_adi','b2b_users.username as bayi', 'b2b_users.email as bayi_email')
                ->where('uye_siparisler.durum','2')
                ->orderBy('uye_siparisler.tarih', 'desc')
                ->orderBy('uye_siparisler.saat', 'desc')
                ->get();
            return view('panel.siparisler.tamamlanan', ['siparisler'=> $siparisler]);
        } catch (\Exception $e) {
            \Log::error('SiparisController@tamamlanan error: ' . $e->getMessage());
            return view('panel.siparisler.tamamlanan', ['siparisler'=> collect([])]);
        }
    }
    public function detay(Request $request,$id) {
        try {
            $query = DB::table('uye_siparisler')->where('sip_id',$id)->first();
            if(!$query) {
                return redirect()->back()->with('error', 'Sipariş bulunamadı');
            }
            $userid = $query->uye;
            $detay = DB::table('uye_siparisler')
                ->leftJoin('uye_siparis_urunler', 'uye_siparis_urunler.sipid', '=', 'uye_siparisler.sip_id')
                ->leftJoin('urunler', 'urunler.id', '=', 'uye_siparis_urunler.urun')
                ->select('uye_siparis_urunler.*','uye_siparisler.iskonto','uye_siparisler.geneltoplam','uye_siparisler.tarih','uye_siparisler.kargotakip','urunler.stokadi as urun_adi','urunler.stokkodu as urun_kodu')
                ->where('uye_siparisler.sip_id', $id)
                ->get();
            $ayarlar = DB::table('ayarlar')->where('id','1')->first();
            $users = DB::table('b2b_users')->where('id',$userid)->first();
            return view('panel/siparisler/detay',compact('detay','ayarlar','users'));
        } catch (\Exception $e) {
            \Log::error('SiparisController@detay error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Sipariş detayı yüklenemedi');
        }
    }
    public function durumkaydet(Request $request) {
        $sid = $request->sid;
        $uye = DB::table('uye_siparisler')->where('sip_id',$sid)->first();
        $user = $uye->uye;
        $bakiyebilgi = DB::table('users')->where('id',$user)->first();
        $bakiye = $bakiyebilgi->bakiye;
        $odenen = $request->odenen;
        $odenen = str_replace(',','.',$odenen);
        $guncelbakiye = $bakiye - $odenen;

        $tarih = date('Y-m-d');
        if($request->odenen != '') {
            $data = (
                [
                    'uye' => $user,
                    'yontem' => $request->odeme,
                    'alacak' => $odenen,
                    'tarih' => $tarih,
                    'guncelbakiye' => $guncelbakiye
                ]
            );
            $bk = (
            [
                'bakiye' => $guncelbakiye
            ]
            );
        $ekstre = DB::table('uye_cari_extre')->insert($data);
        if($ekstre) {
            DB::table('users')->where('id',$user)->update($bk);
            DB::table('uye_siparisler')->where('sip_id',$sid)->update(
                [
                    'durum' => $request->siparisdurum
                ]
            );
            echo '1';
        }
        else { echo '0'; }
        }
    }
    public function odemetip() {
        $query = DB::table('odeme_yontemleri')->where('durum',1)->get();
        return $query;
    }

}
