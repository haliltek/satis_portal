@echo off
REM PHP’nin kurulu olduğu dizin
SET PHP_PATH=C:\xampp\php\php.exe

REM Çalıştırılacak PHP dosyasının tam yolu
SET SCRIPT_PATH=C:\xampp\htdocs\b2b-gemas-project\scripts\update_currency.php

REM Log dosyası (logs klasörü yoksa önce oluşturun)
SET LOG_PATH=C:\xampp\htdocs\b2b-gemas-project\logs\doviz_cron.log

"%PHP_PATH%" "%SCRIPT_PATH%" >> "%LOG_PATH%" 2>&1
