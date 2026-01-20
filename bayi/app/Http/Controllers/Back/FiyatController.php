<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class FiyatController extends Controller
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
        $fiyatlar = DB::table('fiyat_ayar')->get();
        return view('panel.fiyat.index', compact('fiyatlar'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pbirim = DB::table('para_birimleri')->where('durum',1)->get();
        return view('panel.fiyat.create', compact('pbirim'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $birimci = DB::table('para_birimleri')->find($request->birim);
        $birim = $birimci->sembol;

        DB::table('fiyat_ayar')->insert(
            ['name' => $request->name, 'para_birimi' => $request->birim, 'birim'=> $birim]
        );
        toastr()->success('Basarılı', 'Yeni Fiyat Eklendi');
        return redirect('panel/fiyatayarlari');
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
        $pbirim = DB::table('para_birimleri')->where('durum',1)->get();
        $fiyat = DB::table('fiyat_ayar')->find($id);
        $name = $fiyat->name;
        $birimi = $fiyat->para_birimi;

        return view('panel.fiyat.edit', compact('pbirim', 'name', 'birimi', 'id'));
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

        $birimci = DB::table('para_birimleri')->find($request->birim);
        $birim = $birimci->sembol;

        $affected = DB::table('fiyat_ayar')
            ->where('id', $id)
            ->update(['name' => $request->name, 'para_birimi'=> $request->birim, 'birim'=> $birim]);

        toastr()->success('Basarılı', 'Fiyat Bilgileri Başarıyla Güncellendi');
        return redirect('panel/fiyatduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('fiyat_ayar')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Fiyat Başarıyla Silindi');
        return redirect('panel/fiyatayarlari');
    }
}
