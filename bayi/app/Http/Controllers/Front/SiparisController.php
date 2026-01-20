<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;

class SiparisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
        $userid = Auth::user()->id;
            $siteadi = baslik();
            
            // Kullanıcının şirket bilgilerini al
            $user = DB::table('b2b_users')->where('id', $userid)->first();
            $companyId = $user->company_id ?? null;
            $cariCode = $user->cari_code ?? null;
            
            $allSiparisler = collect([]);
            
            // 1) uye_siparisler tablosundan siparişleri çek
            try {
                $uyeSiparisler = DB::table('uye_siparisler')
                    ->where('uye', $userid)
                    ->orderBy('tarih', 'desc')
                    ->orderBy('saat', 'desc')
                    ->orderBy('sip_id', 'desc')
                    ->get();
                
                // uye_siparisler formatını ogteklif2 formatına çevir
                foreach ($uyeSiparisler as $siparis) {
                    $allSiparisler->push((object)[
                        'id' => $siparis->sip_id ?? null,
                        'sip_id' => $siparis->sip_id ?? null,
                        'teklifkodu' => $siparis->sip_id ?? '',
                        'projeadi' => $siparis->projeadi ?? '',
                        'tekliftarihi' => ($siparis->tarih ?? '') . ' ' . ($siparis->saat ?? ''),
                        'durum' => $siparis->durum ?? '0',
                        'sdurum' => $siparis->durum ?? '0',
                        'geneltoplam' => $siparis->geneltoplam ?? 0,
                        'tltutar' => $siparis->tutar ?? 0,
                        'dolartutar' => 0,
                        'eurotutar' => 0,
                        'odeme_adi' => $siparis->odeme_adi ?? 'Bilinmiyor',
                        'name' => $siparis->name ?? 'Bilinmiyor',
                        'kargotakip' => $siparis->kargotakip ?? '',
                        'source' => 'uye_siparisler'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('SiparisController@index - uye_siparisler hatası: ' . $e->getMessage());
            }
            
            // 2) ogteklif2 tablosundan siparişleri çek (admin panelinden oluşturulan siparişler)
            try {
                $ogteklifSiparisler = collect([]);
                
                if ($companyId) {
                    // sirketid ile filtrele
                    $ogteklifSiparisler = DB::table('ogteklif2')
                        ->where('sirketid', $companyId)
                        ->whereNotNull('tekliftarihi')
                        ->orderBy('tekliftarihi', 'desc')
                        ->get();
                }
                
                // Eğer cariCode varsa ve henüz sipariş bulunamadıysa, sirket_arp_code ile de filtrele
                if ($cariCode && $ogteklifSiparisler->isEmpty()) {
                    $ogteklifSiparisler = DB::table('ogteklif2')
                        ->where('sirket_arp_code', $cariCode)
                        ->whereNotNull('tekliftarihi')
                        ->orderBy('tekliftarihi', 'desc')
                        ->get();
                }
                
                // ogteklif2 formatını uye_siparisler formatına çevir
                foreach ($ogteklifSiparisler as $siparis) {
                    $allSiparisler->push((object)[
                        'id' => $siparis->id ?? null,
                        'sip_id' => $siparis->id ?? null,
                        'teklifkodu' => $siparis->teklifkodu ?? '',
                        'projeadi' => $siparis->projeadi ?? $siparis->musteriadi ?? '',
                        'tekliftarihi' => $siparis->tekliftarihi ?? '',
                        'durum' => $siparis->durum ?? 'Beklemede',
                        'sdurum' => $siparis->durum ?? 'Beklemede',
                        'geneltoplam' => $siparis->geneltoplam ?? 0,
                        'tltutar' => $siparis->tltutar ?? 0,
                        'dolartutar' => $siparis->dolartutar ?? 0,
                        'eurotutar' => $siparis->eurotutar ?? 0,
                        'odeme_adi' => $siparis->odemeturu ?? 'Bilinmiyor',
                        'name' => 'Bilinmiyor',
                        'kargotakip' => '',
                        'source' => 'ogteklif2'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('SiparisController@index - ogteklif2 hatası: ' . $e->getMessage());
            }
            
            // Tüm siparişleri tarihe göre sırala
            $siparisler = $allSiparisler->sortByDesc(function($siparis) {
                return $siparis->tekliftarihi ?? '';
            })->values();
            
            \Log::info('SiparisController@index - Toplam sipariş sayısı: ' . count($siparisler) . ', User ID: ' . $userid . ', Company ID: ' . $companyId . ', Cari Code: ' . $cariCode);
            
            // bankalar tablosu yoksa boş array döndür
            try {
        $hesaplar = DB::table('bankalar')->get();
            } catch (\Exception $e) {
                \Log::warning('bankalar tablosu bulunamadı: ' . $e->getMessage());
                $hesaplar = collect([]);
            }
            
        return view('front.siparis',compact('siteadi','siparisler','hesaplar'));
        } catch (\Exception $e) {
            \Log::error('SiparisController@index error: ' . $e->getMessage());
            return view('front.siparis', [
                'siteadi' => baslik(),
                'siparisler' => collect([]),
                'hesaplar' => collect([])
            ]);
        }
    }

    public function odenmemis()
    {
        $userid = Auth::user()->id;
        $siteadi = baslik();
        $siparisler = DB::table('uye_siparisler')
            ->where('uye', $userid)
            ->where('durum', 1)
            ->get();
        return $siparisler;
    }

    public function siparisdetay(Request $request,$id)
    {
        $userid = Auth::user()->id;
        $siteadi = baslik();
        //$siparisler = DB::table('uye_siparisler')->where('sip_id',$id)->get();
        //$detay = DB::table('uye_siparis_urunler')->where('sipid',$id)->get();

        $detay = DB::table('uye_siparisler')
        ->leftJoin('uye_siparis_urunler', 'uye_siparis_urunler.sipid', '=', 'uye_siparisler.sip_id')
        ->leftJoin('urunler', 'urunler.id', '=', 'uye_siparis_urunler.urun')
        ->select('uye_siparis_urunler.*','uye_siparisler.iskonto','uye_siparisler.geneltoplam','uye_siparisler.tarih','urunler.urun_adi','urunler.urun_kodu')
        ->where('uye_siparisler.sip_id', $id)
        ->get();


        return view('front.siparis-detay',compact('siteadi','detay'));
    }
    public function sipnot(Request $request){
        $not = $request->sipnot;
        $sid = $request->sid;
        $upnote = DB::table('uye_siparisler')->where('sip_id',$sid)->update([
            'sipnot' => $not
            ]
        );
        if($upnote) { echo '1'; }
    }
    public function oemistek(Request $request){
        $oem = $request->oemno;
        $userid = Auth::user()->id;
        $reg = DB::table('oemistek')->insert(['oem' => $oem,'userid'=>$userid]);
        if($reg) { echo '1'; mailer()-sender('info@acarhortum.com','oem talep'.$oem,'',''); }
    }
    public function detail(Request $request,$id)
    {
        try {
            $userid = Auth::user()->id;
            
            // Siparişin bu kullanıcıya ait olduğunu kontrol et
            $siparisCheck = DB::table('uye_siparisler')
                ->where('sip_id', $id)
                ->where('uye', $userid)
                ->first();
            
            if (!$siparisCheck) {
                \Log::warning('detail: Sipariş bulunamadı veya kullanıcıya ait değil. Sipariş ID: ' . $id . ', User ID: ' . $userid);
                return response()->json([]);
            }
            
            // urunler tablosunda urun_id kolonu var mı kontrol et
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }
            
            // urunler tablosunda stokadi ve stokkodu kolonları var mı kontrol et
            $stokadiColumn = 'stokadi';
            $stokkoduColumn = 'stokkodu';
            try {
                $stokadiCheck = DB::select("SHOW COLUMNS FROM urunler LIKE 'stokadi'");
                $stokkoduCheck = DB::select("SHOW COLUMNS FROM urunler LIKE 'stokkodu'");
                
                if (count($stokadiCheck) == 0) {
                    $stokadiColumn = 'urun_adi';
                }
                if (count($stokkoduCheck) == 0) {
                    $stokkoduColumn = 'urun_kodu';
                }
            } catch (\Exception $e) {
                // Varsayılan değerleri kullan
            }
            
            $sdetail = DB::table('uye_siparisler')
                ->leftJoin('uye_siparis_urunler', 'uye_siparis_urunler.sipid', '=', 'uye_siparisler.sip_id')
                ->leftJoin('urunler', 'urunler.' . $idColumn, '=', 'uye_siparis_urunler.urun')
                ->select(
                    'uye_siparis_urunler.*',
                    'uye_siparisler.iskonto',
                    'uye_siparisler.geneltoplam',
                    'uye_siparisler.tarih',
                    'urunler.' . $stokadiColumn . ' as urun_adi',
                    'urunler.' . $stokkoduColumn . ' as urun_kodu',
                    'uye_siparisler.kdv as sipkdv',
                    'uye_siparisler.tutar as siptutar',
                    'uye_siparisler.iskonto as sipiskonto'
                )
                ->where('uye_siparisler.sip_id', $id)
                ->where('uye_siparisler.uye', $userid)
                ->get();
            
            \Log::info('detail: Sipariş detayı çekildi. Sipariş ID: ' . $id . ', Ürün sayısı: ' . count($sdetail));
            
            return response()->json($sdetail);
        } catch (\Exception $e) {
            \Log::error('detail error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([]);
        }
    }

    public function makbuz(Request $request,$id) {
        try {
        $userid = Auth::user()->id;
            $siteadi = baslik();
            
            // ayarlar tablosu için default değerler oluştur
            $ayarlar = collect([
                (object)[
                    'unvan' => baslik() ?? 'Şirket Adı',
                    'gsm' => '',
                    'fax' => '',
                    'adres' => ''
                ]
            ]);
            
            // b2b_users tablosundan kullanıcı bilgilerini çek
            $user = DB::table('b2b_users')->where('id',$userid)->first();
            
            // sirket bilgilerini çek (cari_code veya company_id ile)
            $sirket = null;
            if ($user) {
                $cariCode = $user->cari_code ?? null;
                $companyId = $user->company_id ?? null;
                
                if ($cariCode) {
                    try {
                        $sirket = DB::table('sirket')
                            ->where('s_arp_code', $cariCode)
                            ->orWhere('logo_company_code', $cariCode)
                            ->first();
                    } catch (\Exception $e) {
                        \Log::warning('makbuz: sirket tablosu hatası: ' . $e->getMessage());
                    }
                }
                
                if (!$sirket && $companyId) {
                    try {
                        $sirket = DB::table('sirket')
                            ->where('sirket_id', $companyId)
                            ->first();
                    } catch (\Exception $e) {
                        \Log::warning('makbuz: sirket tablosu hatası (company_id): ' . $e->getMessage());
                    }
                }
            }
            
            // users collection'ını oluştur (b2b_users + sirket bilgileri)
            $users = collect([
                (object)[
                    'firma_unvani' => $sirket->s_adi ?? $user->username ?? 'Bilinmiyor',
                    'yetkili_ad_soyad' => $user->username ?? 'Bilinmiyor',
                    'sirket_telefonu' => $sirket->s_telefonu ?? $user->telefon ?? '',
                    'sirket_adres' => ($sirket->s_adresi ?? '') . ' ' . ($sirket->s_ilce ?? '') . ' ' . ($sirket->s_il ?? ''),
                    'vd' => $sirket->s_vd ?? '',
                    'vno' => $sirket->s_vno ?? ''
                ]
            ]);
            
            // urunler tablosunda urun_id kolonu var mı kontrol et
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }
            
        $detay = DB::table('uye_siparisler')
            ->leftJoin('uye_siparis_urunler', 'uye_siparis_urunler.sipid', '=', 'uye_siparisler.sip_id')
                ->leftJoin('urunler', 'urunler.' . $idColumn, '=', 'uye_siparis_urunler.urun')
                ->select('uye_siparis_urunler.*','uye_siparisler.iskonto','uye_siparisler.geneltoplam','uye_siparisler.tarih','uye_siparisler.kargotakip','uye_siparisler.saat','urunler.stokadi as urun_adi','urunler.stokkodu as urun_kodu')
            ->where('uye_siparisler.sip_id', $id)
            ->get();
            
        return view('front.makbuz',compact('siteadi','ayarlar','users','detay','id'));
        } catch (\Exception $e) {
            \Log::error('makbuz error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Makbuz yüklenirken bir hata oluştu.']);
        }
    }

    public function siparistamamla(Request $request) {
        try {
            $sipid = $request->sipid;
            $teslimat = $request->teslimat ?? null;
            $fatura = $request->fatura ?? null;
            $tip = $request->tip ?? null;
            
            // uye_siparisler tablosunu kontrol et
            if (!DB::getSchemaBuilder()->hasTable('uye_siparisler')) {
                \Log::error('siparistamamla: uye_siparisler tablosu bulunamadı');
                return response('0', 500)->header('Content-Type', 'text/plain');
            }
            
            // Eksik kolonları kontrol et ve ekle
            if (!DB::getSchemaBuilder()->hasColumn('uye_siparisler', 'teslimat_adres')) {
                try {
                    DB::statement("ALTER TABLE `uye_siparisler` ADD COLUMN `teslimat_adres` INT(11) NULL DEFAULT NULL AFTER `kargotakip`");
                    \Log::info('teslimat_adres kolonu eklendi');
                } catch (\Exception $e) {
                    \Log::warning('teslimat_adres kolonu eklenemedi: ' . $e->getMessage());
                }
            }
            
            if (!DB::getSchemaBuilder()->hasColumn('uye_siparisler', 'fatura_adres')) {
                try {
                    DB::statement("ALTER TABLE `uye_siparisler` ADD COLUMN `fatura_adres` INT(11) NULL DEFAULT NULL AFTER `teslimat_adres`");
                    \Log::info('fatura_adres kolonu eklendi');
                } catch (\Exception $e) {
                    \Log::warning('fatura_adres kolonu eklenemedi: ' . $e->getMessage());
                }
            }
            
            // Siparişin var olduğunu kontrol et
            $siparis = DB::table('uye_siparisler')->where('sip_id', $sipid)->first();
            if (!$siparis) {
                \Log::error('siparistamamla: Sipariş bulunamadı. Sipariş ID: ' . $sipid);
                return response('0', 404)->header('Content-Type', 'text/plain');
            }
            
            // Kullanıcının siparişi olduğunu kontrol et
            if ($siparis->uye != Auth::user()->id) {
                \Log::error('siparistamamla: Sipariş bu kullanıcıya ait değil. Sipariş ID: ' . $sipid . ', User ID: ' . Auth::user()->id);
                return response('0', 403)->header('Content-Type', 'text/plain');
            }
            
            // Adresler boş olsa bile siparişi tamamla (cariler zaten kayıtlı)
            $updateData = [
                'durum' => '1'
            ];
            
            // Adresler varsa ekle, yoksa null bırak
            if ($teslimat && $teslimat !== '') {
                $updateData['teslimat_adres'] = $teslimat;
            }
            if ($fatura && $fatura !== '') {
                $updateData['fatura_adres'] = $fatura;
            }
            
            // Update işlemini dene
            try {
                // Önce mevcut durumu kontrol et
                \Log::info('siparistamamla - Mevcut durum: ' . ($siparis->durum ?? 'null') . ', Yeni durum: 1');
                
                $up = DB::table('uye_siparisler')
                    ->where('sip_id', $sipid)
                    ->where('uye', Auth::user()->id) // Güvenlik için kullanıcı kontrolü
                    ->update($updateData);
                
                \Log::info('siparistamamla update sonucu: ' . ($up ? 'başarılı' : 'başarısız') . ', Sipariş ID: ' . $sipid . ', Update Data: ' . json_encode($updateData) . ', Affected Rows: ' . $up);
                
                // Update sonrası siparişi tekrar kontrol et
                $updatedSiparis = DB::table('uye_siparisler')->where('sip_id', $sipid)->first();
                if ($updatedSiparis) {
                    \Log::info('siparistamamla - Güncellenmiş sipariş durumu: ' . ($updatedSiparis->durum ?? 'null') . ', User ID: ' . ($updatedSiparis->uye ?? 'null'));
                } else {
                    \Log::error('siparistamamla - Update sonrası sipariş bulunamadı!');
                }
                
                // Update başarısız olursa nedenini kontrol et
                if (!$up || $up === 0) {
                    // Belki durum zaten '1' veya '1'?
                    $checkSiparis = DB::table('uye_siparisler')->where('sip_id', $sipid)->first();
                    if ($checkSiparis) {
                        $currentDurum = $checkSiparis->durum;
                        // Durum '1' veya 1 ise zaten tamamlanmış say
                        if ($currentDurum == '1' || $currentDurum == 1) {
                            \Log::info('siparistamamla: Sipariş zaten tamamlanmış durumda (durum: ' . $currentDurum . ')');
                            $up = true; // Zaten tamamlanmışsa başarılı say
                        } else {
                            \Log::error('siparistamamla: Update başarısız - Mevcut durum: ' . $currentDurum . ', İstenen durum: 1');
                            \Log::error('Sipariş detayları: ' . json_encode($checkSiparis));
                            
                            // Manuel update dene (durum kolonu tipini kontrol et)
                            try {
                                // Önce durum kolonunun tipini kontrol et
                                $columns = DB::select("SHOW COLUMNS FROM uye_siparisler WHERE Field = 'durum'");
                                if (!empty($columns)) {
                                    $columnType = $columns[0]->Type;
                                    \Log::info('durum kolonu tipi: ' . $columnType);
                                    
                                    // String ise '1', integer ise 1 kullan
                                    if (strpos($columnType, 'varchar') !== false || strpos($columnType, 'char') !== false || strpos($columnType, 'text') !== false) {
                                        $updateData['durum'] = '1';
                                    } else {
                                        $updateData['durum'] = 1;
                                    }
                                    
                                    $up = DB::table('uye_siparisler')
                                        ->where('sip_id', $sipid)
                                        ->where('uye', Auth::user()->id)
                                        ->update($updateData);
                                    
                                    \Log::info('Manuel update denemesi sonucu: ' . ($up ? 'başarılı' : 'başarısız'));
                                }
                            } catch (\Exception $manualError) {
                                \Log::error('Manuel update hatası: ' . $manualError->getMessage());
                            }
                        }
                    } else {
                        \Log::error('siparistamamla: Sipariş bulunamadı (update sonrası kontrol)');
                    }
                }
            } catch (\Exception $updateError) {
                \Log::error('siparistamamla update hatası: ' . $updateError->getMessage() . ', Sipariş ID: ' . $sipid);
                \Log::error('Update Data: ' . json_encode($updateData));
                \Log::error('Stack trace: ' . $updateError->getTraceAsString());
                return response('0', 500)->header('Content-Type', 'text/plain');
            }
            
            if($up) {
                $userid = Auth::user()->id;
                
                // Bildirim fonksiyonları varsa çağır, yoksa atla
                if (function_exists('bildirim')) {
                    try {
                        bildirim('Yeni sipariş', $sipid, '1 yeni sipariş', '/panel/siparisler');
                    } catch (\Exception $e) {
                        \Log::warning('bildirim fonksiyonu hatası: ' . $e->getMessage());
                    }
                }
                
                if (function_exists('reg')) {
                    try {
                        reg('yeni sipariş', $userid, 'Sipariş', $sipid);
                    } catch (\Exception $e) {
                        \Log::warning('reg fonksiyonu hatası: ' . $e->getMessage());
                    }
                }
                
                // Mailer fonksiyonu varsa çağır, yoksa atla
                if (function_exists('mailer')) {
                    try {
                        $mailer = mailer();
                        if ($mailer !== null && is_object($mailer) && method_exists($mailer, 'pdf')) {
                            $mailer->pdf($sipid);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('mailer fonksiyonu hatası: ' . $e->getMessage());
                    }
                }
                
                \Log::info('Sipariş tamamlandı - ID: ' . $sipid . ', User ID: ' . $userid);
                
                // Son kontrol - siparişin durumunu tekrar kontrol et
                $finalCheck = DB::table('uye_siparisler')->where('sip_id', $sipid)->where('uye', $userid)->first();
                if ($finalCheck) {
                    \Log::info('siparistamamla - Final check - Sipariş durumu: ' . ($finalCheck->durum ?? 'null') . ', Sipariş ID: ' . $sipid);
                    return response($sipid, 200)->header('Content-Type', 'text/plain');
                } else {
                    \Log::error('siparistamamla - Final check başarısız - Sipariş bulunamadı!');
                    return response('0', 500)->header('Content-Type', 'text/plain');
                }
            } else {
                \Log::error('siparistamamla: Sipariş güncellenemedi. Sipariş ID: ' . $sipid);
                return response('0', 500)->header('Content-Type', 'text/plain');
            }
        } catch (\Exception $e) {
            \Log::error('siparistamamla error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response('0', 500)->header('Content-Type', 'text/plain');
        }
    }

    public function siparisdurum(Request $request,$id) {
        $siteadi = baslik();
        if($id == '0') {
            $mesaj = 'hata!';
            return view('front.siparisson',compact('mesaj'));
        }

    }
    public function siparissil(Request $request) {
        try {
        $query = DB::table('uye_siparisler')->where('sip_id',$request->sid)->first();
            if (!$query) {
                return response('0', 404)->header('Content-Type', 'text/plain');
            }
            
        $userid = Auth::user()->id;
            $bakiye = Auth::user()->bakiye ?? 0;
            $geneltoplam = $query->geneltoplam ?? 0;
        $guncelbakiye = $bakiye - $geneltoplam;
            
            // uye_siparis_urunler tablosunu kontrol et
            if (DB::getSchemaBuilder()->hasTable('uye_siparis_urunler')) {
        DB::table('uye_siparis_urunler')->where('sipid',$request->sid)->delete();
            }
            
        $sil = DB::table('uye_siparisler')->where('sip_id',$request->sid)->delete();
            
        if($sil){
                // b2b_users tablosunda bakiye kolonu varsa güncelle
                if (DB::getSchemaBuilder()->hasTable('b2b_users') && DB::getSchemaBuilder()->hasColumn('b2b_users', 'bakiye')) {
                    DB::table('b2b_users')->where('id',$userid)->update([
                    'bakiye' => $guncelbakiye
                    ]);
                }
                return response('1', 200)->header('Content-Type', 'text/plain');
            } else {
                return response('0', 500)->header('Content-Type', 'text/plain');
            }
        } catch (\Exception $e) {
            \Log::error('siparissil error: ' . $e->getMessage());
            return response('0', 500)->header('Content-Type', 'text/plain');
        }
    }

    public function sipcontrol() {
        try {
        $userid = Auth::user()->id;
        $query = DB::table('uye_siparisler')->where('uye',$userid)->where('durum','0')->get();
        $adet = count($query);
            return response($adet, 200)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            \Log::error('sipcontrol error: ' . $e->getMessage());
            return response('0', 500)->header('Content-Type', 'text/plain');
        }
    }

    public function siparisfiyat(Request $request) {
        $sid = $request->sid;
        $fiyat_sorgu = DB::table('uye_siparisler')->where('sip_id',$sid)->first();
        return $fiyat_sorgu->geneltoplam;
    }
    public function odemebildir(Request $request) {
        $userid = Auth::user()->id;
        $odenen = $request->odenen;
        $odenen = str_replace(',','.',$odenen);
        $kayit = DB::table('odemebildirim')->insert(
            [
                'hesap' => $request->hesap,
                'odenen' => $odenen,
                'gonderen' => $request->gonderen,
                'uye' => $userid,
                'sipid' => $request->sid
            ]
        );

        if($kayit){

            bildirim('Yeni ödeme bildirimi',$userid,'Havale/Eft ödeme bildirimi','');

            echo '1';
        }
    }
    public function siparisbilgi(Request $request) {
        $query = DB::table('uye_siparisler')
            ->leftJoin('kargolar','kargolar.id','=','uye_siparisler.kargo')
            ->leftJoin('odeme_yontemleri','odeme_yontemleri.id','=','uye_siparisler.odeme')
            ->where('sip_id',$request->sid)
            ->get();
        return $query;
    }
    public function updateorder(Request $request){
        $update = DB::table('uye_siparisler')->where('sip_id',$request->sid)
            ->update(
                [
                    'kargo' => $request->kargo,
                    'odeme' => $request->odeme
                ]
            );
        if($update) { echo '1'; }
    }
    public function siparisurun(Request $request) {
        $urunid = $request->urunid;
        $adet = $request->adet;
        $sid = $request->sid;

        $query = DB::table('uye_siparis_urunler')
            ->leftJoin('urun_fiyatlari','urun_fiyatlari.urun','=','uye_siparis_urunler.urun')
            ->where('s_urun_id',$urunid)
            ->first();
        $urun = $query->urun;
        $iskonto = Auth::user()->iskonto;
        $fiyat = $query->fiyat;
        $ara = $fiyat * $adet;
        $iskonto_tutar = $ara / 100 * $iskonto;
        $iskontolu = $ara - $iskonto_tutar;
        $kdv = $iskontolu / 100 * 18;
        $toplam = $iskontolu + $kdv;
        $urun_guncelle = DB::table('uye_siparis_urunler')->where('s_urun_id',$urunid)->update(
            [
                'adet' => $adet,
                'tutar' => $ara,
                'kdv' => $kdv,
                'iskonto' => $iskonto_tutar,
                'genel_toplam' => $toplam
            ]
        );
        if($urun_guncelle) {
            $total = '0';
            $urunler = DB::table('uye_siparis_urunler')
                ->leftJoin('urun_fiyatlari','urun_fiyatlari.urun','=','uye_siparis_urunler.urun')
                ->where('sipid',$sid)
                ->get();
            foreach($urunler as $hesap) {
                $total += $hesap->fiyat * $hesap->adet;
            }
            $diskonto = $total / 100 * $iskonto;
            $iskontolu = $total - $total / 100 * $iskonto;
            $kdv = $iskontolu / 100 * 18;
            $geneltoplam = $iskontolu + $kdv;

            DB::table('uye_siparisler')
                ->where('sip_id',$sid)
                ->update(
                    [
                        'tutar' => $total,
                        'iskonto' => $diskonto,
                        'kdv' => $kdv,
                        'geneltoplam' => $geneltoplam
                    ]
                );

            $query = DB::table('uye_siparis_urunler')->where('s_urun_id',$urunid)->get();
            return $query;
        }
    }
}
