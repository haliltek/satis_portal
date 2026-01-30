FROM php:8.2-apache

# 1. System Dependencies & Microsoft ODBC Driver Prerequisites
RUN apt-get update && apt-get install -y \
    gnupg2 \
    unixodbc-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    unzip \
    git \
    curl \
    netcat-openbsd \
    ca-certificates \
    apt-transport-https \
    && rm -rf /var/lib/apt/lists/*

# 2. Add Microsoft Repo for SQL Server Drivers (Debian 12 - Bookworm)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools \
    && echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc \
    && rm -rf /var/lib/apt/lists/*

# 2.1 Fix for MSSQL Error 0x2746 (OpenSSL 3.0 vs Legacy SQL Server)
# Lower security level to allow legacy algorithms/handshakes
RUN sed -i 's/SECLEVEL=2/SECLEVEL=0/g' /etc/ssl/openssl.cnf || true

# 3. Install PHP Extensions
# Core: mysqli (for App), pdo_mysql (for App), gd, zip, intl, soap
RUN docker-php-ext-install mysqli pdo_mysql gd zip intl soap opcache

# 4. Install PECL Extensions
# Redis & SQL Server
RUN pecl install redis sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv

# 5. Enable Apache Modules
RUN a2enmod rewrite headers

# 5.1 Copy Custom Apache VirtualHost Configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 5.2 Configure Apache to Allow .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 5.3 Ensure /var/www/html has proper access in apache2.conf
RUN echo '\n<Directory /var/www/html>\n    Options -Indexes +FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' >> /etc/apache2/apache2.conf

# 6. Copy Custom PHP Configuration
COPY php.ini $PHP_INI_DIR/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# 7. Copy entrypoint script first
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 8. Copy Application Code
# Note: In development/Coolify, this might be overridden by volume mounts or git pulls
COPY . /var/www/html

# 8.1 Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 8.2 Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# 9. Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 10. Health Check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/healthcheck.php || exit 1

# Expose Port
EXPOSE 80

# Start Apache
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
