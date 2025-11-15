FROM php:8.2-fpm

# Sistem bağımlılıklarını yükle
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# PHP eklentilerini kur (PostgreSQL için pdo_pgsql şart)
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Composer'ı indir
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizini
WORKDIR /var/www

# Projeyi kopyala
COPY . .

# İzinleri ayarla
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]