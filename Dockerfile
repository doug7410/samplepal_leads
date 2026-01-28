# Multi-stage build for Laravel application with React (Inertia.js)
# Build arguments with defaults
ARG BUILDTIME="unknown"
ARG VERSION="latest"
ARG REVISION="unknown"

# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install Node dependencies
RUN npm ci

# Copy frontend source files
COPY resources ./resources
COPY vite.config.ts ./
COPY tsconfig.json ./
COPY public ./public
COPY components.json ./

# Build frontend assets for production
RUN npm run build

# Stage 2: Use the Laravel base image
FROM kiwitechlab/laravel-base:php82-laravel12

# Switch to root to install packages
USER root

# Install additional system dependencies needed for this app
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    nodejs \
    npm \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install PHP dependencies first (better caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copy application files
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend-builder /app/public/build ./public/build
COPY --from=frontend-builder /app/public/build/manifest.json ./public/build/

# Finish composer installation
RUN composer dump-autoload --optimize --no-dev

# Create storage and cache directories with proper permissions
RUN mkdir -p storage/framework/{cache,sessions,views} \
    storage/logs \
    storage/app/public \
    bootstrap/cache \
    database \
    public/storage

# Generate application key and cache config (will be overridden if ENV is set)
RUN php artisan storage:link || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache \
    && chmod -R 775 storage/logs

# Copy Docker configuration files
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-enabled/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Redeclare build arguments for metadata labels
ARG BUILDTIME="unknown"
ARG VERSION="latest"
ARG REVISION="unknown"

# Add metadata labels
LABEL org.opencontainers.image.title="SamplePal Leads"
LABEL org.opencontainers.image.description="Laravel application for SamplePal leads management"
LABEL org.opencontainers.image.vendor="SamplePal"
LABEL org.opencontainers.image.created="${BUILDTIME}"
LABEL org.opencontainers.image.version="${VERSION}"
LABEL org.opencontainers.image.revision="${REVISION}"
LABEL org.opencontainers.image.source="https://github.com/doug7410/samplepal_leads"

# Expose port 80 for web traffic
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start supervisord
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
