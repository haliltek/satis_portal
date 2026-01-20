<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Services\LogoExtreService;

class HesapController extends Controller
{
    protected $logoExtreService;
    
    public function __construct(LogoExtreService $logoExtreService)
    {
        $this->middleware('auth');
        $this->logoExtreService = $logoExtreService;
    }
    public function index() {
        try {
            $siteadi = baslik();
            $userid = Auth::user()->id;
            $user = Auth::user();
            
            // Kullanıcının cari kodunu al
            $cariCode = $user->cari_code ?? null;
            
            // Logo'dan son 5 ödeme kaydını çek
            $sonOdemeler = collect([]);
            if ($cariCode) {
                try {
                    \Log::info('Logo ödeme kayıtları çekiliyor - Cari Code: ' . $cariCode);
                    $logoOdemeData = $this->logoExtreService->getSonOdemeKayitlari($cariCode, 5);
                    \Log::info('Logo ödeme kayıtları sayısı: ' . count($logoOdemeData));
                    \Log::info('Logo ödeme kayıtları: ' . json_encode($logoOdemeData, JSON_UNESCAPED_UNICODE));
                    
                    $sonOdemeler = collect($logoOdemeData)->map(function($item) {
                        // Tarih formatlamasını burada yap
                        $tarih = isset($item['tarih']) ? $item['tarih'] : '';
                        $tarihFormatted = $tarih;
                        if ($tarih && strpos($tarih, '-') !== false) {
                            $tarihParsed = strtotime($tarih);
                            if ($tarihParsed !== false) {
                                $tarihFormatted = date('d.m.Y', $tarihParsed);
                            }
                        }
                        $item['tarih_formatted'] = $tarihFormatted;
                        return (object)$item;
                    });
                } catch (\Exception $e) {
                    \Log::error('Logo son ödeme kayıtları çekme hatası: ' . $e->getMessage());
                    \Log::error('Stack trace: ' . $e->getTraceAsString());
                }
            } else {
                \Log::warning('Cari code bulunamadı - User ID: ' . $userid);
            }
            
            // bankalar tablosu yoksa boş array döndür
            try {
                $bankalar = DB::table('bankalar')->where('durum','1')->get();
            } catch (\Exception $e) {
                \Log::warning('bankalar tablosu bulunamadı: ' . $e->getMessage());
                $bankalar = collect([]);
            }

            // uye_siparisler tablosu yoksa boş array döndür
            try {
                $siparisler = DB::table('uye_siparisler')
                    ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_siparisler.odeme')
                    ->leftJoin('kargolar', 'kargolar.id', '=', 'uye_siparisler.kargo')
                    ->select('uye_siparisler.*','kargolar.name','odeme_yontemleri.odeme_adi')
                    ->where('uye_siparisler.uye', $userid)
                    ->get();
            } catch (\Exception $e) {
                \Log::warning('uye_siparisler tablosu bulunamadı: ' . $e->getMessage());
                $siparisler = collect([]);
            }

            return view('front/hesap', compact('siteadi','siparisler','bankalar','sonOdemeler'));
        } catch (\Exception $e) {
            \Log::error('HesapController@index error: ' . $e->getMessage());
            return view('front/hesap', [
                'siteadi' => baslik(),
                'siparisler' => collect([]),
                'bankalar' => collect([])
            ]);
        }
    }

