<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;

class AyarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $userid = Auth::user()->id;
            $user = DB::table('b2b_users')->where('id',$userid)->first();
            $siteadi = baslik();
            
            // Cari bilgilerini çek (sirket tablosundan)
            $sirketBilgileri = null;
            $cariCode = $user->cari_code ?? null;
            $companyId = $user->company_id ?? null;
            
            if ($cariCode) {
                try {
                    $sirketBilgileri = DB::table('sirket')
                        ->where('s_arp_code', $cariCode)
                        ->orWhere('logo_company_code', $cariCode)
                        ->first();
                    
                    // Eğer bulunduysa ve company_id farklıysa güncelle
                    if ($sirketBilgileri && $sirketBilgileri->sirket_id != $companyId) {
                        DB::table('b2b_users')
                            ->where('id', $userid)
                            ->update(['company_id' => $sirketBilgileri->sirket_id]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Sirket bilgileri çekme hatası: ' . $e->getMessage());
                }
            } elseif ($companyId) {
                try {
                    $sirketBilgileri = DB::table('sirket')
                        ->where('sirket_id', $companyId)
                        ->first();
                } catch (\Exception $e) {
                    \Log::warning('Sirket bilgileri çekme hatası (company_id): ' . $e->getMessage());
                }
            }
            
            return view('front.ayar', compact('siteadi', 'user', 'sirketBilgileri', 'cariCode'));
        } catch (\Exception $e) {
            \Log::error('AyarController@index error: ' . $e->getMessage());
            return view('front.ayar', [
                'siteadi' => baslik(),
                'user' => null,
                'sirketBilgileri' => null,
                'cariCode' => null
            ]);
        }
    }

    public function adreskaydet(Request $request) {
        $userid =Auth::user()->id;
        $kaydet = DB::table('uye_adresler')->insert(
            [
                'uye' => $userid,
                'il' => $request->sehir,
                'ilce' => $request->ilce,
                'adres' => $request->adres,
                'baslik' => $request->adresad,
                'tel' => $request->telefon
            ]
        );
        if($kaydet) {
            echo 'Adres kayıt edildi';
            // bildirim fonksiyonu varsa çağır
            if (function_exists('bildirim')) {
                try {
                    $userName = Auth::user()->username ?? Auth::user()->email ?? 'Kullanıcı';
                    bildirim('Yeni adres', $userName, 'Onay bekleyen yeni adres kaydı', '/panel/adreslist');
                } catch (\Exception $e) {
                    \Log::warning('bildirim() fonksiyonu çağrılamadı: ' . $e->getMessage());
                }
            }
        }
        else {
            echo 'hata!';
        }
    }
    public function adresliste() {
        try {
            $userid =Auth::user()->id;
            $adres = DB::table('uye_adresler')->where('uye',$userid)->get();
            return response()->json($adres);
        } catch (\Exception $e) {
            \Log::error('adresliste error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
    public function adressil(Request $request) {
        $userid =Auth::user()->id;
        $id = $request->adresid;
        $adressil = DB::table('uye_adresler')
            ->where('adres_id',$id)
            ->where('uye',$userid)
            ->delete();
        if($adressil) {
            echo '1';
        }
        else {
            echo '0';
        }
    }
    public function adrestalep(Request $request) {
        echo '1';
    }
    public function parolaguncelle(Request $request){
        try {
            $userid = Auth::user()->id;
            $oldpass = $request->oldpass;
            $newpass = $request->newpass;
            
            // Mevcut kullanıcıyı al
            $user = DB::table('b2b_users')->where('id',$userid)->first();
            
            if (!$user) {
                echo '0';
                return;
            }
            
            // Eski şifreyi kontrol et
            if (!\Hash::check($oldpass, $user->password)) {
                echo '2'; // Eski şifre yanlış
                return;
            }
            
            // Yeni şifreyi güncelle
            $password_update = DB::table('b2b_users')
                ->where('id',$userid)
                ->update(['password' => bcrypt($newpass)]);
                
            if($password_update) { 
                echo '1'; 
            } else { 
                echo '0'; 
            }
        } catch (\Exception $e) {
            \Log::error('parolaguncelle error: ' . $e->getMessage());
            echo '0';
        }
    }
}
