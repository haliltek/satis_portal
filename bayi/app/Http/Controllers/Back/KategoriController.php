<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class KategoriController extends Controller
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
        $kategoriler = DB::table('kategoriler')->where('ust_id', 0)->get();

        return view('panel.kategoriler.index', ['kategoriler'=> $kategoriler]);
    }

    public function altkategoriler($id)
    {
        $kategoriler = DB::table('kategoriler')->where('ust_id', $id)->get();

        return view('panel.kategoriler.index', ['kategoriler'=> $kategoriler]);
    }

    public function altgetir(Request $request)
    {
        $id= $request->kat;
        $kategoriler = DB::table('kategoriler')->where('ust_id', $id)->get();
        $toplam = count($kategoriler);

        if($toplam==0){
            return 0;
        }else{
            return $kategoriler;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $kategoriler = DB::table('kategoriler')->where('ust_id', 0)->get();

        return view('panel.kategoriler.create', ['kategoriler'=> $kategoriler]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {



        if($request->alt !=0){
            $agac = "$request->ust , $request->alt";

                DB::table('kategoriler')->insert(
                ['kategori_adi' => $request->kategori, 'ust_id'=> $request->alt, 'kategori_agac'=> $agac]
            );
        }else{

            $agac = "$request->ust ";

            DB::table('kategoriler')->insert(
                ['kategori_adi' => $request->kategori, 'ust_id'=> $request->ust, 'kategori_agac'=> $agac]
            );
        }


        toastr()->success('Basarılı', 'Yeni Kategori Eklendi');

        if($request->ust==0){
            return redirect('panel/kategoriler');
        }else if($request->ust > 0 and $request->alt==0){
            return redirect('panel/kategoriler/'.$request->ust);
        }else if($request->alt > 0){
            return redirect('panel/kategoriler/'.$request->alt);
        }else{
            return redirect('panel/kategoriler');
        }

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
        $kategoriler = DB::table('kategoriler')->where('ust_id', 0)->get();
        $kategori = DB::table('kategoriler')->find($id);

        $katadi = $kategori->kategori_adi;
        $ust =$kategori->ust_id;

        return view('panel.kategoriler.edit', compact('katadi','id', 'kategoriler', 'ust'));

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
        $kat = $request->kategori;
        $ust = $request->ust;
        $alt = $request->alt;

        if($ust==0){
            $affected = DB::table('kategoriler')
                ->where('id', $id)
                ->update(['kategori_adi' => $kat]);
        }else if($alt=="" and $ust > 0){
            $agac = "$request->ust ";
            $affected = DB::table('kategoriler')
                ->where('id', $id)
                ->update(['kategori_adi' => $kat, 'ust_id'=> $ust, 'kategori_agac'=>$agac]);
        }else{
            $agac = "$request->ust , $request->alt";
            $affected = DB::table('kategoriler')
                ->where('id', $id)
                ->update(['kategori_adi' => $kat, 'ust_id'=> $alt, 'kategori_agac'=>$agac]);
        }

        toastr()->success('Basarılı', 'Kategori Bilgileri Başarıyla Güncellendi');
        return redirect('panel/kategoriduzenle/'.$id);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('kategoriler')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Kategori Başarıyla Silindi');
        return redirect('panel/kategoriler');
    }
}
