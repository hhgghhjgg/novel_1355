# -- مرحله ۱: استفاده از تصویر رسمی PHP با وب‌سرور آپاچی --
FROM php:8.2-apache

# -- مرحله ۲: نصب ابزارهای مورد نیاز و اکستنشن‌های PHP --
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring

# -- مرحله ۳: نصب Composer (مدیریت پکیج‌های PHP) --
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -- مرحله ۴: کپی کردن فایل composer.json --
WORKDIR /var/www/html
# *** تغییر کلیدی اینجاست: فقط composer.json را کپی می‌کنیم ***
COPY composer.json ./

# -- مرحله ۵: نصب وابستگی‌ها (این کار composer.lock را هم می‌سازد) --
# *** تغییر کلیدی: ما اینجا install می‌کنیم، قبل از کپی بقیه کد ***
RUN composer install --no-dev --optimize-autoloader

# -- مرحله ۶: کپی کردن بقیه کدهای اپلیکیشن --
COPY . .

# -- مرحله ۷: تنظیم دسترسی‌ها --
RUN chown -R www-data:www-data /var/www/html
