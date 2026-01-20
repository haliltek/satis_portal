<?php
include "fonk.php";
oturumkontrol();

function processFormSubmissions() {
    global $db, $yonetici_id_sabit, $zaman, $sistemayar;
    $message = "";

    if (isset($_POST['kayit'])) {
        // Yeni Personel Kayıt İşlemi
        $adsoyad     = xss(addslashes($_POST["adsoyad"]));
        $eposta      = xss(addslashes($_POST["eposta"]));
        $gelenParola = xss($_POST["parola"]); 
        $parola = password_hash($gelenParola, PASSWORD_DEFAULT);
        $tur         = xss(addslashes($_POST["tur"]));
        $satis_tipi  = xss(addslashes($_POST["satis_tipi"]));
        $telefon     = xss(addslashes($_POST["telefon"]));
        $bolum       = xss(addslashes($_POST["bolum"]));
        $mailposta   = xss(addslashes($_POST["mailposta"]));
        $mailparola  = xss(addslashes($_POST["mailparola"]));
        $unvan       = xss(addslashes($_POST["unvan"]));
        $mailport    = xss(addslashes($_POST["mailport"]));
        $mailsmtp    = xss(addslashes($_POST["mailsmtp"]));
        $iskonto_max = xss(addslashes($_POST["iskonto_max"]));

        // Eksik alan kontrolü
        if (empty($adsoyad) || empty($eposta) || empty($parola) || empty($tur) ||
            empty($satis_tipi) || empty($telefon) || empty($bolum) || empty($iskonto_max)) {
            $message = '<div class="alert alert-danger">Eksik alanlar var. Tüm bilgileri doldurduğunuzdan emin olun.</div>';
            return $message;
        }

        // Yeni kayıt sorgusu
        $query = "INSERT INTO yonetici (
                    mailposta, mailparola, unvan, adsoyad, eposta, parola, tur, satis_tipi, telefon, bolum, onay, mailport, mailsmtp, iskonto_max
                  ) VALUES (
                    '$mailposta', '$mailparola', '$unvan', '$adsoyad', '$eposta', '$parola', '$tur', '$satis_tipi', '$telefon', '$bolum', '0', '$mailport', '$mailsmtp', '$iskonto_max'
                  )";
        $result = mysqli_query($db, $query);

        if (!$result) {
            $message = '<div class="alert alert-danger">Hata: ' . mysqli_error($db) . '</div>';
        } else {
            // Log kaydı
            $logquery = "INSERT INTO log_yonetim (islem, personel, tarih, durum)
                         VALUES ('Yeni Personel Kayıt', '$yonetici_id_sabit', '$zaman', 'Başarılı')";
            mysqli_query($db, $logquery);
            $message = '<div class="alert alert-success">Sayın ' . htmlspecialchars($adsoyad) . '<br> Personel başarıyla kaydedildi. Lütfen bekleyiniz...</div>';
            header("refresh:2; url=personeller.php");
        }
    } elseif (isset($_POST['duzenleme'])) {
        // Personel Güncelleme İşlemi
        $adsoyad     = xss(addslashes($_POST["adsoyad"]));
        $eposta      = xss(addslashes($_POST["eposta"]));
        $mailposta   = xss(addslashes($_POST["mailposta"]));
        $mailparola  = xss(addslashes($_POST["mailparola"]));
        $unvan       = xss(addslashes($_POST["unvan"]));
        $mailport    = xss(addslashes($_POST["mailport"]));
        $mailsmtp    = xss(addslashes($_POST["mailsmtp"]));
        $tur         = xss(addslashes($_POST["tur"]));
        $satis_tipi  = xss(addslashes($_POST["satis_tipi"]));
        $telefon     = xss(addslashes($_POST["telefon"]));
        $bolum       = xss(addslashes($_POST["bolum"]));
        $icerikid    = intval($_POST["icerikid"]);
        $iskonto_max = xss(addslashes($_POST["iskonto_max"]));
        $parola      = xss(addslashes($_POST["parola"]));

        // Parola güncelleme kontrolü: Eğer parola girilmişse md5 ile şifrele
        if (!empty($parola)) {
            $gelenParola = xss($_POST["parola"]);
            $parolam = password_hash($gelenParola, PASSWORD_DEFAULT);
            $query = "UPDATE yonetici SET 
                        mailport='$mailport',
                        mailsmtp='$mailsmtp',
                        unvan='$unvan',
                        mailparola='$mailparola',
                        mailposta='$mailposta',
                        parola='$parolam',
                        adsoyad='$adsoyad',
                        eposta='$eposta',
                        tur='$tur',
                        satis_tipi='$satis_tipi',
                        telefon='$telefon',
                        bolum='$bolum',
                        iskonto_max='$iskonto_max'
                      WHERE yonetici_id='$icerikid'";
        } else {
            $query = "UPDATE yonetici SET 
                        mailport='$mailport',
                        mailsmtp='$mailsmtp',
                        unvan='$unvan',
                        mailparola='$mailparola',
                        mailposta='$mailposta',
                        adsoyad='$adsoyad',
                        eposta='$eposta',
                        tur='$tur',
                        satis_tipi='$satis_tipi',
                        telefon='$telefon',
                        bolum='$bolum',
                        iskonto_max='$iskonto_max'
                      WHERE yonetici_id='$icerikid'";
        }
        $result = mysqli_query($db, $query);

        if ($result) {
            $logquery = "INSERT INTO log_yonetim (islem, personel, tarih, durum)
                         VALUES ('Personel Güncelleme', '$yonetici_id_sabit', '$zaman', 'Başarılı')";
            mysqli_query($db, $logquery);
            $message = '<div class="alert alert-success">Sayın ' . htmlspecialchars($adsoyad) . '<br> Personel başarıyla güncellendi. Lütfen bekleyiniz...</div>';
            header("refresh:2; url=personeller.php");
        } else {
            $logquery = "INSERT INTO log_yonetim (islem, personel, tarih, durum)
                         VALUES ('Personel Güncelleme', '$yonetici_id_sabit', '$zaman', 'Başarısız')";
            mysqli_query($db, $logquery);
            $message = '<div class="alert alert-danger">Sayın ' . htmlspecialchars($adsoyad) . '<br> Personel güncellenemedi. Lütfen tekrar deneyiniz...</div>';
            header("refresh:2; url=personeller.php");
        }
    }
    return $message;
}

