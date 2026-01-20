<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SepetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $siteadi = baslik();
            $userid = Auth::user()->id;
            $bakiye_limit = Auth::user()->acik_hesap_limit ?? 0;
            
            // odeme_yontemleri tablosu yoksa varsayılan ödeme tiplerini oluştur
            try {
                $odeme_tipi = DB::table('odeme_yontemleri')->where('durum','1')->get();
                // Eğer hiç ödeme tipi yoksa varsayılanları ekle
                if($odeme_tipi->count() == 0) {
                    // Tablo varsa ama boşsa varsayılan verileri ekle
                    try {
                        DB::table('odeme_yontemleri')->insert([
                            ['id' => 1, 'odeme_adi' => 'Havale/EFT', 'durum' => 1],
                            ['id' => 2, 'odeme_adi' => 'Açık Hesap', 'durum' => 1],
                            ['id' => 3, 'odeme_adi' => 'Nakit', 'durum' => 1],
                        ]);
                        $odeme_tipi = DB::table('odeme_yontemleri')->where('durum','1')->get();
                    } catch (\Exception $insertError) {
                        // Tablo yoksa veya insert başarısızsa boş collection döndür
                        \Log::warning('odeme_yontemleri varsayılan veriler eklenemedi: ' . $insertError->getMessage());
                        $odeme_tipi = collect([]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('odeme_yontemleri tablosu bulunamadı: ' . $e->getMessage());
                $odeme_tipi = collect([]);
            }
            
            // kargolar tablosu yoksa boş array döndür
            try {
                $kargo = DB::table('kargolar')->where('durum','1')->get();
            } catch (\Exception $e) {
                \Log::warning('kargolar tablosu bulunamadı: ' . $e->getMessage());
                $kargo = collect([]);
            }
            
            // markalar tablosunda durum kolonu yok, tüm markaları getir
            try {
                $markalar = DB::table('markalar')->get();
            } catch (\Exception $e) {
                \Log::warning('markalar tablosu bulunamadı: ' . $e->getMessage());
                $markalar = collect([]);
            }
            
            // kategoriler tablosunda ust_id kolonu olmayabilir, kontrol et
            try {
                $kategoriler = DB::table('kategoriler')->get();
            } catch (\Exception $e) {
                \Log::warning('kategoriler tablosu bulunamadı: ' . $e->getMessage());
                $kategoriler = collect([]);
            }
            
            // uye_adresler tablosu yoksa boş array döndür
            try {
                $adresler = DB::table('uye_adresler')->where('uye',$userid)->where('durum','1')->get();
            } catch (\Exception $e) {
                \Log::warning('uye_adresler tablosu bulunamadı: ' . $e->getMessage());
                $adresler = collect([]);
            }
            
            $adres = count($adresler);
            $fiyat = DB::table('b2b_users')->find($userid);
            // b2b_users tablosunda fiyat_gurubu kolonu yok, varsayılan olarak 1 kullan
            $fiyatid = isset($fiyat->fiyat_gurubu) ? $fiyat->fiyat_gurubu : 1;

            // Önce urun_id kolonunu kontrol et, yoksa id kullan
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }
            
            // sepet tablosu yoksa boş array döndür
            try {
                // urun_fiyatlari tablosu var mı kontrol et
                if (DB::getSchemaBuilder()->hasTable('urun_fiyatlari')) {
                    $sepet = DB::table('sepet')
                        ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                        ->leftJoin('urun_fiyatlari', 'sepet.urun', '=', 'urun_fiyatlari.urun')->where('fiyat_id', $fiyatid)
                        ->select('urunler.stokkodu as urun_kodu', 'urunler.stokadi as urun_adi', 'urunler.*', 'sepet.id', 'urun_fiyatlari.fiyat', 'sepet.adet', 'sepet.kampanya_tipi', 'sepet.gosterilecek_adet')
                        ->where('uye', $userid)
                        ->get();
                } else {
                    // urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
                    $sepet = DB::table('sepet')
                        ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                        ->select('urunler.stokkodu as urun_kodu', 'urunler.stokadi as urun_adi', 'urunler.*', 'sepet.id', 'urunler.fiyat', 'sepet.adet', 'sepet.kampanya_tipi', 'sepet.gosterilecek_adet')
                        ->where('uye', $userid)
                        ->get();
                }
                
                // Kampanya paketler için gösterilecek adeti ayarla
                $sepet = $sepet->map(function($item) {
                    if (!empty($item->kampanya_tipi) && !empty($item->gosterilecek_adet)) {
                        // Kampanya paket ise gösterilecek adeti kullan
                        $item->gosterilen_adet = $item->gosterilecek_adet;
                    } else {
                        // Normal ürün ise normal adeti kullan
                        $item->gosterilen_adet = $item->adet;
                    }
                    return $item;
                });
            } catch (\Exception $e) {
                \Log::warning('sepet tablosu bulunamadı: ' . $e->getMessage());
                $sepet = collect([]);
            }

            $iskonto = Auth::user()->iskonto ?? 0;
            // Eğer iskonto 0 ise varsayılan olarak %10 kullan
            if ($iskonto == 0) {
                $iskonto = 10;
            }
            return view('front.sepet',compact('siteadi','sepet','iskonto','odeme_tipi','kargo','markalar','kategoriler','bakiye_limit','adres'));
        } catch (\Exception $e) {
            \Log::error('SepetController@index error: ' . $e->getMessage());
            // Hata durumunda varsayılan iskonto %10
            $defaultIskonto = Auth::user()->iskonto ?? 10;
            if ($defaultIskonto == 0) {
                $defaultIskonto = 10;
            }
            return view('front.sepet', [
                'siteadi' => baslik(),
                'sepet' => collect([]),
                'iskonto' => $defaultIskonto,
                'odeme_tipi' => collect([]),
                'kargo' => collect([]),
                'markalar' => collect([]),
                'kategoriler' => collect([]),
                'bakiye_limit' => 0,
                'adres' => 0
            ]);
        }
    }
    public function sepetonay(Request $request, $id) {
        try {
            $aid=1;
            $userid = Auth::user()->id;
            
            // ayarlar tablosu yoksa varsayılan değer
            try {
                $ayar = DB::table('ayarlar')->find($aid);
                $siteadi = ($ayar && isset($ayar->site_adi)) ? $ayar->site_adi : baslik();
            } catch (\Exception $e) {
                $siteadi = baslik();
            }
            
            // uye_siparisler tablosu yoksa boş array
            try {
                $siparis = DB::table('uye_siparisler')->where('sip_id',$id)->get();
            } catch (\Exception $e) {
                \Log::warning('sepetonay: uye_siparisler tablosu bulunamadı: ' . $e->getMessage());
                $siparis = collect([]);
            }
            
            // uye_adresler tablosu yoksa boş array
            try {
                $adresler = DB::table('uye_adresler')->where('uye',$userid)->where('durum','1')->get();
            } catch (\Exception $e) {
                \Log::warning('sepetonay: uye_adresler tablosu bulunamadı: ' . $e->getMessage());
                $adresler = collect([]);
            }
            
            $bakiyelimit = Auth::user()->acik_hesap_limit ?? 0;
            
            // bankalar tablosu yoksa boş array
            try {
                $bankalar = DB::table('bankalar')->where('durum','1')->get();
            } catch (\Exception $e) {
                \Log::warning('sepetonay: bankalar tablosu bulunamadı: ' . $e->getMessage());
                $bankalar = collect([]);
            }
            
            return view('front.sepetonay',compact('siteadi','siparis','adresler','bakiyelimit','bankalar'));
        } catch (\Exception $e) {
            \Log::error('sepetonay error: ' . $e->getMessage());
            return view('front.sepetonay', [
                'siteadi' => baslik(),
                'siparis' => collect([]),
                'adresler' => collect([]),
                'bakiyelimit' => 0,
                'bankalar' => collect([])
            ]);
        }
    }
    public function siparisonay(Request $request){
        try {
            $userid = Auth::user()->id;
            $odeme_tipi = $request->odeme_tipi;
            $kargo = $request->kargo ?? '1'; // Varsayılan kargo ID
            $iskonto = Auth::user()->iskonto ?? 0;
            $fiyat = DB::table('b2b_users')->find($userid);
            // b2b_users tablosunda fiyat_gurubu kolonu yok, varsayılan olarak 1 kullan
            $fiyatid = isset($fiyat->fiyat_gurubu) ? $fiyat->fiyat_gurubu : 1;

            // Önce urun_id kolonunu kontrol et, yoksa id kullan
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }

            // Sepet verilerini çek - urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
            if (DB::getSchemaBuilder()->hasTable('urun_fiyatlari')) {
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->leftJoin('urun_fiyatlari', function($join) use ($fiyatid, $idColumn) {
                        $join->on('sepet.urun', '=', 'urun_fiyatlari.urun')
                             ->where('urun_fiyatlari.fiyat_id', '=', $fiyatid);
                    })
                    ->select('urunler.*', 'urun_fiyatlari.fiyat', 'sepet.adet', 'sepet.id as sepet_id')
                    ->where('sepet.uye', $userid)
                    ->get();
            } else {
                // urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->select('urunler.*', 'urunler.fiyat', 'sepet.adet', 'sepet.id as sepet_id')
                    ->where('sepet.uye', $userid)
                    ->get();
            }
            
            if($sepet->count() == 0) {
                \Log::error('Sepet boş - User ID: ' . $userid);
                return response()->json(['error' => 'Sepet boş'], 400);
            }
            
            $total = 0;
            foreach($sepet as $hesap) {
                $fiyat = floatval($hesap->fiyat ?? 0);
                $adet = intval($hesap->adet ?? 0);
                $total += $fiyat * $adet;
            }

            $iskontolu = $total - ($total / 100 * $iskonto);
            $kdv = ($iskontolu / 100 * 20); // KDV %20
            $geneltoplam = $iskontolu + $kdv;
            $tarih = date('Y-m-d');
            $saat = date('H:i:s');
            
            // Token oluştur
            $token = Str::random(32);
            
            // uye_siparisler tablosunu kontrol et ve yoksa oluştur
            if (!DB::getSchemaBuilder()->hasTable('uye_siparisler')) {
                \Log::info('uye_siparisler tablosu bulunamadı, oluşturuluyor...');
                try {
                    DB::statement("CREATE TABLE IF NOT EXISTS `uye_siparisler` (
                        `sip_id` int(11) NOT NULL AUTO_INCREMENT,
                        `uye` int(11) NOT NULL,
                        `durum` varchar(255) DEFAULT '0',
                        `odeme` int(11) DEFAULT NULL,
                        `tutar` decimal(10,2) DEFAULT 0.00,
                        `iskonto` decimal(10,2) DEFAULT 0.00,
                        `kdv` decimal(10,2) DEFAULT 0.00,
                        `geneltoplam` decimal(10,2) DEFAULT 0.00,
                        `kargo` int(11) DEFAULT NULL,
                        `token` varchar(255) DEFAULT NULL,
                        `tarih` date DEFAULT NULL,
                        `saat` time DEFAULT NULL,
                        `kargo_durum` varchar(255) DEFAULT NULL,
                        `kargotakip` varchar(255) DEFAULT NULL,
                        PRIMARY KEY (`sip_id`),
                        KEY `uye` (`uye`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    \Log::info('uye_siparisler tablosu oluşturuldu');
                } catch (\Exception $e) {
                    \Log::error('uye_siparisler tablosu oluşturulamadı: ' . $e->getMessage());
                    return response()->json(['error' => 'Sipariş tablosu oluşturulamadı'], 500);
                }
            }
            
            $insert = DB::table('uye_siparisler')->insertGetId([
                'uye' => $userid,
                'durum' => '0',
                'odeme' => $odeme_tipi,
                'tutar' => $total,
                'iskonto' => $iskonto,
                'kdv' => $kdv,
                'geneltoplam' => $geneltoplam,
                'kargo' => $kargo,
                'token' => $token,
                'tarih' => $tarih,
                'saat' => $saat
            ]);
            
            \Log::info('Sipariş oluşturuldu - ID: ' . $insert . ', User ID: ' . $userid);
            
            // Bakiye güncelleme (b2b_users tablosunda bakiye kolonu yoksa atla)
            try {
                if (DB::getSchemaBuilder()->hasColumn('b2b_users', 'bakiye')) {
                    $uye_bakiye = Auth::user()->bakiye ?? 0;
                    $bakiye_toplam = $uye_bakiye + $geneltoplam;
                    DB::table('b2b_users')
                        ->where('id', $userid)
                        ->update(['bakiye' => $bakiye_toplam]);
                }
            } catch (\Exception $e) {
                \Log::warning('Bakiye güncelleme hatası: ' . $e->getMessage());
            }

            // uye_cari_extre tablosuna ekle (varsa)
            try {
                if (DB::getSchemaBuilder()->hasTable('uye_cari_extre')) {
                    DB::table('uye_cari_extre')->insert([
                        'uye' => $userid,
                        'yontem' => $odeme_tipi,
                        'tutar' => $geneltoplam,
                        'borc' => $geneltoplam,
                        'sid' => $insert,
                        'tarih' => $tarih,
                        'guncelbakiye' => $bakiye_toplam ?? 0
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('uye_cari_extre ekleme hatası: ' . $e->getMessage());
            }

            // Sipariş ürünlerini ekle - tablo yoksa oluştur
            if (!DB::getSchemaBuilder()->hasTable('uye_siparis_urunler')) {
                \Log::info('uye_siparis_urunler tablosu bulunamadı, oluşturuluyor...');
                try {
                    DB::statement("CREATE TABLE IF NOT EXISTS `uye_siparis_urunler` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `sipid` int(11) NOT NULL,
                        `uye` int(11) NOT NULL,
                        `urun` int(11) NOT NULL,
                        `adet` int(11) DEFAULT 1,
                        `tutar` decimal(10,2) DEFAULT 0.00,
                        `kdv` decimal(10,2) DEFAULT 0.00,
                        `iskonto` decimal(10,2) DEFAULT 0.00,
                        `genel_toplam` decimal(10,2) DEFAULT 0.00,
                        PRIMARY KEY (`id`),
                        KEY `sipid` (`sipid`),
                        KEY `uye` (`uye`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    \Log::info('uye_siparis_urunler tablosu oluşturuldu');
                } catch (\Exception $e) {
                    \Log::error('uye_siparis_urunler tablosu oluşturulamadı: ' . $e->getMessage());
                }
            }
            
            // Sipariş ürünlerini ekle
            foreach ($sepet as $show) {
                $toplam_fiyat = floatval($show->fiyat ?? 0) * intval($show->adet ?? 0);
                $iskonto_tutar = $toplam_fiyat / 100 * $iskonto;
                $iskontolu = $toplam_fiyat - $iskonto_tutar;
                $kdv_tutar = $iskontolu / 100 * 18;
                $genel_toplam = $iskontolu + $kdv_tutar;
                
                try {
                    DB::table('uye_siparis_urunler')->insert([
                        'sipid' => $insert,
                        'uye' => $userid,
                        'urun' => $show->id ?? $show->urun_id ?? 0,
                        'adet' => $show->adet,
                        'tutar' => $show->fiyat,
                        'kdv' => $kdv_tutar,
                        'iskonto' => $iskonto_tutar,
                        'genel_toplam' => $genel_toplam
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Sipariş ürünü eklenemedi: ' . $e->getMessage());
                }
            }

            // ogteklif2 tablosuna kayıt yap (admin paneli için)
            try {
                $user = DB::table('b2b_users')->where('id', $userid)->first();
                $cariCode = $user->cari_code ?? null;
                $companyId = $user->company_id ?? null;
                
                // Sirket bilgilerini çek
                $sirket = null;
                if ($cariCode) {
                    $sirket = DB::table('sirket')
                        ->where('s_arp_code', $cariCode)
                        ->orWhere('logo_company_code', $cariCode)
                        ->first();
                } elseif ($companyId) {
                    $sirket = DB::table('sirket')
                        ->where('sirket_id', $companyId)
                        ->first();
                }
                
                // ogteklif2 tablosuna kayıt yap (sirket bilgisi olsun ya da olmasın)
                if (DB::getSchemaBuilder()->hasTable('ogteklif2')) {
                    // Teklif kodu oluştur
                    $teklifkodu = 'SP-' . date('Ymd') . '-' . str_pad($insert, 6, '0', STR_PAD_LEFT);
                    
                    // Ödeme yöntemi bilgisini çek
                    $odemeAdi = 'Havale/EFT';
                    if ($odeme_tipi) {
                        try {
                            $odemeYontem = DB::table('odeme_yontemleri')->where('id', $odeme_tipi)->first();
                            if ($odemeYontem) {
                                $odemeAdi = $odemeYontem->odeme_adi ?? 'Havale/EFT';
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Ödeme yöntemi bilgisi alınamadı: ' . $e->getMessage());
                        }
                    }
                    
                    // Müşteri adını belirle
                    $musteriAdi = 'Bilinmiyor';
                    if ($sirket && isset($sirket->s_adi)) {
                        $musteriAdi = $sirket->s_adi;
                    } elseif ($user && isset($user->username)) {
                        $musteriAdi = $user->username;
                    }
                    
                    // ogteklif2 tablosuna kayıt
                    try {
                        $ogteklifId = DB::table('ogteklif2')->insertGetId([
                            'musteriadi' => $musteriAdi,
                            'teklifsiparis' => 'siparis',
                            'hazirlayanid' => $userid, // Bayi kullanıcı ID'si
                            'musteriid' => $companyId ?? 0,
                            'kime' => $musteriAdi,
                            'projeadi' => '',
                            'tekliftarihi' => $tarih . ' ' . $saat,
                            'teklifkodu' => $teklifkodu,
                            'teklifsartid' => 1,
                            'odemeturu' => $odemeAdi,
                            'sirketid' => $companyId ?? 0,
                            'sirket_arp_code' => $cariCode ?? '',
                            'tltutar' => $total,
                            'dolartutar' => 0,
                            'eurotutar' => 0,
                            'toplamtutar' => $total,
                            'kdv' => $kdv,
                            'geneltoplam' => $geneltoplam,
                            'kurtarih' => $tarih,
                            'eurokur' => 0,
                            'dolarkur' => 0,
                            'tur' => 'bayi_siparis',
                            'teklifgecerlilik' => date('Y-m-d', strtotime('+30 days')),
                            'teslimyer' => '',
                            'durum' => 'Sipariş Oluşturuldu / Gönderilecek',
                            'statu' => 'Beklemede',
                            'notes1' => 'Bayi panelinden oluşturuldu. Sipariş No: ' . $insert,
                            'order_status' => 'pending',
                            'sozlesme_id' => 5, // Varsayılan sözleşme ID
                            'doviz_goster' => 'TL',
                            'auxil_code' => ($sirket && isset($sirket->s_auxil_code)) ? $sirket->s_auxil_code : '',
                            'auth_code' => 'GMP',
                            'division' => '',
                            'department' => '',
                            'source_wh' => '',
                            'factory' => '',
                            'salesmanref' => 0
                        ]);
                        
                        \Log::info('ogteklif2 tablosuna kayıt yapıldı - ID: ' . $ogteklifId . ', Sipariş ID: ' . $insert . ', Cari Code: ' . ($cariCode ?? 'null') . ', Company ID: ' . ($companyId ?? 'null'));
                    } catch (\Exception $insertError) {
                        \Log::error('ogteklif2 insert hatası: ' . $insertError->getMessage());
                        \Log::error('Stack trace: ' . $insertError->getTraceAsString());
                        throw $insertError; // Hata oluşursa tekrar fırlat
                    }
                    
                    \Log::info('ogteklif2 tablosuna kayıt yapıldı - ID: ' . $ogteklifId);
                    
                    // ogteklifurun2 tablosuna ürünleri ekle
                    if (DB::getSchemaBuilder()->hasTable('ogteklifurun2')) {
                        foreach ($sepet as $show) {
                            $urunKodu = $show->stokkodu ?? $show->urun_kodu ?? '';
                            $urunAdi = $show->stokadi ?? $show->urun_adi ?? '';
                            $toplam_fiyat = floatval($show->fiyat ?? 0) * intval($show->adet ?? 0);
                            $iskonto_tutar = $toplam_fiyat / 100 * $iskonto;
                            $iskontolu = $toplam_fiyat - $iskonto_tutar;
                            
                            // LOGICALREF bilgisini çek (urunler tablosundan)
                            $logicalRef = null;
                            $urunId = $show->id ?? $show->urun_id ?? 0;
                            if ($urunId) {
                                try {
                                    // urunler tablosunda urun_id kolonu var mı kontrol et
                                    $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                                    $idCol = count($columns) > 0 ? 'urun_id' : 'id';
                                    
                                    $urunDetay = DB::table('urunler')
                                        ->where($idCol, $urunId)
                                        ->first();
                                    if ($urunDetay) {
                                        $logicalRef = $urunDetay->LOGICALREF ?? null;
                                    }
                                } catch (\Exception $e) {
                                    \Log::warning('LOGICALREF bilgisi alınamadı: ' . $e->getMessage());
                                }
                            }
                            
                            DB::table('ogteklifurun2')->insert([
                                'teklifid' => $ogteklifId,
                                'kod' => $urunKodu,
                                'adi' => $urunAdi,
                                'miktar' => $show->adet ?? 1,
                                'birim' => 'Adet',
                                'liste' => $show->fiyat ?? 0,
                                'doviz' => 'TL',
                                'iskonto' => $iskonto,
                                'nettutar' => $iskontolu,
                                'tutar' => $toplam_fiyat,
                                'product_internal_ref' => $logicalRef
                            ]);
                        }
                        \Log::info('ogteklifurun2 tablosuna ' . count($sepet) . ' ürün eklendi');
                    }
                    
                    // Durum geçişi kaydı (varsa)
                    if (DB::getSchemaBuilder()->hasTable('durum_gecisleri')) {
                        try {
                            $durumGecisData = [
                                'teklif_id' => $ogteklifId,
                                's_arp_code' => $cariCode ?? '',
                                'eski_durum' => '',
                                'yeni_durum' => 'Sipariş Oluşturuldu / Gönderilecek',
                                'degistiren_personel_id' => 0,
                                'notlar' => 'Bayi panelinden oluşturuldu'
                            ];
                            
                            // created_at kolonu varsa ekle
                            if (DB::getSchemaBuilder()->hasColumn('durum_gecisleri', 'created_at')) {
                                $durumGecisData['created_at'] = date('Y-m-d H:i:s');
                            }
                            
                            DB::table('durum_gecisleri')->insert($durumGecisData);
                        } catch (\Exception $e) {
                            \Log::warning('Durum geçişi kaydı yapılamadı: ' . $e->getMessage());
                        }
                    }
                } else {
                    \Log::warning('ogteklif2 tablosu bulunamadı. Cari Code: ' . ($cariCode ?? 'null') . ', Company ID: ' . ($companyId ?? 'null') . ', Sirket: ' . ($sirket ? 'var' : 'yok'));
                }
            } catch (\Exception $e) {
                \Log::error('ogteklif2 kayıt hatası: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                // Hata olsa bile sipariş oluşturulmuş sayılır, sadece log'a yazılır
            }

            // Sepeti temizle
            DB::table('sepet')
                ->where('uye', $userid)
                ->delete();

            \Log::info('Sipariş başarıyla oluşturuldu - ID: ' . $insert);
            return response()->json(['success' => true, 'siparis_id' => $insert], 200);
        } catch (\Exception $e) {
            \Log::error('siparisonay error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Sipariş oluşturulamadı: ' . $e->getMessage()], 500);
        }
    }
    public function sepetsil(Request $request) {
        $userid =Auth::user()->id;
        $id = $request->sepetid;
        $sepetsil = DB::table('sepet')
            ->where('id',$id)
            ->where('uye',$userid)
            ->delete();
        if($sepetsil) {
            echo '1';
        }
        else {
            echo '0';
        }
    }
    public function sepetsil2(Request $request) {
        $userid =Auth::user()->id;
        $id = $request->uid;
        $sepetsil = DB::table('sepet')
            ->where('urun',$id)
            ->where('uye',$userid)
            ->delete();
        if($sepetsil) {
            echo '1';
        }
        else {
            echo '0';
        }
    }
    public function sepetlist() {
        try {
            $userid = Auth::user()->id;
            $fiyat = DB::table('b2b_users')->find($userid);
            // b2b_users tablosunda fiyat_gurubu kolonu yok, varsayılan olarak 1 kullan
            $fiyatid = isset($fiyat->fiyat_gurubu) ? $fiyat->fiyat_gurubu : 1;
            
            // Önce urun_id kolonunu kontrol et, yoksa id kullan
            try {
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
            } catch (\Exception $colError) {
                $idColumn = 'id';
            }
            
            // urun_fiyatlari tablosu var mı kontrol et
            if (DB::getSchemaBuilder()->hasTable('urun_fiyatlari')) {
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->leftJoin('urun_fiyatlari', 'sepet.urun', '=', 'urun_fiyatlari.urun')->where('fiyat_id', $fiyatid)
                    ->select('urunler.*', 'sepet.id', 'urun_fiyatlari.fiyat', 'sepet.adet')
                    ->where('uye', $userid)
                    ->get();
            } else {
                // urun_fiyatlari tablosu yoksa direkt urunler tablosundan fiyat çek
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->select('urunler.*', 'sepet.id', 'urunler.fiyat', 'sepet.adet')
                    ->where('uye', $userid)
                    ->get();
            }
            
            $data = [];
            $toplam = 0;
            
            foreach($sepet as $show) {
                $fiyat = floatval($show->fiyat ?? 0);
                $toplam += $fiyat * $show->adet;
                
                // urun_adi yerine stokadi kullan
                $urunAdi = $show->stokadi ?? $show->urun_adi ?? 'Ürün Adı Yok';
                
                $data[] = [
                    'id' => $show->id,
                    'urun_adi' => $urunAdi,
                    'fiyat' => number_format($fiyat, 2, ',', '.'),
                    'adet' => $show->adet
                ];
            }
            
            // Son elemana toplam ekle
            if (count($data) > 0) {
                $data[count($data) - 1]['toplam'] = number_format($toplam, 2, ',', '.');
            }
            
            return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            \Log::error('sepetlist error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([], 500, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    public function sepetibosalt() {
        $userid =Auth::user()->id;
        $sepetibosalt = DB::table('sepet')
            ->where('uye',$userid)
            ->delete();
        if($sepetibosalt) {
            echo '1';
        }
        else {
            echo '0';
        }
    }

    public function upbasket(Request $request) {
        $urunid = $request->urunid;
        $adet = $request->adet;
        if($adet < 1) {
            echo '2';
        }
        else {
            // Sepet kaydını al
            $sepetKayit = DB::table('sepet')->where('id', $urunid)->first();
            
            if ($sepetKayit && !empty($sepetKayit->kampanya_tipi)) {
                // Kampanya paket ise gösterilecek adeti güncelle
                // Kampanya tipini parse et (10+1 gibi)
                preg_match('/(\d+)\+(\d+)/', $sepetKayit->kampanya_tipi, $matches);
                $alinanAdet = isset($matches[1]) ? intval($matches[1]) : 10;
                $hediyeAdet = isset($matches[2]) ? intval($matches[2]) : 1;
                
                // Gösterilecek adet = kullanıcının girdiği adet (paket sayısı)
                // Toplam adet = paket sayısı * (alinan + hediye)
                $paketSayisi = intval($adet);
                $toplamAdet = $paketSayisi * ($alinanAdet + $hediyeAdet);
                $gosterilecekAdet = $paketSayisi * $alinanAdet;
                
                $up = DB::table('sepet')
                    ->where('id', $urunid)
                    ->update([
                        'adet' => $toplamAdet,
                        'gosterilecek_adet' => $gosterilecekAdet
                    ]);
            } else {
                // Normal ürün ise normal güncelleme
                $up = DB::table('sepet')
                    ->where('id', $urunid)
                    ->update(['adet' => $adet]);
            }
            
            if($up) {
                // Önce urun_id kolonunu kontrol et
                try {
                    $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                    $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
                } catch (\Exception $colError) {
                    $idColumn = 'id';
                }
                
                $sepet = DB::table('sepet')
                    ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
                    ->leftJoin('urun_fiyatlari', 'sepet.urun', '=', 'urun_fiyatlari.urun')->where('fiyat_id', '1')
                    ->select('urunler.*', 'sepet.id', 'urun_fiyatlari.fiyat', 'sepet.adet', 'sepet.kampanya_tipi', 'sepet.gosterilecek_adet')
                    ->where('sepet.id', $urunid)
                    ->get();
                
                // Gösterilecek adeti ayarla
                $sepet = $sepet->map(function($item) {
                    if (!empty($item->kampanya_tipi) && !empty($item->gosterilecek_adet)) {
                        $item->gosterilen_adet = $item->gosterilecek_adet;
                    } else {
                        $item->gosterilen_adet = $item->adet;
                    }
                    return $item;
                });

                $iskonto = Auth::user()->iskonto;
                return $sepet;
            }
            else { echo '0'; }
        }

    }
    public function basketprice(){
        $userid = Auth::user()->id;
        
        // Önce urun_id kolonunu kontrol et
        try {
            $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
            $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
        } catch (\Exception $colError) {
            $idColumn = 'id';
        }
        
        $basket = DB::table('sepet')
            ->leftJoin('urunler', 'sepet.urun', '=', 'urunler.' . $idColumn)
            ->leftJoin('urun_fiyatlari', 'sepet.urun', '=', 'urun_fiyatlari.urun')->where('fiyat_id', '1')
            ->select('urunler.*', 'sepet.id', 'urun_fiyatlari.fiyat', 'sepet.adet', 'sepet.kampanya_tipi', 'sepet.gosterilecek_adet')
            ->where('uye', $userid)
            ->get();
        $total = 0;
        foreach($basket as $show) {
            // Kampanya paket ise gösterilecek adeti kullan, değilse normal adeti
            $hesaplamaAdet = (!empty($show->kampanya_tipi) && !empty($show->gosterilecek_adet)) 
                ? $show->gosterilecek_adet 
                : $show->adet;
            $total += $show->fiyat * $hesaplamaAdet;
        }
        $iskonto = Auth::user()->iskonto ?? 0;
        // Eğer iskonto 0 ise varsayılan olarak %10 kullan
        if ($iskonto == 0) {
            $iskonto = 10;
        }

        $indirim = $total / 100 * $iskonto;
        $aratoplam = $total - $indirim;
        $kdv = $aratoplam / 100 * 20; // KDV %20
        $geneltoplam = $aratoplam + $kdv;
        $fiyat = array(
            [
                'toplam' => para($total),
                'indirim' => para($indirim),
                'aratoplam' => para($aratoplam),
                'kdv' => para($kdv),
                'geneltoplam' => para($geneltoplam)
            ]
        );
        //echo '{';
            echo json_encode($fiyat);
        //echo '}';
    }
}
