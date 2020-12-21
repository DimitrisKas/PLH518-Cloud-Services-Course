FROM php:8-apache

WORKDIR /var/www/html/

# enable mod_rewrite
RUN a2enmod rewrite

COPY init_slim.sh /
RUN bash /init_slim.sh

