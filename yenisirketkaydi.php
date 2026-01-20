<?php
include "fonk.php";
oturumkontrol();

use App\Models\ArpMap;

global $dbManager, $logoService;
$firmNr   = (int) env('FIRM_NR', '0');
$payPlans  = $logoService->getPayPlans($firmNr);
$specodes  = $logoService->getSpecodes($firmNr);
$authCodes = $logoService->getAuthCodes($firmNr);
$defaultPayplanCode = '';
$defaultPayplanDef  = '';
if ($payPlans) {
    $defaultPayplanCode = $payPlans[0]['CODE'] ?? '';
    $defaultPayplanDef  = $payPlans[0]['DEFINITION_'] ?? '';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        a {
            text-decoration: none;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <button type="button" class="btn btn-info btn-sm waves-effect waves-light float-right" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                            <hr>
                            <?php if (isset($_POST['kayityaptir'])) {
                                $logFile = __DIR__ . '/logs/yenisirketkaydi.log';
                                error_log("POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);
                                $s_adisd = xss(addslashes($_POST["adi"]));
                                $s_adi = mb_convert_case($s_adisd, MB_CASE_TITLE, "UTF-8");
                                $data = [
                                    's_adi'        => $s_adi,
                                    's_arp_code'   => xss(addslashes($_POST['s_arp_code'] ?? '')),
                                    's_turu'       => xss(addslashes($_POST["turu"])),
                                    's_auxil_code' => xss(addslashes($_POST['auxil_code'] ?? '')),
                                    's_auth_code'  => xss(addslashes($_POST['auth_code'] ?? '')),
                                    'account_type' => isset($_POST['account_type']) ? (int) $_POST['account_type'] : 3,
                                    's_tel1_code'  => xss(addslashes($_POST['tel1_code'] ?? '')),
                                    's_telefonu'   => xss(addslashes($_POST["telefonu"])),
                                    's_tel2_code'  => xss(addslashes($_POST['tel2_code'] ?? '')),
                                    's_telefonu2'  => xss(addslashes($_POST['telefonu2'] ?? '')),
                                    's_il'         => xss(addslashes($_POST["il"])),
                                    's_ilce'       => xss(addslashes($_POST["ilce"])),
                                    's_country_code' => xss(addslashes($_POST['country_code'] ?? '')),
                                    's_country'      => xss(addslashes($_POST['country'] ?? '')),
                                    's_adresi2'      => xss(addslashes($_POST['adresi2'] ?? '')),
                                    's_postal_code'  => xss(addslashes($_POST['postal_code'] ?? '')),
                                    's_vno'        => xss(addslashes($_POST["vno"])),
                                    's_vd'         => xss(addslashes($_POST["vd"])),
                                    's_adresi'     => xss(addslashes($_POST["adresi"])),
                                    'yetkili'      => xss(addslashes($_POST['yetkili'] ?? '')),
                                    'yetkili2'     => xss(addslashes($_POST['yetkili2'] ?? '')),
                                    'mail'         => xss(addslashes($_POST['mail'] ?? '')),
                                    'mail2'        => xss(addslashes($_POST['mail2'] ?? '')),
                                    'mail3'        => xss(addslashes($_POST['mail3'] ?? '')),
                                    'payplan_code' => xss(addslashes($_POST['payplan_code'] ?? '')),
                                    'payplan_def'  => xss(addslashes($_POST['payplan_def'] ?? '')),
                                    's_web'        => xss(addslashes($_POST['s_web'] ?? '')),
                                    's_fax'        => xss(addslashes($_POST['s_fax'] ?? '')),
                                    's_corresp_lang' => xss(addslashes($_POST['s_corresp_lang'] ?? '')),
                                    's_tax_office_code' => xss(addslashes($_POST['s_tax_office_code'] ?? '')),
                                    'credit_limit' => isset($_POST['credit_limit']) && $_POST['credit_limit'] !== ''
                                        ? (float) xss(addslashes($_POST['credit_limit']))
                                        : 0,
                                    'risk_limit'   => isset($_POST['risk_limit']) && $_POST['risk_limit'] !== ''
                                        ? (float) xss(addslashes($_POST['risk_limit']))
                                        : 0,
                                ];
                                error_log("Sanitized data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);
                                $payload = ArpMap::unmap($data);
                                error_log("Logo payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);

                                $logoResp = $logoService->createArpFromDb($data);
                                error_log("Logo response: " . json_encode($logoResp, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);

                                $logoError = $logoResp['error'] ?? $logoResp['Message'] ?? '';

                                if ($logoError === '') {
                                    $ref  = $logoResp['INTERNAL_REFERENCE'] ?? ($logoResp['Arps'][0]['INTERNAL_REFERENCE'] ?? null);
                                    $code = $logoResp['CODE'] ?? ($logoResp['Arps'][0]['CODE'] ?? null);
                                    if ($ref) {
                                        $data['internal_reference'] = $ref;
                                        if (!$code) {
                                            $fetched = $logoService->getArpMappedByRef((int)$ref);
                                            error_log("Logo GET response: " . json_encode($fetched, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);
                                            $code = $fetched['s_arp_code'] ?? null;
                                        }
                                    }
                                    if ($code) {
                                        $data['s_arp_code'] = $code;
                                        // push the full details now that Logo generated the code
                                        $logoService->updateArpFromDb($data);
                                    }
                                    $sirketekleme = $dbManager->insertCompany($data);
                                    if ($sirketekleme) {
                                        error_log("DB insert success" . PHP_EOL, 3, $logFile);
                                        $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES(?,?,?,?)";
                                        $logstmt = mysqli_prepare($db, $logbaglanti);
                                        $islem = 'Yeni Şirket Oluşturma';
                                        $durum = 'Başarılı';
                                        mysqli_stmt_bind_param($logstmt, "ssss", $islem, $yonetici_id_sabit, $zaman, $durum);
                                        mysqli_stmt_execute($logstmt);
                                        mysqli_stmt_close($logstmt);
                                        echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Şirket Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                        echo '<meta http-equiv="refresh" content="2; url=tumsirketler.php"> ';
                                    } else {
                                        $error_message = 'DB insert failed';
                                        error_log('yenisirketkaydi.php - Insert error: ' . $error_message);
                                        error_log("DB insert failed" . PHP_EOL, 3, $logFile);
                                        echo '<div class="alert alert-danger" role="alert">Veritabanı kaydı başarısız.</div>';
                                    }
                                } else {
                                    $error_message = $logoError;
                                    error_log('yenisirketkaydi.php - Logo error: ' . $logoError);
                                    error_log("Logo error: " . $logoError . PHP_EOL, 3, $logFile);
                                    echo '<div class="alert alert-danger" role="alert">Logo API Hatası: ' . htmlspecialchars($logoError) . '</div>';
                                }
                            } ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Yeni Şirket Oluşturun!</h4>
                                    <div class="p-2 mt-2">
                                        <form method="post" action="yenisirketkaydi.php">
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="username">Şirket Adı</label>
                                                    <input required type="text" class="form-control" id="username" name="adi" placeholder="ÖRN: Gemaş">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="username">Şirket Türü</label>
                                                    <select class="form-control" name="turu">
                                                        <option value="Limited Şirket">Limited Şirket</option>
                                                        <option value="Anonim Şirket">Anonim Şirket</option>
                                                        <option value="Komandit Şirket">Komandit Şirket</option>
                                                        <option value="Şahıs Şirket">Şahıs Şirket</option>
                                                        <option value="Holding">Holding</option>
                                                        <option value="Dernek">Dernek</option>
                                                        <option value="Belediye">Belediye</option>
                                                        <option value="Diğer">Diğer</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="account_type">Account Type</label>
                                                    <select class="form-control" name="account_type" id="account_type">
                                                        <option value="1">(AL) Alıcı</option>
                                                        <option value="2">(SA) Satıcı</option>
                                                        <option value="3" selected>(AS) Alıcı + Satıcı</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="s_arp_code">ARP Kodu (opsiyonel)</label>
                                                <input type="text" class="form-control" id="s_arp_code" name="s_arp_code">
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="auxil_code">Özel Kod</label>
                                                    <select id="auxil_code" name="auxil_code" class="form-select">
                                                        <option value="">Seçiniz</option>
                                                        <?php foreach ($specodes as $s): ?>
                                                            <option value="<?= htmlspecialchars($s['SPECODE'], ENT_QUOTES) ?>">
                                                                <?= htmlspecialchars($s['SPECODE'] . ' - ' . $s['DEFINITION_']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="auth_code">Yetki Kodu</label>
                                                    <select id="auth_code" name="auth_code" class="form-select">
                                                        <option value="">Seçiniz</option>
                                                        <?php foreach ($authCodes as $a): ?>
                                                            <option value="<?= htmlspecialchars($a['CODE'], ENT_QUOTES) ?>">
                                                                <?= htmlspecialchars($a['CODE'] . ($a['DEFINITION_'] ? ' - ' . $a['DEFINITION_'] : '')) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                            <label class="form-label" for="username">Şirket Adresi</label>
                                            <textarea required type="text" class="form-control" rows="1" id="username" name="adresi" placeholder="ÖRN: Şirket Tam Adresinizi Belirtiniz"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="adresi2">Şirket Adresi 2</label>
                                                <input type="text" class="form-control" id="adresi2" name="adresi2">
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="tel1_code">Telefon Kodu</label>
                                                    <input type="text" class="form-control" id="tel1_code" name="tel1_code">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="telefonu">Şirket Telefonu</label>
                                                    <input required type="text" class="form-control" id="telefonu" name="telefonu">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="tel2_code">Telefon 2 Kodu</label>
                                                    <input type="text" class="form-control" id="tel2_code" name="tel2_code">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="telefonu2">Şirket Telefonu 2</label>
                                                    <input type="text" class="form-control" id="telefonu2" name="telefonu2">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="yetkili">Yetkili Kişi</label>
                                                <input type="text" class="form-control" id="yetkili" name="yetkili">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="yetkili2">2. Yetkili Kişi</label>
                                                <input type="text" class="form-control" id="yetkili2" name="yetkili2">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="mail">E-posta</label>
                                                <input type="email" class="form-control" id="mail" name="mail">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="mail2">E-posta 2</label>
                                                <input type="email" class="form-control" id="mail2" name="mail2">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="mail3">E-posta 3</label>
                                                <input type="email" class="form-control" id="mail3" name="mail3">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="s_web">Web</label>
                                                <input type="text" class="form-control" id="s_web" name="s_web">
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                <label class="form-label" for="il">Şirket Bulunduğu İl </label>
                                                <input type="text" id="il" name="il" class="form-control">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                <label class="form-label" for="ilce"> Şirket Bulunduğu İlçe</label>
                                                <input type="text" id="ilce" name="ilce" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                <label class="form-label" for="country_code">Ülke Kodu</label>
                                                <input type="text" id="country_code" name="country_code" class="form-control">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                <label class="form-label" for="country">Ülke</label>
                                                <input type="text" id="country" name="country" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="username">Vergi No </label>
                                                    <input required type="number" maxlength="11" class="form-control" id="username" name="vno" placeholder="ÖRN: 05555555555">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="tax_office"> Vergi Dairesi</label>
                                                    <select required class="form-control" id="tax_office" name="vd">
                                                        <option value="">Lütfen Vergi Dairesi Seçiniz</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="tax_office_code">Vergi Dairesi Kodu</label>
                                                    <input type="text" id="tax_office_code" name="s_tax_office_code" class="form-control">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="postal_code">Posta Kodu</label>
                                                    <input type="text" id="postal_code" name="postal_code" class="form-control">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="fax">Fax</label>
                                                <input type="text" class="form-control" name="s_fax" id="fax">
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="payplan_code">Ödeme Planı</label>
                                                    <select name="payplan_code" id="payplan_code" class="form-select">
                                                        <?php foreach ($payPlans as $idx => $p): ?>
                                                            <option value="<?= htmlspecialchars($p['CODE'], ENT_QUOTES) ?>"
                                                                data-def="<?= htmlspecialchars($p['DEFINITION_'], ENT_QUOTES) ?>"
                                                                <?= $idx === 0 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars("{$p['CODE']} - {$p['DEFINITION_']}") ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="hidden" name="payplan_def" id="payplan_def" value="<?= htmlspecialchars($defaultPayplanDef, ENT_QUOTES) ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="credit_limit">Kredi Limiti</label>
                                                    <input type="number" step="0.01" class="form-control" name="credit_limit" id="credit_limit">
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="risk_limit">Risk Limiti</label>
                                                    <input type="number" step="0.01" class="form-control" name="risk_limit" id="risk_limit">
                                                </div>
                                            </div>
                                            <div class="mt-3 text-end col-md-12">
                                                <button style="width:100%" class="btn btn-success w-sm waves-effect waves-light" name="kayityaptir" type="submit">Şirket Oluştur</button>
                                            </div>
                                        </form>
                                    </div>
                                </div> <!-- Card-Body Bitişi -->
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include "menuler/footer.php"; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->
    <div class="modal fade yardim" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Yardım</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <b>GENEL TANIM</b>
                            <p>Sistem üzerinden kendileri kayıt olabilirken panel üzerinden yönetim ve erişim yetkisi bulunan personel veya personellerce yeni şirket kaydı oluşturup bu şirkete personel ataması gerçekleştirebiliriniz. </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Anladım, Kapat</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>
    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <!-- apexcharts -->
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <!-- Responsive examples -->
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <!-- Datatable init js -->
    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        $(function () {
            $.getJSON('get_tax_offices.php', function (data) {
                if (data.success && Array.isArray(data.items)) {
                    var $sel = $('#tax_office');
                    $.each(data.items, function (_, item) {
                        $('<option>', {value: item.CODE, text: item.NAME}).appendTo($sel);
                    });
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