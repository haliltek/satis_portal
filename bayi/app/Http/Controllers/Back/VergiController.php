<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class VergiController extends Controller
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
        $vergiler = DB::table('vergi_oranlari')->get();
        return view('panel.vergi.index', compact('vergiler'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel/vergi.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('vergi_oranlari')->insert(
            ['name' => $request->name, 'oran' => $request->oran]
        );
        toastr()->success('Basarılı', 'Yeni Vergi Eklendi');
        return redirect('panel/vergiayarlari');
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
        $vergi = DB::table('vergi_oranlari')->find($id);

        $name = $vergi->name;
        $oran = $vergi->oran;

        return view('panel.vergi.edit', compact('id','name', 'oran'));
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
        $affected = DB::table('vergi_oranlari')
            ->where('id', $id)
            ->update(['name' => $request->name, 'oran'=> $request->oran]);

        toastr()->success('Basarılı', 'Vergi Bilgileri Başarıyla Güncellendi');
        return redirect('panel/vergiduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('vergi_oranlari')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Vergi Oranı Başarıyla Silindi');
        return redirect('panel/vergiayarlari');
    }
}
