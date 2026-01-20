<?php
// services/AuthService.php

class AuthService
{
    private $db;
    private $yoneticiId;

    public function __construct($db, $yoneticiId)
    {
        $this->db = $db;
        $this->yoneticiId = $yoneticiId;
    }

    public function checkSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getUserType(): ?string
    {
        $query = "SELECT tur FROM yonetici WHERE yonetici_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("s", $this->yoneticiId);
        if (!$stmt->execute()) {
            return null;
        }
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['tur'];
        }
        return null;
    }
}
