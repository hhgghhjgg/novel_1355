# استفاده از ایمیج رسمی PHP با Apache
FROM php:8.2-apache

# نصب پکیج‌های سیستمی مورد نیاز (برای Postgres و Zip)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# نصب اکستنشن‌های PHP مورد نیاز پروژه شما
# نکته: چون در db_connect.php از pgsql استفاده کردید، pdo_pgsql ضروری است
RUN docker-php-ext-install pdo pdo_pgsql zip

# فعال‌سازی mod_rewrite برای هندل کردن URLها (حیاتی برای پروژه‌های PHP)
RUN a2enmod rewrite

# تنظیم پورت آپاچی برای Render
# این دستور پورت پیش‌فرض 80 را با پورت محیطی Render جایگزین می‌کند
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# نصب کامپوزر
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تنظیم دایرکتوری کاری
WORKDIR /var/www/html

# کپی کردن فایل‌های کامپوزر و نصب وابستگی‌ها
COPY composer.json ./
# اگر composer.lock دارید آن را هم کپی کنید، اگر نه این خط را نادیده بگیرید
# COPY composer.lock ./ 

RUN composer install --no-dev --optimize-autoloader --prefer-dist

# کپی کردن تمام فایل‌های پروژه به کانتینر
COPY . .

# تنظیم مجوزها برای کارکرد صحیح آپاچی
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# تغییر پورت در زمان اجرا (Entrypoint Script)
# این بخش حیاتی است تا آپاچی با پورت داینامیک Render بالا بیاید
CMD sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && docker-php-entrypoint apache2-foreground
