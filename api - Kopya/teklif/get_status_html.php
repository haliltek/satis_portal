<?php
// api/teklif/get_status_html.php
require_once "../../include/vt.php";

session_start();
// Basit oturum kontrolü (Tam redirect yapmayalım, sadece boş dönelim)
if (empty($_SESSION['yonetici_id']) && empty($_SESSION['bayi_id']) && empty($_SESSION['personel_id'])) {
    http_response_code(401);
    exit;
}

$teklifid = (int)($_GET['id'] ?? 0);
if (!$teklifid) exit;

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Manuel DB bağlantısı (vt.php include etmiş olsa da nesne scope sorunu yaşamamak için)
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    exit;
}
$db->set_charset("utf8");

$stmt = $db->prepare("SELECT durum FROM ogteklif2 WHERE id = ?");
$stmt->bind_param("i", $teklifid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$currentStatus = trim($row['durum'] ?? '');
$stmt->close();
$db->close();

if ($currentStatus === 'Yönetici Onayı Bekleniyor'): ?>
<!-- Admin Approval Action Buttons -->
<div class="card mb-3" style="border: 1px solid #ffc107; background-color: #fffbf0;">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1 text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Yönetici Onayı Bekleniyor</h5>
            <small class="text-muted">Bu teklif özel fiyat/şartlar içerdiği için onaylanmadan iletilemez.</small>
        </div>
        <div class="d-flex gap-2">
             <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu teklifi ONAYLAMAK istediğinize emin misiniz?');">
                 <button type="submit" name="approveOffer" class="btn btn-success btn-lg">
                     <i class="bi bi-check-lg"></i> ONAYLA
                 </button>
             </form>
             <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu teklifi REDDETMEK istediğinize emin misiniz?');">
                 <button type="submit" name="rejectOffer" class="btn btn-danger btn-lg">
                     <i class="bi bi-x-lg"></i> REDDET
                 </button>
             </form>
        </div>
    </div>
</div>
<?php elseif ($currentStatus === 'Yönetici Onayladı / Gönderilecek'): ?>
 <div class="card mb-3" style="border: 1px solid #198754; background-color: #d1e7dd;">
    <div class="card-body">
         <h5 class="mb-1 text-success"><i class="bi bi-check-circle-fill"></i> ONAYLANDI</h5>
         <div>Bu teklif yönetici tarafından onaylanmıştır. Artık bayi veya müşteriye iletilebilir.</div>
    </div>
</div>
<?php elseif ($currentStatus === 'Yönetici Tarafından Red'): ?>
 <div class="card mb-3" style="border: 1px solid #dc3545; background-color: #f8d7da;">
    <div class="card-body">
         <h5 class="mb-1 text-danger"><i class="bi bi-x-circle-fill"></i> REDDEDİLDİ</h5>
         <div>Bu teklif yönetici tarafından reddedilmiştir. Revize edilmesi gerekmektedir.</div>
    </div>
</div>
<?php endif; ?>
