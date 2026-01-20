<?php
$userType = $_SESSION['user_type'] ?? '';
if ($userType === 'Bayi') {
    ?>
    <div class="container-fluid">
        <div class="topnav">
            <nav class="navbar navbar-light navbar-expand-lg topnav-menu">
                <div class="collapse navbar-collapse" id="topnav-menu-content">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="anasayfa.php">Anasayfa</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $userType==='Bayi' ? 'siparis-olustur.php' : 'teklif-olustur.php' ?>">Yeni Sipariş</a></li>
                        <li class="nav-item"><a class="nav-link" href="dealer_orders.php">Siparişlerim</a></li>
                        <li class="nav-item"><a class="nav-link" href="include/cikisyap.php">Çıkış</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
    <?php
    return;
}

$departmanKodu = $yoneticisorgula["bolum"] ?? '';
if ($departmanKodu) {
    $departmansor = mysqli_query($db, "SELECT * FROM departmanlar WHERE departman='$departmanKodu'");
    $departmanim = mysqli_fetch_array($departmansor);
    $departmanid = $departmanim["id"] ?? 0;
    $yetkisor = mysqli_query($db, "SELECT * FROM yetkiler WHERE departmanid='$departmanid'");
    $yetkim = mysqli_fetch_array($yetkisor);
} else {
    $yetkim = [];
}
$urunler = $yetkim['urunler'] ?? '';
$urunekle = $yetkim['urunekle'] ?? '';
$urunduzenle = $yetkim['urunduzenle'] ?? '';
$urunsil = $yetkim['urunsil'] ?? '';
$tanimlar = $yetkim['tanimlar'] ?? '';
$tanimekle = $yetkim['tanimekle'] ?? '';
$tanimduzenle = $yetkim['tanimduzenle'] ?? '';
$tanimsil = $yetkim['tanimsil'] ?? '';
$degiskenler = $yetkim['degiskenler'] ?? '';
$degiskenekle = $yetkim['degiskenekle'] ?? '';
$degiskenduzenle = $yetkim['degiskenduzenle'] ?? '';
$degiskensil = $yetkim['degiskensil'] ?? '';
$topluislemler = $yetkim['topluislemler'] ?? '';
$siparisler = $yetkim['siparisler'] ?? '';
$siparisekle = $yetkim['siparisekle'] ?? '';
$siparisduzenle = $yetkim['siparisduzenle'] ?? '';
$siparissil = $yetkim['siparissil'] ?? '';
$kargoyonetimi = $yetkim['kargoyonetimi'] ?? '';
$kargoyonetimiekle = $yetkim['kargoyonetimiekle'] ?? '';
$kargoyonetimiduzenle = $yetkim['kargoyonetimiduzenle'] ?? '';
$kargoyonetimisil = $yetkim['kargoyonetimisil'] ?? '';
$yapilandirma = $yetkim['yapilandirma'] ?? '';
$yapilandirmaekle = $yetkim['yapilandirmaekle'] ?? '';
$yapilandirmaduzenle = $yetkim['yapilandirmaduzenle'] ?? '';
$yapilandirmasil = $yetkim['yapilandirmasil'] ?? '';
$kategoriler = $yetkim['kategoriler'] ?? '';
$kategorilerekle = $yetkim['kategorilerekle'] ?? '';
$kategorilerduzenle = $yetkim['kategorilerduzenle'] ?? '';
$kategorilersil = $yetkim['kategorilersil'] ?? '';
$sirketler = $yetkim['sirketler'] ?? '';
$sirketlerekle = $yetkim['sirketlerekle'] ?? '';
$sirketlerduzenle = $yetkim['sirketlerduzenle'] ?? '';
$sirketlersil = $yetkim['sirketlersil'] ?? '';
$uyeler = $yetkim['uyeler'] ?? '';
$uyelerekle = $yetkim['uyelerekle'] ?? '';
$uyelerduzenle = $yetkim['uyelerduzenle'] ?? '';
$uyelersil = $yetkim['uyelersil'] ?? '';
$entegrasyonlar = $yetkim['entegrasyonlar'] ?? '';
$entegrasyonlarekle = $yetkim['entegrasyonlarekle'] ?? '';
$entegrasyonlarduzenle = $yetkim['entegrasyonlarduzenle'] ?? '';
$entegrasyonlarsil = $yetkim['entegrasyonlarsil'] ?? '';
$departmanlar = $yetkim['departmanlar'] ?? '';
$departmanlarekle = $yetkim['departmanlarekle'] ?? '';
$departmanlarduzenle = $yetkim['departmanlarduzenle'] ?? '';
$departmanlarsil = $yetkim['departmanlarsil'] ?? '';
$log = $yetkim['log'] ?? '';
$raporlar = $yetkim['raporlar'] ?? '';
$ayarlar = $yetkim['ayarlar'] ?? '';
?>
<div class="container-fluid">
    <div class="topnav">
        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">
            <div class="collapse navbar-collapse" id="topnav-menu-content">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="anasayfa.php">
                            Anasayfa
                        </a>
                    </li>
                    <?php if ($urunler == 'Evet' or $tanimlar == 'Evet' or $degiskenler == 'Evet' or $topluislemler == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-uielement" role="button">
                                <i class="uil-flask me-2"></i>Ürünler <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-xl"
                                aria-labelledby="topnav-uielement">
                                <div class="row">
                                    <?php if ($urunler == 'Evet') { ?>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">ÜRÜNLER</b>
                                                <a href="<?= $userType === 'Yönetici' ? 'urunlerlogo.php' : 'urun_fiyat_onerisi.php' ?>" class="dropdown-item">Ürünler Logo</a>
                                                <a href="urun_fiyat_log.php" class="dropdown-item">Ürün Fiyat Logu</a>
                                            </div>
                                        </div>
                                    <?php  }
                                    if ($tanimlar == 'Evet') { ?>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">TANIMLAR</b>
                                                <a href="markalar.php" class="dropdown-item">Markalar</a>
                                                <a href="sozlesmeler.php" class="dropdown-item">Sözleşmeler</a>
                                                <a href="stokbirimi.php" class="dropdown-item">Stok Birimleri</a>
                                                <a href="stokalarmlari.php" class="dropdown-item">Stok Alarmları</a>
                                            </div>
                                        </div>
                                    <?php  }
                                    if ($degiskenler == 'Evet') { ?>
                                    <?php  }
                                    if ($topluislemler == 'Evet') { ?>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">TOPLU İŞLEMLER</b>

                                                <a href="iskontoiceaktar.php" class="dropdown-item">Toplu Ürün Güncelleme</a>
                                                <a href="genelexcelyazdir.php" class="dropdown-item">Toplu Excele Gönder</a>

                                            </div>
                                        </div>
                                    <?php  }  ?>
                                </div>
                            </div>
                        </li>
                    <?php  } ?>
                    <?php if ($siparisler == 'Evet' or $kargoyonetimi == 'Evet' or $yapilandirma == 'Evet' or $topluislemler == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-uielement" role="button">
                                <i class="uil-flask me-2"></i>Siparişler <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-xl"
                                aria-labelledby="topnav-uielement">
                                <div class="row">
                                    <?php if ($siparisler == 'Evet') { ?>
                                        <div class="col-lg-4">
                                            <div>
                                                <a href="<?= $userType==='Bayi' ? 'siparis-olustur.php' : 'teklif-olustur.php' ?>" class="dropdown-item">Yeni Teklif/Sipariş Oluştur</a>
                                                <a href="teklifsiparisler.php" class="dropdown-item">Teklif / Siparişler</a>
                                                <a href="admin_logo_transfer.php" class="dropdown-item text-primary fw-bold">Logo Aktarım Yönetimi</a>
                                            </div>
                                        </div>
                                    <?php  }
                                    if ($kargoyonetimi == 'Evet') { ?>

                                    <?php  }
                                    if ($yapilandirma == 'Evet') { ?>
                                        <div class="col-lg-4">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">YAPILANDIRMA</b>
                                                <a href="siparissurecleri.php" class="dropdown-item">Sipariş Süreçleri</a>
                                            </div>
                                        </div>
                                    <?php  } ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                    <?php if ($sirketler == 'Evet' or $uyeler == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-uielement" role="button">
                                <i class="uil-flask me-2"></i>Üye/Şirket <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-xl"
                                aria-labelledby="topnav-uielement">
                                <div class="row">
                                    <?php if ($sirketler == 'Evet') { ?>
                                        <div class="col-lg-6">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">ŞİRKETLER</b>
                                                <a href="tumsirketler.php" class="dropdown-item">Tüm Şirketler</a>
                                                <a href="sirket_kategori.php" class="dropdown-item">Şirket Kategorileri</a>
                                               <a href="sirket_personel_sorgulama.php" class="dropdown-item">Şirket Personel Sorgulama</a>
                                               <a href="yenisirketkaydi.php" class="dropdown-item">Yeni Şirket Kaydı</a>
                                               <a href="sirket_cek.php" class="dropdown-item">Logo Şirket Senkronizasyonu</a>
                                           </div>
                                        </div>
                                    <?php  }
                                    if ($uyeler == 'Evet') { ?>
                                        <div class="col-lg-6">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">ÜYELER</b>
                                                <a href="beklemedekiuyeler.php" class="dropdown-item">Beklemedeki Üyeler</a>
                                                <a href="reddedilenuyeler.php" class="dropdown-item">Reddedilen Üyeler</a>
                                                <a href="uyeyifarklisirketeatama.php" class="dropdown-item">Üyeyi Farklı Şirkete Atama</a>
                                                <a href="dealer_register.php" class="dropdown-item">Bayi Kayıt Formu</a>
                                                <a href="pending_dealers.php" class="dropdown-item">Bekleyen Bayi Hesapları</a>
                                                <a href="dealer_bulk_upload.php" class="dropdown-item">Toplu Bayi Yükle</a>
                                                <a href="dealer_list.php" class="dropdown-item">Bayi Listesi</a>
                                            </div>
                                        </div>
                                    <?php  } ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                    <?php
                    $kargosor = mysqli_query($db, "SELECT * FROM  kargoyonetimi");
                    $kargom = mysqli_fetch_array($kargosor);
                    $kendisor = $kargom["kendiniz"];
                    if ($kendisor == 'Aktif') { ?>
                    <?php  } ?>
                    <?php if ($entegrasyonlar == 'Evet' or $departmanlar == 'Evet' or $log == 'Evet' or $topluislemler == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-uielement" role="button">
                                <i class="uil-flask me-2"></i> Bölümler <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-xl"
                                aria-labelledby="topnav-uielement">
                                <div class="row">
                                    <?php if ($entegrasyonlar == 'Evet') { ?>

                                    <?php }
                                    if ($departmanlar == 'Evet') { ?>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">SMS/E-Posta</b>

                                                <a href="mailgonderin.php" class="dropdown-item"> E-Posta Gönder </a>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">DEPARTMANLAR</b>
                                                <a href="personeller.php" class="dropdown-item">Personel Kayıtları</a>
                                                <a href="departmanlar.php" class="dropdown-item">İletişim Departmanları</a>
                                            </div>
                                        </div>
                                    <?php }
                                    if ($log == 'Evet') { ?>
                                        <div class="col-lg-3">
                                            <div>
                                                <b class="dropdown-item" style="font-weight: 700; color:#448CCD">LOG</b>
                                                <a href="log.php" class="dropdown-item">Yönetim Log</a>

                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                    <?php if ($raporlar == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-reports" role="button">
                                <i class="bx bx-bar-chart-alt-2 me-2"></i>Raporlar <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-reports">
                                <a href="cari-durum-analiz.php" class="dropdown-item">Cari Durum Analiz</a>
                                <a href="yaslandirma_raporu.php" class="dropdown-item">Borç Yaşlandırma Raporu</a>
                                <a href="odeme_gecikmeleri_raporu.php" class="dropdown-item">Ödeme Gecikmeleri Raporu (320)</a>
                                <a href="odeme_gecikmeleri_raporu.php?type=120" class="dropdown-item">Tahsilat Gecikmeleri Raporu (120)</a>
                            </div>
                        </li>
                    <?php } ?>
                    <?php if ($raporlar == 'Evet') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-pages" role="button">
                                <i class="uil-apps me-2"></i>Ayarlar <div class="arrow-down"></div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-pages">
                                <a href="genelayarlar.php" class="dropdown-item">Genel Ayarlar</a>
                                <a href="gelistirmekaydi.php" class="dropdown-item">Duyuru ve Geliştirme</a>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </nav>
    </div>
</div>