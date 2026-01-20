# Logo Veritabanı Bağlantı Rehberi

## 1. .env Dosyası Oluşturma

Proje kök dizininde `.env` dosyası oluşturun ve şu içeriği ekleyin:

```env
# Logo MSSQL Server Bilgileri
LOGO_HOST=192.168.5.253,1433
LOGO_USER=halil
LOGO_PASS=12621262
LOGO_DB=GEMPA2025

# MySQL Veritabanı Bilgileri (Local)
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=b2bgemascom_teklif
```

## 2. PDO SQLSRV Extension Yükleme (XAMPP için)

### Adım 1: Microsoft SQL Server PHP Driver İndirin
- https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
- PHP sürümünüze uygun driver'ı indirin (örn: PHP 8.x için)

### Adım 2: Driver Dosyalarını Kopyalayın
- İndirdiğiniz zip dosyasını açın
- `php_pdo_sqlsrv_xxx_ts.dll` ve `php_sqlsrv_xxx_ts.dll` dosyalarını kopyalayın
- XAMPP'in `php/ext/` klasörüne yapıştırın (örn: `C:\xampp\php\ext\`)

### Adım 3: php.ini Dosyasını Düzenleyin
- `C:\xampp\php\php.ini` dosyasını açın
- Şu satırları ekleyin (extension dosya adlarını kendi versiyonunuza göre düzenleyin):
```ini
extension=php_pdo_sqlsrv_83_ts.dll
extension=php_sqlsrv_83_ts.dll
```

### Adım 4: Apache'yi Yeniden Başlatın
- XAMPP Control Panel'den Apache'yi durdurun ve tekrar başlatın

### Adım 5: Extension'ı Kontrol Edin
- `phpinfo.php` dosyası oluşturun:
```php
<?php phpinfo(); ?>
```
- Tarayıcıda açın ve "pdo_sqlsrv" veya "sqlsrv" arayın

## 3. Bağlantıyı Test Etme

`test_logo_connection.php` dosyasını oluşturup çalıştırın (aşağıdaki dosyaya bakın).

## Sorun Giderme

- **"could not find driver" hatası**: PDO SQLSRV extension yüklü değil
- **"Connection failed" hatası**: Logo sunucusuna erişim yok veya bilgiler yanlış
- **"Access denied" hatası**: Kullanıcı adı/şifre yanlış

