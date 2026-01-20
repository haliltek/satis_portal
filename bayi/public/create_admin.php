<?php
/**
 * Admin Kullanıcısı Oluşturma Scripti
 * http://localhost/b2b-gemas-project-main/bayi/public/create_admin.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    // Admin kullanıcısı bilgileri
    $adminData = [
        'username' => 'admin',
        'email' => 'admin@gemas.com',
        'password' => Hash::make('admin123'),
        'company_id' => 1,
        'cari_code' => 'ADMIN001',
        'status' => 1,
        'role' => 'Admin',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    // Mevcut admin kullanıcısını kontrol et
    $existingAdmin = DB::table('b2b_users')
        ->where('email', 'admin@gemas.com')
        ->orWhere('username', 'admin')
        ->first();

    if ($existingAdmin) {
        // Mevcut admin kullanıcısını güncelle
        DB::table('b2b_users')
            ->where('id', $existingAdmin->id)
            ->update([
                'password' => Hash::make('admin123'),
                'status' => 1,
                'role' => 'Admin',
                'updated_at' => now(),
            ]);
        
        echo "✅ Mevcut admin kullanıcısı güncellendi!\n";
        echo "Kullanıcı ID: {$existingAdmin->id}\n";
    } else {
        // Yeni admin kullanıcısı oluştur
        $adminId = DB::table('b2b_users')->insertGetId($adminData);
        echo "✅ Yeni admin kullanıcısı oluşturuldu!\n";
        echo "Kullanıcı ID: {$adminId}\n";
    }

    echo "\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "🔐 ADMIN PANEL GİRİŞ BİLGİLERİ\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "URL: http://localhost/b2b-gemas-project-main/bayi/public/panel/login\n";
    echo "Kullanıcı Adı: admin\n";
    echo "E-posta: admin@gemas.com\n";
    echo "Şifre: admin123\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "\n";
    echo "⚠️  GÜVENLİK UYARISI: Bu script'i kullanımdan sonra silin!\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo "Dosya: " . $e->getFile() . "\n";
    echo "Satır: " . $e->getLine() . "\n";
}

