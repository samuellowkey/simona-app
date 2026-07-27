# ==========================================
# Stage 1: Build Frontend Assets
# ==========================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==========================================
# Stage 2: Install Composer Dependencies
# ==========================================
FROM php:8.2-fpm-alpine AS composer-builder
WORKDIR /app
COPY composer*.json ./
# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Install requirements for composer install
RUN apk add --no-cache git unzip \
    && composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# ==========================================
# Stage 3: Production Runtime
# ==========================================
FROM php:8.2-fpm-alpine
WORKDIR /var/www

# Install PHP extensions, Nginx, and system dependencies
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd opcache

# Copy OPcache configuration for production
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Copy PHP config
COPY docker-compose/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copy Nginx config
COPY docker-compose/nginx/app.conf /etc/nginx/http.d/default.conf

# Copy application files
COPY --chown=www-data:www-data . /var/www

# Copy vendor dependencies from Composer stage
COPY --from=composer-builder --chown=www-data:www-data /app/vendor /var/www/vendor

# Copy built frontend assets from Frontend stage
COPY --from=frontend-builder --chown=www-data:www-data /app/public/build /var/www/public/build

# Setup storage permissions and bootstrap cache
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy entrypoint script
COPY docker-compose/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port (Nginx default)
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]