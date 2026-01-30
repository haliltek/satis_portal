@echo off
chcp 65001 > nul
echo ------------------------------------------
echo SQL Import Araci (Guncel)
echo ------------------------------------------

set /p sqlfile="SQL Dosyasinin Tam Yolunu Yapistirin veya Surukleyin: "
:: Remove quotes if exists
set sqlfile=%sqlfile:"=%

echo.
echo Varsayilan veritabani: b2bgemascom_teklif
set /p dbname="Veritabani Adi (Farkli ise yazin, yoksa Enter'a basin): "
if "%dbname%"=="" set dbname=b2bgemascom_teklif

echo.
echo [BILGI] Veritabani kontrol ediliyor: %dbname%
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS %dbname% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;"

echo.
echo Islem Basliyor...
echo Veritabani: %dbname%
echo Dosya: %sqlfile%
echo.

C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8 %dbname% < "%sqlfile%"

if %errorlevel% neq 0 (
    echo.
    echo [HATA] Bir sorun olustu.
    echo Hata Mesaji yukaridadir.
    pause
) else (
    echo.
    echo [BASARILI] Import islemi tamamlandi.
    pause
)
