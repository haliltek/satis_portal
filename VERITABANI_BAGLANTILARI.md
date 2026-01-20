# Veritabanı Bağlantıları ve Veri Akışı Dokümantasyonu

## 📊 VERİTABANI BAĞLANTILARI

### 1. **Local MySQL Veritabanı** (Ana Uygulama)
- **Host:** `localhost:3306`
- **Veritabanı:** `b2bgemascom_teklif`
- **Kullanıcı:** `root`
- **Şifre:** `` (boş)
- **Bağlantı Fonksiyonu:** `local_database()` (`include/fonksiyon.php`)
- **Global Değişken:** `$db` (mysqli)

**Kullanım Alanları:**
- Ürün listesi (`urunler` tablosu)
- Siparişler ve teklifler
- Kullanıcı yönetimi (`yonetici`, `b2b_users`)
- Şirket bilgileri (`sirket`)
- Sistem ayarları (`genelayarlar`, `ayarlar`)

---

### 2. **Uzak MySQL Veritabanı** (Portal)
- **Host:** `89.43.31.214:3306`
- **Veritabanı:** `gemas_pool_technology`
- **Kullanıcı:** `gemas_mehmet`
- **Şifre:** `2261686Me!`
- **Bağlantı Fonksiyonu:** `gemas_web_database()` (`include/fonksiyon.php`)
- **Global Değişken:** `$gemas_web_db` (mysqli)

**Kullanım Alanları:**
- Portal ürün senkronizasyonu (`portal_urunler` tablosu)
- Malzeme çevirileri (`malzeme_translations` tablosu)
- Ürün çevirileri (`urun_translations` tablosu)
- Malzeme bilgileri (`malzeme` tablosu)

**ÖNEMLİ:** Bu bağlantı başarısız olsa bile sistem çalışmaya devam eder (hata loglanır, uygulama durmaz).

---

### 3. **Logo MSSQL Veritabanları** (ERP Sistemleri)

#### 3.1. GEMPA Logo Veritabanı
- **Host:** `192.168.5.253:1433`
- **Veritabanı:** `GEMPA2025`
- **Kullanıcı:** `halil`
- **Şifre:** `12621262`
- **Bağlantı Fonksiyonu:** `gempa_logo_veritabani()` (`include/fonksiyon.php`)
- **Global Değişken:** `$gempa_logo_db` (PDO)
- **Tablo:** `LG_565_ITEMS` (Firma No: 565)

#### 3.2. GEMAS Logo Veritabanı
- **Host:** `192.168.5.253:1433`
- **Veritabanı:** `GEMAS2025`
- **Kullanıcı:** `halil`
- **Şifre:** `12621262`
- **Bağlantı Fonksiyonu:** `gemas_logo_veritabani()` (`include/fonksiyon.php`)
- **Global Değişken:** `$gemas_logo_db` (PDO)
- **Tablo:** `LG_525_ITEMS` (Firma No: 525)

**Kullanım Alanları:**
- Ürün bilgileri çekme (stok kodu, ad, fiyat, miktar)
- Ürün senkronizasyonu (`scripts/sync_products.php`)
- **SADECE OKUMA İŞLEMLERİ** - Logo veritabanına yazma yapılmaz!

---

## 🔄 VERİ ÇEKME İŞLEMLERİ

### 1. **Ürün Listesi Çekme**
**Dosya:** `uruncekdatatable.php`
- **Kaynak:** Local MySQL (`urunler` tablosu)
- **Çekilen Veriler:**
  - `stokkodu` (Stok Kodu)
  - `stokadi` (Türkçe Ürün Adı)
  - `stokadi_en` (İngilizce Ürün Adı) - **YENİ EKLENEN**
  - `olcubirimi` (Ölçü Birimi)
  - `fiyat` (Yurtiçi Fiyat)
  - `export_fiyat` (Yurtdışı Fiyat)
  - `doviz` (Döviz)
  - `miktar` (Stok Miktarı)
  - `marka` (Marka)
  - `aciklama` (Açıklama)

**Pazar Tipine Göre Filtreleme:**
- **Yurtiçi:** `stokadi` (Türkçe) ve `fiyat` gösterilir
- **Yurtdışı:** `stokadi_en` (İngilizce) ve `export_fiyat` gösterilir

---

### 2. **Ürün Senkronizasyonu** (Logo'dan Local'e)
**Dosya:** `scripts/sync_products.php`

**Akış:**
1. **Logo MSSQL'den Veri Çekme:**
   ```sql
   SELECT DISTINCT
       I.LOGICALREF, I.CODE, I.NAME, I.NAME3,
       USL.CODE AS ANA_BIRIM_KODU,
       MIKTAR, FIYAT, EXPORT_FIYAT, DOVIZ,
       ...
   FROM LG_565_ITEMS I
   ```

