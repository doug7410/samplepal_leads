# Multi-stage build for Laravel application with React (Inertia.js)
# SamplePal Leads - Production Docker Image
ARG BUILDTIME="unknown"
ARG VERSION="latest"
ARG REVISION="unknown"

# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.ts ./
COPY tsconfig.json ./
COPY public ./public
COPY components.json ./

RUN npm run build

# Stage 2: PHP application
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip gd bcmath opcache pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install PHP dependencies first (better caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copy application files
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend-builder /app/public/build ./public/build

# Finish composer installation
RUN composer dump-autoload --optimize --no-dev

# Create storage and cache directories with proper permissions
RUN mkdir -p storage/framework/{cache,sessions,views} \
    storage/logs \
    storage/app/public \
    bootstrap/cache \
    database \
    public/storage

# Create storage link
RUN php artisan storage:link || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache \
    && chmod -R 775 storage/logs

# Copy Docker configuration files
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-enabled/default.conf 2>/dev/null || true
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Metadata labels
ARG BUILDTIME="unknown"
ARG VERSION="latest"
ARG REVISION="unknown"
LABEL org.opencontainers.image.title="SamplePal Leads"
LABEL org.opencontainers.image.description="Laravel application for SamplePal leads management"
LABEL org.opencontainers.image.vendor="SamplePal"
LABEL org.opencontainers.image.created="${BUILDTIME}"
LABEL org.opencontainers.image.version="${VERSION}"
LABEL org.opencontainers.image.revision="${REVISION}"
LABEL org.opencontainers.image.source="https://github.com/doug7410/samplepal_leads"

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
