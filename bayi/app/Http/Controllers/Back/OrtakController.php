<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;

class OrtakController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function ayarlar(){

        $id=1;
        $ayar = DB::table('ayarlar')->find($id);

        $siteadi    = $ayar->site_adi;
        $site_tel   = $ayar->site_tel;
        $site_mail  = $ayar->site_mail;
        $simail     = $ayar->siparis_mail;
        $logo       = $ayar->logo;
        $kurumsal   = $ayar->kurumsal;
        $firma_adi  = $ayar->firma_adi;
        $unvan      = $ayar->unvan;
        $fax        = $ayar->fax;
        $gsm        = $ayar->gsm;
        $adres      = $ayar->adres;
        $desc       = $ayar->descript;
        $keyw       = $ayar->keyword;
        $vergi_d       = $ayar->vergi_d;
        $vergi_no       = $ayar->vergi_no;
        $mersis_no       = $ayar->mersis_no;

        return view('panel.ayarlar.siteayar', compact(
            'siteadi',
            'site_tel',
            'site_mail',
            'simail',
            'logo',
            'kurumsal',
            'firma_adi',
            'unvan',
            'fax',
            'gsm',
            'adres',
            'desc',
            'keyw',
            'vergi_d',
            'vergi_no',
            'mersis_no'
        ));
    }


    public function ayarduzenle(Request $request, $id){

		/*
		$allowedfileExtension=['jpeg','jpg','png'];

        if($request->hasFile('image')){
            $file_name = time() . "." . $request->image->getClientOriginalExtension();

			$check=in_array($file_name,$allowedfileExtension);
			
			if($check){
				$request->image->move(public_path('uploads/ayarlar'), $file_name);
			}else{
				echo "Siktir git lan at ağazlı yavşak puşt";
			}
            

        }else{
            $file_name = $request->resim;
        }
*/
	$file_name = $request->resim;

        $affected = DB::table('ayarlar')
            ->where('id', $id)
            ->update([
                'site_adi'      => $request->title,
                'descript'      => $request->desc,
                'keyword'       => $request->keyw,
                'firma_adi'     => $request->firmaadi,
                'unvan'         => $request->unvan,
                'site_tel'      => $request->telefon,
                'fax'           => $request->fax,
                'gsm'           => $request->gsm,
                'site_mail'     => $request->sitemail,
                'siparis_mail'  => $request->sipmail,
                'adres'         => $request->adres,
                'kurumsal'      => $request->kurumsal,
                'vergi_d'      => $request->vergi_d,
                'vergi_no'      => $request->vergi_no,
                'mersis_no'      => $request->mersis_no,
                'logo'          => $file_name
            ]);


        toastr()->success('Basarılı', 'Ayarlar Başarıyla Güncellendi');
        return redirect('panel/ayarlar/');

    }

    public function durumdegis(Request $request){
        $durum = $request->durum;
        $table = $request->table;
        $id = $request->id;

        $affected = DB::table($table)
            ->where('id', $id)
            ->update(['durum' => $durum]);
    }

    public function kampdegis(Request $request){
        $durum = $request->durum;
        $table = $request->table;
        $id = $request->id;

        $affected = DB::table($table)
            ->where('id', $id)
            ->update(['kampanya' => $durum]);
    }

    public function kapakyap(Request $request){
        $durum = $request->durum;
        $urun = $request->urun;
        $rid = $request->id;

        $affected = DB::table('urun_resimler')
            ->where('urun', $urun)
            ->update(['kapak' => 0]);

        $affected = DB::table('urun_resimler')
            ->where('id', $rid)
            ->update(['kapak' => 1]);
    }
    public function changenoticestat(Request $request){
        $id = $request->id;
        $upnotice = DB::table('bildirim')->where('id',$id)->update([ 'view' => '1' ]);
        if($upnotice) {
            echo '1';
        }
    }
    public function islemler(){
        $kategoriler = DB::table('kategoriler')->get();
        return view('panel.ayarlar.toplu-islemler',compact('kategoriler'));
    }

    public function tedarik() {
        $tedarikciler = DB::table('tedarikciler')->get();
        return view('panel.tedarik.index',compact('tedarikciler'));
    }

    public function tedarikciekle(Request $request) {
        $tedarikci = $request->tedarikci;
        $tekle = DB::table('tedarikciler')->insert(['tedarikci' => $tedarikci]);
        if($tekle){
            echo '1';
        }
    }

    public function allnotice(){
        $notice = DB::table('bildirim')->leftJoin('users','users.id','=','bildirim.uye')
            ->select('bildirim.*','users.firma_unvani')
            ->orderBy('bildirim.id','desc')
            ->get();
        return $notice;
    }
    public function havalebildirim() {
        $havale = DB::table('odemebildirim')
            ->leftJoin('users','users.id','=','odemebildirim.uye')
            ->select('users.name','odemebildirim.*','odemebildirim.id as id')
            ->orderBy('odemebildirim.id','desc')
            ->limit('20')
            ->get();
        return view('panel.bildirim.havalebildirim', compact('havale'));
    }
    public function feedback() {
        $feed = DB::table('feedback')->get();
        return view('panel.bildirim.feedback', compact('feed'));
    }
    public function feedmessage(Request $request, $id)
    {
        $msg = DB::table('feedback')->where('id',$id)->first();
        return $msg->mesaj;
    }
}
