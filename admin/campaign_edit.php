<?php
include "../fonk.php";
oturumkontrol();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaign = [
    'product_id'=> '', 'group_id'=>'', 'discount_rate'=>'0',
    'start_date'=>'', 'end_date'=>'', 'description'=>''
];
if ($id > 0) {
    $stmt = $dbManager->getConnection()->prepare('SELECT * FROM campaigns WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) $campaign = $res;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [
        'product_id'   => $_POST['product_id'] ?: null,
        'group_id'     => $_POST['group_id'] ?: null,
        'discount_rate'=> (float)($_POST['discount_rate'] ?? 0),
        'start_date'   => $_POST['start_date'] ?: null,
        'end_date'     => $_POST['end_date'] ?: null,
        'description'  => $_POST['description'] ?? ''
    ];
    if ($id>0) {
        $dbManager->updateCampaign($id, $data);
    } else {
        $id = $dbManager->createCampaign($data);
    }
    header('Location: campaign_edit.php?id='.$id);
    exit;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kampanya Düzenle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/icons.min.css" rel="stylesheet">
    <link href="../assets/css/app.min.css" rel="stylesheet">
</head>
<body data-layout="horizontal" data-topbar="colored">
<div id="layout-wrapper">
<header id="page-topbar">
<?php include "../menuler/ustmenu.php"; ?>
<?php include "../menuler/solmenu.php"; ?>
</header>
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
<div class="card">
<div class="card-header"><h4 class="mb-0">Kampanya</h4></div>
<div class="card-body">
<form method="post">
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Ürün ID</label>
<input type="number" name="product_id" class="form-control" value="<?= htmlspecialchars($campaign['product_id']) ?>">
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Grup ID</label>
<input type="number" name="group_id" class="form-control" value="<?= htmlspecialchars($campaign['group_id']) ?>">
</div>
</div>
<div class="mb-3">
<label class="form-label">İndirim Oranı (%)</label>
<input type="number" step="0.01" name="discount_rate" class="form-control" value="<?= htmlspecialchars($campaign['discount_rate']) ?>" required>
</div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Başlangıç</label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($campaign['start_date']) ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Bitiş</label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($campaign['end_date']) ?>"></div>
</div>
<div class="mb-3">
<label class="form-label">Açıklama</label>
<textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($campaign['description']) ?></textarea>
</div>
<button type="submit" class="btn btn-success">Kaydet</button>
<a href="campaign_list.php" class="btn btn-secondary">Listeye Dön</a>
</form>
</div>
</div>
</div>
</div>
<?php include "../menuler/footer.php"; ?>
</div>
</div>
<script src="../assets/libs/jquery/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
