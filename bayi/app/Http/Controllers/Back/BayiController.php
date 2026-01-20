<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;

use App\User;
use Illuminate\Support\Facades\Auth;
class BayiController extends Controller
{



    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bayiler = DB::table('users')->where('seviye', 2)->get();
        return view('panel.bayiler.index', ['bayiler'=> $bayiler]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fiyat = DB::table('fiyat_ayar')->where('durum', 1)->get();
        $iller = DB::table('iller')->get();

        return view('panel.bayiler.create', compact('fiyat','iller'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $sifre = $request->sifre;
        $sifre = bcrypt($sifre);
        $tarih = date('d.m.Y H:i:s');

        DB::table('users')->insert(
            [
                'email' => $request->email,
                'password' => $sifre,
                'name'=> $request->name,
                'seviye'=>2,
                'cep_telefonu'=>$request->cep,
                'sirket_telefonu'=>$request->sirkettel,
                'sirket_adres'=>$request->sirketadres,
                'sirket_sube'=>$request->sirketsube,
                'pozisyon'=>$request->pozisyon,
                'yetki'=>$request->yetki,
                'firma_unvani'=>$request->firmaunvan,
                'vno'=>$request->vno,
                'vd'=>$request->vd,
                'mernis'=>$request->mernis,
                'firma_sahibi'=>$request->firmasahibi,
                'muhasebe_mail'=>$request->muhasebemail,
                'bankahesapadi'=>$request->bankahesapad,
                'sehir'=>$request->sehir,
                'iban'=>$request->iban,
                'banka'=>$request->bankaadi,
                'sube'=>$request->bankasube,
                'fiyat_gurubu'=>$request->fiyat,
                'acikhesap'=>$request->acikhesap,
                'acik_hesap_limit'=>$request->acikhesaplimit,
                'hesapno'=>$request->hesapno
            ]
        );
        toastr()->success('Basarılı', 'Yeni Admin Eklendi');
        //sendmail()-newdealer($request->email);
        return redirect('panel/bayiler');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $bayi = DB::table('users')->find($id);
        $name = $bayi->name;
        $mail = $bayi->email;
        $sirkettel =$bayi->sirket_telefonu;
        $cep = $bayi->cep_telefonu;
        $vd = $bayi->vd;
        $vno =$bayi->vno;
        $firmaunvan = $bayi->firma_unvani;
        $sirketadres = $bayi->sirket_adres;
        $sube = $bayi->sirket_sube;
        $pozisyon = $bayi->pozisyon;
        $yetki=$bayi->yetki;
        $fsahip = $bayi->firma_sahibi;
        $mernis = $bayi->mernis;
        $muhasebe_mail = $bayi->muhasebe_mail;
        $bankahesapadi =$bayi->bankahesapadi;
        $iban=$bayi->iban;
        $bsube=$bayi->sube;
        $banka= $bayi->banka;
        $sehir= $bayi->sehir;
        $fiyatgurup =$bayi->fiyat_gurubu;
        $acik_hesap_limit =$bayi->acik_hesap_limit;
        $acikhesap = $bayi->acikhesap;
        $iskonto = $bayi->iskonto;
        $hesapno = $bayi->hesapno;

        $fiyat = DB::table('fiyat_ayar')->where('durum', 1)->get();

        return view('panel.bayiler.edit', compact('name', 'fiyat', 'cep', 'sirkettel', 'cep', 'mail', 'vd', 'vno', 'firmaunvan',
            'sirketadres', 'acik_hesap_limit', 'acikhesap', 'sube', 'fiyatgurup', 'pozisyon', 'yetki', 'fsahip', 'mernis', 'muhasebe_mail', 'bankahesapadi', 'iban', 'bsube', 'banka', 'sehir','iskonto','hesapno', 'id'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $affected = DB::table('users')
            ->where('id', $id)
            ->update([
                'email' => $request->email,
                'name'=> $request->name,
                'cep_telefonu'=>$request->cep,
                'sirket_telefonu'=>$request->sirkettel,
                'sirket_adres'=>$request->sirketadres,
                'sirket_sube'=>$request->sirketsube,
                'pozisyon'=>$request->pozisyon,
                'yetki'=>$request->yetki,
                'firma_unvani'=>$request->firmaunvan,
                'vno'=>$request->vno,
                'vd'=>$request->vd,
                'mernis'=>$request->mernis,
                'firma_sahibi'=>$request->firmasahibi,
                'muhasebe_mail'=>$request->muhasebemail,
                'bankahesapadi'=>$request->bankahesapad,
                'sehir'=>$request->sehir,
                'iban'=>$request->iban,
                'banka'=>$request->bankaadi,
                'sube'=>$request->bankasube,
                'fiyat_gurubu'=>$request->fiyat,
                'acikhesap'=>$request->acikhesap,
                'acik_hesap_limit'=>$request->acikhesaplimit,
                'iskonto'=>$request->iskonto,
                'hesapno'=>$request->hesapno
        ]);

        toastr()->success('Basarılı', 'Admin Bilgileri Başarıyla Güncellendi');
        return redirect('panel/bayiduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Bayi Başarıyla Silindi');
        return redirect('panel/bayiler');
    }

    public function bayidetay(Request $request) {
        $id = $request->id;
        $user = DB::table('users')->where('id',$id)->get();

        $siparis = DB::table('uye_siparisler')->where('uye',$id)->where('durum',2)->get();
        $bsiparis = DB::table('uye_siparisler')->where('uye',$id)->where('durum',1)->get();
        $siparisler = DB::table('uye_siparisler')
            ->leftJoin('uye_siparis_urunler','uye_siparis_urunler.sipid','=','uye_siparisler.sip_id')
            ->leftJoin('kargolar','kargolar.id','=','uye_siparisler.kargo')
            ->select('uye_siparisler.durum as adurum','uye_siparisler.*','kargolar.name')
            ->where('uye_siparisler.uye',$id)
            ->get();
        $siparis_adet = count($siparis);
        $bekleyen_siparis = count($bsiparis);
        $satis = '0';
        foreach($siparis as $hesap) {
            $satis += $hesap->geneltoplam;
        }

        return view('/panel/bayi/bayi', compact('user','siparis_adet','bekleyen_siparis','satis','siparisler'));
    }
    public function editpass(Request $request) {
        $id = $request->id;
        $password = $request->password;
        $password = bcrypt($password);
        $up = DB::table('users')
            ->where('id', $id)
            ->update([
                'password' => $password

            ]);
        if($up) {
            echo '1';
            $user = DB::table('users')->where('id',$id)->first();
                $user_mail = $user->email;
            mailer()-sender($user_mail,'Parolanız değiştirildi','Oturum Aç','http://b2b.acarhortum.com/');
        }
    }
    public function adreslist(){
        $adresler = DB::table('uye_adresler')
            ->leftJoin('users','users.id','=','uye_adresler.uye')
            ->select('uye_adresler.*','users.name','users.firma_unvani')
            ->get();
        return view('/panel/bayiler/adres',compact('adresler'));
    }
    public function adresdurum(Request $request) {
        if($request->durum == '1') { $durum = '0'; }
        else { $durum = '1'; }
        DB::table('uye_adresler')->where('adres_id',$request->adres)->update([ 'durum' => $durum ]);
        echo $durum;
    }
}
