# -- مرحله ۱: استفاده از تصویر رسمی PHP با وب‌سرور آپاچی --
FROM php:8.2-apache

# -- مرحله ۲: نصب ابزارهای مورد نیاز و اکستنشن‌های PHP --
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_pgsql mbstring zip

# -- مرحله ۳: نصب Composer --
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -- مرحله ۴: کپی کردن فایل‌های پروژه --
WORKDIR /var/www/html

# *** تغییر کلیدی: کپی کردن فایل تنظیمات آپلود ***
# این خط به داکر می‌گوید که تنظیمات سفارشی ما را اعمال کند.
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY composer.json ./

# -- مرحله ۵: نصب وابستگی‌ها --
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# -- مرحله ۶: کپی کردن بقیه کدهای اپلیکیشن --
COPY . .

# -- مرحله ۷: تنظیم دسترسی‌ها --
RUN chown -R www-data:www-data /var/www/html
