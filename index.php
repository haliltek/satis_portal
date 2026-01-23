<?php
include "fonk.php";

// Oturum başlamamışsa başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eğer oturum zaten açılmışsa anasayfaya yönlendir
girisyapildiysaYonlendir();

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Loglama fonksiyonu: log.txt dosyasına ekleme yapar
function writeLog($message) {
    $logFile = __DIR__ . "/logs/log.txt"; // logs klasörüne yaz
    $currentDateTime = date("Y-m-d H:i:s");
    $formattedMessage = "[$currentDateTime] " . $message . "\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Genel ayarları çekelim
$genelayar_sorgulama = mysqli_query($db, "SELECT * FROM ayarlar");
if (!$genelayar_sorgulama) {
    writeLog("Ayar sorgusu hatası: " . mysqli_error($db));
    $ayarim = [];
} else {
    $ayarim = mysqli_fetch_array($genelayar_sorgulama);
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo isset($ayarim["title"]) ? htmlspecialchars($ayarim["title"]) : 'Site Title'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo isset($ayarim["description"]) ? htmlspecialchars($ayarim["description"]) : ''; ?>" name="description" />
    <meta content="<?php echo isset($ayarim["keywords"]) ? htmlspecialchars($ayarim["keywords"]) : ''; ?>" name="keywords" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets_login/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets_login/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets_login/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets_login/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <style type="text/css">
        body {
            background-color: #f4f7f6; /* Gri arka plan */
            background-size: cover;
            opacity: 1;
        }
    </style>
</head>
<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <a href="index.php" class="mb-5 d-block auth-logo">
                            <img src="images/<?php echo isset($ayarim["resim"]) ? htmlspecialchars($ayarim["resim"]) : 'default.png'; ?>" alt="" height="100" class="logo logo-dark">
                            <img src="images/<?php echo isset($ayarim["resim"]) ? htmlspecialchars($ayarim["resim"]) : 'default.png'; ?>" alt="" height="100" class="logo logo-light">
                        </a>
                    </div>
                </div>
            </div>
            <div class="row align-items-center justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card" style="border:3px solid lightgray; border-radius: 5%; box-shadow: -1px 5px 72px -3px rgba(0,0,0,0.75);">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-primary"><?php echo isset($ayarim["title"]) ? htmlspecialchars($ayarim["title"]) : ''; ?></h5>
                                <?php
                                if (isset($_POST['oturumyaptir'])) {

                                    $kullanici = trim($_POST['eposta']);
                                    $parola = $_POST['parola'];

                                    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                                        writeLog("CSRF validation failed for $kullanici");
                                        $error = 'Geçersiz istek';
                                    } else {

                                    $kontrolsorgusu = $db->prepare("SELECT * FROM yonetici WHERE eposta = ?");
                                    $kontrolsorgusu->bind_param('s', $kullanici);
                                    $kontrolsorgusu->execute();
                                    $result = $kontrolsorgusu->get_result();
                                    if (!$result) {
                                        writeLog("Sorgu Hatası: " . $db->error);
                                    }
                                    $durum = $result->fetch_array(MYSQLI_ASSOC);

                                    if ($durum) {
                                        // Veritabanındaki hash ile gelen düz şifreyi karşılaştırıyoruz
                                        if (password_verify($parola, $durum['parola'])) {
                                            date_default_timezone_set('Etc/GMT-3');
                                            $tarih = date("d.m.Y");
                                            $saat = date("H:i");
                                            $zaman = $tarih . ' Saat: ' . $saat;
                                            $yonetici_id_sabit = $durum['yonetici_id'];
                                            // Kullanıcı ID ve tipini oturuma atıyoruz
                                            $_SESSION['yonetici_id'] = $yonetici_id_sabit;
                                            $_SESSION['user_type'] = $durum['tur'];
                                            $adsoyadi = $durum['adsoyad'];

                                            // Log kaydı oluşturuyoruz
                                            $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Oturum Açma İşlemi','$yonetici_id_sabit','$zaman','Başarılı')";
                                            mysqli_query($db, $logbaglanti);

                                            session_regenerate_id(true);
                                            if (!empty($_POST['beni_hatirla'])) {
                                                setcookie('remember_email', $kullanici, time() + 60 * 60 * 24 * 30, '/');
                                            } else {
                                                setcookie('remember_email', '', time() - 3600, '/');
                                            }

                                            writeLog("Login success for $kullanici");
                                            header('Location: ' . ($durum['tur'] === 'Müşteri' ? 'siparis-olustur.php' : 'anasayfa.php'));
                                            exit;
                                        } else {
                                            writeLog("Login failed for $kullanici: wrong password");
                                            $error = 'E-Posta veya parola hatalı. Lütfen tekrar deneyin.';
                                        }
                                    } else {
                                        // Yönetici bulunamadıysa bayi tablosunda ara
                                        $dealerStmt = $db->prepare("SELECT * FROM b2b_users WHERE email = ? AND status = 1");
                                        $dealerStmt->bind_param('s', $kullanici);
                                        $dealerStmt->execute();
                                        $dealerRes = $dealerStmt->get_result();
                                        $dealer = $dealerRes ? $dealerRes->fetch_array(MYSQLI_ASSOC) : null;
                                        $dealerStmt->close();

                                        if ($dealer && password_verify($parola, $dealer['password'])) {
                                            date_default_timezone_set('Etc/GMT-3');
                                            $tarih = date("d.m.Y");
                                            $saat = date("H:i");
                                            $zaman = $tarih . ' Saat: ' . $saat;
                                            $yonetici_id_sabit = $dealer['id'];

                                            $_SESSION['yonetici_id'] = $yonetici_id_sabit;
                                            $_SESSION['user_type'] = 'Bayi';
                                            $_SESSION['dealer_company_id'] = (int)$dealer['company_id'];
                                            $_SESSION['dealer_cari_code']  = $dealer['cari_code'];

                                            $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Bayi Oturum Açma','$yonetici_id_sabit','$zaman','Başarılı')";
                                            mysqli_query($db, $logbaglanti);

                                            session_regenerate_id(true);
                                            if (!empty($_POST['beni_hatirla'])) {
                                                setcookie('remember_email', $kullanici, time() + 60 * 60 * 24 * 30, '/');
                                            } else {
                                                setcookie('remember_email', '', time() - 3600, '/');
                                            }

                                            writeLog("Login success for dealer $kullanici");
                                            header('Location: anasayfa.php');
                                            exit;
                                        } else {
                                            writeLog("Login failed for $kullanici: user not found");
                                            $error = 'E-Posta veya parola hatalı. Lütfen tekrar deneyin.';
                                        }
                                    }
                                }
                                }
                                ?>
                            </div>
                            <div class="p-2 mt-4">
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <form action="index.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <div class="mb-3">
                                        <label class="form-label" for="username">E-Posta Adresiniz</label>
                                        <input type="email" class="form-control" id="email" name="eposta" placeholder="ÖRN: info@gemas.com" value="<?php echo htmlspecialchars($_POST['eposta'] ?? ($_COOKIE['remember_email'] ?? '')); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">Parola</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input required type="password" class="form-control" id="userpassword" name="parola" placeholder="****************" aria-label="password">
                                            <button class="btn btn-light" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="remember" name="beni_hatirla" <?php echo isset($_POST['beni_hatirla']) || isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="remember">Beni Hatırla</label>
                                    </div>
                                    <div class="mb-3">
                                        <a href="sifirlamakodgir.php">Şifremi Unuttum?</a>
                                    </div>
                                    <div class="row">
                                        <div class="mt-3 text-end col-md-12">
                                            <button style="width:100%" class="btn btn-success w-sm waves-effect waves-light" type="submit" name="oturumyaptir">Oturum Aç</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- JAVASCRIPT -->
    <script src="assets_login/libs/jquery/jquery.min.js"></script>
    <script src="assets_login/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets_login/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets_login/libs/simplebar/simplebar.min.js"></script>
    <script src="assets_login/libs/node-waves/waves.min.js"></script>
    <script src="assets_login/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets_login/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <!-- App js -->
    <script src="assets_login/js/app.js"></script>
    <script>
        const passBtn = document.getElementById('password-addon');
        const passInput = document.getElementById('userpassword');
        if (passBtn && passInput) {
            passBtn.addEventListener('click', function () {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                this.innerHTML = type === 'password'
                    ? '<i class="mdi mdi-eye-outline"></i>'
                    : '<i class="mdi mdi-eye-off-outline"></i>';
            });
        }
    </script>
</body>
</html>
