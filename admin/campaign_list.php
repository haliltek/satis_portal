<?php
include "../fonk.php";
oturumkontrol();

$campaigns = [];
$res = $dbManager->getConnection()->query("SELECT * FROM campaigns ORDER BY start_date DESC");
if ($res) {
    $campaigns = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kampanya Listesi</title>
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
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="mb-0">Kampanyalar</h4>
                        <a href="campaign_edit.php" class="btn btn-success btn-sm">Yeni Kampanya</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Açıklama</th>
                                        <th>İndirim %</th>
                                        <th>Başlangıç</th>
                                        <th>Bitiş</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['description']) ?></td>
                                        <td><?= $c['discount_rate'] ?></td>
                                        <td><?= $c['start_date'] ?></td>
                                        <td><?= $c['end_date'] ?></td>
                                        <td><a class="btn btn-sm btn-primary" href="campaign_edit.php?id=<?= $c['id'] ?>">Düzenle</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
