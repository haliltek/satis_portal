<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SozlesmeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){

        $sozlesme = DB::table('sozlesmeler')->get();
        return view('panel.sozlesmeler.index', compact('sozlesme'));
    }

    public function edit($id){

        $sozlesme = DB::table('sozlesmeler')->where('sozlesme_id', $id)->first();
        $metin = $sozlesme->sozlesme_metni ?? $sozlesme->metin ?? '';
        $baslik = $sozlesme->sozlesme_adi ?? $sozlesme->sozlesmeadi ?? '';
        return view('panel.sozlesmeler.edit', compact('metin', 'id', 'baslik'));

    }

    public function update(Request $request, $id)
    {
        $affected = DB::table('sozlesmeler')
            ->where('sozlesme_id', $id)
            ->update(['sozlesme_metni' => $request->metin]);


        toastr()->success('Basarılı', 'Sözleşme Başarıyla Güncellendi');
        return redirect('panel/sozlesmeduzenle/'.$id);
    }
}
