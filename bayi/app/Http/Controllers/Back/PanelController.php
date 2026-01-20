<?php

namespace App\Http\Controllers\Back;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use DB;

use App\User;
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{


    public function index()
    {
        // Admin panel login sayfası - users tablosu kontrolü gerekmiyor
       return view('panel.login');
    }

public function home(){
    $siparis = collect(); // $siparis değişkenini boş bir koleksiyon olarak başlat
    try {
        // Oturum Açıldıysa
        if(\Auth::check()){
            // Kullanıcı Seviyesini Tespit Et
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Kullanıcı bulunamadı');
            }
            // b2b_users tablosunda role kolonu var, seviye yok
            // Admin için role kontrolü yapılmalı
            $role = $user->role ?? null;

            // Admin ise (role = 'Admin' veya role = null ve id = 1 gibi kontrol) Panele Gönder, Değilse Siteye Gönder
            if($role == 'Admin' || $user->id == 1) {
                $siparisler = DB::table('uye_siparisler')
                    ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                    ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                    ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_siparisler.uye')
                    ->select('uye_siparisler.*', 'kargolar.name as kargo_adi', 'odeme_yontemleri.odeme_adi', 'b2b_users.username as bayi')
                    ->orderBy('sip_id', 'desc')
                    ->limit(12)
                    ->get();

                // Eğer bir sipariş sorgusu yapacaksanız, burada $siparis değişkenini doldurun
                // Örneğin:
                // $siparis = DB::table('diger_tablo')->get(); // Gerçek sorgunuzu buraya yazın

                $bayi = DB::table('b2b_users')->where('role', 'Bayi')->count();

                $havale = DB::table('odemebildirim')
                    ->leftJoin('b2b_users', 'b2b_users.id', '=', 'odemebildirim.uye')
                    ->select('b2b_users.username as name', 'odemebildirim.*', 'odemebildirim.id as id')
                    ->orderBy('odemebildirim.id', 'desc')
                    ->limit(20)
                    ->get();

                return view('panel.home', compact('siparisler', 'siparis', 'bayi', 'havale'));
            } else {
                return view('front.welcome');
            }
        } else {
            // Oturum Açılmadıysa
            return redirect()->route('login');
        }
    } catch (\Exception $e) {
        \Log::error("Hata: " . $e->getMessage());
        return response()->view('errors.custom', [], 500);
    }
}



    public function login(Request $request){
        // b2b_users tablosunda seviye yok, role var
        // Admin girişi için role kontrolü yapılmalı
        $login = $request->email;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        if(Auth::attempt([$field => $login, 'password' => $request->password, 'status' => 1])){
            $user = Auth::user();
            // Admin kontrolü - role = 'Admin' veya id = 1
            if(($user->role == 'Admin' || $user->id == 1)) {
                return redirect('panel');
            } else {
                Auth::logout();
                return redirect('panel/login')->with('error', 'Admin yetkisi gerekli');
            }
        }
        return redirect('panel/login')->with('error', 'Kullanıcı adı veya şifre hatalı');

    }

    public function logout(){

        Auth::logout();
        toastr()->success('Basarılı', 'Çıkış İşlemi Başarıyla Gerçekleştirildi');
        return redirect('panel/login');

    }

    public function adminyetki(Request $request, $id) {
        $roller = DB::table('yetkiler')
            ->leftJoin('yonetici_yetki','yonetici_yetki.modul','=','yetkiler.yetki_id')
            ->where('yonetici_yetki.uye',$id)
            ->get();
        return view('panel.ayarlar.admin-role', compact('roller'));
    }
    public function roltanim(Request $request){
        if($request->durum == '1'){
            $durum = '0';
        }
        else { $durum = '1'; }
        $rec = DB::table('yonetici_yetki')->where('yid',$request->yid)->update(
            [
                'durum' => $durum
            ]
        );
        echo $durum;
    }
}
