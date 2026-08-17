FROM php:8.4-apache

# =========================================================
# Install system dependencies + Node.js for Vite build
# =========================================================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install \
    pdo \
    pdo_sqlite \
    pdo_pgsql \
    pdo_mysql \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20.x for Vite build
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*


# =========================================================
# Enable Apache rewrite
# =========================================================
RUN a2enmod rewrite


# =========================================================
# Laravel public directory
# =========================================================
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf


# =========================================================
# Application directory
# =========================================================
WORKDIR /var/www/html


# =========================================================
# Install Composer
# =========================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# =========================================================
# Copy application files
# =========================================================
COPY . .


# =========================================================
# Install PHP dependencies (production)
# =========================================================
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction


# =========================================================
# Build frontend assets with Vite
# =========================================================
RUN npm ci --ignore-scripts && npm run build


# =========================================================
# Create required directories and SQLite database
# =========================================================
RUN mkdir -p database \
    storage/app/public \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache \
    && touch database/database.sqlite


# =========================================================
# Set permissions for Laravel
# =========================================================
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/database

RUN chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/database


# =========================================================
# Apache listens on dynamic PORT (Render provides $PORT)
# =========================================================
EXPOSE 80


# =========================================================
# Startup: configure port, migrate, cache, serve
# =========================================================
CMD ["sh", "-c", "\
    sed -i \"s/Listen 80/Listen ${PORT:-80}/g\" /etc/apache2/ports.conf && \
    sed -i \"s/:80/:${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf && \
    php artisan storage:link --force 2>/dev/null || true && \
    touch /var/www/html/database/database.sqlite && \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    apache2-foreground"]