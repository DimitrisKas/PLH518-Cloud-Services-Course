FROM php:8-apache

RUN apt-get update && apt-get install -y curl libcurl4-openssl-dev

RUN docker-php-ext-install mysqli
RUN docker-php-ext-install curl