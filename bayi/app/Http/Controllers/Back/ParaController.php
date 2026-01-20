<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class ParaController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $birim = DB::table('para_birimleri')->get();

        return view('panel.para.index', compact('birim'));
    }


    public function update(Request $request)
    {
        $id= $request->id;
        $kur = $request->kur;

        $affected = DB::table('para_birimleri')
            ->where('id', $id)
            ->update(['kur' => $kur]);
    }

    public function birimtopluguncelle()
    {
        $birimler = DB::table('para_birimleri')->get();
        foreach($birimler as $br){

            $kur = $br->kur;
            $id= $br->id;
            $birim = $br->birim;

            $yenikur = doviz($birim, 1);

            $affected = DB::table('para_birimleri')
                ->where('id', $id)
                ->update(['kur' => $yenikur]);


        }
    }


}
