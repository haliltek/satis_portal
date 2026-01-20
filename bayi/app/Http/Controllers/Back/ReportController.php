<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class ReportController extends Controller
{
    public function testreport() {
        $dateS = Carbon::now()->startOfMonth()->subMonth(3);
        $dateE = Carbon::now()->startOfMonth();
        $TotalSpent = DB::table('orders')
            ->select('total_cost','placed_at')
            ->whereBetween('placed_at',[$dateS,$dateE])
            ->get();
    }
    // report by selected date
    public function doreport(Request $request) {
        $month = $request->month;
        $year = $request->year;
        $query = DB::table('uye_siparisler')
            ->leftJoin('users','users.id','=','uye_siparisler.uye')
            ->whereMonth('tarih', $month)
            ->whereYear('tarih', $year)
            ->get();
        $key = '1';
        $count = count($query);
        $toplam = '0';
        echo '[';
        foreach($query as $show) {
            $toplam += $show->geneltoplam;
            $geneltoplam = number_format($show->geneltoplam, 2, ',', '.').'₺';;
            $tutar = number_format($show->tutar, 2, ',', '.').'₺';;
            $data = array(
              'sip_id' => $show->sip_id,
              'name' => $show->name,
              'tarih' => $show->tarih,
              'tutar' => $tutar,
              'geneltoplam' => $geneltoplam
            );
            echo json_encode($data);
            if($key == $count) {
                $toplam = number_format($toplam, 2, ',', '.').'₺';;
                $total = array(
                    'toplam' => $toplam,
                    'adet' => $count
                );
                echo ',';
                echo json_encode($total);
            }
            else {
                echo ',';
            }
            $key++;
        }
        echo ']';

    }
    public function siparisrapor(){
        return view('panel.raporlar.siparisrapor');
    }

    public function monthlyreport() {
        $date = \Carbon\Carbon::today()->subDays(60);
        $query = DB::table('uye_siparisler')
            ->leftJoin('users','users.id','=','uye_siparisler.uye')
            ->where('created_at','>=',$date)->get();
        return $query;
    }
}
