<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class YilController extends Controller
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
        $yillar = DB::table('yillar')->get();

        return view('panel.yillar.index', ['yillar'=> $yillar]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.yillar.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('yillar')->insert(
            ['yil_adi' => $request->yil]
        );
        toastr()->success('Basarılı', 'Yeni Yıl Eklendi');
        return redirect('panel/yillar');
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
        $yil = DB::table('yillar')->find($id);
        $yiladi = $yil->yil_adi;

        return view('panel.yillar.edit', compact('yiladi', 'id'));
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
        $yil = $request->yil;

        $affected = DB::table('yillar')
            ->where('id', $id)
            ->update(['yil_adi' => $yil]);

        toastr()->success('Basarılı', 'Yıl Bilgileri Başarıyla Güncellendi');
        return redirect('panel/yilduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('yillar')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Yıl Başarıyla Silindi');
        return redirect('panel/yillar');
    }
}
