<?php
include "fonk.php";
oturumkontrol();

global $dbManager;

$result = $dbManager->getConnection()->query(
    "SELECT u.*, s.s_adi, s.s_arp_code FROM b2b_users u
     LEFT JOIN sirket s ON s.sirket_id = u.company_id
     WHERE u.status = 0"
);
$dealers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Bekleyen Bayi Hesapları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
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
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Onay Bekleyen Bayi Hesapları</h4>
                                <div class="table-responsive">
                                    <table id="dealerTable" class="table table-bordered dt-responsive nowrap" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Şirket</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dealers as $d): ?>
            <tr>
                <td><?php echo $d['id']; ?></td>
                <td><?php echo $d['s_arp_code'] . ' - ' . $d['s_adi']; ?></td>
                <td><?php echo $d['username']; ?></td>
                <td><?php echo $d['email']; ?></td>
                <td><?php echo $d['role']; ?></td>
                <td>
                    <a href="dealer_account_action.php?id=<?php echo $d['id']; ?>&action=approve" class="btn btn-success btn-sm">Onayla</a>
                    <a href="dealer_account_action.php?id=<?php echo $d['id']; ?>&action=reject" class="btn btn-danger btn-sm">Reddet</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
                                </div>
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
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
$(function(){
    $('#dealerTable').DataTable({
        language: { url: 'assets/libs/datatables.net/i18n/tr.json' },
        pageLength: 50,
        order: []
    });
});
</script>
</body>
</html>
