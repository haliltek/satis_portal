<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class OdemeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $yontem = DB::table('odeme_yontemleri')->get();

        return view('panel.odeme.index', compact('yontem'));
    }

    public function iyzico()
    {
        $yontem = DB::table('odeme_yontemleri')->find(61);
        $secret_id = $yontem->secret_id;
        $secret_key = $yontem->secret_key;
        $back_url =$yontem->back_url;

        return view('panel.odeme.iyzico', compact('secret_id', 'secret_key', 'back_url'));
    }

    public function iyzico_update(Request $request, $id)
    {
        $affected = DB::table('odeme_yontemleri')
            ->where('id', $id)
            ->update(['secret_id' => $request->secret, 'secret_key'=> $request->key, 'back_url'=>$request->back]);

        toastr()->success('Basarılı', 'İyzico Bilgileri Başarıyla Güncellendi');
        return redirect('panel/iyzico/');
    }

    public function odemedetay(Request $request,$id) {
        //$oid = $request->oid;
        $query = DB::table('odemebildirim')
            ->leftJoin('users','users.id','=','odemebildirim.uye')
            ->leftJoin('uye_siparisler','uye_siparisler.uye','=','odemebildirim.uye')
            ->where('odemebildirim.id',$id)
            ->select('odemebildirim.*','users.name','uye_siparisler.geneltoplam','odemebildirim.id as id')
            ->get();
        return $query;
    }
    public function havaleonay(Request $request) {
        $oid = $request->oid;
        $odemedurum = DB::table('odemebildirim')
            ->where('id',$oid)
            ->update(
                [
                  'onay' => '1'
                ]
            );
        if($odemedurum) { echo '1'; }
    }


}
