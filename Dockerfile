# https://hub.docker.com/_/php
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# FROM php:7.4-cli
FROM php:8.0.13-cli
RUN pecl install xdebug-3.1.1 \
    && docker-php-ext-enable xdebug \
    && echo 'xdebug.mode=coverage' > /usr/local/etc/php/conf.d/xdebug.ini

# install zip for Composer and entr for file-watching
RUN apt-get update -yqq \
    && apt-get install -y \
        libzip-dev \
        zip \
        entr \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/

# Remove 10 MB /usr/src/php.tar.xz file. Unnecesary since we never update PHP without rebuilding.
# Ref: https://github.com/docker-library/php/issues/488
RUN rm /usr/src/php.tar.xz /usr/src/php.tar.xz.asc

# install Composer
ENV COMPOSER_HOME="/usr/src/.composer"
ENV PATH="/usr/src/.composer/vendor/bin:${PATH}"
RUN curl -L https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Global install PHPUnit
RUN composer global require phpunit/phpunit --prefer-dist

RUN echo '#!/bin/bash' > /usr/local/bin/phpunit-watch \
    && echo "find {src,tests} -name '*.php' | entr phpunit" >> /usr/local/bin/phpunit-watch \
    && chmod +x /usr/local/bin/phpunit-watch

CMD phpunit


# This should probably spin off into it's own repository
# By default, it will run PHPUnit in the current workind directory (from docker compose)
# That's equavalent to running the image with `phpunit` as the command:
#
#    docker compose run test phpunit
#
# To watch files change the command to `phpunit-watch` like this:
#
#    docker compose run test phpunit-watch
#
# TODO: Change that command to just 'watch'? Too magical?
#
# To see coverage in VSCode with the Coverage Gutters extension
# Be sure to add this to settings.json to remap the docker paths:
#    "coverage-gutters.remotePathResolve": ["/app/", "./"]
