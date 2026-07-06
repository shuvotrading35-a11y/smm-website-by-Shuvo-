FROM dunglas/frankenphp:php8.3

WORKDIR /app

COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

ENV SERVER_NAME=:${PORT}

CMD ["frankenphp", "php-server", "--root", "/app/public"]