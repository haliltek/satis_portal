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
    && rm -rf /var/lib/apt/lists/*

# 2. Add Microsoft Repo for SQL Server Drivers
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc \
    && rm -rf /var/lib/apt/lists/*

# 3. Install PHP Extensions
# Core: mysqli (for App), pdo_mysql (for App), gd, zip, intl, soap
RUN docker-php-ext-install mysqli pdo_mysql gd zip intl soap opcache

# 4. Install PECL Extensions
# Redis & SQL Server
RUN pecl install redis sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv

# 5. Enable Apache Modules
RUN a2enmod rewrite headers

# 6. Configure PHP
# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# 7. Copy Application Code
# Note: In development/Coolify, this might be overridden by volume mounts or git pulls
COPY . /var/www/html

# 8. Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && sed -i 's/\r$//' entrypoint.sh \
    && chmod +x entrypoint.sh

# Expose Port
EXPOSE 80

# Start Apache
ENTRYPOINT ["./entrypoint.sh"]
