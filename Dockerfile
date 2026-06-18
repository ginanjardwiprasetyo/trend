# Menggunakan image resmi PHP 8.2 dengan Apache
FROM php:8.2-apache

# Aktifkan mod_rewrite Apache (berguna untuk .htaccess jika ada)
RUN a2enmod rewrite

# Instal pustaka sistem yang dibutuhkan oleh PHP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    libpq-dev \
  && docker-php-ext-install zip mysqli pdo pdo_mysql pdo_pgsql pgsql mbstring

# Salin semua file web ke folder publik Apache
COPY . /var/www/html/

# Berikan hak akses ke server web untuk mengakses (dan menulis) file
RUN chown -R www-data:www-data /var/www/html && \
    chmod +x /var/www/html/start.sh

# Ekspos port 80 untuk lalu lintas HTTP
EXPOSE 80

CMD ["/var/www/html/start.sh"]
