# استفاده از نسخه رسمی PHP Apache
FROM php:8.2-apache

# نصب پیش‌نیازها و اکستنشن‌های دیتابیس
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# فعال‌سازی ماژول Rewrite
RUN a2enmod rewrite

# نصب کامپوزر
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تنظیم دایرکتوری پروژه
WORKDIR /var/www/html

# کپی کردن فایل‌ها
COPY . .

# نصب پکیج‌های PHP
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# تنظیم دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# --- بخش مهم: ساخت اسکریپت استارت‌آپ ---
# این اسکریپت چک می‌کند اگر متغیر PORT وجود نداشت، آن را برابر 80 قرار دهد
# این جلوی ارور "Port must be specified" را می‌گیرد
RUN echo '#!/bin/bash\n\
if [ -z "$PORT" ]; then\n\
    echo "PORT variable is empty, defaulting to 80"\n\
    export PORT=80\n\
fi\n\
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf\n\
exec docker-php-entrypoint apache2-foreground' > /usr/local/bin/start.sh

# قابل اجرا کردن اسکریپت
RUN chmod +x /usr/local/bin/start.sh

# اجرای نهایی
CMD ["/usr/local/bin/start.sh"]
