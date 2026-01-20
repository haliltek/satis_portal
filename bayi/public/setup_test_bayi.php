<?php
/**
 * test_bayi Kullanıcısını Oluştur/Güncelle
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
$cariCode = '320.01.A01'; // Örnek cari kodu

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Bayi Kullanıcı Kurulumu</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #3498db; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Test Bayi Kullanıcı Kurulumu</h1>
        
        <?php
        try {
            // Mevcut kullanıcıyı kontrol et
            $existingUser = DB::table('b2b_users')
                ->where('username', $username)
                ->orWhere('email', $email)
                ->first();
            
            $passwordHash = Hash::make($password);
            
            if ($existingUser) {
                // Kullanıcı var, güncelle
                echo '<div class="info">';
                echo '<h3>ℹ️ Kullanıcı Zaten Mevcut</h3>';
                echo '<p>Kullanıcı bulundu, bilgileri güncelleniyor...</p>';
                echo '</div>';
                
                // Şirket ID'yi bul
                $company = DB::table('sirket')->where('s_arp_code', $cariCode)->first();
                $companyId = $company ? $company->sirket_id : 1;
                
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
                
                echo '<div class="success">';
                echo '<h3>✅ Kullanıcı Güncellendi!</h3>';
                echo '</div>';
                
                $user = DB::table('b2b_users')->find($existingUser->id);
            } else {
                // Yeni kullanıcı oluştur
                echo '<div class="info">';
                echo '<h3>➕ Yeni Kullanıcı Oluşturuluyor</h3>';
                echo '</div>';
                
                // Şirket ID'yi bul
                $company = DB::table('sirket')->where('s_arp_code', $cariCode)->first();
                $companyId = $company ? $company->sirket_id : 1;
                
                $userId = DB::table('b2b_users')->insertGetId([
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
                
                echo '<div class="success">';
                echo '<h3>✅ Kullanıcı Oluşturuldu!</h3>';
                echo '</div>';
                
                $user = DB::table('b2b_users')->find($userId);
            }
            
            // Kullanıcı bilgilerini göster
            echo '<div class="info">';
            echo '<h3>📋 Giriş Bilgileri:</h3>';
            echo '<table>';
            echo '<tr><th>Alan</th><th>Değer</th></tr>';
            echo '<tr><td><strong>Kullanıcı Adı (Username)</strong></td><td>' . htmlspecialchars($user->username) . '</td></tr>';
            echo '<tr><td><strong>E-Posta</strong></td><td>' . htmlspecialchars($user->email) . '</td></tr>';
            echo '<tr><td><strong>Şifre</strong></td><td><code>' . htmlspecialchars($password) . '</code></td></tr>';
            echo '<tr><td><strong>Cari Kodu</strong></td><td>' . htmlspecialchars($user->cari_code ?? 'NULL') . '</td></tr>';
            echo '<tr><td><strong>Durum</strong></td><td>' . (($user->status ?? 0) == 1 ? '✅ Aktif' : '❌ Pasif') . '</td></tr>';
            echo '<tr><td><strong>Rol</strong></td><td>' . htmlspecialchars($user->role ?? 'NULL') . '</td></tr>';
            echo '<tr><td><strong>Şirket ID</strong></td><td>' . ($user->company_id ?? 'NULL') . '</td></tr>';
            echo '</table>';
            echo '</div>';
            
            echo '<div class="success">';
            echo '<h3>🎯 Login Sayfası:</h3>';
            echo '<p><strong>E-Posta veya Kullanıcı Adı:</strong> <code>' . htmlspecialchars($email) . '</code> veya <code>' . htmlspecialchars($username) . '</code></p>';
            echo '<p><strong>Şifre:</strong> <code>' . htmlspecialchars($password) . '</code></p>';
            echo '<p><a href="/b2b-gemas-project-main/bayi/public/login" class="btn">🔐 Login Sayfasına Git</a></p>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ Hata!</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3>💡 Notlar:</h3>
            <ul>
                <li>Bu sayfayı sadece bir kez çalıştırın</li>
                <li>Kullanıcı zaten varsa bilgileri güncellenir</li>
                <li>Şifre: <code>test123</code> (bcrypt ile hash'lenmiş)</li>
                <li>Kullanıcı aktif durumda (<code>is_active = 1</code>)</li>
            </ul>
        </div>
    </div>
</body>
</html>

