FROM php:8.3-apache

RUN docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite

COPY . /var/www/html/
COPY config.example.php /var/www/html/config.php

RUN sed -ri 's!Listen 80!Listen 10000!g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000
