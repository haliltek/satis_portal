#!/bin/bash
set -e

# 1. Klasör İzinlerini Ayarla
# Upload ve diğer yazılabilir klasörler için
echo "Dosya izinleri ayarlanıyor..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Eğer upload klasörü varsa yazma izni ver
if [ -d "/var/www/html/upload" ]; then
    chmod -R 777 /var/www/html/upload
fi

# 2. Veritabanının Hazır Olmasını Bekle
# (Basit bir bekleme döngüsü - production için daha smart bir wait-for-it scripti kullanılabilir)
echo "Veritabanı bağlantısı bekleniyor..."
sleep 10

# 3. Apache'yi Başlat
echo "Apache başlatılıyor..."
exec apache2-foreground
