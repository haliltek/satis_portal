<?php

namespace App\Http\Controllers\Back;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use DB;

use App\User;
use Illuminate\Support\Facades\Auth;

class UyeController extends Controller
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
        $adminler = DB::table('users')->where('seviye', 1)->get();
        return view('panel.uyeler.index', ['adminler'=> $adminler]);
    }
    public function roller()
    {
        $adminler = DB::table('users')->where('seviye', 1)->get();
        return view('panel.ayarlar.roller', compact('adminler'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('panel.uyeler.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $sifre = $request->sifre;
        $sifre = bcrypt($sifre);

        $insert = DB::table('users')->insertGetId(
            ['email' => $request->email, 'password' => $sifre, 'name'=> $request->ad, 'seviye'=>1]
        );
        if($insert){
            $yetki = DB::table('yetkiler')->get();
            foreach($yetki as $show) {
                DB::table('yonetici_yetki')->insert(
                    [
                        'uye' => $insert,
                        'modul' => $show->yetki_id,
                        'durum' => '1'
                    ]
                );
            }
        }
        toastr()->success('Basarılı', 'Yeni Admin Eklendi');
        return redirect('panel/adminler');
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
        $uye = DB::table('users')->find($id);
        $name = $uye->name;
        $email = $uye->email;
        $uyeid = $uye->id;

        return view('panel.uyeler.edit', compact('name','email', 'uyeid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $name = $request->ad;
        $mail = $request->email;
        $uye = $request->uye;
        $sifre = $request->sifre;
        $sifre = bcrypt($sifre);


        $affected = DB::table('users')
            ->where('id', $uye)
            ->update(['email' => $mail, 'name'=> $name, 'password'=>$sifre]);

        toastr()->success('Basarılı', 'Admin Bilgileri Başarıyla Güncellendi');
        return redirect('panel/adminduzenle/'.$uye);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        toastr()->success('Basarılı', 'Admin Başarıyla Silindi');
        return redirect('panel/adminler');
    }

}
