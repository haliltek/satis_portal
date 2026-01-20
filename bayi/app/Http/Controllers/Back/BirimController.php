<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class BirimController extends Controller
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
        $birimler = DB::table('birimler')->get();
        return view('panel.birimler.index',compact('birimler'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.birimler.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('birimler')->insert(
            ['name' => $request->name]
        );
        toastr()->success('Basarılı', 'Yeni Birim Eklendi');
        return redirect('panel/birimler');
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
        $birimler = DB::table('birimler')->find($id);
        $name = $birimler->name;
        return view('panel.birimler.edit', compact('id', 'name'));
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
        $affected = DB::table('birimler')
            ->where('id', $id)
            ->update(['name' => $request->name]);

        toastr()->success('Basarılı', 'Birim Bilgileri Başarıyla Güncellendi');
        return redirect('panel/birimduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('birimler')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Birim Başarıyla Silindi');
        return redirect('panel/birimler');
    }
}
