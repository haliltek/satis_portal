# Döviz Kuru Güncelleme Sistemi

## Özet

Sistem artık **Logo'dan** döviz kurlarını çekiyor. Logo her sabah bankadan güncel kurları aldığı için, en doğru ve güncel kurları Logo'dan almak en mantıklı çözümdür.

## Nasıl Çalışır?

Logo veritabanındaki `L_CAPIRATE` tablosundan USD ve EUR kurları okunur ve sisteme kaydedilir.

### Logo Kur Kodları:
- **CURCODE:** 1 = USD, 20 = EUR (veya 2 = EUR bazı versiyonlarda)
- **CURTYPE:** 0 = Alış, 1 = Satış

## Kullanım

1. Ana sayfaya veya teklif oluşturma sayfasına gidin
2. "Döviz Kurlarını Güncelle" butonuna tıklayın
3. Açılan sayfada "Veritabanını Güncelle" butonuna tıklayın
4. Logo'dan kurlar çekilir ve MySQL veritabanına kaydedilir

## Gereksinimler

- ✅ Logo MSSQL veritabanı bağlantısı aktif olmalı
- ✅ `L_CAPIRATE` tablosuna erişim yetkisi olmalı
- ✅ Logo'da güncel kurlar mevcut olmalı

## Sorun Giderme

### Hata: "Logo veritabanı bağlantısı bulunamadı"
**Çözüm:** 
- `fonk.php` dosyasında Logo bağlantısının aktif olduğundan emin olun
- `gempa_logo_veritabani()` fonksiyonunun çağrıldığını kontrol edin
- MSSQL sürücüsünün yüklü olduğunu kontrol edin

### Hata: "Logo'dan döviz kurları alınamadı"
**Çözüm:**
- Logo'da `L_CAPIRATE` tablosunda veri olduğundan emin olun
- Logo'nun her sabah bankadan kurları çektiğinden emin olun
- Tablo erişim yetkilerinizi kontrol edin

### Hata: "USD veya EUR kurları eksik"
**Çözüm:**
- Logo'da hem USD hem de EUR kurlarının tanımlı olduğundan emin olun
- Hem alış hem de satış kurlarının mevcut olduğunu kontrol edin
- Log dosyasını kontrol edin: `logs/doviz_cron.log`

## Otomatik Güncelleme (Opsiyonel)

Döviz kurlarını otomatik olarak güncellemek isterseniz cron job ekleyebilirsiniz:

```bash
# Her gün saat 10:00'da çalışır (Logo kurları 09:00'da güncelledikten sonra)
0 10 * * * php /path/to/your/project/dovizguncelleme.php
```

## Teknik Detaylar

### Logo Kur Tablosu: `LG_EXCHANGE_565`

**Önemli Alanlar:**
- `DATE_`: Kur tarihi (YYYYMMDD)
- `CRTYPE`: Para birimi kodu (1=USD, 20=EUR, 3=GBP)
- `RATES1`: Alış kuru
- `RATES2`: Satış kuru
- `RATES3`: Efektif alış
- `RATES4`: Efektif satış
- `EDATE`: Bitiş tarihi
- `APPROVE`: Onay durumu
- `APPROVEDATE`: Onay tarihi

**SQL Sorgusu:**
```sql
SELECT DATE_, CRTYPE, RATES1, RATES2
FROM LG_EXCHANGE_565
WHERE CRTYPE IN (1, 20)
ORDER BY DATE_ DESC
```

**Not:** 565 = GEMPA firma numarası. Farklı bir firma için (örn. GEMAS=525) tablo adını `LG_EXCHANGE_525` olarak değiştirin.

---

## Değişiklik Geçmişi

### v2.0 - 02 Aralık 2025
- ✅ Logo entegrasyonu eklendi
- ✅ Garanti API kaldırıldı (çalışmıyor)
- ✅ `L_CAPIRATE` tablosundan kur çekimi
- ✅ Daha hızlı ve güvenilir

### v1.0 - Önceki versiyon
- ❌ `Ahmeti\BankExchangeRates` paketi (artık çalışmıyor)
- ❌ Garanti BBVA API (sunucu hatası)

---

**Son Güncelleme:** 02 Aralık 2025
**Durum:** ✅ Aktif ve Çalışıyor

