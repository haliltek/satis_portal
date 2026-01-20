<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class BankaController extends Controller
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
        $bankalar = DB::table('bankalar')->get();
        return view('panel.bankalar.index', compact('bankalar'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.bankalar.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::table('bankalar')->insert(
            [
                'banka_adi' => $request->name,
                'unvan' => $request->unvan,
                'sube'=>$request->sube,
                'iban'=>$request->iban,
                'hesapno'=>$request->hesapno
            ]
        );
        toastr()->success('Basarılı', 'Yeni Banka Eklendi');
        return redirect('panel/bankalar');
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
        $banka =DB::table('bankalar')->find($id);
        $name = $banka->banka_adi;
        $sube = $banka->sube;
        $hesap = $banka->hesapno;
        $iban = $banka->iban;
        $unvan = $banka->unvan;
        $hesapno = $banka->hesapno;


        return view('panel.bankalar.edit', compact('id', 'name', 'sube', 'hesap', 'iban', 'unvan','hesapno'));
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
        $affected = DB::table('bankalar')
            ->where('id', $id)
            ->update([
                'banka_adi' => $request->name,
                'sube'=>$request->sube,
                'unvan'=>$request->unvan,
                'iban'=>$request->iban,
                'hesapno'=>$request->hesapno
            ]);

        toastr()->success('Basarılı', 'Banka Bilgileri Başarıyla Güncellendi');
        return redirect('panel/bankaduzenle/'.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('bankalar')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Banka Başarıyla Silindi');
        return redirect('panel/bankalar');
    }
}
