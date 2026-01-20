# GEMAS B2B Bayi - Admin Panel Entegrasyonu

## ✅ Tamamlanan Entegrasyonlar

### 📦 Sipariş Akışı

#### 1. Bayi Panelinde Sipariş Oluşturma
**Dosya:** `dealer/create_order.php`

```php
// Sipariş oluşturulurken:
- Tablo: ogteklif2
- tur: 'bayi_siparis' (özel işaretleme)
- durum: 'Beklemede'
- sirket_arp_code: Bayi cari kodu
- hazirlayanid: Bayi kullanıcı ID'si
```

#### 2. Admin Panelinde Görüntüleme
**Dosya:** `teklifsiparisler.php`

**Özel İşaretlemeler:**
- 🛒 **BAYİ** badge'i müşteri adının yanında
- 🔵 Açık mavi arka plan rengi
- Sol tarafta mavi çizgi

**Kod Kontrolleri:**
```php
$turKolonu = trim($dev2['tur'] ?? '');
$bayiSiparisiMi = ($turKolonu === 'bayi_siparis');

if ($bayiSiparisiMi) {
    $musteriAdi .= ' <span class="badge bg-info">🛒 BAYİ</span>';
    $rowClass .= ' bayi-order-row';
}
```

## 🎨 Görsel Ayırt Edici Özellikler

### Admin Panelinde Bayi Siparişleri

1. **Badge**: Müşteri adının yanında "🛒 BAYİ" yazısı
2. **Arka Plan**: Açık mavi (#e8f4f8)
3. **Sol Çizgi**: Mavi (#3498db) 4px kalınlıkta
4. **Hover**: Daha koyu mavi (#d4ebf2)

### CSS Stilleri
```css
.bayi-order-row {
    background-color: #e8f4f8 !important;
    border-left: 4px solid #3498db !important;
}

.bayi-order-row:hover {
    background-color: #d4ebf2 !important;
}
```

## 📋 Veritabanı Yapısı

### ogteklif2 Tablosu - Bayi Siparişleri İçin Kolonlar

| Kolon | Açıklama | Bayi Siparişi Değeri |
|-------|----------|---------------------|
| `tur` | Sipariş türü | `'bayi_siparis'` |
| `sirket_arp_code` | Cari kodu | Bayi şirket kodu |
| `sirketid` | Şirket ID | Bayi company_id |
| `hazirlayanid` | Oluşturan | Bayi kullanıcı ID |
| `musteriadi` | Müşteri adı | Bayi şirket adı |
| `durum` | Sipariş durumu | `'Beklemede'` |
| `tekliftarihi` | Oluşturma tarihi | timestamp |
| `toplamtutar` | Ara toplam | decimal |
| `kdv` | KDV tutarı | decimal |
| `geneltoplam` | Genel toplam | decimal |
| `notes1` | Sipariş notu | text |
| `teslimyer` | Teslimat adresi | text |

### ogteklifurun2 Tablosu - Sipariş Ürünleri

| Kolon | Açıklama | Değer |
|-------|----------|-------|
| `teklifid` | Sipariş ID | ogteklif2.id |
| `kod` | Stok kodu | Ürün kodu |
| `adi` | Ürün adı | Ürün adı |
| `miktar` | Adet | decimal |
| `liste` | Birim fiyat | decimal |
| `birim` | Birim | 'Adet' |

## 🔄 Sipariş Akış Şeması

```
[Bayi Paneli]
    ↓
1. Ürünleri sepete ekle
    ↓
2. Sipariş oluştur (create_order.php)
    ↓
3. ogteklif2 tablosuna kaydet
   tur = 'bayi_siparis'
    ↓
[Admin Paneli]
    ↓
4. teklifsiparisler.php'de görüntüle
   🛒 BAYİ badge'i ile işaretle
    ↓
5. Admin sipariş kontrolü yapar
    ↓
6. Durumu günceller:
   - Beklemede → Onaylandı
   - Onaylandı → Logo'ya aktarım
   - Logo'ya aktarıldı → Tamamlandı
```

## 📊 Admin İşlemleri

### 1. Sipariş Görüntüleme
- Tüm bayi siparişleri listede görünür
- Özel badge ve renk ile işaretli
- Filtreler çalışır

### 2. Sipariş Detayı
- Tıklayarak detay sayfasına gidilir
- Tüm ürünler listelenir
- Müşteri bilgileri görünür

### 3. Sipariş Onaylama
Admin aşağıdaki işlemleri yapabilir:
- ✅ Durumu değiştir
- ✅ Atama yap
- ✅ Not ekle
- ✅ Logo'ya aktar

### 4. Durum Değiştirme
Mevcut durumlar:
- Beklemede (varsayılan)
- Onaylandı
- Logo'ya Aktarıldı
- Tamamlandı
- İptal Edildi

## 🔍 Filtreleme

Admin panelinde bayi siparişlerini filtrelemek için:

1. **Durum Filtresi**: "Beklemede" seç
2. **Hazırlayan Filtresi**: Bayi kullanıcısını seç
3. **Tarih Filtresi**: Son siparişleri görmek için

## 📝 Örnek Kullanım

### Bayi Panelinden Sipariş
```php
// dealer/create_order.php
INSERT INTO ogteklif2 (
    sirket_arp_code,
    sirketid,
    tekliftarihi,
    durum,
    tur,
    toplamtutar,
    kdv,
    geneltoplam,
    musteriadi,
    hazirlayanid
) VALUES (
    '120.03.A59',
    34183,
    '2025-11-20 14:30:00',
    'Beklemede',
    'bayi_siparis',
    1000.00,
    200.00,
    1200.00,
    'ABC Şirketi',
    1
);
```

### Admin Panelinde Görüntüleme
```sql
SELECT t.*, s.s_adi, t.tur
FROM ogteklif2 t
LEFT JOIN sirket s ON s.s_arp_code = t.sirket_arp_code
WHERE t.tekliftarihi IS NOT NULL
ORDER BY t.tekliftarihi DESC;
```

## 🎯 Kontrol Listesi

Admin panelinde bayi siparişi kontrolü:

- [ ] Sipariş listede görünüyor mu?
- [ ] 🛒 BAYİ badge'i var mı?
- [ ] Mavi arka plan var mı?
- [ ] Müşteri bilgileri doğru mu?
- [ ] Ürünler listeleniyor mu?
- [ ] Toplam tutar doğru mu?
- [ ] Durum değiştirme çalışıyor mu?

## 🔧 Sorun Giderme

### Sipariş Görünmüyor
**Kontroller:**
1. `tekliftarihi` NULL değil mi?
2. `tur` kolonu 'bayi_siparis' mi?
3. Filtreler aktif mi?

### Badge Görünmüyor
**Kontroller:**
1. `tur` kolonu düzgün kaydedildi mi?
2. SQL sorgusunda `tur` seçili mi?
3. CSS yüklendi mi?

### Renkler Yanlış
**Kontroller:**
1. `.bayi-order-row` CSS tanımlı mı?
2. `$bayiSiparisiMi` kontrolü çalışıyor mu?
3. Cache temizlendi mi?

## 📞 Önemli Notlar

1. ✅ Bayi siparişleri otomatik olarak admin panelinde görünür
2. ✅ Özel işaretleme ile kolayca ayırt edilir
3. ✅ Tüm admin özellikleri çalışır (durum değiştirme, atama, vb.)
4. ✅ Sipariş akışı normal tekliflerle aynıdır
5. ⚠️ `tur` kolonu mutlaka 'bayi_siparis' olmalı

---

© 2025 GEMAS B2B Portal - Bayi-Admin Entegrasyonu v1.0

