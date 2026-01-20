@echo off
SET PHP=php
SET DIR=%~dp0
SET LOG=%DIR%\..\logs\logo_sync.log
IF NOT EXIST "%DIR%\..\logs" mkdir "%DIR%\..\logs"

%PHP% "%DIR%sync_companies.php" >> "%LOG%" 2>&1
%PHP% "%DIR%sync_products.php" >> "%LOG%" 2>&1
