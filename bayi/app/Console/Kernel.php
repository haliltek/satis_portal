<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*
        $schedule->call(function () {


            $url = '';
            $readJson = file_get_contents($url);
            $data = json_decode($readJson,true);


            foreach($data as $key => $get) {
                $productquery = DB::table('urunler')
                    ->where('urun_kodu',$get['product_code'])
                    ->get();
                $product_count = count($productquery);
                if($product_count < '1') {


                    $insert = DB::table('urunler')->insertGetId(
                        [
                            'urun_adi' => $get['product_name_TR'],
                            'urun_kodu' => $get['product_code'],
                            'durum' => '1',
                            'stok' => '1000',
                            'ust_kat' => $get['category']['category_id'],
                            'ureticino' => '1',
                            'barkod' => $get['product_barcode']

                        ]);

                    if($insert) {
                        foreach($data[$key]['adaptive_vehicles'] as $show) {

                            DB::table('urun_ozellikleri')->insert(
                                [
                                    'marka' => $show['model_id'],
                                    'model' => $show['brand_id'],
                                    'urun' => $insert

                                ]
                            );

                            $brand_query = DB::table('urun_marka')
                                ->where('marka',$show['model_id'])
                                ->where('urun',$insert)
                                ->get();
                            $count = count($brand_query);
                            if($count < '1') {
                                DB::table('urun_marka')->insert(
                                    [
                                        'marka' => $show['model_id'],
                                        'urun' => $insert
                                    ]
                                );
                            }

                            DB::table('urun_model')->insert(
                                [
                                    'urun' => $insert,
                                    'model' => $show['brand_id']

                                ]
                            );

                        }
                        DB::table('urun_fiyatlari')->insert(
                            [
                                'urun' => $insert,
                                'fiyat' => $get['price'],
                                'fiyat_id' => '1'
                            ]); // end insert price
                        DB::table('urun_resimler')->insert(
                            [
                                'urun' => $insert,
                                'resim' => $get['product_code'].'.jpg'
                            ]); // end insert image
                        DB::table('urun_tanimlari')->insert(
                            [
                                'durum'=> '1',
                                'tanimadi' => 'Ağırlık',
                                'tanim_deger' => $get['product_weight'],
                                'urun' => $insert
                            ]); // end insert uruntanım


                        $oems = explode(',', $get['product_oems']);
                        foreach ($oems as $oem) {
                            DB::table('urun_oem')->insert(
                                [
                                    'urun' => $insert,
                                    'oem' => $oem
                                ]);
                        } // end oem foreach



                    }

                    echo $insert.' - '.$get['product_name_TR'].'<br>';
                }
                else {
                    DB::table('urunler')
                        ->leftJoin('urun_fiyatlari','urun_fiyatlari.urun','=','urunler.id')
                        ->where('urunler.urun_kodu',$show['product_code'])
                        ->update(
                            [
                                'fiyat' => $get['price']
                            ]
                        );
                }
            }

        })->dailyAt('02:00');
        */
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
