<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SehirController extends Controller
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
        $iller = DB::table('iller')->get();
        return view('panel.sehirler.index', compact('iller'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.sehirler.create');
    }

    public function ilceekle($id)
    {
        return view('panel.sehirler.ilceekle', compact('id'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('iller')->insert(
            ['il_adi' => $request->il]
        );
        toastr()->success('Basarılı', 'Yeni İl Eklendi');
        return redirect('panel/iller');
    }

    public function ilceeklestore(Request $request)
    {
        DB::table('ilceler')->insert(
            ['il' => $request->il, 'ilce_adi'=>$request->ilce]
        );
        toastr()->success('Basarılı', 'Yeni İl Eklendi');
        return redirect('panel/ilceler/'.$request->il);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ilceler($id)
    {
        $ilceler = DB::table('ilceler')->where('il', $id)->get();
        return view('panel.sehirler.ilceler', compact('ilceler', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ils = DB::table('iller')->find($id);
        $il = $ils->il_adi;

        return view('panel.sehirler.edit', compact('id', 'il'));
    }

    public function ilceduzenle($id)
    {
        $ilceler = DB::table('ilceler')->find($id);
        $ili = $ilceler->il;
        $ilceadi = $ilceler->ilce_adi;

        $iller = DB::table('iller')->get();

        return view('panel.sehirler.ilceduzenle', compact('id', 'ili', 'ilceadi', 'iller'));
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
        $affected = DB::table('iller')
            ->where('id', $id)
            ->update(['il_adi' => $request->il]);

        toastr()->success('Basarılı', 'İl Bilgileri Başarıyla Güncellendi');
        return redirect('panel/ilduzenle/'.$id);
    }


    public function ilceupdate(Request $request, $id)
    {
        $affected = DB::table('ilceler')
            ->where('id', $id)
            ->update(['ilce_adi' => $request->ilce, 'il'=>$request->il]);

        toastr()->success('Basarılı', 'İlçe Bilgileri Başarıyla Güncellendi');
        return redirect('panel/ilceduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('iller')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'İl Başarıyla Silindi');
        return redirect('panel/iller');
    }

    public function ilcesil($id)
    {
        $ilce = DB::table('ilceler')->find($id);
        $ilid = $ilce->il;

        DB::table('ilceler')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'İlçe Başarıyla Silindi');
        return redirect('panel/ilceler/'.$ilid);
    }
}
