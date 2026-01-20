# GEMAS B2B Bayi Paneli - Kurulum ve Kullanım

## ✅ Yapılan Güncellemeler

### 🎨 Yeni Kurumsal Tasarım
- Mor-mavi gradient → Koyu lacivert profesyonel tasarım
- İkonlar CDN üzerinden yükleniyor (Material Design Icons)
- Daha minimal ve kurumsal görünüm

### 📋 Test Etme Adımları

1. **Test Kullanıcısı Oluştur:**
   ```
   http://localhost/b2b-gemas-project-main/dealer/test_login.php
   ```
   - Bu sayfa otomatik olarak test kullanıcısı oluşturacak
   - Giriş bilgilerini gösterecek

2. **Giriş Yap:**
   ```
   http://localhost/b2b-gemas-project-main/dealer/
   ```
   - Kullanıcı Adı: `test_bayi`
   - Şifre: `test123`

3. **Test Et:**
   - ✅ Dashboard açılıyor mu?
   - ✅ İkonlar görünüyor mu?
   - ✅ Menü çalışıyor mu?
   - ✅ Renkler kurumsal mı?

## 🔧 Sorun Giderme

### İkonlar Görünmüyor

**Sebep:** CDN yüklenemiyor veya internet bağlantısı yok

**Çözüm 1:** İnternet bağlantınızı kontrol edin

**Çözüm 2:** Local olarak icon font dosyalarını indirin:
```bash
# İndirme linki
https://github.com/Templarian/MaterialDesign-Webfont/releases
```

Ardından `dealer/includes/styles.php` dosyasında CDN linkini değiştirin:
```php
<!-- CDN yerine -->
<link href="assets/fonts/materialdesignicons.min.css" rel="stylesheet">
```

### Veritabanı Bağlantı Hatası

**Çözüm:**
1. XAMPP'in MySQL servisinin çalıştığından emin olun
2. `include/vt.php` dosyasındaki veritabanı bilgilerini kontrol edin

### Sayfa Bulunamadı (404)

**Çözüm:**
Doğru URL'yi kullandığınızdan emin olun:
```
http://localhost/b2b-gemas-project-main/dealer/
```

## 📱 Özellikler

### ✅ Tamamlanan
- Giriş sistemi
- Dashboard (istatistikler)
- Ürün kataloğu (yurtiçi)
- Sepet sistemi
- Sipariş oluşturma
- Sipariş takibi
- Cari bilgileri
- Faturalar
- Ödemeler
- İskontolar
- Profil ayarları
- Destek sayfası

### 🎨 Tasarım Özellikleri
- Kurumsal renk paleti
- Responsive tasarım
- Modern UI/UX
- Material Design iconları
- Profesyonel görünüm

## 🚀 Kullanım

### Yeni Sipariş Verme
1. **Ürünler** → Ürünleri listele
2. **Sepete Ekle** → İstediğiniz ürünleri ekleyin
3. **Sepetim** → Sepeti kontrol edin
4. **Sipariş Oluştur** → Siparişi tamamlayın

### Sipariş Takibi
1. **Siparişlerim** → Tüm siparişleri görüntüle
2. **Detay** → Sipariş detaylarını incele

### Cari Bilgileri
1. **Hesabım** → **Cari Bilgilerim**
2. Şirket bilgilerini, açık hesabı görüntüle

## 🔐 Güvenlik

- Bcrypt şifre şifreleme
- SQL injection koruması
- XSS koruması
- Oturum yönetimi
- Yetki kontrolü

## 📞 Destek

Sorun yaşıyorsanız:
1. Browser console'u kontrol edin (F12)
2. `dealer/GUNCEL_NOTLAR.md` dosyasına bakın
3. Log dosyalarını kontrol edin

---

© 2025 GEMAS B2B Portal

