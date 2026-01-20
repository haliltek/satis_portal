<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SeriController extends Controller
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


        $seriler = DB::table('motorhacmi')
            ->join('markalar', 'motorhacmi.marka', '=', 'markalar.marka_id')
            ->join('modeller', 'motorhacmi.model', '=', 'modeller.id')
            ->select('motorhacmi.*', 'markalar.marka_adi', 'modeller.model_adi', 'motorhacmi.motor_adi', 'motorhacmi.id')
            ->get();

        return view('panel.seriler.index', ['seriler'=>$seriler]);
    }

    public function modelseri($id)
    {

        $seriler = DB::table('motorhacmi')
            ->join('markalar', 'motorhacmi.marka', '=', 'markalar.marka_id')
            ->join('modeller', 'motorhacmi.model', '=', 'modeller.id')
            ->select('motorhacmi.*', 'markalar.marka_adi', 'modeller.model_adi', 'motorhacmi.motor_adi', 'motorhacmi.id')
            ->where('model', $id)
            ->get();

        return view('panel.seriler.index', ['seriler'=>$seriler]);
    }


    public function modelgetir(Request $request){

        $marka = $request->marka;

        $modeller = DB::table('modeller')->where('marka', $marka)->get();
        return($modeller);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $markalar = DB::table('markalar')->get();
        return view('panel.seriler.create', ['markalar'=> $markalar]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('motorhacmi')->insert(
            ['motor_adi' => $request->seri, 'marka' => $request->marka, 'model'=> $request->model]
        );
        toastr()->success('Basarılı', 'Yeni Seri Eklendi');
        return redirect('panel/seriler');
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
        $markalar = DB::table('markalar')->get();
        $seri = DB::table('motorhacmi')->find($id);

        $motoradi = $seri->motor_adi;
        $id = $seri->id;
        $markaid = $seri->marka;
        $modelid = $seri->model;

        $models = DB::table('modeller')->find($modelid);
        $modeladi = $models->model_adi;

        return view('panel.seriler.edit', compact('motoradi','id', 'markalar', 'markaid', 'modelid', 'modeladi'));
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
        $model = $request->model;
        $marka = $request->marka;
        $motor = $request->seri;

        $affected = DB::table('motorhacmi')
            ->where('id', $id)
            ->update(['model' => $model, 'marka'=> $marka, 'motor_adi'=>$motor]);

        toastr()->success('Basarılı', 'Seri Bilgileri Başarıyla Güncellendi');
        return redirect('panel/seriduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('motorhacmi')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Seri Başarıyla Silindi');
        return redirect('panel/seriler');
    }
}
