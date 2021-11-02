FROM php:7.4-cli
RUN pecl install xdebug-3.1.1 \
    && docker-php-ext-enable xdebug \
    && echo 'xdebug.mode=coverage' > /usr/local/etc/php/conf.d/xdebug.ini