$message = processFormSubmissions();

// Personel listesini gösteren tabloyu oluşturan fonksiyon
function renderTable($db) {
    $html = '<table id="datatable" class="table table-bordered table-hover table-responsive">
              <thead class="table-light">
                <tr>
                  <th>Personel No</th>
                  <th>Ad Soyad</th>
                  <th>E-Posta</th>
                  <th>Tür</th>
                  <th>Satış Tipi</th>
                  <th>Telefon</th>
                  <th>Bölüm</th>
                  <th>İşlemler</th>
                </tr>
              </thead>
              <tbody>';
    $result = mysqli_query($db, "SELECT * FROM yonetici");
    while ($personel = mysqli_fetch_array($result)) {
        $html .= '<tr>
                    <td>' . $personel["kartno"] . '</td>
                    <td>' . htmlspecialchars($personel["adsoyad"]) . '</td>
                    <td>' . htmlspecialchars($personel["eposta"]) . '</td>
                    <td>' . htmlspecialchars($personel["tur"]) . '</td>
                    <td>' . htmlspecialchars($personel["satis_tipi"] ?? '') . '</td>
                    <td>' . htmlspecialchars($personel["telefon"]) . '</td>
                    <td>' . htmlspecialchars($personel["bolum"]) . '</td>
                    <td>
                      <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target=".duzenle' . $personel["yonetici_id"] . '">Düzenle</button>
                      <a target="_blank" href="konumsor.php?id=' . $personel["yonetici_id"] . '" class="btn btn-sm btn-' . 
                        ($personel["bolum"] == 'Şöför' ? 'warning' : 'primary') . '">Konumu İncele</a>
                      <a href="personelsil.php?id=' . $personel["yonetici_id"] . '" class="btn btn-sm btn-danger">Sistemden Sil</a>
                    </td>
                   </tr>';
    }
    $html .= '</tbody>
              <tfoot class="table-light">
                <tr>
                  <th>Personel No</th>
                  <th>Ad Soyad</th>
                  <th>E-Posta</th>
                  <th>Tür</th>
                  <th>Satış Tipi</th>
                  <th>Telefon</th>
                  <th>Bölüm</th>
                  <th>İşlemler</th>
                </tr>
              </tfoot>
             </table>';
    return $html;
}

