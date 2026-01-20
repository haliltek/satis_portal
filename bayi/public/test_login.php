<?php
/**
 * Login Test Sayfası
 * Bu sayfa login işlevselliğini test etmek için kullanılır
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test - GEMAS B2B</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #2c3e50; }
        .user-list { margin: 20px 0; }
        .user-item { padding: 10px; margin: 5px 0; background: #e8f4f8; border-left: 4px solid #3498db; }
        .test-form { margin: 20px 0; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Login Test Sayfası</h1>
        
        <div class="user-list">
            <h2>📋 b2b_users Tablosundaki Kullanıcılar:</h2>
            <?php
            try {
                $users = DB::table('b2b_users')->select('id', 'username', 'email', 'is_active', 'user_type')->get();
                
                if ($users->count() > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Active</th><th>Type</th></tr>';
                    foreach ($users as $user) {
                        $activeBadge = ($user->is_active ?? 0) == 1 ? '<span class="success">✅ Aktif</span>' : '<span class="error">❌ Pasif</span>';
                        echo '<tr>';
                        echo '<td>' . ($user->id ?? 'NULL') . '</td>';
                        echo '<td>' . ($user->username ?? 'NULL') . '</td>';
                        echo '<td>' . ($user->email ?? 'NULL') . '</td>';
                        echo '<td>' . $activeBadge . '</td>';
                        echo '<td>' . ($user->user_type ?? 'NULL') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p class="error">❌ Hiç kullanıcı bulunamadı!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Hata: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <div class="test-form">
            <h2>🧪 Login Test</h2>
            <p><strong>Test için kullanabileceğiniz bilgiler:</strong></p>
            <ul>
                <li>Yukarıdaki listeden bir kullanıcı seçin</li>
                <li>Email veya username ile giriş yapabilirsiniz</li>
                <li>Sadece <strong>is_active = 1</strong> olan kullanıcılar giriş yapabilir</li>
            </ul>
            
            <p><strong>Login Sayfası:</strong></p>
            <a href="/b2b-gemas-project-main/bayi/public/login" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">
                🔐 Login Sayfasına Git
            </a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #3498db;">
            <h3>✅ Login Özellikleri:</h3>
            <ul>
                <li>✅ Email veya username ile giriş yapılabilir</li>
                <li>✅ Sadece aktif kullanıcılar giriş yapabilir</li>
                <li>✅ Başarılı girişten sonra ana sayfaya yönlendirilir</li>
                <li>✅ Hatalı girişte hata mesajı gösterilir</li>
            </ul>
        </div>
    </div>
</body>
</html>

