# GEMAS B2B Bayi Paneli

## 📋 Genel Bakış

GEMAS B2B Bayi Paneli, bayilerin kendi cari hesapları üzerinden sipariş verebilecekleri, fatura ve ödeme bilgilerini görüntüleyebilecekleri, profesyonel bir B2B e-ticaret platformudur.

## ✨ Özellikler

### 🔐 Güvenli Giriş Sistemi
- Kullanıcı adı ve şifre ile güvenli giriş
- Oturum yönetimi
- Şifre şifreleme (bcrypt)

### 📊 Dashboard
- Genel istatistikler (toplam sipariş, bekleyen sipariş, son 30 gün, açık hesap)
- Son siparişler özeti
- Hızlı erişim linkleri
- Modern ve kullanıcı dostu arayüz

### 🏢 Cari Bilgileri
- Detaylı şirket bilgileri
- Vergi ve mali bilgiler
- İletişim bilgileri
- Ödeme planı ve ticari grup bilgileri

### 📄 Faturalar
- Tüm fatura ve irsaliyeleri görüntüleme
- Tarih bazlı filtreleme
- Fatura detayları
- DataTables ile gelişmiş arama ve sıralama

### 💰 Ödemeler ve Açık Hesap
- Güncel açık hesap durumu
- Ödeme planı bilgileri
- Ödeme talimatları

### 🏷️ İskontolar
- Marka bazlı iskonto oranları
- Peşin, kredi kartı ve vadeli ödeme iskontoları
- İskonto tablosu

### 📦 Ürün Kataloğu
- Sadece yurtiçi (domestic) ürünler
- Gelişmiş filtreleme (kategori, marka, arama)
- Stok durumu gösterimi
- Sepete ekleme özelliği
- Server-side DataTables entegrasyonu

### 🛒 Sepet Yönetimi
- LocalStorage tabanlı sepet sistemi
- Ürün adedi güncelleme
- Ürün silme
- Otomatik toplam hesaplama (KDV dahil)

### 📋 Sipariş Yönetimi
- Sipariş oluşturma
- Sipariş geçmişi
- Sipariş detayları
- Sipariş durumu takibi (Beklemede, Onaylandı, Tamamlandı, İptal)
- Teslimat adresi ve sipariş notu ekleme

### 👤 Profil Ayarları
- E-posta güncelleme
- Şifre değiştirme
- Kullanıcı bilgileri

### 🆘 Destek
- İletişim bilgileri
- Sık sorulan sorular (SSS)
- Telefon, e-posta ve WhatsApp desteği

## 🗂️ Dosya Yapısı

```
dealer/
├── index.php                 # Giriş sayfası
├── logout.php               # Çıkış işlemi
├── dashboard.php            # Ana sayfa / Dashboard
├── account.php              # Cari bilgileri
├── invoices.php             # Faturalar
├── payments.php             # Ödemeler
├── open_account.php         # Açık hesap
├── discounts.php            # İskontolar
├── orders.php               # Sipariş geçmişi
├── order_detail.php         # Sipariş detayı
├── products.php             # Ürün kataloğu
├── cart.php                 # Sepet
├── create_order.php         # Sipariş oluştur
├── profile.php              # Profil ayarları
├── support.php              # Destek
├── README.md                # Bu dosya
│
├── includes/
│   ├── header.php           # Üst menü
│   ├── menu.php             # Ana menü
│   └── footer.php           # Footer
│
└── api/
    └── get_products.php     # Ürün listesi API (DataTables için)
```

## 🚀 Kurulum

### 1. Gereksinimler
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web server
- XAMPP veya benzeri yerel geliştirme ortamı

### 2. Veritabanı Ayarları

Veritabanı bağlantısı `include/vt.php` dosyasında tanımlıdır:

```php
$sql_details = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db'   => 'b2bgemascom_teklif',
];
```

### 3. İlk Bayi Kullanıcısı Oluşturma

```sql
INSERT INTO `b2b_users` (`id`, `company_id`, `cari_code`, `username`, `email`, `password`, `status`, `role`) 
VALUES (NULL, [SIRKET_ID], '[CARI_KOD]', 'bayikullanici', 'bayi@example.com', '$2y$10$zeWnpQUisvwmZqm9co75MuObTIK53RgMG2rpznNEDk6HcorzjmGye', 1, 'dealer');
```

**Not:** Yukarıdaki şifre hash'i `test123` şifresine karşılık gelir. Gerçek kullanımda güçlü bir şifre kullanın.

