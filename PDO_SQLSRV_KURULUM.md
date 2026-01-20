# PDO SQLSRV Extension Kurulum Rehberi (PHP 8.2 ZTS x64)

## Adım 1: Microsoft SQL Server PHP Driver İndirin

1. Şu adresi açın: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
2. **PHP 8.2** için uygun driver'ı indirin
3. Veya doğrudan şu linkten indirin:
   - https://github.com/Microsoft/msphpsql/releases
   - `Windows` klasöründen `8.2` klasörüne gidin
   - `ZTS` (Thread Safe) versiyonunu indirin
   - Dosya adı: `php_pdo_sqlsrv_82_ts_x64.dll` ve `php_sqlsrv_82_ts_x64.dll`

## Adım 2: Driver Dosyalarını Kopyalayın

1. İndirdiğiniz zip dosyasını açın
2. `Windows\8.2\ZTS\x64\` klasöründen şu dosyaları bulun:
   - `php_pdo_sqlsrv_82_ts_x64.dll`
   - `php_sqlsrv_82_ts_x64.dll`
3. Bu dosyaları kopyalayın
4. XAMPP'in `php/ext/` klasörüne yapıştırın:
   ```
   C:\xampp\php\ext\
   ```

## Adım 3: Microsoft Visual C++ Redistributable Yükleyin

PDO SQLSRV extension'ı çalışması için Microsoft Visual C++ Redistributable gerekli:
- https://aka.ms/vs/17/release/vc_redist.x64.exe
- İndirip yükleyin

## Adım 4: php.ini Dosyasını Düzenleyin

1. `C:\xampp\php\php.ini` dosyasını bir metin editörü ile açın
2. Dosyanın sonuna şu satırları ekleyin:
   ```ini
   ; Microsoft SQL Server PHP Driver
   extension=php_sqlsrv_82_ts.dll
   extension=php_pdo_sqlsrv_82_ts.dll
   ```
   
   **ÖNEMLİ:** Dosya adlarında `_x64` OLMAMALI! Sadece `php_sqlsrv_82_ts.dll` ve `php_pdo_sqlsrv_82_ts.dll` yazın.
3. Dosyayı kaydedin

## Adım 5: Apache'yi Yeniden Başlatın

1. XAMPP Control Panel'i açın
2. Apache'yi durdurun (Stop)
3. Apache'yi tekrar başlatın (Start)

## Adım 6: Extension'ı Kontrol Edin

Komut satırından:
```bash
php -m | findstr sqlsrv
```

Veya `test_logo_connection.php` scriptini tekrar çalıştırın.

## Sorun Giderme

- **"Unable to load dynamic library"**: Dosya adı yanlış veya dosya yanlış klasörde
- **"The specified module could not be found"**: Visual C++ Redistributable eksik
- **Extension görünmüyor**: php.ini dosyasını kontrol edin, Apache'yi yeniden başlatın

