<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class UrunController extends Controller
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

        $markalar = DB::table('markalar')->orderBy('marka_adi','asc')->get();
        $yillar = DB::table('yillar')->get();
        $id = '1';
        $markamodel = DB::table('urun_ozellikleri')
            ->leftJoin('markalar', 'urun_ozellikleri.marka', '=', 'markalar.marka_id')
            ->leftJoin('modeller', 'urun_ozellikleri.model', '=', 'modeller.id')
            ->leftJoin('motorhacmi', 'urun_ozellikleri.motor', '=', 'motorhacmi.id')
            ->leftJoin('yillar', 'urun_ozellikleri.yil', '=', 'yillar.id')
            ->select('urun_ozellikleri.id', 'urun_ozellikleri.yil', 'markalar.marka_adi', 'modeller.model_adi', 'motorhacmi.motor_adi', 'yillar.yil_adi')
            ->where('urun', $id)
            ->get();

        $urunler = DB::table('urunler')
            ->leftJoin('kategoriler', 'kategoriler.id', '=', 'urunler.ust_kat')
            ->leftJoin('urun_resimler', 'urunler.id', '=', 'urun_resimler.urun')
            ->select('urun_resimler.resim','kategoriler.kategori_adi','urunler.id', 'urunler.stok', 'urunler.urun_adi', 'urunler.vergi', 'urunler.durum')
            ->groupBy('urunler.id')
            ->get();

        //return view('panel.urunler.index', ['urunler'=> $urunler]);
        return view('panel.urunler.index', compact('urunler','id', 'markalar', 'yillar', 'markamodel'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $kategoriler = DB::table('kategoriler')->where('ust_id', 0)->get();
        $vergiler = DB::table('vergi_oranlari')->where('durum', 1)->get();
        $birim = DB::table('birimler')->where('durum', 1)->get();
        $tedarikci = DB::table('tedarikciler')->get();

        return view('panel.urunler.create', ['kategoriler'=>$kategoriler, 'vergiler'=>$vergiler, 'birimler'=>$birim,'tedarikci'=>$tedarikci]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sid= DB::table('urunler')->insertGetId(array(

                'urun_adi' => $request->urunadi,
                'ureticiadi' => $request->ureticiadi,
                'ureticino' => $request->ureticino,
                'durum' =>0,
                'urun_kodu'=>$request->urunno,
                'stok'=>$request->stok,
                'termin'=>$request->termin,
                'stok_alti_satis'=>$request->stoksuz,
                'vergi'=>$request->vergi,
                'ust_kat'=>$request->ust,
                'alt_kat'=>$request->altid,
                'aciklama'=>$request->aciklama,
                'birim'=>$request->birim,

        ));

        newProduct($sid);
        toastr()->success('Basarılı', 'Yeni Ürün Eklendi');
        return redirect('panel/urunmarkamodel/'.$sid);
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
        $urun = DB::table('urunler')->find($id);
        $urunadi = $urun->urun_adi;
        $ureticiadi = $urun->ureticiadi;
        $urunkodu =$urun->urun_kodu;
        $aciklama =$urun->aciklama;
        $stok=$urun->stok;
        $termin =$urun->termin;
        $stokalt = $urun->stok_alti_satis;
        $vergi=$urun->vergi;
        $ureticino =$urun->ureticino;
        $ustkat = $urun->ust_kat;
        $altkat = $urun->alt_kat;
        $birimi =$urun->birim;



        $urunresim = DB::table('urun_resimler')->where('urun', $id)->get();
        $vergiler = DB::table('vergi_oranlari')->where('durum', 1)->get();
        $birimler = DB::table('birimler')->where('durum', 1)->get();
        $tedarikci = DB::table('tedarikciler')->get();
        if($ureticino != '') {
            $uretici = DB::table('tedarikciler')->find($ureticino);
            $uretici_ad = $uretici->tedarikci;
        }
        else { $uretici_ad = 'Seçilmedi'; }

        return view('panel.urunler.edit', compact(
            'kategoriler',
            'urunadi',
            'ureticiadi',
            'urunkodu',
            'aciklama',
            'stok',
            'termin',
            'stokalt',
            'vergi',
            'ureticino',
            'id',
            'urunresim',
            'vergiler',
            'ustkat',
            'altkat',
            'birimler',
            'birimi',
            'tedarikci',
            'uretici_ad'
        ));
    }

    public function copy($id)
    {
        $kategoriler = DB::table('kategoriler')->where('ust_id', 0)->get();
        $urun = DB::table('urunler')->find($id);
        $urunadi = $urun->urun_adi;
        $ureticiadi = $urun->ureticiadi;
        $urunkodu =$urun->urun_kodu;
        $aciklama =$urun->aciklama;
        $stok=$urun->stok;
        $termin =$urun->termin;
        $stokalt = $urun->stok_alti_satis;
        $vergi=$urun->vergi;
        $ureticino =$urun->ureticino;
        $ustkat = $urun->ust_kat;
        $altkat = $urun->alt_kat;
        $birimi =$urun->birim;



        $urunresim = DB::table('urun_resimler')->where('urun', $id)->get();
        $vergiler = DB::table('vergi_oranlari')->where('durum', 1)->get();
        $birimler = DB::table('birimler')->where('durum', 1)->get();
        $tedarikci = DB::table('tedarikciler')->get();
        if($ureticino != '') {
            $uretici = DB::table('tedarikciler')->find($ureticino);
            $uretici_ad = $uretici->tedarikci;
        }
        else { $uretici_ad = 'Seçilmedi'; }

        return view('panel.urunler.copy', compact(
            'kategoriler',
            'urunadi',
            'ureticiadi',
            'urunkodu',
            'aciklama',
            'stok',
            'termin',
            'stokalt',
            'vergi',
            'ureticino',
            'id',
            'urunresim',
            'vergiler',
            'ustkat',
            'altkat',
            'birimler',
            'birimi',
            'tedarikci',
            'uretici_ad'
        ));
    }

    public function modelgetir(Request $request){
        $marka = $request->marka;

        $modelal = DB::table('modeller')->where('marka', $marka)->orderBy('model_adi','asc')->get();
        return $modelal;
    }



    public function motorgetir(Request $request){
        $model = $request->model;

        $motor = DB::table('motorhacmi')->get();
        return $motor;
    }

    public function markamodel($id){


        $markalar = DB::table('markalar')->get();
        $yillar = DB::table('yillar')->get();

        $markamodel = DB::table('urun_ozellikleri')
            ->leftJoin('markalar', 'urun_ozellikleri.marka', '=', 'markalar.marka_id')
            ->leftJoin('modeller', 'urun_ozellikleri.model', '=', 'modeller.id')
            ->leftJoin('motorhacmi', 'urun_ozellikleri.motor', '=', 'motorhacmi.id')
            ->leftJoin('yillar', 'urun_ozellikleri.yil', '=', 'yillar.id')
            ->select('urun_ozellikleri.id', 'markalar.marka_adi', 'modeller.model_adi', 'motorhacmi.motor_adi', 'yillar.yil_adi')
            ->where('urun', $id)
            ->orderBy('marka_adi','asc')
            ->get();

        return view('panel.urunler.component.markamodel', compact('id', 'markalar', 'yillar', 'markamodel'));
    }
    public function markamodel2(Request $request,$id){
        $id = $request->id;

        $markalar = DB::table('markalar')->get();
        $yillar = DB::table('yillar')->get();

        $markamodel2 = DB::table('urun_ozellikleri')
            ->leftJoin('markalar', 'urun_ozellikleri.marka', '=', 'markalar.marka_id')
            ->leftJoin('modeller', 'urun_ozellikleri.model', '=', 'modeller.id')
            ->leftJoin('motorhacmi', 'urun_ozellikleri.motor', '=', 'motorhacmi.id')
            ->select('urun_ozellikleri.id', 'markalar.marka_adi', 'modeller.model_adi', 'motorhacmi.motor_adi','urun_ozellikleri.yil')
            ->where('urun', $id)
            ->orderBy('marka_adi','asc')
            ->get();

        return $markamodel2;
    }
    public function fiyat($id){

        $urunfiyat = DB::table('fiyat_ayar')
            ->leftJoin('urun_fiyatlari', 'fiyat_ayar.id', '=', 'urun_fiyatlari.fiyat_id')
            ->select('fiyat_ayar.name', 'fiyat_ayar.id as fyid','fiyat_ayar.id as ayarid','urun_fiyatlari.id', 'urun_fiyatlari.fiyat_id', 'urun_fiyatlari.fiyat')
            ->where('urun_fiyatlari.urun',$id)
            ->get();


        $ayar = DB::table('fiyat_ayar')->where('durum', 1)->get();
        $ayarcount = DB::table('fiyat_ayar')->where('durum', 1)->count();


        //$urunfiyat = DB::table('urun_fiyatlari')->where('urun',$id)->get();
        return view('panel.urunler.component.fiyat', compact('id','urunfiyat', 'ayar', 'ayarcount'));
    }

    public function oem($id){

        $oemler = DB::table('urun_oem')->where('urun', $id)->get();
        return view('panel.urunler.component.oem', compact('id', 'oemler'));
    }

    public function tanim($id){

        $tanimlar = DB::table('urun_tanimlari')->where('urun', $id)->get();
        return view('panel.urunler.component.tanim', compact('id', 'tanimlar'));
    }

    public function tanimcek(Request $request){

        $id = $request->id;
        $tanimlar = DB::table('urun_tanimlari')->where('urun', $id)->get();
        return $tanimlar;
    }

    public function oemcek(Request $request){

        $id = $request->id;
        $oemler = DB::table('urun_oem')->where('urun', $id)->get();
        return $oemler;
    }

    public function tanimsil(Request $request){

        $id = $request->id;
        DB::table('urun_tanimlari')->where('id', $id)->delete();
    }

    public function markamodelsil(Request $request){

        $id = $request->id;
        DB::table('urun_ozellikleri')->where('id', $id)->delete();
    }

    public function oemsil(Request $request){

        $id = $request->id;
        DB::table('urun_oem')->where('id', $id)->delete();
    }

    public function resim($id){

        $urunresim = DB::table('urun_resimler')->where('urun', $id)->get();
        return view('panel.urunler.component.resim', compact('id', 'urunresim'));
    }

    public function resimsil(Request $request){

        $id = $request->id;
        DB::table('urun_resimler')->where('id', $id)->delete();
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
        $affected = DB::table('urunler')
            ->where('id', $id)
            ->update([
                'urun_adi' => $request->urunadi,
                'ureticiadi' => $request->ureticiadi,
                'ureticino' => $request->ureticino,
                'durum' =>0,
                'urun_kodu'=>$request->urunno,
                'stok'=>$request->stok,
                'termin'=>$request->termin,
                'stok_alti_satis'=>$request->stoksuz,
                'vergi'=>$request->vergi,
                'ust_kat'=>$request->ust,
                'alt_kat'=>$request->altid,
                'aciklama'=>$request->aciklama,
                'birim'=>$request->birim,
            ]);

        toastr()->success('Basarılı', 'Ürün Bilgileri Başarıyla Güncellendi');
        return redirect('panel/urunmarkamodel/'.$id);
    }

    public function urunmarkamodelekle(Request $request){

        DB::table('urun_ozellikleri')->insert(
            [
                'marka' => $request->marka,
                'model' => $request->model,
                'motor'=>$request->motor,
                'yil'=>$request->yil,
                'urun'=>$request->urun
            ]
        );

    }


    public function fiyatguncelle(Request $request)
    {
        $durum = $request->fiyatid;

        //kontrol

        $sor = DB::table('urun_fiyatlari')->where('urun', $request->urun)->where('fiyat_id', $request->ayar)->count();



        echo $sor;

        if($sor==0){

          $kaydet= DB::table('urun_fiyatlari')->insert(
                [
                    'fiyat' => $request->fiyat,
                    'urun' => $request->urun,
                    'fiyat_id'=>$request->ayar
                ]
            );

            echo "girdi";
            exit();
        }
        echo "çıktı";



        if($durum==0){
            DB::table('urun_fiyatlari')->insert(
                [
                    'fiyat' => $request->fiyat,
                    'urun' => $request->urun,
                    'fiyat_id'=>$request->ayar
                ]
            );

        }else{
            $affected = DB::table('urun_fiyatlari')
                ->where('id', $request->fiyatid)
                ->update([
                    'fiyat'=> $request->fiyat
                ]);
        }


    }


    public function tanimekle(Request $request)
    {
        DB::table('urun_tanimlari')->insert(
            [
                'tanimadi' => $request->name,
                'tanim_deger' => $request->tanim,
                'urun'=>$request->id
            ]
        );

    }

    public function oemekle(Request $request)
    {
        DB::table('urun_oem')->insert(
            [
                'oem' => $request->oem,
                'urun'=>$request->id
            ]
        );

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Ürünü Sil


        // Transaction Başlat
        DB::beginTransaction();

            DB::table('urunler')->where('id', $id)->delete();

            // Resimleri Sil
            DB::table('urun_resimler')->where('urun', $id)->delete();

            // Tanımları Sİl

            // Marka Model Sil

            // Oemleri Sil

            // Fiyatları Sil

        try {
            // Tüm Komutlar başarıyla çalışıtırıldı ise işlemi yap
            DB::commit();
            toastr()->error('Başarısız', 'Ürün Silinemedi');
        }  catch (\Exception $e) {
            // işlemler den biri yapılamadıysa tüm işlemleri geri al
            DB::rollback();
            toastr()->success('Basarılı', 'Ürün Başarıyla Silindi');
        }

        return redirect('panel/urunler');
    }

    public function urunresimekle(Request $request){

        $urunid = $request->urunid;

        $this->validate($request,[
            'image' =>'required',
            'image.*' => 'mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if($request->hasFile('image')){

            $image = $request->file('image');
            foreach ($image as $files) {
                $destinationPath = 'uploads/urunler/';
                $file_name = time() . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $file_name);
                $data[] = $file_name;


                DB::table('urun_resimler')->insert(
                    ['resim' => $file_name, 'urun' => $urunid]
                );
            }


        }

        toastr()->success('Basarılı', 'Yeni Resim Eklendi');
        return redirect('panel/urunresim/'.$urunid);
    }
    public function urunlist() {
        $urunler = DB::table('urunler')
            ->leftJoin('kategoriler', 'kategoriler.id', '=', 'urunler.ust_kat')
            ->leftJoin('tedarikciler','tedarikciler.id','=','urunler.ureticino')
            ->leftJoin('urun_resimler', 'urun_resimler.urun', '=', 'urunler.id')
            ->leftJoin('urun_fiyatlari', 'urun_fiyatlari.urun', '=', 'urunler.id')
            ->select('urun_resimler.resim','kategoriler.kategori_adi','urunler.id', 'urunler.stok','urunler.ureticino', 'urunler.urun_adi', 'urunler.vergi', 'urunler.durum','urunler.kampanya','urunler.urun_kodu','urun_fiyatlari.fiyat','tedarikciler.tedarikci')
            ->where('urun_fiyatlari.fiyat_id','1')
            ->groupBy('urunler.id')
            ->get();

            $count = count($urunler);
            echo ' {
            "draw": "'.$count.'",
            "recordsTotal": "'.$count.'",
            "recordsFiltered": "'.$count.'",
            "data": [ ';
            $key = 1;
            foreach ($urunler as $urun){
                $urunadi = str_replace(PHP_EOL,' ', $urun->urun_adi);

                $class = "btn-success";
                echo "[";
                echo '"'.$urun->urun_kodu.'",';
                echo '"<a class=zoom-gallery ><img src=/uploads/urunler/'.$urun->resim.' height=30></a>",';
                echo '"'.$urunadi.'",';
                if($urun->kategori_adi != '') {
                    echo '"'.$urun->kategori_adi.'",';
                }
                else {
                    echo '"<div class=bkate >Seçilmedi</div>",';
                }
                echo '"'.$urun->stok.'",';
                echo '"'.$urun->fiyat.'₺",';


                echo '"18",';
                if($urun->kampanya!=1) {
                    echo '"<button class=btn-danger onclick=kampdegis('.$urun->id.') id=c'.$urun->id.' durum=1 table=urunler>Pasif</button>",';
                }

                else{
                    echo '"<button class=btn-success onclick=kampdegis('.$urun->id.') id=c'.$urun->id.' durum=0 table=urunler>Aktif</button>",';
                }
                if($urun->durum==0) {
                    echo '"<button class=btn-danger onclick=durumdegis('.$urun->id.') id=b'.$urun->id.' durum=1 table=urunler>Pasif</button>",';
                }

                else{
                    echo '"<button class=btn-success onclick=durumdegis('.$urun->id.') id=b'.$urun->id.' durum=0 table=urunler>Aktif</button>",';
                }
                echo '"'.$urun->tedarikci.'",';
                echo '"<a href=urunduzenle/'.$urun->id.' alt=Düzenle><button class=button-edit>Düzenle</button></a><a href=urunkopyala/'.$urun->id.' alt=Kopyala><button class=button-edit>Kopyala</button></a><a href=urunsil/'.$urun->id.' alt=Sil class=alert><button class=button2>Sil</button></a><button class=button1 onclick=addcart('.$urun->id.')>Araç Tanımla</button>"';
                if($count == $key ) {
                    echo "]";
                }
                else {
                    echo "],";
                }
                $key++;


            }

            echo '] }';

    }



    public function urunliste(){
        $urunler = DB::table('urunler')->get();
        return view('panel.urunler.list',compact('urunler'));
    }

    public function cleardb() {
        /*
        $del = DB::table('urun_resimler')->delete();
        $del2 = DB::table('urun_oem')->delete();
        $del3 = DB::table('urunler')->delete();
        $del4 = DB::table('urun_tanimlari')->delete();
        $del5 = DB::table('urun_fiyatlari')->delete();
        $del6 = DB::table('urun_marka')->delete();
        $del7 = DB::table('urun_model')->delete();
        $del8 = DB::table('urun_ozellikleri')->delete();
        if($del) { echo ' veriler silindi'.'<br>'; }
        if($del2) { echo ' veriler silindi '.'<br>'; }
        if($del3) { echo ' veriler silindi '.'<br>'; }
        if($del4) { echo ' veriler silindi '.'<br>'; }
        if($del5) { echo ' veriler silindi '.'<br>'; }
        if($del6) { echo ' veriler silindi '.'<br>'; }
        if($del7) { echo ' veriler silindi '.'<br>'; }
        if($del8) { echo ' veriler silindi '.'<br>'; }
        */
    }



    public function product_update(){
        $url = 'https://www.narinkaucuk.com.tr/acar_webservis.php?type=products';
        $readJson = file_get_contents($url);
        $data = json_decode($readJson);

        foreach($data as $show) {

        $reg = DB::table('urunler')
            ->leftJoin('urun_fiyatlari','urun_fiyatlari.urun','=','urunler.id')
            ->where('urunler.urun_kodu',$show->product_code)
            ->update(
                [
                    'fiyat' => $show->price
                ]
            );
        if($reg) { echo '1'; }

        }
    }
    public function getoems() {
        $json = file_get_contents('https://www.narinkaucuk.com.tr/acar_webservis.php?type=products');
        $data = json_decode($json);
        foreach($data as $show) {
            $oems = explode(',', $show->product_oems);
            $product = DB::table('urunler')->where('urun_kodu',$show->product_code)->first();
            $product_id = $product->id;

            foreach ($oems as $oem) {
                DB::table('urun_oem')->insert(
                    [
                        'urun' => $product_id,
                        'oem' => $oem
                    ]);
                echo '[ '.$product_id.' ]'.' '.$oem.' added.'.'<br>';
            }

        }
        /*
        foreach ($oems as $oem) {
            DB::table('urun_oem')->insert(
                [
                    'urun' => $insert,
                    'oem' => $oem
                ]);
        }*/
    }
    //acar ürün
    public function getjson() {

        $url = 'https://www.narinkaucuk.com.tr/acar_webservis.php?type=products';
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
        }

        /*
         * marka model ekleme
         *
        $url = 'https://www.narinkaucuk.com.tr/acar_webservis.php?type=brands';
        $readJson = file_get_contents($url);
        $data = json_decode($readJson,true);

        foreach($data as $key => $get) {
            DB::table('markalar')->insert(
                [
                    'marka_id' => $get['id'],
                    'marka_adi' => $get['brand_name'],
                    'durum' => '1'
                ]
            );
            echo '<b>'.$get['brand_name'].'</b>';
            echo '<br>';
            $markaid = $get['id'];
            if(($get['brand_name'] != 'DS') and ($get['brand_name'] != 'TOFAS')) {
                foreach($data[$key]['models'] as $show) {
                    DB::table('modeller')->insert(
                        [
                            'id' => $show['id'],
                            'marka' => $markaid,
                            'model_adi' => $show['model_name'],
                            'durum' => '1'
                        ]
                    );
                    echo $show['id'];
                    echo ' - '.$show['model_name'];
                    echo '<br>';
                }
            }

        }
        */



            /*
            DB::table('')->insert(
                [
                    'id' => $get->id,
                    'kategori_adi' => $get->category_name,
                    'ust_id' => '0',
                    'durum' => '1'
                ]
            );
            */



        /*
                $url = 'https://acarhortum.com/urunlist';
                $readJson = file_get_contents($url);
                $data = json_decode($readJson);
                foreach($data as $get) {
                DB::table('modeller')->insertGetId(
                        [
                            'id' => $get->id,
                            'marka' => $get->parent,
                            'model_adi' => $get->mka,
                            'durum' => '1'

                        ]);
                }
        /*
               foreach ($data as $get) {

                   $insert = DB::table('urunler')->insertGetId(
                       [
                           'urun_adi' => $get->urun,
                           'urun_kodu' => $get->urun_kodu,
                           'durum' => '1',
                           'stok' => $get->stok,
                           'ust_kat' => $get->kategori

                       ]);
                   if($insert) {
                       DB::table('urun_fiyatlari')->insert(
                       [
                          'urun' => $insert,
                          'fiyat' => $get->fiyat,
                          'fiyat_id' => '1'
                       ]); // end insert price
                       DB::table('urun_resimler')->insert(
                           [
                               'urun' => $insert,
                               'resim' => $get->resim
                       ]); // end insert image
                       DB::table('urun_tanimlari')->insert(
                           [
                               'durum'=> '1',
                               'tanimadi' => 'Ağırlık',
                               'tanim_deger' => $get->agirlik,
                               'urun' => $insert
                       ]); // end insert uruntanım
                       if($get->paket != ''){
                           DB::table('urun_tanimlari')->insert(
                               [
                                   'durum'=> '1',
                                   'tanimadi' => 'Paket',
                                   'tanim_deger' => $get->paket,
                                   'urun' => $insert
                               ]);
                       }// end packet control

                       $oems = explode(PHP_EOL, $get->oem);
                       foreach ($oems as $show) {
                           DB::table('urun_oem')->insert(
                               [
                                   'urun' => $insert,
                                   'oem' => $show
                               ]);
                       } // end oem foreach



                   } // endif
               }//endforeach
                */
    }


}
