<?php
// services/MailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $logger;

    private $secure = 'ssl';
    public function __construct($host, $port, $secure, $username, $password, LoggerService $logger)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->secure   = $secure;
        $this->username = $username;
        $this->password = $password;
        $this->logger   = $logger;
    }

    /**
     * Verilen bilgilerle e-posta gönderir.
     *
     * @param string      $to         Alıcı e-posta adresi.
     * @param string      $toName     Alıcının adı (opsiyonel).
     * @param string      $subject    E-posta konusu.
     * @param string      $body       E-posta içeriği.
     * @param string|null $fromName   Gönderen adı (opsiyonel).
     * @param string|null $fromEmail  Gönderen e-posta adresi (opsiyonel).
     * @param array       $attachments Ek dosyalar [['path' => 'dosya.pdf', 'name' => 'Teklif.pdf']]
     * @return bool                   Gönderim başarılı ise true, aksi halde false.
     */
    // services/MailService.php

    public function sendMail($to, $toName, $subject, $bodyHtml, $fromName = 'Gemas Fiyat Güncelleme', $fromEmail = null, $attachments = [])
    {
        $mail = new PHPMailer(true);
        try {
            // 1) Charset ve encoding
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->isSMTP();
            $mail->SMTPAuth   = true;
            $mail->Host       = $this->host;
            $mail->Port       = $this->port;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = $this->secure;

            // 2) HTML e‑posta
            $mail->isHTML(true);

            $fromAddress = $fromEmail ?? $this->username;
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to, $toName);

            // 3) Konu ve body
            $mail->Subject = $subject;      // artık UTF-8 olarak gönderilecek
            $mail->Body    = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);

            // 4) Ek dosyalar
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $mail->addAttachment($attachment['path'], $name);
                        $this->logger->log("MailService: Ek eklendi → {$name}");
                    }
                }
            }

            $this->logger->log("MailService: Gönderiliyor → To: {$to}, Subject: {$subject}");
            return $mail->send();
        } catch (Exception $e) {
            $this->logger->log("MailService Exception: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
}
