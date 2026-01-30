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

# 3. Set Permissions - AGGRESSIVE MODE
echo "🔐 Setting file permissions (aggressive mode)..."

# First, ensure www-data owns everything
chown -R www-data:www-data /var/www/html || true

# Set base permissions
find /var/www/html -type d -exec chmod 755 {} \; || true
find /var/www/html -type f -exec chmod 644 {} \; || true

# Make PHP files executable by Apache
find /var/www/html -type f -name "*.php" -exec chmod 644 {} \; || true

# Ensure writable directories
for dir in uploads pdfs temp cache logs; do
    if [ -d "/var/www/html/$dir" ]; then
        chmod -R 777 "/var/www/html/$dir"
        chown -R www-data:www-data "/var/www/html/$dir"
        echo "✅ $dir directory is writable"
    fi
done

# Ensure index.php is readable
if [ -f "/var/www/html/index.php" ]; then
    chmod 644 /var/www/html/index.php
    chown www-data:www-data /var/www/html/index.php
    echo "✅ index.php is readable"
fi

# Debug: List permissions
echo "📋 Checking /var/www/html permissions:"
ls -la /var/www/html/ | head -20

# 4. Start Apache
echo "🌐 Starting Apache as www-data..."
exec apache2-foreground
