FROM php:8.4-apache

# Cài đặt các thư viện hệ thống cần thiết cho Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Dọn dẹp cache apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Cài đặt các extension PHP (bao gồm pdo_mysql và pdo_pgsql)
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Kích hoạt mod_rewrite của Apache
RUN a2enmod rewrite

# Cấu hình Document Root của Apache trỏ vào thư mục public của Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Lấy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Sao chép file dependency và chạy cài đặt trước để tận dụng Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --no-progress --no-interaction

# Sao chép toàn bộ mã nguồn
COPY . .

# Tối ưu autoload của Composer
RUN composer dump-autoload --optimize

# Cấp quyền ghi cho storage và bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
