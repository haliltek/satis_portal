<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use DB;
class Bildirim extends Component
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
        $sayi = count($this->bildirim());
        $bildirim = $this->bildirim();
        return view('components.bildirim',compact('bildirim','sayi'));

    }


    public function bildirim(){

        $userid =Auth::user()->id;
        $bildirim = DB::table('bildirim')
            ->leftJoin('users','users.id','=','bildirim.uye')
            ->where('view', '0')
            ->select('users.name','bildirim.*')
            ->orderBy('bildirim.id','desc')
            ->get();
        return $bildirim;
    }
}
