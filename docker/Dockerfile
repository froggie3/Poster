FROM php:8.2-cli AS builder

FROM --platform=arm64 composer:2.6.6 AS composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /poster
COPY ./backend .
RUN rm -rf ./tests ./vendor ./app/.phpdoc
RUN ls -la
RUN composer update
RUN rm -f composer* 

FROM php:8.2-cli AS vendor
WORKDIR /poster
COPY --from=composer /poster .
RUN ls -la

CMD ["php", "./bin/minicli", "forecast"]
