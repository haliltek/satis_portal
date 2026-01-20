<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class MarkaController extends Controller
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

    $markalar = DB::table('markalar')->get();
    return view('panel.markalar.index', ['markalar' => $markalar]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.markalar.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('markalar')->insert(
            ['marka_adi' => $request->marka]
        );
        toastr()->success('Basarılı', 'Yeni Marka Eklendi');
        return redirect('panel/markalar');
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

        $markalar = DB::table('markalar')->where('marka_id', $id)->get();
        $id = $id;
        return view('panel.markalar.edit', ['markalar'=> $markalar, 'id'=>$id]);
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
        $marka = $request->marka;
        $id = $request->id;


        $affected = DB::table('markalar')
            ->where('marka_id', $id)
            ->update(['marka_adi' => $marka]);

        toastr()->success('Basarılı', 'Marka Bilgileri Başarıyla Güncellendi');
        return redirect('panel/markaduzenle/'.$id);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('markalar')->where('marka_id', $id)->delete();
        toastr()->success('Basarılı', 'Marka Başarıyla Silindi');
        return redirect('panel/markalar');
    }


    public function markaresim(Request $request){

        $urunid = $request->urunid;

        if($request->hasFile('image')){
            $imagename = $urunid.'.'.$request->image->getClientOriginalExtension();

            $request->image->move(public_path('uploads/markalar'), $imagename);

            DB::table('urun_resim')->insert(
                ['resim' => $imagename, 'urun' => $urunid]
            );
            toastr()->success('Basarılı', 'Yeni Resim Eklendi');
            return redirect('panel/urunduzenle/'.$urunid);
        }
    }
}
