FROM node:20-alpine AS frontend

WORKDIR /app

COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY app ./app
COPY public ./public

RUN npm ci && npm run build

FROM unit:1.34.1-php8.3

RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN printf '%s\n' \
    "opcache.enable=1" \
    "opcache.jit=tracing" \
    "opcache.jit_buffer_size=256M" \
    "memory_limit=512M" \
    "upload_max_filesize=64M" \
    "post_max_size=64M" \
    > /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

RUN mkdir -p storage/framework/views \
             storage/framework/cache \
             storage/framework/sessions \
             bootstrap/cache

RUN chown -R unit:unit /var/www/html/storage bootstrap/cache && chmod -R 775 /var/www/html/storage

COPY . .

COPY --from=frontend /app/public/build /var/www/html/public/build

RUN mkdir -p /var/www/html/storage/framework/cache/data \
 && chown -R unit:unit /var/www/html/storage \
 && chmod -R ug+rwX /var/www/html/storage

RUN chown -R unit:unit /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache

RUN composer install --prefer-dist --optimize-autoloader --no-interaction

COPY unit.json /docker-entrypoint.d/unit.json

EXPOSE 8000

CMD ["unitd", "--no-daemon"]
