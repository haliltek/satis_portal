<?php
// services/MailRepository.php

class MailRepository {
    private $db;
    private $logger;

    public function __construct($db, LoggerService $logger) {
        $this->db     = $db;
        $this->logger = $logger;
    }

    /**
     * Tüm mail adreslerini getirir.
     *
     * @return array
     */
    public function getMailList() {
        $result = $this->db->query("SELECT * FROM fiyat_guncelleme_mail");
        $mailList = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $mailList[] = $row;
            }
        }
        return $mailList;
    }

    /**
     * Yeni mail adresi ekler.
     *
     * @param string $email
     * @param string $adsoyad
     * @return array
     */
    public function addMail($email, $adsoyad) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Geçersiz e-posta adresi.'];
        }
        $stmt = $this->db->prepare("INSERT INTO fiyat_guncelleme_mail (email, adsoyad) VALUES (?, ?)");
        if (!$stmt) {
            $this->logger->log("MailRepository: addMail statement oluşturulamadı", "ERROR");
            return ['success' => false, 'message' => 'E-posta eklenirken hata oluştu.'];
        }
        $stmt->bind_param("ss", $email, $adsoyad);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'E-posta eklendi.'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'E-posta eklenirken hata oluştu.'];
        }
    }

    /**
     * Mail adresini günceller.
     *
     * @param int    $mail_id
     * @param string $email
     * @param string $adsoyad
     * @return array
     */
    public function updateMail($mail_id, $email, $adsoyad) {
        if ($mail_id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Geçersiz veriler.'];
        }
        $stmt = $this->db->prepare("UPDATE fiyat_guncelleme_mail SET email = ?, adsoyad = ? WHERE mail_id = ?");
        if (!$stmt) {
            $this->logger->log("MailRepository: updateMail statement oluşturulamadı", "ERROR");
            return ['success' => false, 'message' => 'Güncelleme sırasında hata oluştu.'];
        }
        $stmt->bind_param("ssi", $email, $adsoyad, $mail_id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'E-posta güncellendi.'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Güncelleme sırasında hata oluştu.'];
        }
    }

    /**
     * Mail adresini siler.
     *
     * @param int $mail_id
     * @return array
     */
    public function deleteMail($mail_id) {
        if ($mail_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz mail id.'];
        }
        $stmt = $this->db->prepare("DELETE FROM fiyat_guncelleme_mail WHERE mail_id = ?");
        if (!$stmt) {
            $this->logger->log("MailRepository: deleteMail statement oluşturulamadı", "ERROR");
            return ['success' => false, 'message' => 'Silme sırasında hata oluştu.'];
        }
        $stmt->bind_param("i", $mail_id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'E-posta silindi.'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Silme sırasında hata oluştu.'];
        }
    }
}
?>
