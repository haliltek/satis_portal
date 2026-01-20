<?php
/**
 * test_bayi Kullanıcısını Hızlıca Oluştur
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$username = 'test_bayi';
$email = 'test_bayi@gemas.com';
$password = 'test123';
$cariCode = '320.01.A01';

try {
    // Şirket ID'yi bul
    $company = DB::table('sirket')->where('s_arp_code', $cariCode)->first();
    if (!$company) {
        // İlk şirketi al
        $company = DB::table('sirket')->first();
        if ($company) {
            $cariCode = $company->s_arp_code;
        }
    }
    $companyId = $company ? $company->sirket_id : 1;
    
    $passwordHash = Hash::make($password);
    
    // Kullanıcı var mı kontrol et
    $existingUser = DB::table('b2b_users')
        ->where('username', $username)
        ->orWhere('email', $email)
        ->first();
    
    if ($existingUser) {
        // Güncelle
        DB::table('b2b_users')
            ->where('id', $existingUser->id)
            ->update([
                'username' => $username,
                'email' => $email,
                'password' => $passwordHash,
                'status' => 1,
                'role' => 'Bayi',
                'company_id' => $companyId,
                'cari_code' => $cariCode,
                'updated_at' => now()
            ]);
        echo "✅ Kullanıcı güncellendi!";
    } else {
        // Oluştur
        DB::table('b2b_users')->insert([
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'status' => 1,
            'role' => 'Bayi',
            'company_id' => $companyId,
            'cari_code' => $cariCode,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✅ Kullanıcı oluşturuldu!";
    }
    
    echo "\n\n";
    echo "📋 GİRİŞ BİLGİLERİ:\n";
    echo "==================\n";
    echo "E-Posta: $email\n";
    echo "Kullanıcı Adı: $username\n";
    echo "Şifre: $password\n";
    echo "\n";
    echo "🔗 Login Sayfası: http://localhost/b2b-gemas-project-main/bayi/public/login\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

