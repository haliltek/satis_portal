<?php
// Bayi Profil Ayarları
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['yonetici_id']) || ($_SESSION['user_type'] ?? '') !== 'Bayi') {
    header('Location: index.php');
    exit;
}

include "../include/vt.php";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

$dealerId = (int)$_SESSION['yonetici_id'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM b2b_users WHERE id = ?");
$stmt->bind_param('i', $dealerId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message = '';
$messageType = '';

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');
    
    if (empty($email)) {
        $message = 'E-posta adresi gereklidir.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Geçerli bir e-posta adresi girin.';
        $messageType = 'danger';
    } else {
        if (!empty($password)) {
            if ($password !== $passwordConfirm) {
                $message = 'Şifreler eşleşmiyor.';
                $messageType = 'danger';
            } elseif (strlen($password) < 6) {
                $message = 'Şifre en az 6 karakter olmalıdır.';
                $messageType = 'danger';
            } else {
                // Şifre ile güncelle
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE b2b_users SET email = ?, password = ? WHERE id = ?");
                $stmt->bind_param('ssi', $email, $hashedPassword, $dealerId);
                $stmt->execute();
                $stmt->close();
                
                $message = 'Profiliniz başarıyla güncellendi.';
                $messageType = 'success';
                
                // Güncel bilgileri yeniden çek
                $stmt = $db->prepare("SELECT * FROM b2b_users WHERE id = ?");
                $stmt->bind_param('i', $dealerId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        } else {
            // Sadece e-posta güncelle
            $stmt = $db->prepare("UPDATE b2b_users SET email = ? WHERE id = ?");
            $stmt->bind_param('si', $email, $dealerId);
            $stmt->execute();
            $stmt->close();
            
            $message = 'Profiliniz başarıyla güncellendi.';
            $messageType = 'success';
            
            // Güncel bilgileri yeniden çek
            $stmt = $db->prepare("SELECT * FROM b2b_users WHERE id = ?");
            $stmt->bind_param('i', $dealerId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları - GEMAS B2B Portal</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/icons.min.css" rel="stylesheet">
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <?php include "includes/header.php"; ?>
        <?php include "includes/menu.php"; ?>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2 class="mb-2">
                            <i class="mdi mdi-account-cog me-2"></i>Profil Ayarları
                        </h2>
                        <p class="mb-0 opacity-90">
                            Hesap bilgilerinizi güncelleyin
                        </p>
                    </div>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="profile-card">
                                <form method="POST">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="mdi mdi-account me-2"></i>Kullanıcı Adı
                                        </label>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars($user['username']) ?>" 
                                               disabled>
                                        <small class="text-muted">Kullanıcı adı değiştirilemez.</small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="mdi mdi-email me-2"></i>E-posta Adresi
                                        </label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= htmlspecialchars($user['email']) ?>" 
                                               required>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="mb-3">
                                        <i class="mdi mdi-lock-reset me-2"></i>Şifre Değiştir
                                    </h5>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" name="password" 
                                               placeholder="Değiştirmek istemiyorsanız boş bırakın" 
                                               minlength="6">
                                        <small class="text-muted">En az 6 karakter olmalıdır.</small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                                        <input type="password" class="form-control" name="password_confirm" 
                                               placeholder="Şifrenizi tekrar girin" 
                                               minlength="6">
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="mdi mdi-content-save me-2"></i>Değişiklikleri Kaydet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <?php include "includes/footer.php"; ?>
        </div>
    </div>

    <script src="../assets/libs/jquery/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>

