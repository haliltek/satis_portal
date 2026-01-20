FROM php:8.2-apache

# 1️⃣ System dependencies
RUN apt-get update && apt-get install -y \
    gnupg2 \
    ca-certificates \
    apt-transport-https \
    unixodbc-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    unzip \
    git \
    curl \
    wget \
    && rm -rf /var/lib/apt/lists/*

# 2️⃣ Microsoft GPG key (NEW WAY)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    | gpg --dearmor \
    | tee /usr/share/keyrings/microsoft.gpg > /dev/null

# 3️⃣ Microsoft repo
RUN echo "deb [signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" \
    > /etc/apt/sources.list.d/mssql-release.list

# 4️⃣ Install MSSQL ODBC drivers
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    msodbcsql18 \
    mssql-tools18 \
    && rm -rf /var/lib/apt/lists/*

# 5️⃣ PATH for mssql-tools
ENV PATH="/opt/mssql-tools18/bin:${PATH}"

# 6️⃣ PHP core extensions
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
    gd \
    zip \
    intl \
    soap \
    opcache

# 7️⃣ PECL extensions
RUN pecl install redis sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv

# 8️⃣ Apache modules
RUN a2enmod rewrite headers

# 9️⃣ PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 10️⃣ Workdir
WORKDIR /var/www/html

# 11️⃣ Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 12️⃣ Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# 13️⃣ Install dependencies
# --no-dev: Exclude dev dependencies
# --optimize-autoloader: Optimize for production
# --no-scripts: Skip scripts to avoid errors if context isn't fully ready
# --no-progress: Reduce log noise
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress

# 14️⃣ Copy ONLY application files
COPY . .

# 12️⃣ Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 13️⃣ Create PHP session directory
RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && chmod -R 700 /var/lib/php/sessions

# 14️⃣ Configure PHP session path
RUN echo "session.save_handler = redis" > /usr/local/etc/php/conf.d/session.ini \
    && echo "session.save_path = \"tcp://redis:6379?auth=GEMAS_REDIS_SECURE_2026\"" >> /usr/local/etc/php/conf.d/session.ini

# Upload dir (runtime needed)
RUN mkdir -p /var/www/html/upload \
    && chown -R www-data:www-data /var/www/html/upload \
    && chmod -R 775 /var/www/html/upload

EXPOSE 80
CMD ["apache2-foreground"]
