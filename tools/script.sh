#!/usr/bin/env bash

wget -O phpunit https://phar.phpunit.de/phpunit-10.phar
chmod +x phpunit
./phpunit --version
