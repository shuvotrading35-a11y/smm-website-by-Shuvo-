FROM dunglas/frankenphp:php8.3

WORKDIR /app

# Install required packages
RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libzip-dev \
    git \
 && docker-php-ext-install zip \
 && rm -rf /var/lib/apt/lists/*

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Allow Composer as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Railway PORT
ENV SERVER_NAME=:80

EXPOSE 80

CMD ["frankenphp", "php-server", "--root", "/app/public"]