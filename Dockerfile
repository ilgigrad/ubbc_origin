FROM php:7.4-apache

RUN apt-get update && \
    apt-get install -y \
      software-properties-common \
      lsb-release \
      ca-certificates \
      curl \
      gnupg && \
    # echo "deb http://deb.debian.org/debian bullseye-backports main" >> /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y certbot python3-certbot-apache openssl


RUN a2enmod rewrite ssl
RUN a2enmod proxy
RUN a2enmod proxy_http


COPY ./web/ports.conf /etc/apache2/ports.conf

RUN docker-php-ext-install mysqli

EXPOSE 80 443 8080


