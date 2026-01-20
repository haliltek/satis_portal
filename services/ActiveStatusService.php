<?php
// services/ActiveStatusService.php

class ActiveStatusService
{
    private PriceUpdater $priceUpdater;
    private MailService $mailService;
    private LoggerService $logger;

    public function __construct(PriceUpdater $priceUpdater, MailService $mailService, LoggerService $logger)
    {
        $this->priceUpdater = $priceUpdater;
        $this->mailService  = $mailService;
        $this->logger       = $logger;
    }

    /**
     * Update usage status and optionally send notification email.
     */
    public function updateStatusWithMail(
        string $stokKodu,
        string $urunAdi,
        int $oldStatus,
        int $newStatus,
        int $gempaLogicalRef,
        int $gemasLogicalRef,
        bool $sendMail = false,
        array $mailList = []
    ): array {
        $result = $this->priceUpdater->updateActiveStatus($stokKodu, $gempaLogicalRef, $gemasLogicalRef, $newStatus);
        $statusTextOld = $oldStatus === 0 ? 'Kullanımda' : 'Kullanım Dışı';
        $statusTextNew = $newStatus === 0 ? 'Kullanımda' : 'Kullanım Dışı';

        $mailErrors = [];
        if ($sendMail && !empty($mailList)) {
            foreach ($mailList as $mailData) {
                $sent = $this->mailService->sendMail(
                    $mailData['email'],
                    $mailData['adsoyad'] ?? '',
                    "Ürün Kullanım Durumu – {$stokKodu}",
                    $this->buildMailBody($stokKodu, $urunAdi, $statusTextNew),
                    'Gemas Kullanım Güncelleme'
                );
                if (!$sent) {
                    $mailErrors[] = $mailData['email'];
                    $this->logger->log("ActiveStatusService: mail failed for {$mailData['email']}", 'ERROR');
                }
            }
        }

        $totalMails = count($mailList);
        $sentCount  = $totalMails - count($mailErrors);
        $message    = $result['message'] ?? '';
        if ($sendMail) {
            if ($mailErrors) {
                $result['status'] = 'warning';
                $message .= ' Mail sorunları: ' . implode(', ', $mailErrors);
            } else {
                $message .= " Mailler gönderildi ({$sentCount}/{$totalMails}).";
            }
        }

        return [
            'status'     => $result['status'] ?? 'error',
            'message'    => $message,
            'results'    => $result['results'] ?? $result,
            'mailTotal'  => $totalMails,
            'mailSent'   => $sentCount,
            'mailFailed' => $mailErrors,
        ];
    }

    private function buildMailBody(string $stokKodu, string $urunAdi, string $newText): string
    {
        $date = date('d.m.Y');
        return <<<HTML
<html>
<body style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px;">
  <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <div style="background:#2a7ae2; color:#ffffff; padding:16px;">
      <h2 style="margin:0; font-size:18px;">Ürün Kullanım Durumu Güncellemesi</h2>
    </div>
    <div style="padding:20px;">
      <p style="margin:0 0 12px; font-size:14px;">Merhaba,</p>
      <p style="margin:0 0 20px; font-size:16px; line-height:1.4;">
        <strong style="color:#2a7ae2;">{$stokKodu}</strong> — <span style="font-weight:bold;">{$urunAdi}</span>
        ürününün kullanım durumu <strong>{$newText}</strong> olmuştur.
      </p>
      <p style="margin:20px 0 0; font-size:12px; color:#999; text-align:right;"><em>Tarih: {$date}</em></p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
