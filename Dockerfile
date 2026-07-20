# ============================================
# Stage 1: Build frontend assets (Node.js)
# ============================================
FROM node:12-alpine AS frontend

WORKDIR /app

# Copy only package files first (cache layer for npm install)
COPY package.json package-lock.json* ./
COPY webpack.mix.js ./

RUN npm install

# Copy frontend source files needed for build
COPY resources/ resources/
COPY public/ public/
COPY .env ./

# Build production assets
RUN npm run production


# ============================================
# Stage 2: Install PHP dependencies (Composer)
# ============================================
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files first (cache layer)
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy full app for autoload generation
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize


# ============================================
# Stage 3: Production image (PHP + Apache)
# ============================================
FROM php:7.4-apache AS production

# Install system dependencies & PHP extensions in one layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application code with correct ownership (avoids separate chown layer)
COPY --chown=www-data:www-data . /var/www/html

# Copy compiled vendor from composer stage (production deps only)
COPY --from=composer --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from node stage
COPY --from=frontend --chown=www-data:www-data /app/public/js /var/www/html/public/js
COPY --from=frontend --chown=www-data:www-data /app/public/css /var/www/html/public/css
COPY --from=frontend --chown=www-data:www-data /app/public/mix-manifest.json /var/www/html/public/mix-manifest.json

WORKDIR /var/www/html

# Set permissions & create storage link
# Note: Using ln -s instead of php artisan storage:link to avoid booting
# Laravel during build (dev-only packages like Ignition aren't installed)
RUN chmod +x artisan \
    && ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80
