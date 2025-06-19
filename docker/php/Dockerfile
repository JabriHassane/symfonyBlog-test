# File: docker/php/Dockerfile
FROM php:8.3-fpm

# Install OS packages and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git \
       unzip \
       libicu-dev \
       libonig-dev \
       libzip-dev \
       libpng-dev \
       libjpeg-dev \
       libfreetype6-dev \
       libwebp-dev \
       libxml2-dev \
       libpq-dev \
       zip \
       curl \
       libssl-dev \
       libcurl4-openssl-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
         intl \
         mbstring \
         pdo_mysql \
         pdo_pgsql \
         zip \
         opcache \
         xml \
         curl \
         gd \
         bcmath \
    && pecl install apcu redis \
    && docker-php-ext-enable apcu redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv ~/.symfony*/bin/symfony /usr/local/bin/symfony

# Set working directory
WORKDIR /var/www/html

# Copy entrypoint script and make executable
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
