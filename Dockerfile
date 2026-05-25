FROM php:8.2-fpm

WORKDIR /var/www

# install dependency sistem
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl

# install PHP extension penting (INI FIX GD)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# copy project
COPY . .

# install backend dependency
RUN composer install --no-dev --optimize-autoloader

# permissions
RUN chmod -R 775 storage bootstrap/cache