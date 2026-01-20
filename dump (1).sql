CREATE TABLE `altkategoriler` (
  `altkategori_id` int(11) NOT NULL AUTO_INCREMENT,
  `altkategori_adi` text DEFAULT NULL,
  `ustkategori_id` text DEFAULT NULL,
  `grupid` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  PRIMARY KEY (`altkategori_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `altkategoriler` (`altkategori_id`, `altkategori_adi`, `ustkategori_id`, `grupid`, `sira`, `title`, `url`) VALUES 
	(9, 'Erse Kablo', 9, 2, 1, 'Erse Kablo Ürünleri', 'erse-kablo-urunleri')
	,(10, 'Öznur Kablo', 9, 2, 2, 'Öznur Kablo Ürünleri', 'oznur-kablo-urunleri')
	,(11, 'Ünal Kablo', 9, 2, 3, 'Ünal Kablo Ürünleri', 'unal-kablo-urunleri')
	,(14, 'Altın Kablo', 9, 2, 4, 'Altın Kablo', 'altin-kablo')
	,(16, 'Zayıf Akım Kabloları', 9, 4, 0, 'Zayıf Akım Kabloları', 'zayif-akim-kablolari')
	,(17, 'Telefon ve İnternet Kabloları', 9, 4, 0, 'Telefon ve İnternet Kabloları', 'telefon-ve-internet-kablolari')
	,(18, 'TV Kabloları', 9, 4, 0, 'TV Kabloları', 'tv-kablolari')
	,(19, 'NYAF Kablolar', 9, 4, 0, 'NYAF Kablolar', 'nyaf-kablolar')
	,(20, 'NYA Kablolar', 9, 4, 0, 'NYA Kablolar', 'nya-kablolar')
	,(21, 'N2Xh Kablolar', 9, 3, 0, 'N2Xh Kablolar', 'n2xh-kablolar')
	,(22, 'NHXMH Halogen Free Kablolar', 9, 3, 0, 'NHXMH Halogen Free Kablolar', 'nhxmh-halogen-free-kablolar')
	,(23, 'NYM Antgron Kablolar', 9, 7, 0, 'NYM Antgron Kablolar', 'nym-antgron-kablolar')
	,(24, 'Hortum Ledler', 9, 7, 0, 'Hortum Ledler', 'hortum-ledler')
	,(25, 'NYY Yer Altı Kablolar', 9, 7, 0, 'NYY Yer Altı Kablolar', 'nyy-yer-alti-kablolar')
	,(26, 'NYFGBY Çelik Zırhlı Kablo', 9, 7, 0, 'NYFGBY Çelik Zırhlı Kablo', 'nyfgby-celik-zirhli-kablo')
	,(27, 'Parça Kesit Kablo', 9, 7, 0, 'Parça Kesit Kablo', 'parca-kesit-kablo')
	,(28, 'Alpek Kablo', 9, 7, 0, 'Alpek Kablo', 'alpek-kablo')
	,(29, 'TTR Yumuşak Kablo', 9, 7, 0, 'TTR Yumuşak Kablo', 'ttr-yumusak-kablo');

CREATE TABLE `ayarlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stokalarmlaribaslangic` text DEFAULT NULL,
  `stokalarmlaribitis` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `resim` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `ayarlar` (`id`, `stokalarmlaribaslangic`, `stokalarmlaribitis`, `title`, `description`, `keywords`, `resim`) VALUES 
	(1, 20, 50, 'Arvensan Software Teklif Sistemi v2', 'Arvensan Software tarafından geliştirilen Teklif ve Sipariş Sistemidir.', 'gemasr elektrik, gemasr elektrik as, gemasr toptan, gemasr b2bArvensan Software tarafından geliştirilen Teklif ve Sipariş Sistemidir.', 'test.php');

CREATE TABLE `departmanlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `departman` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `departmanlar` (`id`, `departman`) VALUES 
	(3, 'E-Ticaret Departmanı')
	,(4, 'Lojistik Departmanı')
	,(5, 'Depo Departmanı')
	,(6, 'Satın Alma Departmanı')
	,(7, 'Yazılım Departmanı')
	,(8, 'Satış - Teklif Departmanı')
	,(9, 'Muhasebe Departmanı')
	,(10, 'Sevkiyat Departmanı')
	,(13, 'Yönetici')
	,(20, 'Schneider İç Satış Ekibi ')
	,(21, 'Şöför');

CREATE TABLE `dovizkuru` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dolaralis` text DEFAULT NULL,
  `dolarsatis` text DEFAULT NULL,
  `euroalis` text DEFAULT NULL,
  `eurosatis` text DEFAULT NULL,
  `tarih` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `dovizkuru` (`id`, `dolaralis`, `dolarsatis`, `euroalis`, `eurosatis`, `tarih`) VALUES 
	(1, 29.3169, 29.4344, 32.3959, 32.5257, '28.12.2023 12:15');

CREATE TABLE `faturairsaliye` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sirketid` text DEFAULT NULL,
  `resim` text DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` text DEFAULT NULL,
  `turu` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `genelayarlar` (
  `ayar_id` int(11) NOT NULL AUTO_INCREMENT,
  `unvan` text DEFAULT NULL,
  `telefon` text DEFAULT NULL,
  `eposta` text DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `playstore` text DEFAULT NULL,
  `host` text DEFAULT NULL,
  `smtp` text DEFAULT NULL,
  `gidenmail` text DEFAULT NULL,
  `mailsifre` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `hakkinda` text DEFAULT NULL,
  `bakimmodu` text DEFAULT NULL,
  PRIMARY KEY (`ayar_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `genelayarlar` (`ayar_id`, `unvan`, `telefon`, `eposta`, `adres`, `playstore`, `host`, `smtp`, `gidenmail`, `mailsifre`, `title`, `description`, `hakkinda`, `bakimmodu`) VALUES 
	(1, 'Arvensan Softawre', 024260060646, 'bilgi@gemas.com', 'Antalya Merkez', '#', '#', 587, '#', '#', 'Arvensan Softawre', 'Arvensan Softawre', 'Arvensan Softawre', 0);

CREATE TABLE `il` (
  `il_no` int(11) NOT NULL,
  `isim` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
INSERT INTO `il` (`il_no`, `isim`) VALUES 
	(1, 'Adana')
	,(2, 'Adıyaman')
	,(3, 'Afyonkarahisar')
	,(4, 'Ağrı')
	,(5, 'Amasya')
	,(6, 'Ankara')
	,(7, 'Antalya')
	,(8, 'Artvin')
	,(9, 'Aydın')
	,(10, 'Balıkesir')
	,(11, 'Bilecik')
	,(12, 'Bingöl')
	,(13, 'Bitlis')
	,(14, 'Bolu')
	,(15, 'Burdur')
	,(16, 'Bursa')
	,(17, 'Çanakkale')
	,(18, 'Çankırı')
	,(19, 'Çorum')
	,(20, 'Denizli')
	,(21, 'Diyarbakır')
	,(22, 'Edirne')
	,(23, 'Elâzığ')
	,(24, 'Erzincan')
	,(25, 'Erzurum')
	,(26, 'Eskişehir')
	,(27, 'Gaziantep')
	,(28, 'Giresun')
	,(29, 'Gümüşhane')
	,(30, 'Hakkâri')
	,(31, 'Hatay')
	,(32, 'Isparta')
	,(33, 'Mersin')
	,(34, 'İstanbul')
	,(35, 'İzmir')
	,(36, 'Kars')
	,(37, 'Kastamonu')
	,(38, 'Kayseri')
	,(39, 'Kırklareli')
	,(40, 'Kırşehir')
	,(41, 'Kocaeli')
	,(42, 'Konya')
	,(43, 'Kütahya')
	,(44, 'Malatya')
	,(45, 'Manisa')
	,(46, 'Kahramanmaraş')
	,(47, 'Mardin')
	,(48, 'Muğla')
	,(49, 'Muş')
	,(50, 'Nevşehir')
	,(51, 'Niğde')
	,(52, 'Ordu')
	,(53, 'Rize')
	,(54, 'Sakarya')
	,(55, 'Samsun')
	,(56, 'Siirt')
	,(57, 'Sinop')
	,(58, 'Sivas')
	,(59, 'Tekirdağ')
	,(60, 'Tokat')
	,(61, 'Trabzon')
	,(62, 'Tunceli')
	,(63, 'Şanlıurfa')
	,(64, 'Uşak')
	,(65, 'Van')
	,(66, 'Yozgat')
	,(67, 'Zonguldak')
	,(68, 'Aksaray')
	,(69, 'Bayburt')
	,(70, 'Karaman')
	,(71, 'Kırıkkale')
	,(72, 'Batman')
	,(73, 'Şırnak')
	,(74, 'Bartın')
	,(75, 'Ardahan')
	,(76, 'Iğdır')
	,(77, 'Yalova')
	,(78, 'Karabük')
	,(79, 'Kilis')
	,(80, 'Osmaniye')
	,(81, 'Düzce');

CREATE TABLE `iller` (
  `ilid` int(11) NOT NULL AUTO_INCREMENT,
  `adi` text DEFAULT NULL,
  `kodu` text DEFAULT NULL,
  PRIMARY KEY (`ilid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `iller` (`ilid`, `adi`, `kodu`) VALUES 
	(1, 'Adana', 01)
	,(2, 07, 'Antalya');

CREATE TABLE `iskontolar` (
  `iskonto_id` int(11) NOT NULL AUTO_INCREMENT,
  `marka` text DEFAULT NULL,
  `pesin` text DEFAULT NULL,
  `kredikarti` text DEFAULT NULL,
  `atmisgun` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  PRIMARY KEY (`iskonto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `kargoyonetimi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `araskargo` text DEFAULT NULL,
  `yurticikargo` text DEFAULT NULL,
  `suratkargo` text DEFAULT NULL,
  `upskargo` text DEFAULT NULL,
  `kendiniz` text DEFAULT NULL,
  `subeteslim` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `kargoyonetimi` (`id`, `araskargo`, `yurticikargo`, `suratkargo`, `upskargo`, `kendiniz`, `subeteslim`) VALUES 
	(1, 'Aktif', 'Aktif', 'Aktif', 'Aktif', 'Aktif', 'Aktif');

CREATE TABLE `kategorigrup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adi` text DEFAULT NULL,
  `ustkategori_id` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  `mbottom` text DEFAULT NULL,
  `mtop` text DEFAULT NULL,
  `mleft` text DEFAULT NULL,
  `mright` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `kategorigrup` (`id`, `adi`, `ustkategori_id`, `sira`, `mbottom`, `mtop`, `mleft`, `mright`) VALUES 
	(2, 'Kablo Markalar', 9, 3, -45, '', '', '')
	,(3, 'Yanmaz Kablolar', 9, 6, -35, '', '', '')
	,(4, 'NYA TV / Zayıf Akım Kablolar', 9, 2, 0, '', '', '')
	,(7, 'Enerji Kabloları Marka', 9, 1, -35, '', '', '')
	,(10, 'Boş', 9, 4, -35, '', '', '');

CREATE TABLE `kategoriler` (
  `kategori_id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_adi` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  PRIMARY KEY (`kategori_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `kategoriler` (`kategori_id`, `kategori_adi`, `url`, `title`, `sira`) VALUES 
	(7, 'Aydınlatma', 'aydinlatma-urunleri', 'Aydınlatma Ürünleri', 1)
	,(8, 'Anahtar Piriz', 'anahtar-piriz-urunleri', 'Anahtar Piriz Ürünleri', 2)
	,(9, 'Enerji Kablosu', 'enerji-kablosu-urunleri', 'Enerji Kablosu Ürünleri', 3)
	,(13, 'Zayıf Akım Kablosu', 'zayif-akim-kablosu', 'Zayıf Akım Kablosu', 4)
	,(14, 'Pano Malzemeleri', 'pano-malzemeleri', 'Pano Malzemeleri', 5)
	,(15, 'Tesisat Malzemeleri', 'tesisat-malzemeleri', 'Tesisat Malzemeleri', 6)
	,(16, 'Sarf Malzemeleri', 'sarf-malzemeleri', 'Sarf Malzemeleri', 7)
	,(17, 'Güvenlik Sistemleri', 'guvenlik-sistemleri', 'Güvenlik Sistemleri', 8)
	,(18, 'Kablo Kanalları', 'kablo-kanallari', 'Kablo Kanalları', 9)
	,(19, 'Topraklama Ürünleri', 'topraklama-urunleri', 'Topraklama Ürünleri', 10)
	,(20, 'Orta Gerilim Ürünleri', 'orta-gerilim-urunleri', 'Orta Gerilim Ürünleri', 11);

CREATE TABLE `log_yonetim` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `islem` text DEFAULT NULL,
  `personel` text DEFAULT NULL,
  `tarih` text DEFAULT NULL,
  `durum` text DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `log_yonetim` (`log_id`, `islem`, `personel`, `tarih`, `durum`) VALUES 
	(1, 'Ürün Silme', 77, '25.02.2023 Saat: 12:30', 'Başarılı')
	,(2, 'Ürün Silme', 77, '25.02.2023 Saat: 12:30', 'Başarılı')
	,(3, 'Siparişe Durum - Statü Ataması', 77, '25.02.2023 Saat: 12:33', 'Başarılı')
	,(4, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 12:46', 'Başarılı')
	,(5, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 12:56', 'Başarılı')
	,(6, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 12:57', 'Başarılı')
	,(7, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 12:58', 'Başarılı')
	,(8, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:00', 'Başarılı')
	,(9, 'Dolar Kuru Güncelleme', 77, '25.02.2023 Saat: 01:00', 'Başarılı')
	,(10, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:00', 'Başarılı')
	,(11, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:06', 'Başarılı')
	,(12, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:08', 'Başarılı')
	,(13, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:09', 'Başarılı')
	,(14, 'Dolar Kuru Güncelleme', 77, '25.02.2023 Saat: 01:09', 'Başarılı')
	,(15, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:11', 'Başarılı')
	,(16, 'Stok Birimi Silme', 77, '25.02.2023 Saat: 01:12', 'Başarılı')
	,(17, 'Stok Birimi Silme', 77, '25.02.2023 Saat: 01:12', 'Başarılı')
	,(18, 'Genel Ayarlar Güncelleme', 77, '25.02.2023 Saat: 01:12', 'Başarısız')
	,(19, 'Genel Ayarlar Güncelleme', 77, '25.02.2023 Saat: 01:13', 'Başarısız')
	,(20, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:13', 'Başarılı')
	,(21, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:16', 'Başarılı')
	,(22, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:24', 'Başarılı')
	,(23, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 01:27', 'Başarılı')
	,(24, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 02:13', 'Başarılı')
	,(25, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 02:23', 'Başarılı')
	,(26, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 03:18', 'Başarılı')
	,(27, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 11:21', 'Başarılı')
	,(28, 'Personel Güncelleme', 77, '25.02.2023 Saat: 11:21', 'Başarılı')
	,(29, 'Genel Ayarlar Güncelleme', 77, '25.02.2023 Saat: 11:22', 'Başarılı')
	,(30, 'Oturum Açma İşlemi', 77, '25.02.2023 Saat: 11:59', 'Başarılı')
	,(31, 'Oturum Açma İşlemi', 77, '26.02.2023 Saat: 04:20', 'Başarılı')
	,(32, 'Oturum Açma İşlemi', 77, '26.02.2023 Saat: 04:20', 'Başarılı')
	,(33, 'Oturum Açma İşlemi', 77, '26.02.2023 Saat: 08:24', 'Başarılı')
	,(34, 'Dolar Kuru Güncelleme', 77, '26.02.2023 Saat: 08:30', 'Başarılı')
	,(35, 'Oturum Açma İşlemi', 77, '27.02.2023 Saat: 10:30', 'Başarılı')
	,(36, 'Genel Ayarlar Güncelleme', 77, '27.02.2023 Saat: 10:34', 'Başarısız')
	,(37, 'Genel Ayarlar Güncelleme', 77, '27.02.2023 Saat: 10:34', 'Başarısız')
	,(38, 'Genel Ayarlar Güncelleme', 77, '27.02.2023 Saat: 10:34', 'Başarısız')
	,(39, 'Dolar Kuru Güncelleme', 77, '27.02.2023 Saat: 10:34', 'Başarılı')
	,(40, 'Dolar Kuru Güncelleme', 77, '27.02.2023 Saat: 10:34', 'Başarılı')
	,(41, 'Oturum Açma İşlemi', 77, '28.02.2023 Saat: 10:52', 'Başarılı')
	,(42, 'Oturum Açma İşlemi', 77, '28.02.2023 Saat: 10:58', 'Başarılı')
	,(43, 'Yeni Personel Kayıt', 77, '28.02.2023 Saat: 11:00', 'Başarılı')
	,(44, 'Ürün Silme', 77, '28.02.2023 Saat: 11:00', 'Başarılı')
	,(45, 'Ürün Silme', 77, '28.02.2023 Saat: 11:00', 'Başarılı')
	,(46, 'Ürün Silme', 77, '28.02.2023 Saat: 11:00', 'Başarılı')
	,(47, 'Ürün Silme', 77, '28.02.2023 Saat: 11:00', 'Başarılı')
	,(48, 'Oturum Açma İşlemi', 77, '28.02.2023 Saat: 11:01', 'Başarılı')
	,(49, 'Yeni Personel Kayıt', 77, '28.02.2023 Saat: 11:02', 'Başarılı')
	,(50, 'Oturum Açma İşlemi', 77, '28.02.2023 Saat: 11:06', 'Başarılı')
	,(51, 'Oturum Açma İşlemi', 77, '28.02.2023 Saat: 07:43', 'Başarılı')
	,(52, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:21', 'Başarılı')
	,(53, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:21', 'Başarılı')
	,(54, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:23', 'Başarılı')
	,(55, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:29', 'Başarılı')
	,(56, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:31', 'Başarılı')
	,(57, 'Siparişe Durum - Statü Ataması', 77, '27.12.2023 Saat: 09:33', 'Başarılı')
	,(58, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:37', 'Başarılı')
	,(59, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:37', 'Başarılı')
	,(60, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 09:38', 'Başarılı')
	,(61, 'Ürün Silme', 77, '27.12.2023 Saat: 09:39', 'Başarılı')
	,(62, 'Ürün Silme', 77, '27.12.2023 Saat: 09:39', 'Başarılı')
	,(63, 'Yeni Personel Kayıt', 77, '27.12.2023 Saat: 09:40', 'Başarılı')
	,(64, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:45', 'Başarılı')
	,(65, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:48', 'Başarılı')
	,(66, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:49', 'Başarılı')
	,(67, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:49', 'Başarılı')
	,(68, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 09:51', 'Başarılı')
	,(69, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:53', 'Başarılı')
	,(70, 'Siparişe Durum - Statü Ataması', 77, '27.12.2023 Saat: 09:54', 'Başarılı')
	,(71, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:05', 'Başarılı')
	,(72, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:11', 'Başarılı')
	,(73, 'Personel Güncelleme', 77, '27.12.2023 Saat: 10:12', 'Başarılı')
	,(74, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:12', 'Başarılı')
	,(75, 'Personel Güncelleme', 77, '27.12.2023 Saat: 10:12', 'Başarılı')
	,(76, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:35', 'Başarılı')
	,(77, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:36', 'Başarılı')
	,(78, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 10:44', 'Başarılı')
	,(79, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 11:49', 'Başarılı')
	,(80, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 02:50', 'Başarılı')
	,(81, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:13', 'Başarılı')
	,(82, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:17', 'Başarılı')
	,(83, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:17', 'Başarılı')
	,(84, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:18', 'Başarılı')
	,(85, 'Siparişe Departman Ataması', 77, '27.12.2023 Saat: 08:18', 'Başarılı')
	,(86, 'Siparişe Durum - Statü Ataması', 77, '27.12.2023 Saat: 08:18', 'Başarılı')
	,(87, 'Siparişe Durum - Statü Ataması', 77, '27.12.2023 Saat: 08:19', 'Başarılı')
	,(88, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:20', 'Başarılı')
	,(89, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 08:20', 'Başarılı')
	,(90, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:23', 'Başarılı')
	,(91, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 08:27', 'Başarılı')
	,(92, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:14', 'Başarılı')
	,(93, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 09:47', 'Başarılı')
	,(94, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 09:47', 'Başarılı')
	,(95, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 09:47', 'Başarılı')
	,(96, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:21', 'Başarılı')
	,(97, 'Dolar Kuru Güncelleme', 77, '27.12.2023 Saat: 10:21', 'Başarılı')
	,(98, 'Oturum Açma İşlemi', 77, '27.12.2023 Saat: 10:38', 'Başarılı')
	,(99, 'Siparişe Departman Ataması', 77, '27.12.2023 Saat: 10:40', 'Başarılı')
	,(100, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:23', 'Başarılı')
	,(101, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 02:54', 'Başarılı')
	,(102, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 08:03', 'Başarılı')
	,(103, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 11:53', 'Başarılı')
	,(104, 'Genel Ayarlar Güncelleme', 77, '28.12.2023 Saat: 11:54', 'Başarılı')
	,(105, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 11:55', 'Başarılı')
	,(106, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 11:56', 'Başarılı')
	,(107, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 11:56', 'Başarılı')
	,(108, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:06', 'Başarılı')
	,(109, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:07', 'Başarılı')
	,(110, 'Ürün Silme', 77, '28.12.2023 Saat: 12:07', 'Başarılı')
	,(111, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:10', 'Başarılı')
	,(112, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:11', 'Başarılı')
	,(113, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:11', 'Başarılı')
	,(114, 'Oturum Açma İşlemi', 77, '28.12.2023 Saat: 12:14', 'Başarılı')
	,(115, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(116, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(117, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(118, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(119, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(120, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(121, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı')
	,(122, 'Dolar Kuru Güncelleme', 77, '28.12.2023 Saat: 12:15', 'Başarılı');

CREATE TABLE `markalar` (
  `marka_id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_adi` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  `resim` text DEFAULT NULL,
  `onecikan` text DEFAULT NULL,
  PRIMARY KEY (`marka_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `markalar` (`marka_id`, `kategori_adi`, `url`, `title`, `sira`, `resim`, `onecikan`) VALUES 
	(2, 'Panasonic', 'panasonic-viko', 'Panasonic - Viko', 0, 'panasonic.png', 'Evet')
	,(5, 'Schneider', 'schneider', 'Schneider', 0, 'sch.png', 'Evet')
	,(6, 'Siemens', 'siemens', 'Siemens', 4, 'siemens.png', 'Evet')
	,(8, 'Cata', 'cata', 'Cata', 7, '273521583-cata-marka.png', 'Evet')
	,(12, 'Hes', 'hes-kablo', 'Hes Kablo', 0, '895941283-hes.png', 'Evet')
	,(13, 'Mutlusan', 'mutlusan', 'Mutlusan', 5, '270696755-mutlusan.png', 'Evet')
	,(14, 'Astor', 'astor', 'Astor', 6, '489927430-astor.png', 'Evet')
	,(15, 'Diğer', 'diger-markalar', 'Diğer Markalar', 0, '1257672869-diger.png', 'Evet');

CREATE TABLE `og` (
  `fihrist_id` int(11) NOT NULL AUTO_INCREMENT,
  `kod` text DEFAULT NULL,
  `adi` text DEFAULT NULL,
  `liste` text DEFAULT NULL,
  `birim` text DEFAULT NULL,
  `doviz` text DEFAULT NULL,
  `piskonto` text DEFAULT NULL,
  `ptutar` text DEFAULT NULL,
  `kiskonto` text DEFAULT NULL,
  `ktutar` text DEFAULT NULL,
  `aiskonto` text DEFAULT NULL,
  `atutar` text DEFAULT NULL,
  PRIMARY KEY (`fihrist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `ogteklif2` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `hazirlayanid` text DEFAULT NULL,
  `musteriid` text DEFAULT NULL,
  `musteriadi` text DEFAULT NULL,
  `kime` text DEFAULT NULL,
  `projeadi` text DEFAULT NULL,
  `tekliftarihi` text DEFAULT NULL,
  `teklifkodu` text DEFAULT NULL,
  `teklifsartid` text DEFAULT NULL,
  `odemeturu` text DEFAULT NULL,
  `sirketid` text DEFAULT NULL,
  `tltutar` text DEFAULT NULL,
  `geciciiskonto` text DEFAULT NULL,
  `dolartutar` text DEFAULT NULL,
  `eurotutar` text DEFAULT NULL,
  `toplamtutar` text DEFAULT NULL,
  `kdv` text DEFAULT NULL,
  `geneltoplam` text DEFAULT NULL,
  `eurokur` text DEFAULT NULL,
  `dolarkur` text DEFAULT NULL,
  `kurtarih` text DEFAULT NULL,
  `durum` varchar(255) DEFAULT 'Beklemede',
  `statu` varchar(255) DEFAULT 'Beklemede',
  `siparistarih` text DEFAULT NULL,
  `tur` varchar(255) DEFAULT 'genel',
  `teklifgecerlilik` text DEFAULT NULL,
  `teslimyer` text DEFAULT NULL,
  `dekont` text DEFAULT NULL,
  `odemetarih` text DEFAULT NULL,
  `odemenot` text DEFAULT NULL,
  `teklifsiparis` varchar(255) DEFAULT NULL,
  `atama` text DEFAULT NULL,
  `siparishazir` varchar(20) DEFAULT 'Hayır',
  `faturaolustu` varchar(20) DEFAULT 'Hayır',
  `satinalmayagonder` varchar(20) DEFAULT 'Hayır',
  `satinalmanotu` longtext DEFAULT NULL,
  `eksikmalzeme` varchar(20) DEFAULT 'Hayır',
  `depodabeklemede` varchar(20) DEFAULT 'Hayır',
  `aracayuklendi` varchar(20) DEFAULT 'Hayır',
  `aractasevkiyatta` varchar(20) DEFAULT 'Hayır',
  `islemtamamlandi` varchar(20) DEFAULT 'Hayır',
  `odemetipi` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `ogteklif2` (`id`, `hazirlayanid`, `musteriid`, `musteriadi`, `kime`, `projeadi`, `tekliftarihi`, `teklifkodu`, `teklifsartid`, `odemeturu`, `sirketid`, `tltutar`, `geciciiskonto`, `dolartutar`, `eurotutar`, `toplamtutar`, `kdv`, `geneltoplam`, `eurokur`, `dolarkur`, `kurtarih`, `durum`, `statu`, `siparistarih`, `tur`, `teklifgecerlilik`, `teslimyer`, `dekont`, `odemetarih`, `odemenot`, `teklifsiparis`, `atama`, `siparishazir`, `faturaolustu`, `satinalmayagonder`, `satinalmanotu`, `eksikmalzeme`, `depodabeklemede`, `aracayuklendi`, `aractasevkiyatta`, `islemtamamlandi`, `odemetipi`) VALUES 
	(1, 77, 786, 'Cari Bilgisi', 'Carisiz Müşteriye', '', '2023-02-24 17:21', '131361547B77-2023', '', '', '', '55,00', '', 0, 0, 55, 9.9, 64.9, 18.15, 18.22, '30.08.2022 12:03', 'Tamamlandı', ' Yöneticilerimiz tarafından Teklifiniz inceleniyor ', '', 'urun', '27.02.2023 Saat: 17:00', 'tEslim yeri', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', '')
	,(2, 77, 10, 'Gemaş A.Ş.', 'Müşteriye', '', '2023-02-24 17:22', '480844063B77-2023', '', '', '', '90,00', '', 0, 0, 90, 16.2, 106.2, 18.15, 18.22, '30.08.2022 12:03', 'Sipariş', ' Yöneticilerimiz tarafından Teklifiniz inceleniyor ', '', 'urun', '27.02.2023 Saat: 17:00', '', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', '')
	,(3, 77, 786, 'asdasdasd', 'Carisiz Müşteriye', '', '2023-02-25 12:31', '781230410B77-2023', '', '', '', '55,00', '', 0, 0, 55, 9.9, 64.9, 20.03, 18.92, '24.02.2023 17:50', 'Teklif Onay Bekleniyor', 'Yöneticilerimiz tarafından Teklifiniz inceleniyor', '', 'urun', '28.02.2023 Saat: 17:00', '', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', '')
	,(4, 77, 786, 'Alp', 'Carisiz Müşteriye', 0555555555, '2023-12-27 09:41', '616811837B77-2023', '', '', '', '0,00', '', 0, 0, 0, 0, 0, 32.35, 29.36, '27.12.2023 09:38', 'Teklif Onay Bekleniyor', 'Yöneticilerimiz tarafından Teklifiniz inceleniyor', '', 'urun', '30.12.2023 Saat: 17:00', 'Online', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', '')
	,(5, 77, 786, 'Test', 'Carisiz Müşteriye', 5554443322, '2023-12-27 09:52', '329466073B77-2023', '', '', '', '0,00', '', 0, 0, 0, 0, 0, 32.35, 29.36, '27.12.2023 09:51', 'Teklif Onay Bekleniyor', '   Yöneticilerimiz tarafındansdiyor   ', '', 'urun', '30.12.2023 Saat: 17:00', 'Depo', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Peşin Ödeme')
	,(6, 77, 786, 'Test', 'Carisiz Müşteriye', '', '2023-12-27 22:22', '509754574B77-2023', '', '', '', '0,00', '', 0, 0, 0, 0, 0, 32.53, 29.43, '27.12.2023 22:21', 'Teklif Onay Bekleniyor', 'Yöneticilerimiz tarafından Teklifiniz inceleniyor', '', 'urun', '30.12.2023 Saat: 17:00', '', '', '', '', 'teklif', '', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', '');

CREATE TABLE `ogteklifurun2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklifid` text DEFAULT NULL,
  `kod` text DEFAULT NULL,
  `adi` text DEFAULT NULL,
  `miktar` text DEFAULT NULL,
  `kar` varchar(255) NOT NULL DEFAULT '0',
  `birim` text DEFAULT NULL,
  `liste` text DEFAULT NULL,
  `doviz` text DEFAULT NULL,
  `iskonto` text DEFAULT NULL,
  `nettutar` text DEFAULT NULL,
  `tutar` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `ogteklifurun2` (`id`, `teklifid`, `kod`, `adi`, `miktar`, `kar`, `birim`, `liste`, `doviz`, `iskonto`, `nettutar`, `tutar`) VALUES 
	(1, 1, 'asda', 'asd', 1, 0, 'Kg', 55, 'TL', 0, 55, 55)
	,(2, 2, 'asda', 'asd', 1, 0, 'Kg', 90, 'TL', 0, 90, 90)
	,(3, 3, 'ASD', 'ASDA', 1, 0, '', 55, 'TL', 0, 55, 55)
	,(4, 4, 2323, 'ALP', 1, 0, 'm', 4500, 'USDs', 0, 4500, 4500)
	,(5, 5, 2323, 'ALP', 1, 0, 'm', 333, 'USDs', 20, 266.4, 266.4)
	,(6, 6, 2323, 'ALP', 1, 0, 'm', 333, 'USDs', 0, 333, 333);

CREATE TABLE `personel` (
  `personel_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_adi` text DEFAULT NULL,
  `p_soyadi` text DEFAULT NULL,
  `p_eposta` text DEFAULT NULL,
  `p_cep` text DEFAULT NULL,
  `p_parola` text DEFAULT NULL,
  `p_sozlesme` varchar(20) DEFAULT NULL,
  `p_kayittarihi` text DEFAULT NULL,
  `p_sonoturum` text DEFAULT NULL,
  `p_durum` varchar(50) DEFAULT NULL,
  `p_sirket` text DEFAULT NULL,
  `islem` text DEFAULT NULL,
  PRIMARY KEY (`personel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `short_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `long_url` varchar(255) NOT NULL,
  `short_code` varchar(25) NOT NULL,
  `hits` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
INSERT INTO `short_urls` (`id`, `long_url`, `short_code`, `hits`, `created`) VALUES 
	(1, 'http://192.168.1.45/b2b/musteriyeteklifgonder.php?te=1&q-xt5fxvDfga=as+ggasHsG4h+ahaf', 'Q4hK4xS', 1, '2022-06-13 10:47:58');

CREATE TABLE `siparissureci` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surec` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `siparissureci` (`id`, `surec`) VALUES 
	(2, 'Beklemede')
	,(3, 'Sipariş')
	,(4, 'Sipariş Ödemesi Bekleniyor')
	,(5, 'Tamamlandı')
	,(6, 'İşlemde')
	,(9, 'Diğer');

CREATE TABLE `sirket` (
  `sirket_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_adi` text DEFAULT NULL,
  `s_arp_code` varchar(100) DEFAULT NULL,
  `s_telefonu` text DEFAULT NULL,
  `s_il` varchar(50) DEFAULT NULL,
  `s_ilce` varchar(50) DEFAULT NULL,
  `s_vno` varchar(20) DEFAULT NULL,
  `s_vd` varchar(50) DEFAULT NULL,
  `s_adresi` text DEFAULT NULL,
  `yetkili` text DEFAULT NULL,
  `mail` text DEFAULT NULL,
  `mailsifre` text DEFAULT NULL,
  `smtp` text DEFAULT NULL,
  `port` text DEFAULT NULL,
  `kategori` text DEFAULT NULL,
  `acikhesap` text DEFAULT NULL,
  PRIMARY KEY (`sirket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `sirket_kategori` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `adi` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `sirket_kategori` (`id`, `adi`) VALUES 
	(1, 'Elektrik Firması2');

CREATE TABLE `sirketmusteriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adsoyad` text DEFAULT NULL,
  `telefon` text DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `eposta` text DEFAULT NULL,
  `ililce` text DEFAULT NULL,
  `sirketid` text DEFAULT NULL,
  `ekleyen` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `sozlesmeler` (
  `sozlesme_id` int(11) NOT NULL AUTO_INCREMENT,
  `sozlesme_yeri` text DEFAULT NULL,
  `sozlesme_metin` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `sozlesmeadi` text DEFAULT NULL,
  `footer` text DEFAULT NULL,
  PRIMARY KEY (`sozlesme_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `sozlesmeler` (`sozlesme_id`, `sozlesme_yeri`, `sozlesme_metin`, `title`, `url`, `sozlesmeadi`, `footer`) VALUES 
	(5, 'Teklif / Sipariş', '<ol>\r\n	<li>S&ouml;zleşme Maddesi 1</li>\r\n	<li>S&ouml;zleşme Maddesi 2</li>\r\n	<li>S&ouml;zleşme Maddesi 3</li>\r\n	<li>S&ouml;zleşme Maddesi 4</li>\r\n	<li>S&ouml;zleşme Maddesi 5</li>\r\n	<li>S&ouml;zleşme Maddesi 6</li>\r\n	<li>S&ouml;zleşme Maddesi 7</li>\r\n</ol>\r\n\r\n<p>Bu alanı &Uuml;r&uuml;nler &gt; S&ouml;zleşmeler alanından d&uuml;zenleyebilirsiniz. Bu alandan teklif / siparişle ilgili şartlarınızı belirtebilirsiniz.&nbsp;&nbsp;</p>\r\n', 'Banka Hesap Bilgilerimiz', 'banka-hesap-bilgilerimiz', 'Teklif Şartları', 'Evet');

CREATE TABLE `sozlesmesartlari` (
  `sart_id` int(11) NOT NULL AUTO_INCREMENT,
  `aciklama` text DEFAULT NULL,
  `sirketid` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  PRIMARY KEY (`sart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `stokbirimi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adi` varchar(60) DEFAULT NULL,
  `birim` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `stokbirimi` (`id`, `adi`, `birim`) VALUES 
	(4, 'Metre', 'm')
	,(5, 'Metrekare', 'm2')
	,(6, 'Metre Küp', 'm3')
	,(7, 'Kilometre', 'Km')
	,(8, 'Santimetre', 'Cm');

CREATE TABLE `teklifsartlari` (
  `sart_id` int(11) NOT NULL AUTO_INCREMENT,
  `aciklama` text DEFAULT NULL,
  `sirketid` text DEFAULT NULL,
  `sira` text DEFAULT NULL,
  PRIMARY KEY (`sart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `teklifsartlari` (`sart_id`, `aciklama`, `sirketid`, `sira`) VALUES 
	(16, 'kdv dahil değildir', 17, 1)
	,(17, 'Peşin Ödeme', 17, 2)
	,(19, 'Kredi Kartıyla Ödeme İstiyorum. ', 21, 1)
	,(20, 'Ödemeler 30 gün içerisinde yapılmalıdır. ', 21, 2)
	,(21, 'Örnek Teklif Şartı', 22, 1)
	,(22, 'şart 1', 166, 1)
	,(23, 'Şart 2', 166, 2)
	,(24, 'şart1', 10, 1)
	,(25, 'şart2', 10, 2)
	,(26, 'şart3', 10, 3);

CREATE TABLE `urunler` (
  `urun_id` int(255) NOT NULL AUTO_INCREMENT,
  `stokkodu` varchar(255) DEFAULT NULL,
  `stokadi` text DEFAULT NULL,
  `olcubirimi` text DEFAULT NULL,
  `fiyat` text DEFAULT NULL,
  `doviz` text DEFAULT NULL,
  `guncelleme` varchar(255) DEFAULT '0',
  `zaman` text DEFAULT NULL,
  `miktar` varchar(255) DEFAULT '0',
  `piskonto` varchar(255) DEFAULT '0',
  `ptutar` varchar(255) DEFAULT '0',
  `kiskonto` varchar(255) DEFAULT '0',
  `ktutar` varchar(255) DEFAULT '0',
  `aiskonto` varchar(255) DEFAULT '0',
  `atutar` varchar(255) DEFAULT '0',
  `marka` varchar(200) DEFAULT NULL,
  `kat1` text DEFAULT NULL,
  `kat2` text DEFAULT NULL,
  `kat3` text DEFAULT NULL,
  `kat4` text DEFAULT NULL,
  `kat5` text DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  PRIMARY KEY (`urun_id`),
  KEY `stokkodu` (`stokkodu`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
CREATE TABLE `yetkiler` (
  `yetki_id` int(11) NOT NULL AUTO_INCREMENT,
  `urunler` varchar(255) DEFAULT '0',
  `urunekle` varchar(255) DEFAULT '0',
  `urunduzenle` varchar(255) DEFAULT '0',
  `urunsil` varchar(255) DEFAULT '0',
  `tanimlar` varchar(255) DEFAULT '0',
  `tanimekle` varchar(255) DEFAULT '0',
  `tanimduzenle` varchar(255) DEFAULT '0',
  `tanimsil` varchar(255) DEFAULT '0',
  `degiskenler` varchar(255) DEFAULT '0',
  `degiskenekle` varchar(255) DEFAULT '0',
  `degiskenduzenle` varchar(255) DEFAULT '0',
  `degiskensil` varchar(255) DEFAULT '0',
  `topluislemler` varchar(255) DEFAULT '0',
  `siparisler` varchar(255) DEFAULT '0',
  `siparisekle` varchar(255) DEFAULT '0',
  `siparisduzenle` varchar(255) DEFAULT '0',
  `siparissil` varchar(255) DEFAULT '0',
  `kargoyonetimi` varchar(255) DEFAULT '0',
  `kargoyonetimiekle` varchar(255) DEFAULT '0',
  `kargoyonetimiduzenle` varchar(255) DEFAULT '0',
  `kargoyonetimisil` varchar(255) DEFAULT '0',
  `yapilandirma` varchar(255) DEFAULT '0',
  `yapilandirmaekle` varchar(255) DEFAULT '0',
  `yapilandirmaduzenle` varchar(255) DEFAULT '0',
  `yapilandirmasil` varchar(255) DEFAULT '0',
  `kategoriler` varchar(255) DEFAULT '0',
  `kategorilerekle` varchar(255) DEFAULT '0',
  `kategorilerduzenle` varchar(255) DEFAULT '0',
  `kategorilersil` varchar(255) DEFAULT '0',
  `sirketler` varchar(255) DEFAULT '0',
  `sirketlerekle` varchar(255) DEFAULT '0',
  `sirketlerduzenle` varchar(255) DEFAULT '0',
  `sirketlersil` varchar(255) DEFAULT '0',
  `uyeler` varchar(255) DEFAULT '0',
  `uyelerekle` varchar(255) DEFAULT '0',
  `uyelerduzenle` varchar(255) DEFAULT '0',
  `uyelersil` varchar(255) DEFAULT '0',
  `entegrasyonlar` varchar(255) DEFAULT '0',
  `entegrasyonlarekle` varchar(255) DEFAULT '0',
  `entegrasyonlarduzenle` varchar(255) DEFAULT '0',
  `entegrasyonlarsil` varchar(255) DEFAULT '0',
  `departmanlar` varchar(255) DEFAULT '0',
  `departmanlarekle` varchar(255) DEFAULT '0',
  `departmanlarduzenle` varchar(255) DEFAULT '0',
  `departmanlarsil` varchar(255) DEFAULT '0',
  `log` varchar(255) DEFAULT '0',
  `raporlar` varchar(255) DEFAULT '0',
  `ayarlar` varchar(255) DEFAULT '0',
  `departmanid` varchar(255) DEFAULT '0',
  PRIMARY KEY (`yetki_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;
INSERT INTO `yetkiler` (`yetki_id`, `urunler`, `urunekle`, `urunduzenle`, `urunsil`, `tanimlar`, `tanimekle`, `tanimduzenle`, `tanimsil`, `degiskenler`, `degiskenekle`, `degiskenduzenle`, `degiskensil`, `topluislemler`, `siparisler`, `siparisekle`, `siparisduzenle`, `siparissil`, `kargoyonetimi`, `kargoyonetimiekle`, `kargoyonetimiduzenle`, `kargoyonetimisil`, `yapilandirma`, `yapilandirmaekle`, `yapilandirmaduzenle`, `yapilandirmasil`, `kategoriler`, `kategorilerekle`, `kategorilerduzenle`, `kategorilersil`, `sirketler`, `sirketlerekle`, `sirketlerduzenle`, `sirketlersil`, `uyeler`, `uyelerekle`, `uyelerduzenle`, `uyelersil`, `entegrasyonlar`, `entegrasyonlarekle`, `entegrasyonlarduzenle`, `entegrasyonlarsil`, `departmanlar`, `departmanlarekle`, `departmanlarduzenle`, `departmanlarsil`, `log`, `raporlar`, `ayarlar`, `departmanid`) VALUES 
	(1, 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 0, 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 18)
	,(2, 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 3)
	,(4, 'Evet', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Evet', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 9)
	,(5, 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', '', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 20)
	,(6, 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 4)
	,(7, 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 13)
	,(8, 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 5)
	,(9, 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 21)
	,(10, 'Evet', 'Evet', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Hayır', 'Hayır', 'Hayır', 'Evet', 'Evet', 'Evet', 6);

CREATE TABLE `yonetici` (
  `yonetici_id` int(11) NOT NULL AUTO_INCREMENT,
  `adsoyad` text DEFAULT NULL,
  `eposta` text DEFAULT NULL,
  `parola` text DEFAULT NULL,
  `tur` text DEFAULT NULL,
  `telefon` text DEFAULT NULL,
  `onay` varchar(20) DEFAULT '0',
  `bolum` text DEFAULT NULL,
  `kartno` text DEFAULT NULL,
  `latx` text DEFAULT NULL,
  `longx` text DEFAULT NULL,
  `mailport` text DEFAULT NULL,
  `mailsmtp` text DEFAULT NULL,
  `mailposta` text DEFAULT NULL,
  `mailparola` text DEFAULT NULL,
  `unvan` text DEFAULT NULL,
  PRIMARY KEY (`yonetici_id`)
) ENGINE=MyISAM AUTO_INCREMENT=96 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;
INSERT INTO `yonetici` (`yonetici_id`, `adsoyad`, `eposta`, `parola`, `tur`, `telefon`, `onay`, `bolum`, `kartno`, `latx`, `longx`, `mailport`, `mailsmtp`, `mailposta`, `mailparola`, `unvan`) VALUES 
	(77, 'begome', 'bilgi@gemas.com', 'fe01ce2a7fbac8fafaed7c982a04e229', 'Yönetici', 324234234, 0, 'E-Ticaret Departmanı', 879853, 36.9151828, 30.7924469, 587, 'mail.arvensan.com', 'bilgi@gemas.com', 'Erkan!1947', 'Yazılım Uzmanı');

