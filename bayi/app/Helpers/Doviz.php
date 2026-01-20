<?php


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function doviz($birim, $tutar)
    {
        $tcmb = simplexml_load_file('http://www.tcmb.gov.tr/kurlar/today.xml');

        $usd = $tcmb->Currency[0]->BanknoteBuying;
        $euro = $tcmb->Currency[3]->BanknoteBuying;
        $yen = $tcmb->Currency[11]->BanknoteBuying;

        if($birim=="usd"){
            return $usd;
        }else if($birim=="euro"){
            return $euro;
        }else if($birim=="yen"){
            return $yen;
        }else if($birim=="rub"){
            return $rub;
        }else if($birim=="tl"){
            return 1;
        }
        // View da Örnek Kullanımı {{ doviz('euro', 1000) }} Controller da kullanımı doviz('euro', 1000);
    }

    function setToken() {
        $comp = 'salter';
        $setToken = $comp.md5(date('D-M-Y-S'));
        return $setToken;
    }
    function smoney($value)
    {
        //return money_format('%i', $value);
    }
    function logo() {
        $id=1;
        $ayar = DB::table('ayarlar')->find($id);
        if ($ayar) {
            // Önce logo kontrol et
            if (isset($ayar->logo) && !empty($ayar->logo)) {
                // Eğer tam path değilse asset() ile döndür
                $logoPath = $ayar->logo;
                if (!str_starts_with($logoPath, 'http') && !str_starts_with($logoPath, '/')) {
                    return asset($logoPath);
                }
                return $logoPath;
            }
            // Sonra resim kontrol et
            if (isset($ayar->resim) && !empty($ayar->resim)) {
                $resimPath = $ayar->resim;
                if (!str_starts_with($resimPath, 'http') && !str_starts_with($resimPath, '/')) {
                    return asset($resimPath);
                }
                return $resimPath;
            }
        }
        // Logo dosyası yoksa varsayılan logo-sm.png kullan
        $fallbackLogo = public_path('assets/panel/images/logo-sm.png');
        if (file_exists($fallbackLogo)) {
            return asset('assets/panel/images/logo-sm.png');
        }
        // Eğer hiç logo yoksa boş string döndür (404 hatasını önlemek için)
        return '';
    }
    function baslik() {
        $id=1;
        $ayar = DB::table('ayarlar')->find($id);
        if ($ayar) {
            // Önce site_adi kontrol et (eski sistem için)
            if (isset($ayar->site_adi) && !empty($ayar->site_adi)) {
                return $ayar->site_adi;
            }
            // Sonra title kontrol et (yeni sistem için)
            if (isset($ayar->title) && !empty($ayar->title)) {
                return $ayar->title;
            }
        }
        // genelayarlar tablosundan unvan almayı dene
        $genelAyar = DB::table('genelayarlar')->find(1);
        if ($genelAyar && isset($genelAyar->unvan) && !empty($genelAyar->unvan)) {
            return $genelAyar->unvan;
        }
        return 'Toptan Parça B2B';
    }
    
    /**
     * Get site name safely (for controllers)
     */
    function getSiteAdi() {
        return baslik();
    }

    function para($value){
       $val = number_format($value, 2, ',', '.');
       return $val;
    }
    function bildirim($baslik,$sipid,$mesaj,$url){
        $tarih = date('Y-m-d');
        $user = Auth::user()->id;
        $data = (
            [
                'bildirim' => $baslik,
                'view' => '0',
                'uye' => $user,
                'sipid' => $sipid,
                'mesaj' => $mesaj,
                'tarih' => $tarih,
                'url' => $url
            ]
        );
        DB::table('bildirim')->insert($data);
    }
    function reg2($val,$user,$prod) {
        $dateFormat = "Y n j, g:i a";
        $logger = new Logger('my_logger');
        $logger->pushHandler(new StreamHandler(__DIR__.'/app.log', Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());
        $logger->info($val, ['kullanıcı' => $user, 'ürün' => $prod]);
    }

    function dateformat($date) {
        $data = explode('-',$date);
        $newdata = $data[2].'-'.$data[1].'-'.$data[0];
        return $newdata;
    }
    function dateformat2($date,$sc) {
        $data = explode('-',$date);
        $newdata = $data[2].$sc.$data[1].$sc.$data[0];
        return $newdata;
    }
    function pdficerik($sip)
    {
        $siparis = DB::table('uye_siparisler')
            ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
            ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
            ->leftJoin('uye_adresler','uye_adresler.uye','=','uye_siparisler.uye')
            ->where('uye_siparisler.sip_id', $sip)
            ->first();
        $odeme_adi = $siparis->odeme_adi;
        $geneltoplam = $siparis->geneltoplam;
        $tarih2 = dateformat($siparis->tarih);
        $adres = $siparis->adres;
        $tarih = date('d.m.Y');
        return view('sozlesme.mesafeli-satis-sozlesmesi', compact('tarih','odeme_adi','geneltoplam','tarih2','adres'));
    }
    function rol($rol) {
        $userid =Auth::user()->id;
        $roles = DB::table('yonetici_yetki')->where('modul',$rol)->where('uye',$userid)->first();
        $durum = $roles->durum;
        return $durum;
    }
    function newProduct($id){
        DB::table('yeniurun')->insert(['uid' => $id]);
    }
