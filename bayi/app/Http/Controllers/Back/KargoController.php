<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class KargoController extends Controller
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
        $kargolar = DB::table('kargolar')->get();
        return view('panel.kargo.index', compact('kargolar'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.kargo.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('kargolar')->insert(
            ['name' => $request->name, 'ucret' => $request->ucret, 'tur'=>$request->tur]
        );
        toastr()->success('Basarılı', 'Yeni Kargo Eklendi');
        return redirect('panel/kargolar');
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
        $kargo = DB::table('kargolar')->find($id);
        $ucret =$kargo->ucret;
        $tur = $kargo->tur;
        $name = $kargo->name;
        $url = $kargo->url;

        return view('panel.kargo.edit', compact('name', 'ucret', 'tur', 'id','url'));
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
        $affected = DB::table('kargolar')
            ->where('id', $id)
            ->update(['name' => $request->name, 'ucret'=> $request->ucret, 'tur'=>$request->tur,'url'=>$request->url]);

        toastr()->success('Basarılı', 'Kargo Bilgileri Başarıyla Güncellendi');
        return redirect('panel/kargoduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('kargolar')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Kargo Başarıyla Silindi');
        return redirect('panel/kargolar');
    }
    public function kargolist() {
        $kargo = DB::table('kargolar')->get();
        return $kargo;
    }
    public function kargolist2() {
        $kargo = DB::table('kargolar')->where('durum','1')->get();
        return $kargo;
    }
    public function kargobilgi(Request $request) {
        $kargo = DB::table('uye_siparisler')
            ->leftJoin('kargolar','kargolar.id','=','uye_siparisler.kargo')
            ->where('uye_siparisler.sip_id',$request->sid)
            ->get();
        return $kargo;
    }
    public function kargodurum(Request $request) {
        $sid = $request->sid;
        if($request->kargofirma != '') {
            $veri = (
                [
                    'kargo_durum' => $request->kargodurum,
                    'kargotakip'=> $request->kargotakip,
                    'kargo'=>$request->kargofirma
                ]
            );
        }
        else {
            $veri = (
                [
                    'kargo_durum' => $request->kargodurum,
                    'kargotakip'=> $request->kargotakip
                ]
            );
        }

        $guncelle = DB::table('uye_siparisler')
            ->where('sip_id', $sid)
            ->update($veri);
        if($guncelle) {
            echo '1';
        }
        else { echo '0'; }
    }
}