Şifre hash'i oluşturmak için:
```php
echo password_hash('yeni_sifre', PASSWORD_BCRYPT);
```

### 4. İlk Giriş

- URL: `http://localhost/b2b-gemas-project-main/dealer/`
- Kullanıcı Adı: `bayikullanici`
- Şifre: `test123` (veya belirlediğiniz şifre)

## 🎨 Tasarım ve UI

- **Modern Gradient Tasarım**: Mor-mavi gradient renk paleti
- **Responsive Tasarım**: Mobil, tablet ve masaüstü uyumlu
- **Bootstrap 5**: Modern bileşenler
- **Material Design Icons**: Zengin ikon seti
- **Smooth Animations**: Hover efektleri ve geçişler
- **Card-based Layout**: Temiz ve düzenli kart tasarımları

## 🔒 Güvenlik Özellikleri

1. **Oturum Kontrolü**: Her sayfada oturum kontrolü yapılır
2. **Prepared Statements**: SQL injection koruması
3. **Password Hashing**: Bcrypt ile şifre şifreleme
4. **XSS Koruması**: htmlspecialchars ile output escape
5. **CSRF Koruması**: POST işlemlerinde token kontrolü (geliştirilecek)
6. **Yetki Kontrolü**: Sadece bayi kullanıcıları erişebilir

## 📊 Veritabanı Tabloları

### Kullanılan Tablolar:
- `b2b_users` - Bayi kullanıcıları
- `sirket` - Şirket/Cari bilgileri
- `urunler` - Ürün bilgileri
- `iskontolar` - İskonto oranları
- `og` - Siparişler
- `ogteklifurun2` - Sipariş ürünleri
- `faturairsaliye` - Faturalar

## 🛠️ Teknik Detaylar

### Frontend
- Bootstrap 5.x
- jQuery 3.x
- DataTables (Ajax server-side processing)
- LocalStorage (Sepet yönetimi)
- Responsive Design
- Modern CSS3 Animations

### Backend
- PHP 8.x (7.4+ uyumlu)
- MySQLi
- Session Management
- RESTful API endpoints

## 📱 Responsive Özellikler

Panel tamamen responsive tasarlanmıştır:
- **Mobil (< 768px)**: Tek sütun layout, hamburger menü
- **Tablet (768px - 1024px)**: İki sütun layout
- **Desktop (> 1024px)**: Tam özellikli layout

## 🎯 Gelecek Geliştirmeler

- [ ] PDF fatura indirme
- [ ] Excel export özellikleri
- [ ] Sipariş takip sistemi (kargo entegrasyonu)
- [ ] E-posta bildirimleri
- [ ] Canlı destek (chat)
- [ ] Mobil uygulama
- [ ] Çoklu dil desteği
- [ ] Favori ürünler
- [ ] Toplu sipariş (Excel upload)

## 📞 Destek

Herhangi bir sorun veya öneriniz için:
- **E-posta**: destek@gemas.com
- **Telefon**: +90 (XXX) XXX XX XX
- **WhatsApp**: +90 (XXX) XXX XX XX

## 📄 Lisans

© 2025 GEMAS - Tüm hakları saklıdır.

## 👨‍💻 Geliştirici Notları

### Önemli Dosyalar:
- `include/vt.php` - Veritabanı bağlantısı
- `include/fonksiyon.php` - Oturum kontrolü ve yardımcı fonksiyonlar
- `dealer/api/get_products.php` - Ürün listesi API endpoint

### Değiştirilmesi Gereken Yerler (Production):
1. Veritabanı şifresi (`include/vt.php`)
2. İletişim bilgileri (telefon, e-posta)
3. Logo ve favicon
4. SSL sertifikası
5. SMTP ayarları (e-posta bildirimleri için)

### Debug Mode:
Hata ayıklama için `php.ini`:
```ini
display_errors = On
error_reporting = E_ALL
```

Production ortamında:
```ini
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

## 🎉 Özellikler Özeti

✅ Kullanıcı dostu modern arayüz  
✅ Tam responsive tasarım  
✅ Güvenli oturum yönetimi  
✅ Detaylı cari bilgileri  
✅ Fatura görüntüleme  
✅ Ödeme takibi  
✅ İskonto yönetimi  
✅ Gelişmiş ürün kataloğu  
✅ Sepet sistemi  
✅ Sipariş oluşturma  
✅ Sipariş takibi  
✅ Profil yönetimi  
✅ Destek sistemi  

---

**GEMAS B2B Bayi Paneli** - Profesyonel B2B E-Ticaret Çözümü

