<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\PDF;
use Barryvdh\Snappy;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//Route::get('/getxml','Back\UrunController@getxml');
/* Front End Route */
Route::get('orderform',function(){
    return view('emails.neworder2');
});
Route::get('testque',function(){
  App\Jobs\Update::dispatch();
  return 'test';
});
// Root route - direkt login sayfasını göster (redirect yapma, sonsuz döngüyü önle)
Route::get('/', function () {
    if (Auth::check()) {
        // Subdirectory desteği için tam URL oluştur
        $basePath = '/b2b-gemas-project-main/bayi/public';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $homeUrl = $protocol . '://' . $host . $basePath . '/home';
        return redirect($homeUrl);
    }
    // Redirect yerine direkt login view'ını göster
    return app('App\Http\Controllers\Auth\LoginController')->showLoginForm();
});
Route::get('/home', 'Front\HomeController@index')->name('home');
// /public route'unu kaldırdık - bu Laravel'in public klasörü ile karışıyor
Route::get('cikis', 'Front\HomeController@cikis');
Route::get('sepet', 'Front\SepetController@index');
Route::get('yeniurun', 'Front\HomeController@yeniurun');
Route::get('kampanya', 'Front\HomeController@kampanya');
Route::get('productimage', 'Front\HomeController@productimage');
Route::get('satinaldiklarim', 'Front\HomeController@satinaldiklarim');
Route::get('kampanyapaket', 'Front\HomeController@kampanyapaket');
Route::get('esdeger/{id}', 'Front\HomeController@esdeger');
Route::get('sepetsil', 'Front\SepetController@sepetsil');
Route::get('sepetsil2', 'Front\SepetController@sepetsil2');
Route::get('sepetibosalt', 'Front\SepetController@sepetibosalt');
Route::get('sepetlist', 'Front\SepetController@sepetlist');
Route::get('sepetonay/{id}', 'Front\SepetController@sepetonay');
Route::get('upbasket', 'Front\SepetController@upbasket');
Route::get('basketprice', 'Front\SepetController@basketprice');
Route::get('siparisonay', 'Front\SepetController@siparisonay');

Route::get('siparistamamla', 'Front\SiparisController@siparistamamla');
Route::get('siparisdurum/{id}', 'Front\SiparisController@siparisdurum');
Route::get('siparissil/', 'Front\SiparisController@siparissil');
Route::get('sipcontrol', 'Front\SiparisController@sipcontrol');
Route::get('siparisfiyat', 'Front\SiparisController@siparisfiyat');
Route::get('siparisbilgi', 'Front\SiparisController@siparisbilgi');
Route::get('sipnot', 'Front\SiparisController@sipnot');


Route::get('oemistek', 'Front\SiparisController@oemistek');
Route::get('send-feedback', 'Front\HomeController@feedback');
Route::get('odemebildir', 'Front\SiparisController@odemebildir');
Route::get('odenmemis', 'Front\SiparisController@odenmemis');
Route::get('updateorder', 'Front\SiparisController@updateorder');
Route::get('siparisurun', 'Front\SiparisController@siparisurun');

Route::get('contract', 'Front\HomeController@contract');
Route::get('getcontract/{id}', 'Front\HomeController@getcontract');
Route::get('verifycontract', 'Front\HomeController@verifycontract');
Route::get('mesafeli', 'Front\HomeController@mesafeli');
Route::get('adresdurum', 'Front\HomeController@adresdurum');

Route::get('yeniurun', 'Front\HomeController@yeniurun');
Route::get('modelgetir', 'Front\HomeController@modelgetir');
Route::get('filtrele', 'Front\HomeController@filtrele');
Route::get('sepetekle', 'Front\HomeController@sepetekle');
Route::post('sepetekleKampanya', 'Front\HomeController@sepetekleKampanya');
Route::get('siparisler', 'Front\SiparisController@index');
Route::get('siparis/{id}', 'Front\SiparisController@siparisdetay');
Route::get('productdetail/{id}', 'Front\HomeController@productdetail');
Route::get('markamodellist/{id}', 'Front\HomeController@markamodellist');
Route::get('urunoem/{id}', 'Front\HomeController@urunoem');
Route::get('detail/{id}', 'Front\SiparisController@detail');
Route::get('makbuz/{id}', 'Front\SiparisController@makbuz');
Route::get('ayarlar', 'Front\AyarController@index');
Route::get('adreskaydet', 'Front\AyarController@adreskaydet');
Route::get('adreslist', 'Front\AyarController@adresliste');
Route::get('adressil', 'Front\AyarController@adressil');
Route::get('adrestalep', 'Front\AyarController@adrestalep');
Route::get('hesap', 'Front\HesapController@index');
Route::get('extre', 'Front\HesapController@extre');
Route::get('pos', 'Front\PayController@index');
Route::post('payresult', 'Front\PayController@payresult');
Route::post('parolaguncelle', 'Front\AyarController@parolaguncelle');
Route::get('urunresim/{id}', 'Front\HomeController@urunresim');


