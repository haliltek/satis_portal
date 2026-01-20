<?php
include "fonk.php";
oturumkontrol();

// Contract edit page
$sozlesme_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing contract if editing
$sozlesme = ['sozlesmeadi' => '', 'sozlesme_yeri' => 'Teklif / Sipariş', 'footer' => 'Evet', 'sozlesme_metin' => ''];
if ($sozlesme_id > 0) {
    $res = mysqli_query($db, "SELECT * FROM sozlesmeler WHERE sozlesme_id = $sozlesme_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $sozlesme = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adi = addslashes($_POST['sozlesmeadi'] ?? '');
    $yeri = addslashes($_POST['yeri'] ?? 'Teklif / Sipariş');
    $footer = addslashes($_POST['footer'] ?? 'Evet');
    $metin = addslashes($_POST['metin'] ?? '');
    if ($sozlesme_id > 0) {
        $sql = "UPDATE sozlesmeler SET sozlesmeadi='$adi', sozlesme_yeri='$yeri', footer='$footer', sozlesme_metin='$metin' WHERE sozlesme_id=$sozlesme_id";
        $ok = mysqli_query($db, $sql);
    } else {
        $sql = "INSERT INTO sozlesmeler (sozlesmeadi, sozlesme_yeri, footer, sozlesme_metin) VALUES ('$adi', '$yeri', '$footer', '$metin')";
        $ok = mysqli_query($db, $sql);
        if ($ok) $sozlesme_id = mysqli_insert_id($db);
    }
    if ($ok) {
        echo '<div class="alert alert-success">Kaydedildi</div>';
    } else {
        echo '<div class="alert alert-danger">Hata oluştu</div>';
    }
    echo '<meta http-equiv="refresh" content="2; url=sozlesme_duzenle.php?id='.$sozlesme_id.'">';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar['title']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet" />
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
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Sözleşme Düzenle</h4>
                            </div>
                            <div class="card-body">
                                <form method="post" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label">Sözleşme Adı</label>
                                        <input type="text" name="sozlesmeadi" value="<?php echo htmlspecialchars($sozlesme['sozlesmeadi']); ?>" class="form-control" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sözleşme Yeri</label>
                                            <input type="text" name="yeri" value="<?php echo htmlspecialchars($sozlesme['sozlesme_yeri']); ?>" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Footer'da Gösterilsin mi?</label>
                                            <select name="footer" class="form-select" required>
                                                <option value="Evet" <?php echo $sozlesme['footer']=='Evet'?'selected':''; ?>>Evet</option>
                                                <option value="Hayır" <?php echo $sozlesme['footer']=='Hayır'?'selected':''; ?>>Hayır</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sözleşme Metni</label>
                                        <textarea class="form-control summernote" name="metin" rows="10" required><?php echo htmlspecialchars($sozlesme['sozlesme_metin']); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">Kaydet</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "menuler/footer.php"; ?>
    </div>
</div>
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script>
$(function(){ $('.summernote').summernote({height:300}); });
</script>
</body>
</html>
