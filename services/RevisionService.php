<?php

namespace Services;

class RevisionService
{
    private \mysqli $db;
    private string $logFile;
    private ?OrderProcessService $processService;

    public function __construct(\mysqli $db, ?OrderProcessService $processService = null, string $logFile = __DIR__ . '/../debug.log')
    {
        $this->db            = $db;
        $this->processService = $processService;
        $this->logFile       = $logFile;
    }

    private function logError(string $msg): void
    {
        $entry = "[" . date("Y-m-d H:i:s") . "] ERROR: $msg\n";
        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }

    private function logInfo(string $msg): void
    {
        $entry = "[" . date("Y-m-d H:i:s") . "] INFO: $msg\n";
        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }

    public function canUserRevise(int $offerId, ?int $userId): bool
    {
        if (!$userId) {
            return true;
        }
        // Yalnızca userId == 1 için 3 defa sınırı
        if ($userId !== 1) {
            return true;
        }

        $sql = "SELECT yeni_durum, degistiren_personel_id
                        FROM durum_gecisleri
                    WHERE teklif_id = ?
                ORDER BY degistirme_tarihi DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $offerId);
        if (!$stmt->execute()) {
            $this->logError("canUserRevise sorgusu başarısız: " . $stmt->error);
            return true;
        }
        $res = $stmt->get_result();
        $count = 0;
        while ($row = $res->fetch_assoc()) {
            if ((int)$row['degistiren_personel_id'] !== $userId) {
                break;
            }
            if ($row['yeni_durum'] === 'Teklife Revize Talep Edildi / İnceleme Bekliyor') {
                if (++$count >= 3) {
                    return false;
                }
            }
        }
        return true;
    }

    public function changeStatus(
        int     $offerId,
        string  $oldStatus,
        string  $newStatus,
        int     $userId,
        ?string $notes,
        string  $companyCode = ''
    ): bool {
        $this->db->begin_transaction();
        // Disable foreign key checks for this transaction to allow userId=0 (Customer/System)
        $this->db->query("SET FOREIGN_KEY_CHECKS=0");

        try {
            // 1) ogteklif2 güncelle
            $upd = $this->db->prepare(
                "UPDATE ogteklif2
                    SET durum = ?, statu = ?, satinalmanotu = ?
                  WHERE id    = ?"
            );
            $upd->bind_param("sssi", $newStatus, $newStatus, $notes, $offerId);
            if (!$upd->execute()) {
                throw new \RuntimeException("UPDATE ogteklif2 hata: " . $upd->error);
            }

            // 2) durum_gecisleri ekle
            $ins = $this->db->prepare(
                "INSERT INTO durum_gecisleri
                    (teklif_id, s_arp_code, eski_durum, yeni_durum, degistiren_personel_id, notlar)
                    VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param(
                "isssis",
                $offerId,
                $companyCode,
                $oldStatus,
                $newStatus,
                $userId,
                $notes
            );
            if (!$ins->execute()) {
                throw new \RuntimeException("INSERT durum_gecisleri hata: " . $ins->error);
            }

            // Re-enable FK checks
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
            $this->db->commit();
            if ($this->processService) {
                $this->processService->record($offerId, $companyCode, $newStatus, $notes, $userId);
            }
            $this->logInfo("Status changed: {$offerId} from '{$oldStatus}' to '{$newStatus}' by user {$userId}");
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logError($e->getMessage());
            return false;
        }
    }

public function getHistory(int $offerId): array
{
    $stmt = $this->db->prepare(
        "SELECT 
            degistirme_tarihi, 
            eski_durum, 
            yeni_durum, 
            degistiren_personel_id,   -- eklendi
            notlar
         FROM durum_gecisleri
        WHERE teklif_id = ?
        ORDER BY degistirme_tarihi DESC"
    );
    $stmt->bind_param("i", $offerId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

}
