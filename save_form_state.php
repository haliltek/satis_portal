<?php
// save_form_state.php - Form durumunu session'a kaydetme
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eğer clear_customer parametresi geldiyse, tüm müşteri bilgilerini temizle
    if (isset($_POST['clear_customer']) && $_POST['clear_customer']) {
        unset($_SESSION['form_musteri_id']);
        unset($_SESSION['form_ekstra_bilgi']);
        unset($_SESSION['form_sozlesme_metin']);
        unset($_SESSION['form_sozlesme_id']);
        unset($_SESSION['form_iskontolar']);
        unset($_SESSION['form_referrer_url']);
        echo json_encode(['success' => true, 'message' => 'Müşteri bilgileri temizlendi']);
        exit;
    }
    
    if (isset($_POST['referrer_url'])) {
        $_SESSION['form_referrer_url'] = $_POST['referrer_url'];
    }
    if (isset($_POST['ekstra_bilgi'])) {
        $_SESSION['form_ekstra_bilgi'] = $_POST['ekstra_bilgi'];
    }
    if (isset($_POST['musteri_id'])) {
        $_SESSION['form_musteri_id'] = $_POST['musteri_id'];
    }
    // Sözleşme metnini kaydet
    if (isset($_POST['sozlesme_metin'])) {
        $_SESSION['form_sozlesme_metin'] = $_POST['sozlesme_metin'];
    }
    // Sözleşme ID'sini kaydet
    if (isset($_POST['sozlesme_id'])) {
        $_SESSION['form_sozlesme_id'] = $_POST['sozlesme_id'];
    }
    // İskontoları kaydet
    if (isset($_POST['iskontolar'])) {
        $_SESSION['form_iskontolar'] = json_decode($_POST['iskontolar'], true);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

