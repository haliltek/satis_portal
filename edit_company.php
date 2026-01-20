<?php
include "fonk.php";
oturumkontrol();

use App\Models\ArpMap;

global $dbManager, $logoService;

$firmNr   = (int) ($config['firmNr'] ?? 997);
$payPlans  = $logoService->getPayPlans($firmNr);
$specodes  = $logoService->getSpecodes($firmNr);
$authCodes = $logoService->getAuthCodes($firmNr);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Geçersiz ID');
}

$company = $dbManager->getCompanyInfoById($id);
if (!$company) {
    die('Şirket bulunamadı');
}

$message = '';

// --- AUTO FETCH RISK DATA (USER REQUEST) ---
// Veri çek demeden otomatik gelmesi istendi.
// Site içinde kullanılacağı için yerel DB'yi de güncelliyoruz.
$code = $company['s_arp_code'] ?? '';
$ref  = $company['internal_reference'] ?? null;
if (!empty($code) || !empty($ref)) {
    // LogicalRef bulmak için gerekirse ARP map çağır (cacheli)
    $logicalRef = 0;
    if (!empty($ref)) {
        $logicalRef = (int)$ref;
    } elseif (!empty($code)) {
        $arpData = $logoService->getArpMapped($code);
        if ($arpData) {
            $logicalRef = $arpData['internal_reference'] ?? $arpData['LOGICALREF'] ?? 0;
            // Eǧer referans DB'de yoksa kaydet
            if ($logicalRef && empty($company['internal_reference'])) {
                 $dbManager->updateCompany($code, ['internal_reference' => $logicalRef]);
                 $company['internal_reference'] = $logicalRef;
            }
        }
    }

    if ($logicalRef > 0) {
        $riskData = $logoService->getRiskInfo($firmNr, (int)$logicalRef, '01');
        if ($riskData) {
            $updates = [];
            
            // Map keys
            if (isset($riskData['CREDIT_LIMIT'])) {
                 $company['credit_limit'] = $riskData['CREDIT_LIMIT'];
                 $updates['credit_limit'] = $riskData['CREDIT_LIMIT'];
            }
            if (isset($riskData['RISK_LIMIT'])) {
                 $company['risk_limit']   = $riskData['RISK_LIMIT'];
                 $updates['risk_limit']   = $riskData['RISK_LIMIT'];
            }

            // DB Update if needed
            if (!empty($updates)) {
                if ($logicalRef) {
                     $dbManager->updateCompanyByRef((int)$logicalRef, $updates);
                } else {
                     $dbManager->updateCompany($code, $updates);
                }
            }
        }
    }
}
// -------------------------------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fetch_logo'])) {
        $code = $company['s_arp_code'] ?? '';
        $ref  = $company['internal_reference'] ?? null;
        if ($code !== '' || $ref) {
            $logoData = $ref
                ? $logoService->getArpMappedByRef((int)$ref)
                : $logoService->getArpMapped($code);
            
            if ($logoData) {
                // Risk verilerini de çek
                $logicalRef = $logoData['internal_reference'] ?? $logoData['LOGICALREF'] ?? 0;
                if ($logicalRef) {
                    $riskData = $logoService->getRiskInfo($firmNr, (int)$logicalRef, '01'); // Default Period 01
                    if ($riskData) {
                        // DB kolonlarına map et (ArpMap ile uyumlu: credit_limit, risk_limit)
                        if (isset($riskData['CREDIT_LIMIT'])) $logoData['credit_limit'] = $riskData['CREDIT_LIMIT'];
                        if (isset($riskData['RISK_LIMIT']))   $logoData['risk_limit']   = $riskData['RISK_LIMIT'];
                    }
                }

                if (!empty($logoData['internal_reference'])) {
                    $dbManager->updateCompanyByRef((int)$logoData['internal_reference'], $logoData);
                } else {
                    $dbManager->updateCompany($code, $logoData);
                }
                $company = array_merge($company, $logoData);
                $message = '<div class="alert alert-success">Logo verileri güncellendi.</div>';
            } else {
                $message = '<div class="alert alert-warning">Logo verisi bulunamadı.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">ARP kodu eksik.</div>';
        }
    } elseif (isset($_POST['update'])) {
        $data = [
            's_adi'        => $_POST['s_adi'] ?? '',
            's_arp_code'   => $_POST['s_arp_code'] ?? '',
            'kategori'     => $_POST['kategori'] ?? '',
            'acikhesap'    => $_POST['acikhesap'] ?? '',
            's_adresi'     => $_POST['s_adresi'] ?? '',
            's_adresi2'    => $_POST['s_adresi2'] ?? '',
            's_tel1_code'  => $_POST['s_tel1_code'] ?? '',
            's_telefonu'   => $_POST['s_telefonu'] ?? '',
            's_tel2_code'  => $_POST['s_tel2_code'] ?? '',
            's_telefonu2'  => $_POST['s_telefonu2'] ?? '',
            's_il'         => $_POST['s_il'] ?? '',
            's_ilce'       => $_POST['s_ilce'] ?? '',
            's_country_code' => $_POST['s_country_code'] ?? '',
            's_country'      => $_POST['s_country'] ?? '',
            'yetkili'      => $_POST['yetkili'] ?? '',
            'yetkili2'     => $_POST['yetkili2'] ?? '',
            's_auxil_code' => $_POST['s_auxil_code'] ?? '',
            's_auth_code'  => $_POST['s_auth_code'] ?? '',
            'mail'         => $_POST['mail'] ?? '',
            'mail2'        => $_POST['mail2'] ?? '',
            'mail3'        => $_POST['mail3'] ?? '',
            's_vno'        => $_POST['s_vno'] ?? '',
            's_vd'         => $_POST['s_vd'] ?? '',
            's_tax_office_code' => $_POST['s_tax_office_code'] ?? '',
            's_postal_code' => $_POST['s_postal_code'] ?? '',
            'account_type' => isset($_POST['account_type']) ? (int) $_POST['account_type'] : 3,
            'payplan_code' => $_POST['payplan_code'] ?? '',
            'payplan_def'  => $_POST['payplan_def'] ?? '',
            's_web'        => $_POST['s_web'] ?? '',
            's_fax'        => $_POST['s_fax'] ?? '',
            's_corresp_lang' => $_POST['s_corresp_lang'] ?? '',
            's_gl_code'    => $_POST['s_gl_code'] ?? '',
            'credit_limit' => $_POST['credit_limit'] ?? '',
            'risk_limit'   => $_POST['risk_limit'] ?? '',
            'internal_reference' => $_POST['internal_reference'] ?? ''
        ];

        $logoResp = $logoService->updateArpFromDb($data);
        $logoErr = $logoResp['error'] ?? $logoResp['Message'] ?? '';
        if ($logoErr === '') {
            if (!empty($company['internal_reference'])) {
                $ok = $dbManager->updateCompanyByRef((int)$company['internal_reference'], $data);
            } else {
                $ok = $dbManager->updateCompany($data['s_arp_code'], $data);
            }
            if ($ok) {
                $message = '<div class="alert alert-success">Şirket güncellendi.</div>';
                $company = array_merge($company, $data);
            } else {
                $message = '<div class="alert alert-danger">DB güncelleme başarısız.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Logo API Hatası: ' . htmlspecialchars($logoErr) . '</div>';
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Şirket Düzenle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
</head>
<body data-layout="horizontal" data-topbar="colored">
<div id="layout-wrapper">
<header id="page-topbar">
<?php include "menuler/ustmenu.php"; ?>
<?php include "menuler/solmenu.php"; ?>
</header>
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
<h4 class="mb-4">Şirket Düzenle</h4>
<?= $message ?>
<form method="post" action="edit_company.php?id=<?= $id ?>">
    <input type="hidden" name="internal_reference" value="<?= htmlspecialchars($company['internal_reference'] ?? '') ?>">
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Şirket Adı</label>
        <input type="text" name="s_adi" class="form-control" value="<?= htmlspecialchars($company['s_adi'] ?? '') ?>" required>
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">ARP Kodu</label>
        <input type="text" name="s_arp_code" class="form-control" value="<?= htmlspecialchars($company['s_arp_code'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Özel Kod</label>
        <select name="s_auxil_code" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach ($specodes as $s): ?>
                <option value="<?= htmlspecialchars($s['SPECODE'], ENT_QUOTES) ?>" <?= ($company['s_auxil_code'] ?? '') === $s['SPECODE'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['SPECODE'] . ' - ' . $s['DEFINITION_']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Yetki Kodu</label>
        <select name="s_auth_code" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach ($authCodes as $a): ?>
                <option value="<?= htmlspecialchars($a['CODE'], ENT_QUOTES) ?>" <?= ($company['s_auth_code'] ?? '') === $a['CODE'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['CODE'] . ($a['DEFINITION_'] ? ' - ' . $a['DEFINITION_'] : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Kategori</label>
        <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($company['kategori'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Açık Hesap</label>
        <input type="text" name="acikhesap" class="form-control" value="<?= htmlspecialchars($company['acikhesap'] ?? '') ?>">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Adres</label>
    <textarea name="s_adresi" class="form-control" rows="2" required><?= htmlspecialchars($company['s_adresi'] ?? '') ?></textarea>
</div>
<div class="mb-3">
    <label class="form-label">Adres 2</label>
    <input type="text" name="s_adresi2" class="form-control" value="<?= htmlspecialchars($company['s_adresi2'] ?? '') ?>">
</div>
<div class="row">
    <div class="mb-3 col-md-3">
        <label class="form-label">Tel Kod</label>
        <input type="text" name="s_tel1_code" class="form-control" value="<?= htmlspecialchars($company['s_tel1_code'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-3">
        <label class="form-label">Telefon</label>
        <input type="text" name="s_telefonu" class="form-control" value="<?= htmlspecialchars($company['s_telefonu'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-3">
        <label class="form-label">Tel2 Kod</label>
        <input type="text" name="s_tel2_code" class="form-control" value="<?= htmlspecialchars($company['s_tel2_code'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-3">
        <label class="form-label">Telefon 2</label>
        <input type="text" name="s_telefonu2" class="form-control" value="<?= htmlspecialchars($company['s_telefonu2'] ?? '') ?>">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Fax</label>
    <input type="text" name="s_fax" class="form-control" value="<?= htmlspecialchars($company['s_fax'] ?? '') ?>">
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Yetkili Kişi</label>
        <input type="text" name="yetkili" class="form-control" value="<?= htmlspecialchars($company['yetkili'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">2. Yetkili Kişi</label>
        <input type="text" name="yetkili2" class="form-control" value="<?= htmlspecialchars($company['yetkili2'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-4">
        <label class="form-label">E-posta</label>
        <input type="email" name="mail" class="form-control" value="<?= htmlspecialchars($company['mail'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-4">
        <label class="form-label">E-posta 2</label>
        <input type="email" name="mail2" class="form-control" value="<?= htmlspecialchars($company['mail2'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-4">
        <label class="form-label">E-posta 3</label>
        <input type="email" name="mail3" class="form-control" value="<?= htmlspecialchars($company['mail3'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Account Type</label>
        <select name="account_type" class="form-control">
            <option value="1" <?= ($company['account_type'] ?? 3) == 1 ? 'selected' : '' ?>>(AL) Alıcı</option>
            <option value="2" <?= ($company['account_type'] ?? 3) == 2 ? 'selected' : '' ?>>(SA) Satıcı</option>
            <option value="3" <?= ($company['account_type'] ?? 3) == 3 ? 'selected' : '' ?>>(AS) Alıcı + Satıcı</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">İl</label>
        <input type="text" name="s_il" class="form-control" value="<?= htmlspecialchars($company['s_il'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">İlçe</label>
        <input type="text" name="s_ilce" class="form-control" value="<?= htmlspecialchars($company['s_ilce'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Ülke Kodu</label>
        <input type="text" name="s_country_code" class="form-control" value="<?= htmlspecialchars($company['s_country_code'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Ülke</label>
        <input type="text" name="s_country" class="form-control" value="<?= htmlspecialchars($company['s_country'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Vergi No</label>
        <input type="text" name="s_vno" class="form-control" value="<?= htmlspecialchars($company['s_vno'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Vergi Dairesi</label>
        <select required class="form-control" id="tax_office" name="s_vd">
            <option value="">Lütfen Vergi Dairesi Seçiniz</option>
        </select>
        <input type="hidden" id="current_tax_office" value="<?= htmlspecialchars($company['s_vd'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Vergi Dairesi Kodu</label>
        <input type="text" name="s_tax_office_code" class="form-control" value="<?= htmlspecialchars($company['s_tax_office_code'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Posta Kodu</label>
        <input type="text" name="s_postal_code" class="form-control" value="<?= htmlspecialchars($company['s_postal_code'] ?? '') ?>">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Web</label>
    <input type="text" name="s_web" class="form-control" value="<?= htmlspecialchars($company['s_web'] ?? '') ?>">
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Ödeme Planı</label>
        <select name="payplan_code" id="payplan_code" class="form-select">
            <?php foreach ($payPlans as $p): ?>
                <option value="<?= htmlspecialchars($p['CODE'], ENT_QUOTES) ?>"
                        data-def="<?= htmlspecialchars($p['DEFINITION_'], ENT_QUOTES) ?>"
                        <?= ($company['payplan_code'] ?? '') === $p['CODE'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars("{$p['CODE']} - {$p['DEFINITION_']}") ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="payplan_def" id="payplan_def" value="<?= htmlspecialchars($company['payplan_def'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">GL Kodu</label>
        <input type="text" name="s_gl_code" class="form-control" value="<?= htmlspecialchars($company['s_gl_code'] ?? '') ?>">
    </div>
    <div class="mb-3 col-md-6">
        <label class="form-label">Kredi Limiti</label>
        <input type="number" step="0.01" name="credit_limit" class="form-control" value="<?= htmlspecialchars($company['credit_limit'] ?? '') ?>">
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label class="form-label">Risk Limiti</label>
        <input type="number" step="0.01" name="risk_limit" class="form-control" value="<?= htmlspecialchars($company['risk_limit'] ?? '') ?>">
    </div>
</div>
<div class="d-flex justify-content-between">
    <button type="submit" name="fetch_logo" class="btn btn-info">Logo'dan Veri Çek</button>
    <button type="submit" name="update" class="btn btn-success">Kaydet</button>
</div>
</form>
</div>
</div>
<?php include "menuler/footer.php"; ?>
</div>
</div>
<div class="rightbar-overlay"></div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
    $(function () {
        $.getJSON('get_tax_offices.php', function (data) {
            if (data.success && Array.isArray(data.items)) {
                var $sel = $('#tax_office');
                $.each(data.items, function (_, item) {
                    $('<option>', {value: item.CODE, text: item.NAME}).appendTo($sel);
                });
                var current = $('#current_tax_office').val();
                if (current) {
                    $sel.val(current);
                }
            }
        });

        $('#payplan_code').on('change', function () {
            var def = $(this).find('option:selected').data('def') || '';
            $('#payplan_def').val(def);
        }).trigger('change');
    });
</script>
</body>
</html>
