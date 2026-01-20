<?php
// Bayi Destek
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['yonetici_id']) || ($_SESSION['user_type'] ?? '') !== 'Bayi') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek - GEMAS B2B Portal</title>
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
        .support-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        .support-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .support-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
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
                            <i class="mdi mdi-help-circle me-2"></i>Destek & İletişim
                        </h2>
                        <p class="mb-0 opacity-90">
                            Size nasıl yardımcı olabiliriz?
                        </p>
                    </div>
                    
                    <div class="row">
                        <!-- Telefon -->
                        <div class="col-lg-4">
                            <div class="support-card">
                                <div class="support-icon">
                                    <i class="mdi mdi-phone"></i>
                                </div>
                                <h4>Telefon</h4>
                                <p class="text-muted">
                                    Haftaiçi 09:00 - 18:00
                                </p>
                                <h5 class="text-primary mb-3">+90 (XXX) XXX XX XX</h5>
                                <a href="tel:+90XXXXXXXXXX" class="btn btn-outline-primary">
                                    <i class="mdi mdi-phone me-2"></i>Ara
                                </a>
                            </div>
                        </div>
                        
                        <!-- E-posta -->
                        <div class="col-lg-4">
                            <div class="support-card">
                                <div class="support-icon">
                                    <i class="mdi mdi-email"></i>
                                </div>
                                <h4>E-posta</h4>
                                <p class="text-muted">
                                    24 saat içinde cevap
                                </p>
                                <h5 class="text-primary mb-3">destek@gemas.com</h5>
                                <a href="mailto:destek@gemas.com" class="btn btn-outline-primary">
                                    <i class="mdi mdi-email me-2"></i>Mail Gönder
                                </a>
                            </div>
                        </div>
                        
                        <!-- WhatsApp -->
                        <div class="col-lg-4">
                            <div class="support-card">
                                <div class="support-icon">
                                    <i class="mdi mdi-whatsapp"></i>
                                </div>
                                <h4>WhatsApp</h4>
                                <p class="text-muted">
                                    Hızlı destek
                                </p>
                                <h5 class="text-primary mb-3">+90 (XXX) XXX XX XX</h5>
                                <a href="https://wa.me/90XXXXXXXXXX" target="_blank" class="btn btn-outline-success">
                                    <i class="mdi mdi-whatsapp me-2"></i>Mesaj Gönder
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SSS -->
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-body p-4">
                                    <h4 class="mb-4">
                                        <i class="mdi mdi-frequently-asked-questions text-primary me-2"></i>
                                        Sık Sorulan Sorular
                                    </h4>
                                    
                                    <div class="accordion" id="faqAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                                    Nasıl sipariş verebilirim?
                                                </button>
                                            </h2>
                                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Ürünler menüsünden istediğiniz ürünleri seçip sepete ekleyebilir, ardından sepetten siparişinizi tamamlayabilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                                    Siparişimin durumunu nasıl öğrenebilirim?
                                                </button>
                                            </h2>
                                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    "Siparişlerim" sayfasından tüm siparişlerinizi ve durumlarını görüntüleyebilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                                    Faturalarıma nasıl ulaşabilirim?
                                                </button>
                                            </h2>
                                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    "Faturalarım" menüsünden tüm faturalarınızı görüntüleyebilir ve indirebilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                                    Özel iskonto talep edebilir miyim?
                                                </button>
                                            </h2>
                                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Özel iskonto talepleri için satış temsilciniz ile iletişime geçebilirsiniz.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

