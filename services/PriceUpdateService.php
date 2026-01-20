<?php
// services/PriceUpdateService.php

class PriceUpdateService
{
  private $priceUpdater;
  private $mailService;
  private $logger;

  /**
   * PriceUpdateService constructor.
   *
   * @param PriceUpdater  $priceUpdater  Fiyat güncelleme işlemlerini yöneten sınıf.
   * @param MailService   $mailService   Mail gönderim işlemlerini yöneten sınıf.
   * @param LoggerService $logger        Loglama işlemleri için.
   */
  public function __construct(PriceUpdater $priceUpdater, MailService $mailService, LoggerService $logger)
  {
    $this->priceUpdater = $priceUpdater;
    $this->mailService = $mailService;
    $this->logger = $logger;
  }

  /**
   * Fiyat güncelleme işlemi ve isteğe bağlı olarak mail gönderimini gerçekleştirir.
   *
   * @param string $stokKodu
   * @param int    $gempaLogoLogicalRef  GEMPA için logical ref değeri
   * @param int    $gemasLogoLogicalRef   GEMAS için logical ref değeri
   * @param float  $domesticPrice         Yeni yurtiçi fiyat
   * @param float  $exportPrice           Yeni ihracat fiyat
   * @param bool   $sendMail              Mail gönderimi yapılacak mı
   * @param array  $mailList              (Her eleman ['email' => ..., 'adsoyad' => ...])
   * @return array İşlem sonucu.
   */
  public function updatePriceWithMail(
    string $stokKodu,
    string $urunAdi,
    float $oldDomesticPrice,
    float $oldExportPrice,
    int $gempaLogoLogicalRef,
    int $gemasLogoLogicalRef,
    float $newDomesticPrice,
    float $newExportPrice,
    bool $sendMail = false,
    array $mailList = []
  ): array {
    // 1) Fiyat güncelleme sonuçlarını al
    $priceResult = $this->priceUpdater
      ->updatePrices($stokKodu, $gempaLogoLogicalRef, $gemasLogoLogicalRef, $newDomesticPrice, $newExportPrice);

    // Eğer fiyat güncellemede kritik bir hata varsa direkt dön
    if (isset($priceResult['status']) && $priceResult['status'] === 'error') {
      return [
        'status' => 'error',
        'message' => $priceResult['message'],
        'platforms' => $priceResult['platforms'],
        'mailTotal' => 0,
        'mailSent' => 0,
        'mailFailed' => [],
      ];
    }

    // 2) Mail gönderimi varsa, mail istatistiklerini topla
    $mailErrors = [];
    if ($sendMail && !empty($mailList)) {
      foreach ($mailList as $mailData) {
        $sent = $this->mailService->sendMail(
          $mailData['email'],
          $mailData['adsoyad'] ?? '',
          "Fiyat Güncelleme – {$stokKodu}",
          $this->buildMailBody($stokKodu, $urunAdi, $oldDomesticPrice, $newDomesticPrice, $oldExportPrice, $newExportPrice),
          'Gemas Fiyat Güncelleme'
        );
        if (!$sent) {
          $mailErrors[] = $mailData['email'];
          $this->logger->log("Mail gönderilemedi: {$mailData['email']}", "ERROR");
        }
      }
    }

    // 3) Genel durum ve mesajı belirle
    $overallStatus = $priceResult['overallStatus']; // 'success' | 'partial' | 'no_change'
    $message = $priceResult['message'];

    // Mail uyarısı varsa mesajı genişlet
    $totalMails = count($mailList);
    $sentCount = $totalMails - count($mailErrors);
    if ($sendMail) {
      if (!empty($mailErrors)) {
        $overallStatus = 'warning';
        $message .= " Mail gönderiminde sorun yaşandı: " . implode(', ', $mailErrors);
      } else {
        $message .= " Tüm mailler başarıyla gönderildi ({$sentCount}/{$totalMails}).";
      }
    }

    // 4) Dönüş yapısı
    return [
      'status' => $overallStatus,          // success | partial | warning | no_change
      'message' => $message,
      'platforms' => $priceResult['platforms'],
      'mailTotal' => $totalMails,
      'mailSent' => $sentCount,
      'mailFailed' => $mailErrors,
    ];
  }

  /**
   * Mail gövdesini hazırlar.
   */
  private function buildMailBody(
    string $stokKodu,
    string $urunAdi,
    float $oldDomestic,
    float $newDomestic,
    float $oldExport,
    float $newExport
  ): string {
    // Tarih bilgisini al
    $tarih = date('d.m.Y');
    // Değerleri her zaman float formatında göster
    $oldD = number_format($oldDomestic, 2, ',', '.') . ' €';
    $newD = number_format($newDomestic, 2, ',', '.') . ' €';
    $oldE = number_format($oldExport, 2, ',', '.') . ' €';
    $newE = number_format($newExport, 2, ',', '.') . ' €';

    // HTML gövdesi
    return <<<HTML
<html>
<body style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px;">
  <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <div style="background:#2a7ae2; color:#ffffff; padding:16px;">
      <h2 style="margin:0; font-size:18px;">Fiyat Güncelleme Bilgilendirme</h2>
    </div>
    <div style="padding:20px;">
      <p style="margin:0 0 12px; font-size:14px;">Merhaba,</p>
      <p style="margin:0 0 20px; font-size:16px; line-height:1.4;">
        <strong style="color:#2a7ae2;">{$stokKodu}</strong> &mdash;
        <span style="font-weight:bold;">{$urunAdi}</span> ürünümüzün fiyatı güncellenmiştir:
      </p>
      <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#f1f1f1;">
          <th style="padding:6px 12px; text-align:left;">Kategori</th>
          <th style="padding:6px 12px; text-align:left;">Eski</th>
          <th style="padding:6px 12px; text-align:left;">Yeni</th>
        </tr>
        <tr>
          <td style="padding:10px; font-size:14px;">Yurtiçi</td>
          <td style="padding:10px; font-size:14px; color:#6c757d;">{$oldD}</td>
          <td style="padding:10px; font-size:14px; color:#28a745; font-weight:bold;">{$newD}</td>
        </tr>
        <tr style="background:#f9f9f9;">
          <td style="padding:10px; font-size:14px;">İhracat</td>
          <td style="padding:10px; font-size:14px; color:#6c757d;">{$oldE}</td>
          <td style="padding:10px; font-size:14px; color:#28a745; font-weight:bold;">{$newE}</td>
        </tr>
      </table>
      <p style="margin:20px 0 0; font-size:12px; color:#999; text-align:right;"><em>Tarih: {$tarih}</em></p>
    </div>
  </div>
</body>
</html>
HTML;
  }
}
