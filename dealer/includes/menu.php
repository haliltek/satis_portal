<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>

<div class="topnav">
    <div class="container-fluid">
        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">
            <div class="collapse navbar-collapse" id="topnav-menu-content">
                <ul class="navbar-nav">
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('dashboard.php') ?>" href="dashboard.php">
                            <i class="mdi mdi-view-dashboard me-2"></i>Dashboard
                        </a>
                    </li>

                    <!-- Ürünler ve Sipariş -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-products" role="button">
                            <i class="mdi mdi-package-variant me-2"></i>Ürünler & Sipariş <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-products">
                            <a href="products.php" class="dropdown-item <?= isActive('products.php') ?>">
                                <i class="mdi mdi-package-variant-closed me-2"></i>Ürün Kataloğu
                            </a>
                            <a href="create_order.php" class="dropdown-item <?= isActive('create_order.php') ?>">
                                <i class="mdi mdi-cart-plus me-2"></i>Sipariş Oluştur
                            </a>
                            <a href="cart.php" class="dropdown-item <?= isActive('cart.php') ?>">
                                <i class="mdi mdi-cart me-2"></i>Sepetim
                            </a>
                        </div>
                    </li>

                    <!-- Siparişlerim -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('orders.php') ?>" href="orders.php">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Siparişlerim
                        </a>
                    </li>

                    <!-- Finans -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-finance" role="button">
                            <i class="mdi mdi-cash-multiple me-2"></i>Finans <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-finance">
                            <a href="invoices.php" class="dropdown-item <?= isActive('invoices.php') ?>">
                                <i class="mdi mdi-file-document me-2"></i>Faturalarım
                            </a>
                            <a href="payments.php" class="dropdown-item <?= isActive('payments.php') ?>">
                                <i class="mdi mdi-cash-check me-2"></i>Ödemelerim
                            </a>
                            <a href="open_account.php" class="dropdown-item <?= isActive('open_account.php') ?>">
                                <i class="mdi mdi-bank me-2"></i>Açık Hesap
                            </a>
                        </div>
                    </li>

                    <!-- Cari Bilgilerim -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-account" role="button">
                            <i class="mdi mdi-account-box me-2"></i>Hesabım <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-account">
                            <a href="account.php" class="dropdown-item <?= isActive('account.php') ?>">
                                <i class="mdi mdi-account-details me-2"></i>Cari Bilgilerim
                            </a>
                            <a href="discounts.php" class="dropdown-item <?= isActive('discounts.php') ?>">
                                <i class="mdi mdi-percent me-2"></i>İskontolarım
                            </a>
                            <a href="profile.php" class="dropdown-item <?= isActive('profile.php') ?>">
                                <i class="mdi mdi-account-cog me-2"></i>Profil Ayarları
                            </a>
                        </div>
                    </li>

                    <!-- Destek -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('support.php') ?>" href="support.php">
                            <i class="mdi mdi-help-circle me-2"></i>Destek
                        </a>
                    </li>

                </ul>
            </div>
        </nav>
    </div>
</div>


