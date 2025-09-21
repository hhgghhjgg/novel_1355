# -- مرحله ۱: استفاده از تصویر رسمی PHP با وب‌سرور آپاچی --
# ما از PHP نسخه 8.2 استفاده می‌کنیم که مدرن و سریع است.
FROM php:8.2-apache

# -- مرحله ۲: نصب ابزارهای مورد نیاز و اکستنشن‌های PHP --
# pdo_pgsql برای اتصال به دیتابیس Neon (PostgreSQL) ضروری است.
# mbstring برای کار با کاراکترهای فارسی (مانند تابع mb_substr) لازم است.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring

# -- مرحله ۳: نصب Composer (مدیریت پکیج‌های PHP) --
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -- مرحله ۴: کپی کردن فایل‌های پروژه --
# ابتدا فقط فایل‌های composer را کپی می‌کنیم تا از کش داکر بهتر استفاده شود.
WORKDIR /var/www/html
COPY composer.json composer.lock ./

# -- مرحله ۵: نصب وابستگی‌های PHP (JWT, Cloudinary) --
RUN composer install --no-dev --optimize-autoloader

# -- مرحله ۶: کپی کردن بقیه کدهای اپلیکیشن --
COPY . .

# -- مرحله ۷: تنظیم دسترسی‌ها (توصیه شده) --
# این خط تضمین می‌کند که وب‌سرور آپاچی دسترسی لازم برای خواندن فایل‌ها را دارد.
RUN chown -R www-data:www-data /var/www/html
