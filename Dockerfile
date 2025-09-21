# -- مرحله ۱: استفاده از تصویر رسمی PHP با وب‌سرور آپاچی --
FROM php:8.2-apache

# -- مرحله ۲: نصب ابزارهای مورد نیاز و اکستنشن‌های PHP --
# *** تغییر کلیدی: libonig-dev به لیست نصب اضافه شده است ***
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring

# -- مرحله ۳: نصب Composer (مدیریت پکیج‌های PHP) --
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -- مرحله ۴: کپی کردن فایل composer.json --
WORKDIR /var/www/html
COPY composer.json ./

# -- مرحله ۵: نصب وابستگی‌ها --
RUN composer install --no-dev --optimize-autoloader

# -- مرحله ۶: کپی کردن بقیه کدهای اپلیکیشن --
COPY . .

# -- مرحله ۷: تنظیم دسترسی‌ها --
RUN chown -R www-data:www-data /var/www/html