// Yeni Personel ekleme modalının HTML'ini oluşturan fonksiyon
function renderNewPersonelModal($db) {
    $html = '<div class="modal fade yenikategori" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Yeni Personel Tanımlayınız</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                  </div>
                  <form method="post" action="personeller.php" class="needs-validation" novalidate>
                    <div class="modal-body">
                      <div class="row">';
    // Form alanlarını tanımlamak için alan dizisi
    $fields = [
        ["label" => "Ad Soyad", "name" => "adsoyad", "type" => "text", "placeholder" => "ÖR. Erkan AK", "col" => 4],
        ["label" => "E-Posta", "name" => "eposta", "type" => "email", "placeholder" => "ÖR. egemasr@gemas.com", "col" => 4],
        ["label" => "Parola", "name" => "parola", "type" => "password", "placeholder" => "***********", "col" => 4],
        ["label" => "Tür", "name" => "tur", "type" => "select", "options" => ["Personel", "Yönetici"], "col" => 4],
        ["label" => "Satış Tipi", "name" => "satis_tipi", "type" => "select", "options" => ["Yurt İçi", "Yurt Dışı"], "col" => 4],
        ["label" => "Bölüm", "name" => "bolum", "type" => "select_db", "query" => "SELECT * FROM departmanlar", "col" => 4],
        ["label" => "Telefon", "name" => "telefon", "type" => "number", "placeholder" => "ÖR. 05333333333", "col" => 4],
        ["label" => "Mail E-Posta", "name" => "mailposta", "type" => "email", "placeholder" => "ÖR. egemasr@gemas.com", "col" => 4],
        ["label" => "Mail Parola", "name" => "mailparola", "type" => "text", "placeholder" => "ÖR. Egema$R@123", "col" => 4],
        ["label" => "Ünvan", "name" => "unvan", "type" => "text", "placeholder" => "ÖR. Müdür", "col" => 4],
        ["label" => "Mail Port", "name" => "mailport", "type" => "number", "placeholder" => "ÖR. 587", "col" => 6],
        ["label" => "Mail SMTP", "name" => "mailsmtp", "type" => "text", "placeholder" => "ÖR. smtp.gemas.com", "col" => 6],
        ["label" => "İskonto Maksimum (%)", "name" => "iskonto_max", "type" => "number", "placeholder" => "ÖR. 10.00", "step" => "0.01", "min" => "0", "col" => 4]
    ];

    foreach ($fields as $field) {
        $html .= '<div class="col-md-' . $field["col"] . '">
                    <div class="mb-3">
                      <label class="form-label" for="' . $field["name"] . '">' . $field["label"] . '</label>';
        if ($field["type"] == "select") {
            $html .= '<select class="form-select" name="' . $field["name"] . '" id="' . $field["name"] . '" required>';
            foreach ($field["options"] as $option) {
                $html .= '<option value="' . $option . '">' . $option . '</option>';
            }
            $html .= '</select>';
        } elseif ($field["type"] == "select_db") {
            $html .= '<select class="form-select" name="' . $field["name"] . '" id="' . $field["name"] . '">';
            $result = mysqli_query($db, $field["query"]);
            while ($row = mysqli_fetch_array($result)) {
                $value = htmlspecialchars($row["departman"]);
                $html .= '<option value="' . $value . '">' . $value . '</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input type="' . $field["type"] . '" name="' . $field["name"] . '" class="form-control" id="' . $field["name"] . '" placeholder="' . $field["placeholder"] . '"';
            if (isset($field["step"])) {
                $html .= ' step="' . $field["step"] . '"';
            }
            if (isset($field["min"])) {
                $html .= ' min="' . $field["min"] . '"';
            }
            $html .= ' required>';
        }
        $html .= '</div></div>';
    }

    $html .= '   </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                      <button type="submit" name="kayit" class="btn btn-success">Yeni Personel Oluştur!</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>';
    return $html;
}

// Yardım modalı
function renderHelpModal() {
    return '<div class="modal fade yardim" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Yardım</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <div class="col-md-8">
                        <img src="images/yardim/kategori.png" alt="Yardım Görseli" class="img-fluid rounded">
                      </div>
                      <div class="col-md-4">
                        <h6 class="mb-2">Kategori Alanı</h6>
                        <p class="mb-0">Kategori alanı E-Ticaret sitesinde sol menü şeklinde bulunur ve ürün kategorilerinin yönetilmesinde kullanılır.</p>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anladım, Kapat</button>
                  </div>
                </div>
              </div>
            </div>';
}

// Düzenle modal'larını oluşturan fonksiyon
function renderEditModals($db) {
    $html = '';
    $result = mysqli_query($db, "SELECT * FROM yonetici");
    while ($personel = mysqli_fetch_array($result)) {
        $modalId = $personel["yonetici_id"];
        $html .= '<div class="modal fade duzenle' . $modalId . '" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="duzenleModalLabel' . $modalId . '">' . htmlspecialchars($personel["adsoyad"]) . ' - Düzenle</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                        </div>
                        <div class="modal-body">
                          <form method="post" action="personeller.php" class="needs-validation" novalidate>
                            <div class="row">
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="adsoyad' . $modalId . '">Ad Soyad</label>
                                  <input type="text" name="adsoyad" value="' . htmlspecialchars($personel["adsoyad"]) . '" class="form-control" id="adsoyad' . $modalId . '" placeholder="ÖR. Erkan AK" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="eposta' . $modalId . '">E-Posta</label>
                                  <input type="email" name="eposta" value="' . htmlspecialchars($personel["eposta"]) . '" class="form-control" id="eposta' . $modalId . '" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="tur' . $modalId . '">Tür</label>
                                  <select class="form-select" name="tur" id="tur' . $modalId . '">
                                    <option value="' . htmlspecialchars($personel["tur"]) . '" selected>' . htmlspecialchars($personel["tur"]) . '</option>
                                    <option value="Personel">Personel</option>
                                    <option value="Yönetici">Yönetici</option>
                                  </select>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="satis_tipi' . $modalId . '">Satış Tipi</label>
                                  <select class="form-select" name="satis_tipi" id="satis_tipi' . $modalId . '" required>
                                    <option value="Yurt İçi"' . (($personel["satis_tipi"] ?? '') === 'Yurt İçi' ? ' selected' : '') . '>Yurt İçi</option>
                                    <option value="Yurt Dışı"' . (($personel["satis_tipi"] ?? '') === 'Yurt Dışı' ? ' selected' : '') . '>Yurt Dışı</option>
                                  </select>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="bolum' . $modalId . '">Bölüm</label>
                                  <select class="form-select" name="bolum" id="bolum' . $modalId . '">
                                    <option value="' . htmlspecialchars($personel["bolum"]) . '" selected>' . htmlspecialchars($personel["bolum"]) . '</option>';
        $depResult = mysqli_query($db, "SELECT * FROM departmanlar");
        while ($departman = mysqli_fetch_array($depResult)) {
            $dep = htmlspecialchars($departman["departman"]);
            $html .= '<option value="' . $dep . '">' . $dep . '</option>';
        }
        $html .= '     </select>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="telefon' . $modalId . '">Telefon</label>
                                  <input type="number" name="telefon" value="' . htmlspecialchars($personel["telefon"]) . '" class="form-control" id="telefon' . $modalId . '" placeholder="ÖR. 05333333333" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="parola' . $modalId . '">Parola</label>
                                  <input type="password" name="parola" class="form-control" id="parola' . $modalId . '" placeholder="***********">
                                  <small class="form-text text-muted">Yeni parola giriniz; değiştirmek istemiyorsanız boş bırakınız.</small>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="mailposta' . $modalId . '">Mail E-Posta</label>
                                  <input type="email" name="mailposta" value="' . htmlspecialchars($personel["mailposta"]) . '" class="form-control" id="mailposta' . $modalId . '" placeholder="ÖR. egemasr@gemas.com" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="mailparola' . $modalId . '">Mail Parola</label>
                                  <input type="text" name="mailparola" value="' . htmlspecialchars($personel["mailparola"]) . '" class="form-control" id="mailparola' . $modalId . '" placeholder="ÖR. Egema$R@123" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="unvan' . $modalId . '">Ünvan</label>
                                  <input type="text" name="unvan" value="' . htmlspecialchars($personel["unvan"]) . '" class="form-control" id="unvan' . $modalId . '" placeholder="ÖR. Müdür" required>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-3">
                                  <label class="form-label" for="mailport' . $modalId . '">Mail Port</label>
                                  <input type="number" name="mailport" value="' . htmlspecialchars($personel["mailport"]) . '" class="form-control" id="mailport' . $modalId . '" placeholder="ÖR. 587" required>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-3">
                                  <label class="form-label" for="mailsmtp' . $modalId . '">Mail SMTP</label>
                                  <input type="text" name="mailsmtp" value="' . htmlspecialchars($personel["mailsmtp"]) . '" class="form-control" id="mailsmtp' . $modalId . '" placeholder="ÖR. smtp.gemas.com" required>
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="mb-3">
                                  <label class="form-label" for="iskonto_max' . $modalId . '">İskonto Maksimum (%)</label>
                                  <input type="number" name="iskonto_max" value="' . htmlspecialchars($personel["iskonto_max"]) . '" class="form-control" id="iskonto_max' . $modalId . '" step="0.01" min="0" required>
                                </div>
                              </div>
                              <input type="hidden" name="icerikid" value="' . intval($personel["yonetici_id"]) . '">
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                              <button type="submit" name="duzenleme" class="btn btn-success">Düzenleyin!</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>';
    }
    return $html;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?php echo htmlspecialchars($sistemayar["title"]); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?php echo htmlspecialchars($sistemayar["description"]); ?>" />
  <meta name="keywords" content="<?php echo htmlspecialchars($sistemayar["keywords"]); ?>" />
  <link rel="shortcut icon" href="assets/images/favicon.ico">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/css/icons.min.css" rel="stylesheet" />
  <link href="assets/css/app.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" />
  <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
    }
    a { text-decoration: none; }
    .card {
      margin-top: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .modal-header {
      background-color: #f1f1f1;
    }
  </style>
  <script src="//cdn.ckeditor.com/4.18.0/full/ckeditor.js"></script>
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
          <div class="row">
            <div class="col-12">
              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target=".yardim">Yardım</button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target=".yenikategori">Yeni Personel Tanımlayınız</button>
              </div>
              <?php echo $message; ?>
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Personel Yönetimi</h5>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <?php echo renderTable($db); ?>
                  </div>
                </div>
              </div>
            </div>
          </div> <!-- row -->
        </div> <!-- container-fluid -->
      </div> <!-- page-content -->
      <?php include "menuler/footer.php"; ?>
    </div> <!-- main-content -->
  </div> <!-- layout-wrapper -->
  <?php
    echo renderNewPersonelModal($db);
    echo renderHelpModal();
    echo renderEditModals($db);
  ?>
  <div class="rightbar-overlay"></div>
  <script src="assets/libs/jquery/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/libs/metismenu/metisMenu.min.js"></script>
  <script src="assets/libs/simplebar/simplebar.min.js"></script>
  <script src="assets/libs/node-waves/waves.min.js"></script>
  <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
  <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
  <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="assets/js/pages/dashboard.init.js"></script>
  <script src="assets/js/app.js"></script>
  <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
  <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
  <script src="assets/js/pages/datatables.init.js"></script>
  <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
  <script src="assets/libs/parsleyjs/parsley.min.js"></script>
</body>
</html>