    public function extre() {
        try {
            $siteadi = baslik();
            $userid = Auth::user()->id;
            $user = Auth::user();
            
            // Kullanıcının cari kodunu ve company_id'sini al
            $cariCode = $user->cari_code ?? null;
            $companyId = $user->company_id ?? null;
            
            // Şirket bilgilerini çek (açık hesap ve ödeme planı)
            $sirketBilgileri = null;
            $acikhesap = 0;
            $payplanCode = '';
            $payplanDef = '';
            
            // Önce company_id ile dene, sonra cari_code ile dene
            if ($companyId) {
                try {
                    $sirketBilgileri = DB::table('sirket')
                        ->where('sirket_id', $companyId)
                        ->select('acikhesap', 'payplan_code', 'payplan_def', 's_adi', 's_arp_code')
                        ->first();
                } catch (\Exception $e) {
                    \Log::warning('Sirket bilgileri çekme hatası (company_id): ' . $e->getMessage());
                }
            }
            
            // Eğer company_id ile bulunamadıysa, cari_code ile dene
            if (!$sirketBilgileri && $cariCode) {
                try {
                    $sirketBilgileri = DB::table('sirket')
                        ->where('s_arp_code', $cariCode)
                        ->orWhere('logo_company_code', $cariCode)
                        ->select('acikhesap', 'payplan_code', 'payplan_def', 's_adi', 's_arp_code', 'sirket_id')
                        ->first();
                    
                    // Eğer cari_code ile bulunduysa, company_id'yi güncelle
                    if ($sirketBilgileri && $sirketBilgileri->sirket_id) {
                        try {
                            DB::table('b2b_users')
                                ->where('id', $userid)
                                ->update(['company_id' => $sirketBilgileri->sirket_id]);
                        } catch (\Exception $e) {
                            \Log::warning('Company ID güncelleme hatası: ' . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Sirket bilgileri çekme hatası (cari_code): ' . $e->getMessage());
                }
            }
            
            if ($sirketBilgileri) {
                // Açık hesap bakiyesini normalize et (virgülü kaldır)
                $acikhesapRaw = $sirketBilgileri->acikhesap ?? '0';
                $acikhesapNormalized = str_replace([','], '', $acikhesapRaw);
                $acikhesap = floatval($acikhesapNormalized);
                $payplanCode = $sirketBilgileri->payplan_code ?? '';
                $payplanDef = $sirketBilgileri->payplan_def ?? '';
                
                // Eğer cari_code yoksa, sirket tablosundan al
                if (!$cariCode && $sirketBilgileri->s_arp_code) {
                    $cariCode = $sirketBilgileri->s_arp_code;
                    // b2b_users tablosuna kaydet
                    try {
                        DB::table('b2b_users')
                            ->where('id', $userid)
                            ->update(['cari_code' => $cariCode]);
                    } catch (\Exception $e) {
                        \Log::warning('Cari code güncelleme hatası: ' . $e->getMessage());
                    }
                }
            }
            
            // uye_siparisler tablosu yoksa boş array döndür
            try {
                $siparis = DB::table('uye_siparisler')->where('uye',$userid)->get();
            } catch (\Exception $e) {
                \Log::warning('uye_siparisler tablosu bulunamadı: ' . $e->getMessage());
                $siparis = collect([]);
            }
            
            // Logo'dan ekstre bilgilerini çek
            $extreData = [];
            $logoBakiye = 0;
            
            try {
                if ($cariCode) {
                    $extreData = $this->logoExtreService->getCariExtre($cariCode);
                    
                    // Cari bakiyesini hesapla ve kullanıcıya güncelle
                    $logoBakiye = $this->logoExtreService->getCariBakiye($cariCode);
                    
                    // b2b_users tablosundaki bakiyeyi güncelle
                    try {
                        DB::table('b2b_users')
                            ->where('id', $userid)
                            ->update(['bakiye' => $logoBakiye]);
                    } catch (\Exception $e) {
                        \Log::warning('Bakiye güncelleme hatası: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Logo ekstre çekme hatası: ' . $e->getMessage());
                // Hata durumunda eski yöntemi dene
                try {
                    $extreData = DB::table('uye_cari_extre')
                        ->leftJoin('odeme_yontemleri', 'odeme_yontemleri.id', '=', 'uye_cari_extre.yontem')
                        ->leftJoin('b2b_users', 'b2b_users.id', '=', 'uye_cari_extre.uye')
                        ->select('uye_cari_extre.*','b2b_users.bakiye','odeme_yontemleri.odeme_adi')
                        ->where('uye_cari_extre.uye', $userid)
                        ->get()
                        ->map(function($item) {
                            return (array)$item;
                        })
                        ->toArray();
                } catch (\Exception $fallbackError) {
                    \Log::warning('Fallback ekstre çekme hatası: ' . $fallbackError->getMessage());
                    $extreData = [];
                }
            }
            
            // Array'i object collection'a çevir (view'da -> kullanıldığı için)
            // Tarih formatlamasını ve renk hesaplamasını burada yap
            $extre = collect($extreData)->map(function($item) {
                // Tarih formatlamasını burada yap
                $tarih = isset($item['tarih']) ? $item['tarih'] : '';
                $tarihFormatted = $tarih;
                if ($tarih && strpos($tarih, '-') !== false) {
                    $tarihParsed = strtotime($tarih);
                    if ($tarihParsed !== false) {
                        $tarihFormatted = date('d.m.Y', $tarihParsed);
                    }
                }
                $item['tarih_formatted'] = $tarihFormatted;
                // Renk hesaplamasını burada yap
                $alacak = isset($item['alacak']) ? floatval($item['alacak']) : 0;
                $item['color'] = ($alacak > 0) ? '#4ead05' : '#666';
                return (object)$item;
            });

            return view('front/extre', compact(
                'siteadi',
                'extre',
                'siparis',
                'acikhesap',
                'payplanCode',
                'payplanDef',
                'logoBakiye',
                'sirketBilgileri',
                'cariCode'
            ));
        } catch (\Exception $e) {
            \Log::error('HesapController@extre error: ' . $e->getMessage());
            return view('front/extre', [
                'siteadi' => baslik(),
                'extre' => collect([]),
                'siparis' => collect([]),
                'acikhesap' => 0,
                'payplanCode' => '',
                'payplanDef' => '',
                'logoBakiye' => 0,
                'sirketBilgileri' => null,
                'cariCode' => null
            ]);
        }
    }
}
