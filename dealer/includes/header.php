<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- Logo -->
            <div class="navbar-brand-box">
                <a href="dashboard.php" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="../assets/images/logo-sm.png" alt="" height="22" style="display:none;">
                        <span style="font-size: 20px; font-weight: 700; color: #495057;">GEMAS</span>
                    </span>
                    <span class="logo-lg">
                        <img src="../assets/images/logo-dark.png" alt="" height="20" style="display:none;">
                        <span style="font-size: 24px; font-weight: 700; color: #495057;">GEMAS B2B</span>
                    </span>
                </a>

                <a href="dashboard.php" class="logo logo-light">
                    <span class="logo-sm">
                        <span style="font-size: 20px; font-weight: 700; color: white;">GEMAS</span>
                    </span>
                    <span class="logo-lg">
                        <span style="font-size: 24px; font-weight: 700; color: white;">GEMAS B2B</span>
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <!-- Bildirimler -->
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
            </div>

            <!-- Şirket Bilgisi -->
            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" style="pointer-events: none;">
                    <i class="mdi mdi-office-building"></i>
                    <span class="ms-2"><?= htmlspecialchars($_SESSION['dealer_company_name'] ?? 'Şirket') ?></span>
                </button>
            </div>

            <!-- Profil Dropdown -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="../assets/images/users/avatar-default.png"
                        alt="Header Avatar" style="display:none;">
                    <span class="d-none d-xl-inline-block ms-1 fw-medium font-size-15">
                        <?= htmlspecialchars($_SESSION['dealer_username'] ?? 'Kullanıcı') ?>
                    </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <a class="dropdown-item" href="account.php">
                        <i class="mdi mdi-account-circle font-size-17 align-middle me-1"></i> Hesabım
                    </a>
                    <a class="dropdown-item" href="profile.php">
                        <i class="mdi mdi-cog font-size-17 align-middle me-1"></i> Ayarlar
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="mdi mdi-logout font-size-17 align-middle me-1 text-danger"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