2. **Local MySQL'e Kaydetme:**
   - `urunler` tablosuna INSERT/UPDATE
   - `stokadi` ← `NAME` (Türkçe)
   - `stokadi_en` ← `NAME3` (İngilizce) - **Logo'dan çekiliyor**

3. **Portal Veritabanına Senkronizasyon:**
   - `portal_urunler` tablosuna INSERT/UPDATE (uzak MySQL)

**ÖNEMLİ:** Bu script Logo veritabanından **SADECE OKUMA** yapar, Logo'ya yazma yapmaz!

---

### 3. **Çeviri Verileri Çekme**
**Dosya:** `services/ProductTranslationService.php`

**Kaynak Tablolar:**
- `malzeme_translations` (Uzak MySQL - `gemas_pool_technology`)
- `urun_translations` (Uzak MySQL - `gemas_pool_technology`)

**Kullanım:**
- Stok koduna göre malzeme çevirileri
- Ürün ID'sine göre ürün çevirileri
- Logo veritabanından NAME, NAME3, NAME4 alanları

---

## 📝 VERİ GÜNCELLEME İŞLEMLERİ

### 1. **Local MySQL Güncellemeleri**
- Ürün ekleme/güncelleme (`urunler` tablosu)
- Sipariş/teklif kaydetme
- Kullanıcı işlemleri

### 2. **Uzak MySQL Güncellemeleri**
**Fonksiyon:** `syncPortalProductImmediate()` (`include/fonksiyon.php`)
- Local'deki ürün değişikliklerini portal veritabanına yansıtma
- `portal_urunler` tablosuna INSERT/UPDATE

**ÖNEMLİ:** Uzak veritabanı bağlantısı yoksa işlem sessizce atlanır.

### 3. **Logo Veritabanı Güncellemeleri**
**YOK!** Logo veritabanına yazma işlemi yapılmaz. Sadece okuma yapılır.

---

## 🔍 İNGİLİZCE ÜRÜN ADLARI NEREDEN GELİYOR?

### Mevcut Durum:
1. **Logo'dan:** `scripts/sync_products.php` → `NAME3` → `stokadi_en` (Local MySQL)
2. **Çeviri Tablolarından:** `malzeme_translations` ve `urun_translations` (Uzak MySQL)

### Kullanım:
- **`uruncekdatatable.php`:** Yurtdışı seçildiğinde `stokadi_en` gösterilir
- **`urunler` tablosu:** `stokadi_en` alanı Logo'dan senkronize edilir

---

## 📋 ÖNEMLİ NOTLAR

1. **Logo Veritabanı Bağlantısı:**
   - PDO SQLSRV extension gereklidir
   - Bağlantı başarısız olsa bile sistem çalışır (hata loglanır)

2. **Uzak Veritabanı Bağlantısı:**
   - Bağlantı başarısız olsa bile sistem çalışır
   - Portal senkronizasyonu atlanır

3. **Veri Akışı:**
   ```
   Logo MSSQL → Local MySQL → Portal MySQL
   (Okuma)     (Okuma/Yazma)  (Yazma)
   ```

4. **Çeviri Verileri:**
   - `malzeme_translations` ve `urun_translations` tabloları uzak MySQL'de
   - Bu tablolar Logo'dan bağımsız çalışır
   - İngilizce ürün adları bu tablolardan da çekilebilir

---

## 🎯 ÖNERİLER

1. **İngilizce Ürün Adları İçin:**
   - Logo'dan çekmek yerine `malzeme_translations` veya `urun_translations` tablolarını kullanabilirsiniz
   - Bu tablolar daha esnek ve Logo'dan bağımsız çalışır

2. **Veritabanı Bağlantı Kontrolü:**
   - Tüm bağlantılar try-catch ile korunmalı
   - Bağlantı başarısız olsa bile uygulama çalışmaya devam etmeli

3. **Senkronizasyon:**
   - Logo'dan senkronizasyon periyodik olarak çalıştırılmalı
   - Portal senkronizasyonu ürün değişikliklerinde otomatik tetiklenir

---

## 📁 İLGİLİ DOSYALAR

- `include/fonksiyon.php` - Veritabanı bağlantı fonksiyonları
- `include/vt.php` - Veritabanı bağlantı ayarları
- `config/config.php` - Yapılandırma dosyası
- `scripts/sync_products.php` - Ürün senkronizasyon scripti
- `services/ProductTranslationService.php` - Çeviri servisi
- `uruncekdatatable.php` - Ürün listesi DataTable

---

**Son Güncelleme:** 2025-01-17

