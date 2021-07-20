FROM php:8.0.7-fpm

# Minimal apt-get (bz2 missing)
RUN \
    apt-get update \
    && apt-get install -y \
        libcurl4-openssl-dev \
        curl \
    && docker-php-ext-install -j$(nproc) curl \
    && apt-get clean

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

CMD php-fpm
