FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    ca-certificates curl openssl \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo_mysql zip \
 && a2enmod rewrite ssl headers

# si tu as besoin de GD (souvent oui)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd


RUN a2enmod rewrite ssl
RUN a2enmod proxy
RUN a2enmod proxy_http


COPY ./web/ports.conf /etc/apache2/ports.conf

RUN docker-php-ext-install mysqli

EXPOSE 80 443 8080


