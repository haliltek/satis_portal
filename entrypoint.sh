#!/bin/bash
set -e

echo "🚀 Starting Gemas Portal Application..."

# 1. Wait for Database
echo "⏳ Waiting for database connection..."
until nc -z -v -w30 ${DB_HOST:-db} ${DB_PORT:-3306}
do
  echo "Waiting for database to be ready..."
  sleep 2
done
echo "✅ Database is ready!"

# 2. Wait for Redis
echo "⏳ Waiting for Redis connection..."
until nc -z -v -w30 ${REDIS_HOST:-redis} ${REDIS_PORT:-6379}
do
  echo "Waiting for Redis to be ready..."
  sleep 2
done
echo "✅ Redis is ready!"

# 3. Set Permissions
echo "🔐 Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Ensure writable directories
for dir in uploads pdfs temp cache; do
    if [ -d "/var/www/html/$dir" ]; then
        chmod -R 777 "/var/www/html/$dir"
        echo "✅ $dir directory is writable"
    fi
done

# 4. Start Apache
echo "🌐 Starting Apache..."
exec apache2-foreground
