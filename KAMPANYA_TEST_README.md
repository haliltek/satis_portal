# Kampanya Test Sistemi

## 📋 Kullanım

### Test Modunu Başlatma:
1. Tarayıcınızda şu adresi açın:
   ```
   http://localhost/b2b-gemas-project-main/kampanya_test.php
   ```

2. Sayfa otomatik olarak:
   - ERTEK (Ana Bayi) müşterisini seçer
   - Tüm kampanyaların ürünlerini çeker
   - Koşulları sağlayacak miktarlarda sepete ekler
   - `teklif-olustur.php?test_mode=1` sayfasına yönlendirir

### Test Modunda Ne Olur?

✅ **Otomatik Yüklenen Veriler:**
- ERTEK müşterisi otomatik seçilir
- Her kampanyadan 5 ürün alınır
- Minimum koşulları sağlayacak miktarlar hesaplanır
- Ürünler otomatik sepete eklenir

✅ **Test Modu Banner:**
- Sayfanın üstünde turuncu bir banner gösterilir
- Hangi kampanyaların test edildiği listelenir
- Toplam kaç ürün yüklendiği gösterilir

✅ **Kampanya Testi:**
- Ürünler eklendikten sonra "Kampanya Uygula" butonunu kontrol edin
- Buton yanıp sönüyorsa kampanya koşulları sağlanmış demektir
- Butona tıklayarak kampanyaları uygulayın
- Özel fiyatların gelip gelmediğini kontrol edin

### Test Modundan Çıkış:
Banner'daki **"Test Modundan Çık"** butonuna tıklayın veya:
```
http://localhost/b2b-gemas-project-main/teklif-olustur.php?new_offer=1
```

## 🎯 Test Edilen Kampanyalar

Sistem şu kampanyaları otomatik test eder:
1. **POMPALAR** - Ana Bayi Özel Fiyat
2. **FİLTRE MEDYA** - Ana Bayi Özel Fiyat (KG birimi)
3. **LEDLER** - Ana Bayi Özel Fiyat
4. **ŞOK YOLLU VANA** - Ana Bayi Özel Fiyat
5. **KENAR EKIPMAN** - IZGARA (Metre birimi)
6. **HAVUZIÇI EKIPMAN** - Ana Bayi Özel Fiyat
7. **TEMİZLİK EKIPMANLARI** - Ana Bayi Özel Fiyat
8. **MERDIVEN KUVARS** - Ana Bayi Özel Fiyat
9. **BORU** - Ana Bayi Özel Fiyat

## 🔍 Kontrol Listesi

### 1. Kampanya Butonu
- [ ] Ürünler eklendikten sonra buton yanıp sönüyor mu?
- [ ] Buton metni "FİLTRE ÖZEL FİYAT" olarak değişiyor mu?

### 2. Kampanya Modal
- [ ] Modal açılıyor mu?
- [ ] Tüm kampanyalar listeleniyor mu?
- [ ] Her kampanya için "Bu Gruba Uygula" butonu var mı?

### 3. Özel Fiyat Uygulaması
- [ ] Butona tıklayınca fiyatlar değişiyor mu?
- [ ] Satırlar yeşil renk oluyor mu?
- [ ] İskonto alanı "Özel Fiyat" olarak kilitleniyor mu?

### 4. Ana Bayi Ek İskonto
- [ ] Özel fiyat uygulandıktan sonra ek iskonto butonu aktif oluyor mu?
- [ ] Ek iskonto uygulanıyor mu?

### 5. Birim Kontrolleri
- [ ] MEDYA kampanyasında "KG" görünüyor mu?
- [ ] KENAR kampanyasında "Metre" görünüyor mu?
- [ ] Diğer kampanyalarda "Adet" ve "EUR" görünüyor mu?

## 📊 Console Logları

Tarayıcı console'unda (F12) şu logları göreceksiniz:
```
🧪 Test Modu Aktif - Ürünler yükleniyor...
➕ Ekleniyor: 09.511.E - FİLTRE MEDYA ÜRÜN (x1000)
➕ Ekleniyor: 09.512.E - FİLTRE MEDYA ÜRÜN 2 (x1000)
...
✅ Toplam 45 test ürünü eklendi!
```

## 🐛 Sorun Giderme

### Ürünler Eklenmiyor
- Console'da hata var mı kontrol edin
- `searchProductByCode` fonksiyonu tanımlı mı?
- Ürün kodları veritabanında var mı?

### Kampanya Butonu Yanmıyor
- Minimum koşullar sağlanıyor mu?
- `campaign_logic.js` yüklendi mi?
- `checkCampaignConditions()` çalışıyor mu?

### Özel Fiyat Gelmiyor
- API çalışıyor mu? (`api/kampanya/get_special_prices.php`)
- Ürün kodları doğru mu?
- Veritabanında özel fiyatlar var mı?

## 📁 Dosyalar

- `kampanya_test.php` - Test modunu başlatan sayfa
- `teklif-olustur.php` - Ana teklif sayfası (test modu desteği eklendi)
- `campaign_logic.js` - Kampanya mantığı
- `api/kampanya/check_conditions.php` - Koşul kontrolü
- `api/kampanya/get_special_prices.php` - Özel fiyat API'si
