FROM composer:latest AS composer

FROM php:cli-bullseye AS build

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /poster

COPY ./backend/composer.json .
COPY ./backend/composer.lock .
RUN apt-get update && \
    apt-get install -y git unzip && \
    composer update

COPY ./backend/src ./src
COPY ./backend/bin ./bin

FROM php:cli-bullseye

COPY --from=build /poster /poster

WORKDIR /poster

CMD ["php", "./bin/forecast", "--database-path", "./sqlite.db"]
