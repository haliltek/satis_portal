<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use DB;

class Rightbar extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $userid =Auth::user()->id;

        $rol1 = DB::table('yonetici_yetki')->where('modul','1')->where('uye',$userid)->get();
        $rol2 = DB::table('yonetici_yetki')->where('modul','2')->where('uye',$userid)->get();
        $rol3 = DB::table('yonetici_yetki')->where('modul','3')->where('uye',$userid)->get();
        $rol4 = DB::table('yonetici_yetki')->where('modul','4')->where('uye',$userid)->get();
        $rol5 = DB::table('yonetici_yetki')->where('modul','5')->where('uye',$userid)->get();
        $rol6 = DB::table('yonetici_yetki')->where('modul','6')->where('uye',$userid)->get();
        $rol9 = DB::table('yonetici_yetki')->where('modul','9')->where('uye',$userid)->get();
        $rol10 = DB::table('yonetici_yetki')->where('modul','10')->where('uye',$userid)->get();

        return view('components.rightbar',compact('rol1','rol2','rol3','rol4','rol5','rol6','rol9','rol10'));

    }



}
