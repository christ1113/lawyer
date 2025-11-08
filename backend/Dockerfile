FROM php:8.1-apache

# Install dependencies and enable pdo_mysql
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zlib1g-dev libonig-dev libpng-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
