FROM php:8.2-cli

# Install dependencies (incl. zip for composer installs)
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /app
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 8000

# Run Laravel server
CMD php artisan serve --host 0.0.0.0 --port 8000