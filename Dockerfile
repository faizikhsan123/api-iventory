FROM php:8.3-apache

# 1. Ekstensi PHP yang dibutuhkan Laravel + MySQL + Excel
RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql bcmath zip gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
# 2. Apache melayani folder public milik Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 3. Setelan PHP production + batas upload
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'upload_max_filesize=20M\npost_max_size=25M\nmemory_limit=256M\n' > "$PHP_INI_DIR/conf.d/uploads.ini"

# 4. Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 5. Install dependency dulu (memanfaatkan Docker Cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# 6. Salin source code
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
