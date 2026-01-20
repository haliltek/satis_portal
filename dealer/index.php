<?php
// Bayi Giriş Sayfası
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zaten giriş yapılmışsa dashboard'a yönlendir
if (isset($_SESSION['yonetici_id']) && ($_SESSION['user_type'] ?? '') === 'Bayi') {
    header('Location: dashboard.php');
    exit;
}

include "../include/vt.php";

$error = '';
$success = '';
$debug = isset($_GET['debug']) ? true : false; // Debug modu
$debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username === '' || $password === '') {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        $db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
        $db->set_charset('utf8mb4');
        
        if ($debug) {
            $debugInfo[] = "Veritabanı bağlantısı: " . ($db->connect_error ? "❌ HATA: " . $db->connect_error : "✅ Başarılı");
        }
        
        $stmt = $db->prepare("SELECT u.*, s.s_adi, s.s_arp_code, s.logo_company_code 
                              FROM b2b_users u 
                              LEFT JOIN sirket s ON s.sirket_id = u.company_id 
                              WHERE u.username = ? AND u.status = 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($debug) {
            $debugInfo[] = "Kullanıcı sorgusu: " . ($user ? "✅ Kullanıcı bulundu (ID: " . $user['id'] . ")" : "❌ Kullanıcı bulunamadı");
            if ($user) {
                $debugInfo[] = "Kullanıcı adı: " . htmlspecialchars($user['username']);
                $debugInfo[] = "E-posta: " . htmlspecialchars($user['email']);
                $debugInfo[] = "Durum: " . ($user['status'] == 1 ? "✅ Aktif" : "❌ Pasif");
                $debugInfo[] = "Şirket: " . htmlspecialchars($user['s_adi'] ?? 'Bulunamadı');
            }
        }
        
        $stmt->close();
        
        if ($user) {
            $passwordMatch = password_verify($password, $user['password']);
            
            if ($debug) {
                $debugInfo[] = "Şifre kontrolü: " . ($passwordMatch ? "✅ Eşleşiyor" : "❌ Eşleşmiyor");
                $debugInfo[] = "Hash (ilk 30 karakter): " . substr($user['password'], 0, 30) . "...";
            }
            
            if ($passwordMatch) {
                $_SESSION['yonetici_id'] = $user['id'];
                $_SESSION['user_type'] = 'Bayi';
                $_SESSION['dealer_company_id'] = $user['company_id'];
                $_SESSION['dealer_username'] = $user['username'];
                $_SESSION['dealer_email'] = $user['email'];
                $_SESSION['dealer_company_name'] = $user['s_adi'];
                $_SESSION['dealer_cari_code'] = $user['s_arp_code'];
                $_SESSION['logo_company_code'] = $user['logo_company_code'];
                
                if ($debug) {
                    $debugInfo[] = "✅ Giriş başarılı! Yönlendiriliyor...";
                } else {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
        
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayi Girişi - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <?php include "includes/styles.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            height: 50px;
        }
        .btn-login {
            height: 50px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="mdi mdi-account-key"></i>
                </div>
                <h1>Bayi Girişi</h1>
                <p>GEMAS B2B Portal'a Hoş Geldiniz</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="mdi mdi-alert-circle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="mdi mdi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($debug && !empty($debugInfo)): ?>
                    <div class="alert alert-info" role="alert">
                        <strong>🔍 Debug Bilgileri:</strong><br>
                        <?php foreach ($debugInfo as $info): ?>
                            • <?= $info ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="username" class="form-label">
                            <i class="mdi mdi-account me-2"></i>Kullanıcı Adı
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Kullanıcı adınızı girin" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="mdi mdi-lock me-2"></i>Şifre
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Şifrenizi girin" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-login">
                            <i class="mdi mdi-login me-2"></i>Giriş Yap
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="mdi mdi-information me-1"></i>
                        Hesabınız yoksa lütfen yöneticinizle iletişime geçin
                    </small>
                </div>
                
                <div class="text-center mt-2">
                    <small>
                        <a href="test_login.php" class="text-muted">
                            <i class="mdi mdi-tools me-1"></i>Test Kullanıcısı Oluştur
                        </a>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white mb-0">
                <small>&copy; 2025 GEMAS - Tüm hakları saklıdır</small>
            </p>
        </div>
    </div>

    <script src="../assets/libs/jquery/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

