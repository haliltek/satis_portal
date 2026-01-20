<?php
namespace Services;

class OrderProcessService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function record(int $offerId, string $companyCode, string $status, ?string $notes = null, ?int $userId = null): bool
    {
        $stmt = $this->db->prepare('INSERT INTO order_processes (teklif_id, s_arp_code, status, notes, created_by) VALUES (?,?,?,?,?)');
        if (!$stmt) {
            error_log('OrderProcessService: prepare failed: ' . $this->db->error);
            return false;
        }
        $stmt->bind_param('isssi', $offerId, $companyCode, $status, $notes, $userId);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('OrderProcessService: execute failed: ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    public function history(int $offerId): array
    {
        $stmt = $this->db->prepare('SELECT created_at, status, notes, created_by FROM order_processes WHERE teklif_id = ? ORDER BY created_at DESC');
        if (!$stmt) {
            error_log('OrderProcessService: prepare history failed: ' . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $offerId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
}