Route::get('productlist', 'Service@product_list');
Route::get('imagelist', 'Service@image_list');
Route::get('newlist', 'Service@newlist');
Route::get('categorylist', 'Service@category_list');
Route::get('modellist', 'Service@model_list');
Auth::routes();

// Panel Route'ları TAMAMEN DEVRE DIŞI - Kendi admin panelimiz kullanılıyor
// Tüm panel route'ları kaldırıldı çünkü kendi admin panelimizi kullanıyoruz
/*
Route::get('panel', 'Back\SiparisController@index');
Route::get('panel/login', 'Back\PanelController@index');
Route::post('panel/login', 'Back\PanelController@login')->name('panel.login.post');
Route::get('panel/cikis', 'Back\PanelController@logout');
Route::get('panel/roltanim', 'Back\PanelController@roltanim');
Route::get('panel/allnotice', 'Back\OrtakController@allnotice');
Route::get('panel/havalebildirim', 'Back\OrtakController@havalebildirim');
Route::get('panel/feedback', 'Back\OrtakController@feedback');

// Adminler
Route::get('panel/adminler', 'Back\UyeController@index');
Route::get('panel/roller', 'Back\UyeController@roller');
Route::get('panel/adminsil/{id}', 'Back\UyeController@destroy');
Route::get('panel/adminekle', 'Back\UyeController@create');
Route::post('panel/admineklepost', 'Back\UyeController@store');
Route::get('panel/adminduzenle/{id}', 'Back\UyeController@edit');
Route::post('panel/adminduzenlepost', 'Back\UyeController@update');

// Bayiler
Route::get('panel/bayiler', 'Back\BayiController@index');
Route::get('panel/bayiekle', 'Back\BayiController@create');
Route::post('panel/bayieklepost', 'Back\BayiController@store');
Route::get('panel/bayisil/{id}', 'Back\BayiController@destroy');
Route::get('panel/bayiduzenle/{id}', 'Back\BayiController@edit');
Route::post('panel/bayiduzenlepost/{id}', 'Back\BayiController@update');
Route::get('panel/editpass', 'Back\BayiController@editpass');
Route::get('panel/adreslist', 'Back\BayiController@adreslist');
Route::get('panel/adresdurum', 'Back\BayiController@adresdurum');

// Markalar
Route::get('panel/markalar', 'Back\MarkaController@index');
Route::get('panel/markasil/{id}', 'Back\MarkaController@destroy');
Route::get('panel/markaekle', 'Back\MarkaController@create');
Route::post('panel/markaeklepost', 'Back\MarkaController@store');
Route::get('panel/markaduzenle/{id}', 'Back\MarkaController@edit');
Route::post('panel/markaduzenlepost/{id}', 'Back\MarkaController@update');

// Modeller
Route::get('panel/modeller', 'Back\ModelController@index');
Route::get('panel/modeller/{id}', 'Back\ModelController@markamodel');
Route::get('panel/modelsil/{id}', 'Back\ModelController@destroy');
Route::get('panel/modelekle', 'Back\ModelController@create');
Route::post('panel/modeleklepost', 'Back\ModelController@store');
Route::get('panel/modelduzenle/{id}', 'Back\ModelController@edit');
Route::post('panel/modelduzenlepost/{id}', 'Back\ModelController@update');

// Seriler
Route::get('panel/seriler', 'Back\SeriController@index');
Route::get('panel/seriler/{id}', 'Back\SeriController@modelseri');
Route::get('panel/serisil/{id}', 'Back\SeriController@destroy');
Route::get('panel/seriekle', 'Back\SeriController@create');
Route::post('panel/serieklepost', 'Back\SeriController@store');
Route::get('panel/seriduzenle/{id}', 'Back\SeriController@edit');
Route::post('panel/seriduzenlepost/{id}', 'Back\SeriController@update');
Route::get('panel/modelgetir', 'Back\SeriController@modelgetir');

// Yıllar
Route::get('panel/yillar', 'Back\YilController@index');
Route::get('panel/yilsil/{id}', 'Back\YilController@destroy');
Route::get('panel/yilekle', 'Back\YilController@create');
Route::post('panel/yileklepost', 'Back\YilController@store');
Route::get('panel/yilduzenle/{id}', 'Back\YilController@edit');
Route::post('panel/yilduzenlepost/{id}', 'Back\YilController@update');

// Kategoriler
Route::get('panel/kategoriler', 'Back\KategoriController@index');
Route::get('panel/kategoriler/{id}', 'Back\KategoriController@altkategoriler');
Route::get('panel/kategorisil/{id}', 'Back\KategoriController@destroy');
Route::get('panel/kategoriekle', 'Back\KategoriController@create');
Route::post('panel/kategorieklepost', 'Back\KategoriController@store');
Route::get('panel/kategoriduzenle/{id}', 'Back\KategoriController@edit');
Route::post('panel/kategoriduzenlepost/{id}', 'Back\KategoriController@update');
Route::get('panel/altgetir', 'Back\KategoriController@altgetir');

// Ürünler
Route::get('panel/urunlist', 'Back\UrunController@urunlist');
Route::get('panel/urunliste', 'Back\UrunController@urunliste');
Route::get('panel/urunler', 'Back\UrunController@index');
Route::get('panel/urunsil/{id}', 'Back\UrunController@destroy');
Route::get('panel/urunekle', 'Back\UrunController@create');
Route::post('panel/uruneklepost', 'Back\UrunController@store');
Route::get('panel/urunduzenle/{id}', 'Back\UrunController@edit');
Route::get('panel/urunkopyala/{id}', 'Back\UrunController@copy');
Route::post('panel/urunduzenlepost/{id}', 'Back\UrunController@update');
Route::post('panel/urunresimekle', 'Back\UrunController@urunresimekle');

// Ürün Alt Modulleri
Route::get('panel/getjson', 'Back\UrunController@getjson');
Route::get('panel/getoems', 'Back\UrunController@getoems');
Route::get('panel/productupdate', 'Back\UrunController@product_update');
//Route::get('panel/cleardb', 'Back\UrunController@cleardb');

Route::get('panel/urunmarkamodel/{id}', 'Back\UrunController@markamodel');
Route::get('panel/urunmarkamodel2/{id}', 'Back\UrunController@markamodel2');
Route::get('panel/urunfiyat/{id}', 'Back\UrunController@fiyat');
Route::get('panel/urunfiyatguncelle', 'Back\UrunController@fiyatguncelle');

Route::get('panel/urunoem/{id}', 'Back\UrunController@oem');
Route::get('panel/urunoemekle', 'Back\UrunController@oemekle');
Route::get('panel/urunoemcek', 'Back\UrunController@oemcek');
Route::get('panel/urunoemsil', 'Back\UrunController@oemsil');

Route::get('panel/uruntanim/{id}', 'Back\UrunController@tanim');
Route::get('panel/uruntanimekle', 'Back\UrunController@tanimekle');
Route::get('panel/uruntanimcek', 'Back\UrunController@tanimcek');
Route::get('panel/tanimsil', 'Back\UrunController@tanimsil');

Route::get('panel/urunresim/{id}', 'Back\UrunController@resim');
Route::get('panel/urunresimsil', 'Back\UrunController@resimsil');

Route::get('panel/urunmodelgetir', 'Back\UrunController@modelgetir');
Route::get('panel/urunmotorgetir', 'Back\UrunController@motorgetir');

Route::get('panel/urunmarkamodelekle', 'Back\UrunController@urunmarkamodelekle');
Route::get('panel/markamodelsil', 'Back\UrunController@markamodelsil');

// Ortak
Route::get('panel/durumdegis', 'Back\OrtakController@durumdegis');
Route::get('panel/kampdegis', 'Back\OrtakController@kampdegis');
Route::get('panel/kapakyap', 'Back\OrtakController@kapakyap');
Route::get('panel/changenoticestat', 'Back\OrtakController@changenoticestat');

// Ayarlar
Route::get('panel/ayarlar', 'Back\OrtakController@ayarlar');
Route::get('panel/islemler', 'Back\OrtakController@islemler');
Route::post('panel/ayarduzenle/{id}', 'Back\OrtakController@ayarduzenle');
Route::get('panel/rol/{id}', 'Back\PanelController@adminyetki');

Route::get('panel/parabirimleri', 'Back\ParaController@index');
Route::get('panel/birimkaydet', 'Back\ParaController@update');
Route::get('panel/birimtopluguncelle', 'Back\ParaController@birimtopluguncelle');

// Fiyat Ayarları
Route::get('panel/fiyatayarlari', 'Back\FiyatController@index');
Route::get('panel/fiyatekle', 'Back\FiyatController@create');
Route::post('panel/fiyateklepost', 'Back\FiyatController@store');
Route::get('panel/fiyatsil/{id}', 'Back\FiyatController@destroy');
Route::get('panel/fiyatduzenle/{id}', 'Back\FiyatController@edit');
Route::post('panel/fiyatduzenlepost/{id}', 'Back\FiyatController@update');

// Vergi Ayarları
Route::get('panel/vergiayarlari', 'Back\VergiController@index');
Route::get('panel/vergiekle', 'Back\VergiController@create');
Route::post('panel/vergieklepost', 'Back\VergiController@store');
Route::get('panel/vergisil/{id}', 'Back\VergiController@destroy');
Route::get('panel/vergiduzenle/{id}', 'Back\VergiController@edit');
Route::post('panel/vergiduzenlepost/{id}', 'Back\VergiController@update');

// Birim Ayarları
Route::get('panel/birimler', 'Back\BirimController@index');
Route::get('panel/birimekle', 'Back\BirimController@create');
Route::post('panel/birimeklepost', 'Back\BirimController@store');
Route::get('panel/birimsil/{id}', 'Back\BirimController@destroy');
Route::get('panel/birimduzenle/{id}', 'Back\BirimController@edit');
Route::post('panel/birimduzenlepost/{id}', 'Back\BirimController@update');

// Ödeme Yöntemleri
Route::get('panel/odemeyontemleri', 'Back\OdemeController@index');
Route::get('panel/iyzico', 'Back\OdemeController@iyzico');
Route::post('panel/iyzicoduzenle/{id}', 'Back\OdemeController@iyzico_update');
Route::get('panel/odemedetay/{id}', 'Back\OdemeController@odemedetay');
Route::get('panel/feedmessage/{id}', 'Back\OrtakController@feedmessage');
Route::get('panel/havaleonay', 'Back\OdemeController@havaleonay');

// Kargolar
Route::get('panel/kargolar', 'Back\KargoController@index');
Route::get('panel/kargolist', 'Back\KargoController@kargolist');
Route::get('panel/kargolist2', 'Back\KargoController@kargolist2');
Route::get('panel/kargobilgi', 'Back\KargoController@kargobilgi');
Route::get('panel/kargodurum', 'Back\KargoController@kargodurum');
Route::get('panel/kargoekle', 'Back\KargoController@create');
Route::post('panel/kargoeklepost', 'Back\KargoController@store');
Route::get('panel/kargosil/{id}', 'Back\KargoController@destroy');
Route::get('panel/kargoduzenle/{id}', 'Back\KargoController@edit');
Route::post('panel/kargoduzenlepost/{id}', 'Back\KargoController@update');

// Bankalar
Route::get('panel/bankalar', 'Back\BankaController@index');
Route::get('panel/bankaekle', 'Back\BankaController@create');
Route::post('panel/bankaeklepost', 'Back\BankaController@store');
Route::get('panel/bankasil/{id}', 'Back\BankaController@destroy');
Route::get('panel/bankaduzenle/{id}', 'Back\BankaController@edit');
Route::post('panel/bankaduzenlepost/{id}', 'Back\BankaController@update');


// İller
Route::get('panel/iller', 'Back\SehirController@index');
Route::get('panel/ilekle', 'Back\SehirController@create');
Route::post('panel/ileklepost', 'Back\SehirController@store');
Route::get('panel/ilsil/{id}', 'Back\SehirController@destroy');
Route::get('panel/ilduzenle/{id}', 'Back\SehirController@edit');
Route::post('panel/ilduzenlepost/{id}', 'Back\SehirController@update');

// İlçeler
Route::get('panel/ilceler/{id}', 'Back\SehirController@ilceler');
Route::get('panel/ilceekle/{id}', 'Back\SehirController@ilceekle');
Route::post('panel/ilceeklepost', 'Back\SehirController@ilceeklestore');
Route::get('panel/ilcesil/{id}', 'Back\SehirController@ilcesil');
Route::get('panel/ilceduzenle/{id}', 'Back\SehirController@ilceduzenle');
Route::post('panel/ilceduzenlepost/{id}', 'Back\SehirController@ilceupdate');

// Sözleşmeler
Route::get('panel/sozlesmeler', 'Back\SozlesmeController@index');
Route::get('panel/sozlesmeduzenle/{id}', 'Back\SozlesmeController@edit');
Route::post('panel/sozlesmeduzenlepost/{id}', 'Back\SozlesmeController@update');

// Siparişler
Route::get('panel/siparisler', 'Back\SiparisController@index');
Route::get('panel/siparis/{id}', 'Back\SiparisController@detay');
Route::get('panel/kargolanan-siparisler', 'Back\SiparisController@kargolanan');
Route::get('panel/onaybekleyen', 'Back\SiparisController@onaybekleyen');
Route::get('panel/tamamlanan-siparisler', 'Back\SiparisController@tamamlanan');
Route::get('panel/durumkaydet', 'Back\SiparisController@durumkaydet');
Route::get('panel/odemetip', 'Back\SiparisController@odemetip');

// Toplu işlemler
Route::get('panel/topluindirim', 'Back\TopluIslem@topluindirim');
Route::get('panel/ktopluindirim', 'Back\TopluIslem@ktopluindirim');
Route::get('panel/ttopluindirim', 'Back\TopluIslem@ttopluindirim');
Route::get('panel/fiyatdurum', 'Back\TopluIslem@fiyatdurum');
Route::get('panel/stokdurum', 'Back\TopluIslem@stokdurum');
Route::get('panel/urundurum', 'Back\TopluIslem@urundurum');
Route::get('panel/stokekle', 'Back\TopluIslem@stokekle');
Route::get('panel/kstokekle', 'Back\TopluIslem@kstokekle');
Route::get('panel/updatelog', 'Back\TopluIslem@updatelog');

// Tedarikçiler
Route::get('panel/tedarikciler', 'Back\OrtakController@tedarik');
Route::get('panel/tedarikciekle', 'Back\OrtakController@tedarikciekle');

// Raporlar
Route::get('panel/doreport', 'Back\ReportController@doreport');
Route::get('panel/siparisrapor', 'Back\ReportController@siparisrapor');
Route::get('panel/monthlyreport', 'Back\ReportController@monthlyreport');
*/
// Admin panel route'ları devre dışı - kendi admin panelimiz kullanılıyor

Route::get('/sendmail', 'Front\MailController@sender');

//update products


/*

Route::get('send-mail', function () {

    $details = [
        'title' => 'Deneme Başlık',
        'body' => 'Deneme İçerik'
    ];

    Mail::to('info@testb2b.com')->send(new \App\Mail\Mailgun($details));

    dd("Email is Sent.");
});


Route::get('/sender',function (){
    $data=[
        'mail_address'=>'info@testb2b.com',
        'name'=>'Test B2B'
    ];
    Mail::send('front/noticemail',$data,function($mail) use ($data) {
        $mail->subject('Site bildirim');
        $mail->from('no-reply@sender.testb2b.com','Örnek Mail Gönderimi');
        $mail->to($data['mail_address']);
    });
});



Route::get('cache', function() {

    $products = Cache::remember('products',600,function(){
        //$productlist = DB::table('urunler')->limit('10')->get();
        //return $productlist;
    });
   return $products;
});
*/
