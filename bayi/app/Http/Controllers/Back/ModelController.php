<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class ModelController extends Controller
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

        $modeller = DB::table('modeller')
            ->leftJoin('markalar', 'modeller.marka', '=', 'markalar.marka_id')
            ->get();

        return view('panel.modeller.index', ['modeller' => $modeller]);
    }


    public function markamodel($id)
    {

        $modeller = DB::table('modeller')
            ->leftJoin('markalar', 'modeller.marka', '=', 'markalar.marka_id')
            ->where('marka', $id)
            ->get();

        return view('panel.modeller.index', ['modeller' => $modeller]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $markalar = DB::table('markalar')->orderBy('marka_adi','asc')->get();
        return view('panel.modeller.create', ['markalar'=> $markalar]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('modeller')->insert(
            ['model_adi' => $request->model, 'marka' => $request->marka,'durum' => '1']
        );
        toastr()->success('Basarılı', 'Yeni Model Eklendi');
        return redirect('panel/modeller');
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
        $model = DB::table('modeller')->find($id);

        $modeladi = $model->model_adi;
        $id = $model->id;
        $markaid = $model->marka;

       return view('panel.modeller.edit', compact('modeladi','id', 'markalar', 'markaid'));
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


        $affected = DB::table('modeller')
            ->where('id', $id)
            ->update(['model_adi' => $model, 'marka'=> $marka]);

        toastr()->success('Basarılı', 'Model Bilgileri Başarıyla Güncellendi');
        return redirect('panel/modelduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('modeller')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Model Başarıyla Silindi');
        return redirect('panel/modeller');
    }
}
