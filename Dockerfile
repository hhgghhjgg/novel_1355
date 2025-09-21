# -- مرحله ۱: استفاده از تصویر رسمی PHP با وب‌سرور آپاچی --
FROM php:8.2-apache

# -- مرحله ۲: نصب ابزارهای مورد نیاز و اکستنشن‌های PHP --
# *** تغییر کلیدی: git و unzip به لیست نصب اضافه شده‌اند ***
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_pgsql mbstring

# -- مرحله ۳: نصب Composer --
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -- مرحله ۴: کپی کردن فایل composer.json --
WORKDIR /var/www/html
COPY composer.json ./

# -- مرحله ۵: نصب وابستگی‌ها --
# ما هر دو فلگ را نگه می‌داریم تا مطمئن شویم از بهینه‌ترین روش استفاده می‌کند
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# -- مرحله ۶: کپی کردن بقیه کدهای اپلیکیشن --
COPY . .

# -- مرحله ۷: تنظیم دسترسی‌ها --
RUN chown -R www-data:www-data /var/www/html
