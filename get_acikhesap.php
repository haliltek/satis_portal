<?php
include "fonk.php";
oturumkontrol();
include "include/vt.php";

// Hata raporlamasını yapılandırma
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/htdocs/b2b-project/error.log');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Müşteri ID'sini al
file_put_contents('acikhesap_debug.log', date('Y-m-d H:i:s') . " - Request received: " . print_r($_GET, true) . "\n", FILE_APPEND);
if (isset($_GET['sirket_id'])) {
    $sirket_id = filter_input(INPUT_GET, 'sirket_id', FILTER_VALIDATE_INT);
    error_log("get_acikhesap.php: Received sirket_id = " . var_export($sirket_id, true));
    
    if ($sirket_id) {
        // Kullanıcının satış tipini belirle
        $yonetici_id = $_SESSION['yonetici_id'] ?? 0;
        $userType = $_SESSION['user_type'] ?? '';
        // Admin panelinde çalışan kullanıcılar: user_type boş veya 'Bayi' değilse admin sayılır
        $isAdmin = ($userType !== 'Bayi');
        
        $userTypeSales = '';
        if ($yonetici_id && !$isAdmin) {
            // Sadece bayi kullanıcıları için satış tipi kontrolü yap
            $ustmt = $db->prepare("SELECT satis_tipi FROM yonetici WHERE yonetici_id = ?");
            $ustmt->bind_param("i", $yonetici_id);
            $ustmt->execute();
            $urow = $ustmt->get_result()->fetch_assoc();
            $userTypeSales = strtolower($urow['satis_tipi'] ?? '');
            $ustmt->close();
        }

        $stmt = $db->prepare("SELECT acikhesap, payplan_code, payplan_def, s_country_code, trading_grp, credit_limit, s_arp_code, risk_limit FROM sirket WHERE sirket_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $sirket_id);
            $stmt->execute();
            $stmt->bind_result($acikhesap, $payplanCode, $payplanDef, $countryCode, $tradingGrp, $creditLimit, $sArpCode, $riskLimit);
            if ($stmt->fetch()) {
                // Admin kullanıcıları için kontrolü atla
                if (!$isAdmin) {
                    // Ertek (120.01.E04 - ID: 26847) için kontrolü atla
                    // Hem ID hem de kod (trimli) kontrol ediliyor
                    if ($sirket_id != 26847 && trim($sArpCode) !== '120.01.E04') {
                        $grpLower = strtolower($tradingGrp ?? '');
                        $companyForeign = strpos($grpLower, 'yd') !== false;
                        $userForeign = strpos($userTypeSales, 'dışı') !== false;
                        if ($companyForeign !== $userForeign) {
                            echo json_encode(['success' => false, 'message' => 'Yetkisiz şirket. (Bölge Uyuşmazlığı)', 'debug_reason' => "Grp: $grpLower, User: $userTypeSales"]);
                            $stmt->close();
                            exit;
                        }
                    }
                }
                
                // Kontrol başarılı veya admin kullanıcı ise devam et
                error_log("get_acikhesap.php: Fetched acikhesap = " . var_export($acikhesap, true));
                $acikhesap_normalized = str_replace([','], '', $acikhesap);
                error_log("get_acikhesap.php: Normalized acikhesap = " . var_export($acikhesap_normalized, true));
                $acikhesap_numeric = floatval($acikhesap_normalized);
                error_log("get_acikhesap.php: Numeric acikhesap = " . var_export($acikhesap_numeric, true));
                
                // Credit limit kontrolü
                $creditLimitNormalized = str_replace([','], '', $creditLimit ?? '0');
                $creditLimitNumeric = floatval($creditLimitNormalized);
                $limitAsildi = ($creditLimitNumeric > 0 && $acikhesap_numeric > $creditLimitNumeric);
                
                // Sunum için: Ertek bayisi (120.01.E04) için her zaman uyarı göster
                if (trim($sArpCode ?? '') === '120.01.E04') {
                    $limitAsildi = true;
                }
                
                echo json_encode([
                    'success'      => true,
                    'acikhesap'    => $acikhesap_numeric,
                    'payplan_code' => $payplanCode,
                    'payplan_def'  => $payplanDef,
                    'country_code' => $countryCode,
                    'trading_grp'  => $tradingGrp,
                    'credit_limit' => $creditLimitNumeric,
                    'risk_limit'   => (float)$riskLimit,
                    'limit_asildi' => $limitAsildi,
                    's_arp_code'   => $sArpCode
                ]);
            } else {
                error_log("get_acikhesap.php: No record found for sirket_id = $sirket_id");
                echo json_encode(['success' => false, 'message' => 'Müşteri bulunamadı.']);
            }
            $stmt->close();
        } else {
            error_log("get_acikhesap.php: DB prepare error: " . $db->error);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
        }
    } else {
        error_log("get_acikhesap.php: Invalid sirket_id provided.");
        echo json_encode(['success' => false, 'message' => 'Geçersiz müşteri ID\'si.']);
    }
} else {
    error_log("get_acikhesap.php: sirket_id not provided in request.");
    echo json_encode(['success' => false, 'message' => 'Müşteri ID\'si belirtilmedi.']);
}
?>
