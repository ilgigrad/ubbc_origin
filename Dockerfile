FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    ca-certificates curl \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo_mysql zip \
 && a2enmod rewrite headers proxy proxy_http

# GD si besoin
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd

COPY ./web/ports.conf /etc/apache2/ports.conf

EXPOSE 80