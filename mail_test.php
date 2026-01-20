<?php
// mail_test.php

// Composer ile kurulan PHPMailer sınıflarını dahil ediyoruz.
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Basit loglama fonksiyonu.
 * Mesajlar belirtilen log dosyasına (varsayılan: mail_log.txt) eklenir.
 *
 * @param string $message Loglanacak mesaj.
 * @param string $logFile Log dosyasının adı.
 */
function logMessage($message, $logFile = 'mail_log.txt') {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// SMTP ayarlarını MailService dosyanızdaki bilgilerden alıyoruz:
$smtpHost     = 'mail.gemas.com.tr';
$smtpPort     = 465;
$smtpSecure   = 'ssl';
$smtpUsername = 'fiyat@gemas.com.tr';
$smtpPassword = 'Y-IG17IH1%rs';

// Mail gönderilecek alıcılar:
$recipients = [
    'orhan.ozan351@gmail.com',
    'ozanyildiz@gemas.com.tr'
];

// Mail konusu ve mesajı:
$subject = 'Fiyat Güncelleme Bildirimi';
$body    = "Merhaba,\n\nBu, test amaçlı gönderilen Fiyat Güncelleme Bildirimidir.\n\nİyi çalışmalar.";

// İşlem başlangıcı loglanıyor.
logMessage("Script çalıştırıldı. Mail gönderim işlemi başlatılıyor.");

$mail = new PHPMailer(true);

try {
    logMessage("PHPMailer nesnesi oluşturuldu.");
    
    // SMTP modunu etkinleştiriyoruz.
    $mail->isSMTP();
    logMessage("SMTP modu etkinleştirildi.");
    
    // SMTP sunucu ayarları:
    $mail->Host       = $smtpHost;
    logMessage("SMTP host ayarlandı: $smtpHost");
    
    $mail->Port       = $smtpPort;
    logMessage("SMTP port ayarlandı: $smtpPort");
    
    $mail->SMTPSecure = $smtpSecure;
    logMessage("SMTP secure protokolü ayarlandı: $smtpSecure");
    
    $mail->SMTPAuth   = true;
    logMessage("SMTP kimlik doğrulaması etkinleştirildi.");
    
    $mail->Username   = $smtpUsername;
    logMessage("SMTP kullanıcı adı ayarlandı: $smtpUsername");
    
    $mail->Password   = $smtpPassword;
    logMessage("SMTP şifresi ayarlandı.");
    
    // Eğer sertifika problemi yaşıyorsanız (test aşamasında) aşağıdaki SMTPOptions ayarını açabilirsiniz.
    /*
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );
    logMessage("SMTPOptions (SSL) ayarlandı.");
    */
    
    // Gönderen bilgilerini ayarlıyoruz.
    $mail->setFrom($smtpUsername, 'Fiyat Güncelleme');
    logMessage("Gönderen bilgileri ayarlandı: $smtpUsername, 'Fiyat Güncelleme'");
    
    // Alıcıları ekliyoruz.
    foreach ($recipients as $address) {
        $mail->addAddress($address);
        logMessage("Alıcı eklendi: $address");
    }
    
    // Mail içeriği ve karakter seti:
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    logMessage("Mail konusu ayarlandı: $subject");
    
    // Mesaj içeriğini, HTML'ye dönüştürerek belirliyoruz.
    $mail->Body = nl2br(htmlspecialchars($body));
    logMessage("Mail içeriği ayarlandı.");
    
    // Mail gönderim işlemi:
    logMessage("Mail gönderimi deneniyor...");
    $mail->send();
    logMessage("Mail başarıyla gönderildi.");
    echo "Mail gönderimi başarılı!";

} catch (Exception $e) {
    // Hata durumunda detayları logluyoruz.
    $errorMessage = "Mail gönderilemedi: " . $mail->ErrorInfo;
    logMessage($errorMessage);
    echo $errorMessage;
}
