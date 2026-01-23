<?php
$genelayar_sorgulama = mysqli_query($db, "SELECT * FROM  ayarlar");
$ayarim = mysqli_fetch_array($genelayar_sorgulama);
$userType = $_SESSION['user_type'] ?? '';
?>
<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="anasayfa.php" class="logo">
                    <span class="logo-lg">
                        <img src="images/<?php echo $ayarim["resim"]; ?>" alt="<?php echo $ayar["unvan"]; ?>" height="60">
                    </span>
                </a>
            </div>
            <div class="ustbos" style="margin-top:5%">
                <button type="button"
                    class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light"
                    data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
            </div>
            <!-- App Search-->
        </div>
        <div class="dropdown d-inline-block">
            <?php
            $displayCompany = $yoneticisorgula["bolum"] ?? '';
            if ($userType === 'Bayi') {
                $cid = (int)($_SESSION['dealer_company_id'] ?? 0);
                if ($cid) {
                    $cRow = $dbManager->getCompanyInfoById($cid);
                    $displayCompany = $cRow['s_adi'] ?? 'Bayi';
                } else {
                    $displayCompany = 'Bayi';
                }
            }
            ?>
            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span
                    class="d-none d-xl-inline-block ms-1 fw-medium font-size-15"><?php echo htmlspecialchars($yoneticisorgula["adsoyad"] ?? ''); ?>
                    (<?php echo htmlspecialchars($displayCompany); ?>)</span>
                <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <!-- item-->
                <a class="dropdown-item" href="<?= $userType==='Bayi' ? 'dealer_profile.php' : 'personeller.php' ?>"><i
                        class="uil uil-user-circle font-size-18 align-middle text-muted me-1"></i> <span
                        class="align-middle">Profilim</span></a>
                <a class="dropdown-item" href="<?= $userType==='Bayi' ? 'dealer_orders.php' : 'teklifsiparisler.php' ?>"><i
                        class="uil uil-wallet font-size-18 align-middle me-1 text-muted"></i> <span
                        class="align-middle">Tekliflerim</span> </a>
                <a class="dropdown-item" href="include/cikisyap.php"><i
                        class="uil uil-sign-out-alt font-size-18 align-middle me-1 text-muted"></i> <span
                        class="align-middle">Oturumu Kapat</span></a>
            </div>
        </div>

    </div>
    <?php include "menuler/solmenu.php"; ?>
</header>